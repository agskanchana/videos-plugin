/**
 * Ekwa Video Block Frontend JavaScript - Vanilla JS
 * With enhanced iOS/Safari support
 */
(function() {
    'use strict';

    // Prevent double initialization
    if (window.EkwaVideoPlayerInitialized) {
        return;
    }
    window.EkwaVideoPlayerInitialized = true;

    class EkwaVideoPlayer {
        constructor() {
            this.players = new Map(); // Store player instances and states
            this.youtubeAPIReady = false;
            this.youtubeAPIPromise = null;
            this.pendingClicks = new Map(); // Store pending click handlers for when API loads
            this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || 
                         (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
            this.isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
            this.init();
        }

        init() {
            this.bindEvents();
            this.loadYouTubeAPI(); // Load API for better control
        }

        bindEvents() {
            // Handle thumbnail click - use capture phase for reliability
            document.addEventListener('click', (e) => {
                const thumbnail = e.target.closest('.ekwa-video-thumbnail');
                if (thumbnail) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.handleThumbnailClick(e);
                }
            }, true);

            // Handle play button click on loaded videos
            document.addEventListener('click', (e) => {
                if (e.target.closest('.ekwa-video-play-button')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.handlePlayButtonClick(e);
                }
            }, true);

            // Handle play icon click (for schema markup structure)
            document.addEventListener('click', (e) => {
                if (e.target.closest('.playicon')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.handlePlayIconClick(e);
                }
            }, true);

            // Enhanced touch event support for video thumbnails (iOS/Safari)
            this.bindTouchEventsForThumbnails();

            // Direct event listeners for transcript buttons (fallback method)
            this.bindTranscriptButtons();

            // Handle transcript toggle - comprehensive event handling for all devices
            // Use capture phase to catch events before Owl Carousel can intercept them
            document.addEventListener('click', (e) => {
                const transcriptBtn = e.target.closest('.btn-transcript');
                if (transcriptBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    this.handleTranscriptToggle(e, transcriptBtn);
                }
            }, true); // true = capture phase

            // Also keep bubbling phase as fallback
            document.addEventListener('click', (e) => {
                const transcriptBtn = e.target.closest('.btn-transcript');
                if (transcriptBtn) {
                    this.handleTranscriptToggle(e, transcriptBtn);
                }
            });

            // Enhanced touch event support for mobile devices (iPhone specifically)
            let touchStartTime = 0;
            let touchTarget = null;

            document.addEventListener('touchstart', (e) => {
                const transcriptBtn = e.target.closest('.btn-transcript');
                if (transcriptBtn) {
                    touchStartTime = Date.now();
                    touchTarget = transcriptBtn;
                }
            }, { passive: false });

            document.addEventListener('touchend', (e) => {
                const transcriptBtn = e.target.closest('.btn-transcript');
                if (transcriptBtn || touchTarget) {
                    const touchDuration = Date.now() - touchStartTime;

                    // Only handle if it's a tap (not a long press or scroll)
                    if (touchDuration < 500) {
                        e.preventDefault();
                        e.stopPropagation();
                        this.handleTranscriptToggle(e, transcriptBtn || touchTarget);
                    }
                }
                touchStartTime = 0;
                touchTarget = null;
            }, { passive: false });
        }

        /**
         * Bind touch events specifically for video thumbnails
         * Critical for iOS/Safari compatibility
         */
        bindTouchEventsForThumbnails() {
            let touchStartX = 0;
            let touchStartY = 0;
            let touchStartTime = 0;
            let touchedThumbnail = null;

            // Touchstart - record initial position
            document.addEventListener('touchstart', (e) => {
                const thumbnail = e.target.closest('.ekwa-video-thumbnail');
                if (thumbnail) {
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                    touchStartTime = Date.now();
                    touchedThumbnail = thumbnail;
                    
                    // Add visual feedback
                    thumbnail.classList.add('ekwa-touching');
                }
            }, { passive: true });

            // Touchend - handle as click if it was a tap
            document.addEventListener('touchend', (e) => {
                const thumbnail = e.target.closest('.ekwa-video-thumbnail');
                const targetThumbnail = thumbnail || touchedThumbnail;
                
                if (targetThumbnail) {
                    // Remove visual feedback
                    targetThumbnail.classList.remove('ekwa-touching');
                    
                    const touchEndX = e.changedTouches[0].clientX;
                    const touchEndY = e.changedTouches[0].clientY;
                    const touchDuration = Date.now() - touchStartTime;
                    const touchDistance = Math.sqrt(
                        Math.pow(touchEndX - touchStartX, 2) + 
                        Math.pow(touchEndY - touchStartY, 2)
                    );
                    
                    // Consider it a tap if: short duration and minimal movement
                    if (touchDuration < 500 && touchDistance < 30) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Create a synthetic event for the handler
                        const syntheticEvent = {
                            target: targetThumbnail,
                            preventDefault: () => {},
                            stopPropagation: () => {}
                        };
                        
                        this.handleThumbnailClick(syntheticEvent);
                    }
                }
                
                // Reset
                touchStartX = 0;
                touchStartY = 0;
                touchStartTime = 0;
                touchedThumbnail = null;
            }, { passive: false });

            // Touchcancel - cleanup
            document.addEventListener('touchcancel', () => {
                if (touchedThumbnail) {
                    touchedThumbnail.classList.remove('ekwa-touching');
                }
                touchStartX = 0;
                touchStartY = 0;
                touchStartTime = 0;
                touchedThumbnail = null;
            }, { passive: true });
        }

        bindTranscriptButtons() {

            // Bind existing transcript buttons (including those in Owl Carousel)
            this.rebindAllTranscriptButtons();

            // Set up mutation observer for dynamically added buttons
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) {
                            // Check if the added node is a transcript button
                            if (node.classList && node.classList.contains('btn-transcript')) {
                                this.bindSingleTranscriptButton(node);
                            }
                            // Check if the added node contains transcript buttons
                            const buttons = node.querySelectorAll && node.querySelectorAll('.btn-transcript');
                            if (buttons && buttons.length > 0) {
                                buttons.forEach(button => {
                                    this.bindSingleTranscriptButton(button);
                                });
                            }
                            
                            // Check if Owl Carousel was initialized (clones added)
                            if (node.classList && (node.classList.contains('owl-item') || node.classList.contains('owl-stage'))) {
                                setTimeout(() => this.rebindAllTranscriptButtons(), 100);
                            }
                        }
                    });
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Listen for Owl Carousel events
            document.addEventListener('initialized.owl.carousel', () => {
                setTimeout(() => this.rebindAllTranscriptButtons(), 100);
            });

            // Also use jQuery event if available (Owl Carousel uses jQuery)
            if (typeof jQuery !== 'undefined') {
                jQuery(document).on('initialized.owl.carousel refreshed.owl.carousel', () => {
                    setTimeout(() => this.rebindAllTranscriptButtons(), 100);
                });
            }
        }

        /**
         * Rebind all transcript buttons on the page
         * This is useful after Owl Carousel clones slides
         */
        rebindAllTranscriptButtons() {
            
            document.querySelectorAll('.btn-transcript').forEach(button => {
                // Skip if already bound (check for our marker)
                if (button.dataset.ekwaTranscriptBound === 'true') {
                    return;
                }
                
                this.bindSingleTranscriptButton(button);
                
                // Mark as bound to prevent duplicate bindings
                button.dataset.ekwaTranscriptBound = 'true';
            });
        }

        bindSingleTranscriptButton(button) {
            
            // Skip if already bound
            if (button.dataset.ekwaTranscriptBound === 'true') {
                return;
            }

            // Create bound handler for this specific button
            const handler = (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.handleTranscriptToggle(e, button);
            };

            // Add listeners
            button.addEventListener('click', handler);
            button.addEventListener('touchend', handler, { passive: false });
            button.addEventListener('touchstart', (e) => {
                button.classList.add('touching');
            });
            button.addEventListener('touchend', (e) => {
                button.classList.remove('touching');
            });
        }

        loadYouTubeAPI() {
            // Return existing promise if already loading
            if (this.youtubeAPIPromise) {
                return this.youtubeAPIPromise;
            }

            // If already loaded, resolve immediately
            if (window.YT && window.YT.Player) {
                this.youtubeAPIReady = true;
                return Promise.resolve(window.YT);
            }

            this.youtubeAPIPromise = new Promise((resolve, reject) => {
                // Set timeout to prevent indefinite waiting - reduced to 5 seconds
                const timeout = setTimeout(() => {
                    console.warn('YouTube API load timeout - using iframe fallback');
                    this.youtubeAPIReady = false;
                    resolve(null); // Resolve with null to use fallback
                }, 5000);

                if (!window.ekwaYTLoading) {
                    window.ekwaYTLoading = true;

                    // Store existing callback if any
                    const existingCallback = window.onYouTubeIframeAPIReady;

                    window.onYouTubeIframeAPIReady = () => {
                        clearTimeout(timeout);
                        this.youtubeAPIReady = true;
                        
                        // Call existing callback if any
                        if (existingCallback) {
                            existingCallback();
                        }
                        
                        resolve(window.YT);
                    };

                    const tag = document.createElement('script');
                    tag.src = 'https://www.youtube.com/iframe_api';
                    tag.async = true;
                    tag.onerror = () => {
                        clearTimeout(timeout);
                        console.warn('YouTube API failed to load - using iframe fallback');
                        this.youtubeAPIReady = false;
                        resolve(null);
                    };
                    document.head.appendChild(tag);
                } else {
                    // Wait for existing load
                    const checkLoaded = setInterval(() => {
                        if (window.YT && window.YT.Player) {
                            clearInterval(checkLoaded);
                            clearTimeout(timeout);
                            this.youtubeAPIReady = true;
                            resolve(window.YT);
                        }
                    }, 100);
                }
            });

            return this.youtubeAPIPromise;
        }

        handleThumbnailClick(e) {
            if (e && e.preventDefault) e.preventDefault();
            
            const thumbnail = e.target.closest ? e.target.closest('.ekwa-video-thumbnail') : e.target;
            
            if (!thumbnail) {
                console.error('No thumbnail found');
                return;
            }

            // Prevent double-clicks / rapid taps - but with a safety timeout
            if (thumbnail.dataset.ekwaLoading === 'true') {
                // Safety: reset after 3 seconds in case it got stuck
                const loadingStarted = parseInt(thumbnail.dataset.ekwaLoadingTime || '0');
                if (loadingStarted && (Date.now() - loadingStarted) > 3000) {
                    console.warn('Loading state was stuck, resetting...');
                    thumbnail.dataset.ekwaLoading = 'false';
                } else {
                    return;
                }
            }
            thumbnail.dataset.ekwaLoading = 'true';
            thumbnail.dataset.ekwaLoadingTime = Date.now().toString();

            // Support both old and new structure
            let player = thumbnail.closest('.ekwa-video-player');
            if (!player) {
                player = thumbnail.closest('.player');
            }

            if (!player) {
                console.error('No player container found');
                thumbnail.dataset.ekwaLoading = 'false';
                return;
            }

            let container = player.querySelector('.ekwa-video-iframe-container');
            if (!container) {
                // Create container if it doesn't exist
                container = document.createElement('div');
                container.className = 'ekwa-video-iframe-container';

                // Pre-set container to match thumbnail dimensions to prevent flash
                const thumbnailRect = thumbnail.getBoundingClientRect();
                container.style.width = '100%';
                container.style.height = thumbnailRect.height + 'px';
                container.style.position = 'relative';
                container.style.background = '#000';
                container.style.display = 'none';

                player.appendChild(container);
            }

            const embedUrl = thumbnail.dataset.embedUrl;
            let videoType = player.dataset.videoType || player.dataset.provider;
            let videoId = player.dataset.videoId || player.dataset.id;
            const autoplay = player.dataset.autoplay;

            if (!embedUrl || !videoType || !videoId) {
                console.error('Missing video data', { embedUrl, videoType, videoId });
                thumbnail.dataset.ekwaLoading = 'false';
                return;
            }

            try {
                if (videoType === 'youtube') {
                    // Check if we have an existing player for this video
                    const existingPlayerState = this.players.get(videoId);

                    if (existingPlayerState && existingPlayerState.player) {
                        // Resume existing player
                        this.resumeYouTubePlayer(existingPlayerState, thumbnail, player);
                        thumbnail.dataset.ekwaLoading = 'false';
                    } else {
                        // Create new player
                        const playerId = `player-${videoId}-${Date.now()}`;
                        this.loadYouTubePlayer(player, container, thumbnail, videoId, playerId);
                    }
                } else if (videoType === 'vimeo') {
                    this.loadVimeoPlayer(player, container, thumbnail, embedUrl);
                } else {
                    console.warn('Unknown video type:', videoType);
                    thumbnail.dataset.ekwaLoading = 'false';
                }
            } catch (err) {
                console.error('Error loading video:', err);
                thumbnail.dataset.ekwaLoading = 'false';
            }
        }

        resumeYouTubePlayer(playerState, thumbnail, playerElement) {
            const { player: ytPlayer, container, currentTime } = playerState;

            // Hide thumbnail overlay to reveal the active video underneath
            thumbnail.style.opacity = '0';
            thumbnail.style.transition = 'opacity 300ms';

            setTimeout(() => {
                // Reset thumbnail positioning after fade
                thumbnail.style.position = '';
                thumbnail.style.top = '';
                thumbnail.style.left = '';
                thumbnail.style.width = '';
                thumbnail.style.height = '';
                thumbnail.style.zIndex = '';
                thumbnail.style.display = 'none';

                // Clear overlay state
                thumbnail.dataset.overlayActive = 'false';

                // Show the player container
                container.style.display = 'block';
                playerElement.classList.add('ekwa-video-loaded');

                // Seek to saved time and play
                if (currentTime > 0) {
                    ytPlayer.seekTo(currentTime, true);
                }
                ytPlayer.playVideo();
            }, 300);
        }

        /**
         * Load YouTube player with fallback for iOS/Safari
         */
        loadYouTubePlayer(player, container, thumbnail, videoId, playerId) {
            // Get stored time if video was paused before
            const playerState = this.players.get(videoId) || { currentTime: 0 };

            // Get container height - use multiple fallbacks to prevent layout shift
            const containerHeight = this.getVideoContainerHeight(player, thumbnail);

            // Set container to match thumbnail dimensions exactly
            Object.assign(container.style, {
                position: 'relative',
                width: '100%',
                height: containerHeight + 'px',
                background: '#000',
                display: 'none' // Keep hidden until ready
            });

            // Show container and fade thumbnail - don't wait for API
            container.style.display = 'block';
            this.fadeOut(thumbnail, 300);

            // Try to load YouTube API, with fallback to iframe
            this.loadYouTubeAPI().then((YT) => {
                if (YT && YT.Player) {
                    // Use YouTube API for better control
                    this.createYouTubeAPIPlayer(player, container, thumbnail, videoId, playerState);
                } else {
                    // Fallback: Use standard iframe (more reliable on iOS/Safari)
                    this.createYouTubeIframeFallback(player, container, thumbnail, videoId, playerState);
                }
            }).catch((err) => {
                console.warn('YouTube API error, using iframe fallback:', err);
                // On error, use iframe fallback
                this.createYouTubeIframeFallback(player, container, thumbnail, videoId, playerState);
            });
        }

        /**
         * Create YouTube player using the Iframe API
         */
        createYouTubeAPIPlayer(player, container, thumbnail, videoId, playerState) {
            const playerId = `player-${videoId}-${Date.now()}`;
            
            // Create placeholder div for YT.Player
            const playerDiv = document.createElement('div');
            playerDiv.id = playerId;
            playerDiv.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            `;

            container.innerHTML = '';
            container.appendChild(playerDiv);

            try {
                const ytPlayer = new YT.Player(playerId, {
                    videoId: videoId,
                    host: 'https://www.youtube-nocookie.com', // Use privacy-enhanced domain
                    playerVars: {
                        autoplay: 1,
                        playsinline: 1, // Critical for iOS
                        rel: 0,
                        modestbranding: 1,
                        fs: 1,
                        cc_load_policy: 0,
                        iv_load_policy: 3,
                        autohide: 0,
                        start: Math.floor(playerState.currentTime || 0),
                        origin: window.location.origin
                    },
                    events: {
                        onReady: (readyEvent) => {
                            // Reset loading state
                            thumbnail.dataset.ekwaLoading = 'false';
                            
                            // Store player reference
                            this.players.set(videoId, {
                                ...playerState,
                                player: ytPlayer,
                                container: container,
                                thumbnail: thumbnail,
                                playerId: playerId,
                                useAPI: true
                            });

                            // Add loaded class to player
                            player.classList.add('ekwa-video-loaded');

                            // Trigger custom event for GA4 tracking
                            const customEvent = new CustomEvent('ekwaVideoLoaded', {
                                detail: {
                                    videoType: 'youtube',
                                    videoId: videoId,
                                    player: ytPlayer,
                                    playerElement: player
                                }
                            });
                            document.dispatchEvent(customEvent);
                        },
                        onStateChange: (stateEvent) => {
                            this.handleYouTubeStateChange(stateEvent, videoId, player);
                        },
                        onError: (errorEvent) => {
                            console.error('YouTube API player error:', errorEvent.data);
                            // On error, try iframe fallback
                            this.createYouTubeIframeFallback(player, container, thumbnail, videoId, playerState);
                        }
                    }
                });
            } catch (e) {
                console.error('Error creating YouTube API player:', e);
                // Fallback to iframe
                this.createYouTubeIframeFallback(player, container, thumbnail, videoId, playerState);
            }
        }

        /**
         * Create YouTube player using standard iframe (fallback for iOS/Safari)
         */
        createYouTubeIframeFallback(player, container, thumbnail, videoId, playerState) {
            // Build iframe URL with all necessary parameters
            const startTime = Math.floor(playerState.currentTime || 0);
            // Use youtube-nocookie.com to prevent blocking in private windows
            let iframeSrc = `https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1&playsinline=1&rel=0&modestbranding=1&fs=1&cc_load_policy=0&iv_load_policy=3&enablejsapi=1&origin=${encodeURIComponent(window.location.origin)}`;
            
            if (startTime > 0) {
                iframeSrc += `&start=${startTime}`;
            }

            // Create iframe - set attributes BEFORE setting src
            const iframe = document.createElement('iframe');
            iframe.frameBorder = '0';
            iframe.allowFullscreen = true;
            // Critical: allow attribute for autoplay - must be set before src
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
            iframe.setAttribute('allowfullscreen', '');
            iframe.setAttribute('webkitallowfullscreen', '');
            iframe.setAttribute('mozallowfullscreen', '');
            iframe.width = '100%';
            iframe.height = '100%';
            iframe.className = 'ekwa-video-iframe';

            Object.assign(iframe.style, {
                position: 'absolute',
                top: '0',
                left: '0',
                width: '100%',
                height: '100%',
                border: 'none'
            });

            // Clear container and add iframe first
            container.innerHTML = '';
            container.appendChild(iframe);
            
            // Set src AFTER iframe is in the DOM (important for some browsers on first load)
            iframe.src = iframeSrc;

            // Reset loading state immediately
            thumbnail.dataset.ekwaLoading = 'false';

            // Store reference (without YT.Player instance)
            this.players.set(videoId, {
                ...playerState,
                player: null,
                iframe: iframe,
                container: container,
                thumbnail: thumbnail,
                useAPI: false
            });

            // Add loaded class to player
            player.classList.add('ekwa-video-loaded');

            // Trigger custom event for GA4 tracking
            const customEvent = new CustomEvent('ekwaVideoLoaded', {
                detail: {
                    videoType: 'youtube',
                    videoId: videoId,
                    player: iframe,
                    playerElement: player
                }
            });
            document.dispatchEvent(customEvent);
        }

        handleYouTubeStateChange(event, videoId, playerElement) {
            const playerState = this.players.get(videoId);
            if (!playerState) return;

            const { player, container, thumbnail } = playerState;

            switch (event.data) {
                case YT.PlayerState.PLAYING:
                    // Cancel any pending pause timeout (user was seeking, not pausing)
                    if (playerState.pauseTimeout) {
                        clearTimeout(playerState.pauseTimeout);
                        this.players.set(videoId, {
                            ...playerState,
                            pauseTimeout: null
                        });
                    }
                    // Hide thumbnail if it was shown during seeking
                    this.hideThumbnailOverlay(thumbnail, playerElement);
                    break;

                case YT.PlayerState.PAUSED:
                    // Store current time
                    const currentTime = player.getCurrentTime();
                    
                    // Use a small delay before showing thumbnail to handle seeking
                    // When seeking on progress bar, PAUSED fires briefly then PLAYING resumes
                    const pauseTimeout = setTimeout(() => {
                        // Double-check the video is still paused before showing thumbnail
                        const currentState = this.players.get(videoId);
                        if (currentState && currentState.pauseTimeout) {
                            this.showThumbnailBack(container, thumbnail, playerElement);
                            this.players.set(videoId, {
                                ...currentState,
                                pauseTimeout: null
                            });
                        }
                    }, 300); // Wait 300ms to confirm it's a real pause, not seeking
                    
                    this.players.set(videoId, {
                        ...playerState,
                        currentTime: currentTime,
                        pauseTimeout: pauseTimeout
                    });
                    break;

                case YT.PlayerState.ENDED:
                    // Cancel any pending pause timeout
                    if (playerState.pauseTimeout) {
                        clearTimeout(playerState.pauseTimeout);
                    }
                    // Reset time and show thumbnail
                    this.players.set(videoId, {
                        ...playerState,
                        currentTime: 0,
                        pauseTimeout: null
                    });

                    this.showThumbnailBack(container, thumbnail, playerElement);
                    break;
            }
        }

        hideThumbnailOverlay(thumbnail, playerElement) {
            // Only hide if the thumbnail is currently overlaying
            if (thumbnail.dataset.overlayActive === 'true') {
                thumbnail.style.opacity = '0';
                thumbnail.style.transition = 'opacity 200ms';
                
                setTimeout(() => {
                    thumbnail.style.display = 'none';
                    thumbnail.style.position = '';
                    thumbnail.style.top = '';
                    thumbnail.style.left = '';
                    thumbnail.style.width = '';
                    thumbnail.style.height = '';
                    thumbnail.style.zIndex = '';
                    thumbnail.dataset.overlayActive = 'false';
                }, 200);
                
                playerElement.classList.add('ekwa-video-loaded');
            }
        }

        showThumbnailBack(container, thumbnail, playerElement) {
            // Instead of hiding the video, overlay the thumbnail on top
            // Keep the video container visible but overlay the thumbnail

            // Get container dimensions to match exactly
            const containerRect = container.getBoundingClientRect();

            // Position thumbnail as overlay with exact container dimensions
            thumbnail.style.position = 'absolute';
            thumbnail.style.top = '0';
            thumbnail.style.left = '0';
            thumbnail.style.width = '100%';
            thumbnail.style.height = containerRect.height + 'px'; // Match container height exactly
            thumbnail.style.zIndex = '10';
            thumbnail.style.display = 'block';
            thumbnail.style.opacity = '0';
            thumbnail.style.transition = 'opacity 300ms';
            thumbnail.style.cursor = 'pointer';

            // Store overlay state for resize handling
            thumbnail.dataset.overlayActive = 'true';

            // Make sure the player container is positioned relative
            playerElement.style.position = 'relative';

            requestAnimationFrame(() => {
                thumbnail.style.opacity = '1';
            });

            // Remove loaded class but keep video active underneath
            playerElement.classList.remove('ekwa-video-loaded');
        }        loadVimeoPlayer(player, container, thumbnail, embedUrl) {
            // Build iframe source with autoplay and no related content
            let iframeSrc = embedUrl;
            const separator = embedUrl.includes('?') ? '&' : '?';
            // Add dnt=1 (Do Not Track) to prevent cookie blocking issues in private windows
            iframeSrc += separator + 'autoplay=1&title=0&byline=0&portrait=0&loop=0&playsinline=1&dnt=1';

            // Create iframe
            const iframe = document.createElement('iframe');
            iframe.frameBorder = '0';
            iframe.allowFullscreen = true;
            // Critical: allow attribute for autoplay - must be set before src
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen';
            iframe.setAttribute('allowfullscreen', '');
            iframe.setAttribute('webkitallowfullscreen', '');
            iframe.setAttribute('mozallowfullscreen', '');
            iframe.width = '100%';
            iframe.height = '100%';
            iframe.className = 'ekwa-video-iframe';

            // Get container height - use multiple fallbacks to prevent layout shift
            const containerHeight = this.getVideoContainerHeight(player, thumbnail);

            // Set container to match thumbnail dimensions exactly
            Object.assign(container.style, {
                position: 'relative',
                width: '100%',
                height: containerHeight + 'px',
                background: '#000'
            });

            Object.assign(iframe.style, {
                position: 'absolute',
                top: '0',
                left: '0',
                width: '100%',
                height: '100%',
                border: 'none'
            });

            // Prepare container first, then load iframe
            container.innerHTML = '';
            container.appendChild(iframe);
            
            // Set src AFTER iframe is in DOM (important for some browsers)
            iframe.src = iframeSrc;

            // Hide thumbnail and show container immediately (don't wait for fade)
            container.style.display = 'block';
            player.classList.add('ekwa-video-loaded');
            
            // Reset loading flag immediately
            thumbnail.dataset.ekwaLoading = 'false';

            // Fade out thumbnail after container is visible
            this.fadeOut(thumbnail, 300, () => {
                // Trigger custom event on document for GA4 tracking
                const customEvent = new CustomEvent('ekwaVideoLoaded', {
                    detail: {
                        videoType: 'vimeo',
                        videoId: this.extractVimeoVideoId(embedUrl),
                        embedUrl: embedUrl,
                        player: iframe, // Pass the iframe for Vimeo tracking
                        playerElement: player // Also pass the player element
                    }
                });
                document.dispatchEvent(customEvent);
            });
        }

        handlePlayButtonClick(e) {
            e.preventDefault();
            e.stopPropagation();

            // This will bubble up to thumbnail click
            const thumbnail = e.target.closest('.ekwa-video-thumbnail');
            if (thumbnail) {
                thumbnail.click();
            }
        }

        handlePlayIconClick(e) {
            e.preventDefault();
            const playIcon = e.target.closest('.playicon');
            const thumbnail = playIcon.closest('.ekwa-video-thumbnail');

            if (thumbnail) {
                thumbnail.click();
            }
        }

        handleTranscriptToggle(e, forcedButton = null) {

            if (e && e.preventDefault) {
                e.preventDefault();
            }
            if (e && e.stopPropagation) {
                e.stopPropagation();
            }

            // Try multiple ways to find the button
            let button = forcedButton;
            if (!button && e.target) {
                button = e.target.closest('.btn-transcript');
            }
            if (!button && e.currentTarget) {
                button = e.currentTarget.closest('.btn-transcript');
            }
            if (!button) {
                button = document.querySelector('.btn-transcript');
            }


            if (!button) {
                return;
            }

            // Find transcript element - prioritize finding within same container (for Owl Carousel compatibility)
            let transcript = null;
            
            // First, try to find transcript within the same video wrapper (handles Owl Carousel clones)
            const videoWrapper = button.closest('.ekwa-video-wrapper, .ekv-wrapper');
            if (videoWrapper) {
                transcript = videoWrapper.querySelector('.transcript-wrapper-del, .transcript');
            }
            
            // Fallback: if no transcript in wrapper, try using data-target attribute
            if (!transcript) {
                let targetId = button.getAttribute('data-target');
                if (targetId) {
                    // For Owl Carousel clones, the ID might be duplicated
                    // Try to find the transcript within the closest carousel item first
                    const carouselItem = button.closest('.owl-item, .carousel-item, .swiper-slide');
                    if (carouselItem) {
                        // Look for transcript with matching ID pattern within the carousel item
                        const transcriptIdWithoutHash = targetId.replace('#', '');
                        transcript = carouselItem.querySelector('[id^="transcript-"], .transcript-wrapper-del, .transcript');
                    }
                    
                    // If still not found, use the global ID selector
                    if (!transcript) {
                        transcript = document.querySelector(targetId);
                    }
                }
            }


            if (!transcript) {
                return;
            }


            // Check current state
            const isOpen = transcript.classList.contains('open') || transcript.style.display === 'block';

            if (isOpen) {
                // Close transcript
                transcript.classList.remove('open');
                this.slideUp(transcript, 300);

                // Update button text
                const buttonText = button.textContent || button.innerText;
                if (buttonText.includes('Hide')) {
                    button.innerHTML = button.innerHTML.replace('Hide', 'Video');
                }
                button.setAttribute('aria-expanded', 'false');

            } else {
                // Open transcript
                transcript.classList.add('open');
                this.slideDown(transcript, 300);

                // Update button text
                const buttonText = button.textContent || button.innerText;
                if (buttonText.includes('Video')) {
                    button.innerHTML = button.innerHTML.replace('Video', 'Hide');
                }
                button.setAttribute('aria-expanded', 'true');

            }
        }

        // Animation utilities to replace jQuery's fadeOut, slideUp, slideDown
        fadeOut(element, duration, callback) {
            element.style.opacity = 1;
            element.style.transition = `opacity ${duration}ms`;

            requestAnimationFrame(() => {
                element.style.opacity = 0;
            });

            setTimeout(() => {
                element.style.display = 'none';
                if (callback) callback();
            }, duration);
        }

        slideUp(element, duration) {

            // Store original styles
            const originalHeight = element.scrollHeight;
            const originalPadding = window.getComputedStyle(element).padding;

            // Set initial state for animation
            element.style.transition = `height ${duration}ms ease-out, padding ${duration}ms ease-out, opacity ${duration}ms ease-out`;
            element.style.overflow = 'hidden';
            element.style.height = originalHeight + 'px';
            element.style.opacity = '1';

            // Force reflow
            element.offsetHeight;

            // Start animation
            requestAnimationFrame(() => {
                element.style.height = '0px';
                element.style.paddingTop = '0';
                element.style.paddingBottom = '0';
                element.style.opacity = '0';
            });

            // Clean up after animation
            setTimeout(() => {
                element.style.display = 'none';
                element.style.transition = '';
                element.style.overflow = '';
                element.style.height = '';
                element.style.paddingTop = '';
                element.style.paddingBottom = '';
                element.style.opacity = '';
            }, duration);
        }

        slideDown(element, duration) {

            // Show element to measure height
            const originalDisplay = element.style.display;
            element.style.visibility = 'hidden';
            element.style.display = 'block';
            element.style.height = 'auto';

            const targetHeight = element.scrollHeight;

            // Reset to starting state
            element.style.visibility = '';
            element.style.height = '0px';
            element.style.paddingTop = '0';
            element.style.paddingBottom = '0';
            element.style.opacity = '0';
            element.style.overflow = 'hidden';
            element.style.transition = `height ${duration}ms ease-out, padding ${duration}ms ease-out, opacity ${duration}ms ease-out`;

            // Force reflow
            element.offsetHeight;

            // Start animation
            requestAnimationFrame(() => {
                element.style.height = targetHeight + 'px';
                element.style.paddingTop = '';
                element.style.paddingBottom = '';
                element.style.opacity = '1';
            });

            // Clean up after animation
            setTimeout(() => {
                element.style.transition = '';
                element.style.overflow = '';
                element.style.height = '';
                element.style.opacity = '';
            }, duration);
        }

        /**
         * Get reliable container height to prevent layout shift
         * Uses multiple fallbacks: image dimensions, thumbnail rect, or calculated from width
         */
        getVideoContainerHeight(player, thumbnail) {
            // Try 1: Get from the image element's rendered height
            const img = thumbnail.querySelector('img');
            if (img && img.offsetHeight > 0) {
                return img.offsetHeight;
            }

            // Try 2: Get from thumbnail's bounding rect
            const thumbnailRect = thumbnail.getBoundingClientRect();
            if (thumbnailRect.height > 0) {
                return thumbnailRect.height;
            }

            // Try 3: Calculate from image's natural dimensions if available
            if (img && img.naturalWidth > 0 && img.naturalHeight > 0) {
                const aspectRatio = img.naturalHeight / img.naturalWidth;
                const width = thumbnail.offsetWidth || player.offsetWidth;
                return width * aspectRatio;
            }

            // Try 4: Calculate from player/wrapper width using 16:9 aspect ratio
            const wrapper = player.closest('.ekwa-video-wrapper, .ekv-wrapper');
            const width = wrapper ? wrapper.offsetWidth : player.offsetWidth;
            return width * (9 / 16);
        }

        // Utility method to get video ID from URL
        static extractVideoId(url, type) {
            if (type === 'youtube') {
                const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
                const match = url.match(regExp);
                return (match && match[2].length === 11) ? match[2] : null;
            } else if (type === 'vimeo') {
                const regExp = /(?:vimeo\.com\/)(?:.*#|.*\/videos\/)?([0-9]+)/;
                const match = url.match(regExp);
                return match ? match[1] : null;
            }
            return null;
        }

        // Instance method to extract Vimeo video ID
        extractVimeoVideoId(url) {
            const regExp = /(?:vimeo\.com\/)(?:.*#|.*\/videos\/)?([0-9]+)/;
            const match = url.match(regExp);
            return match ? match[1] : null;
        }

        // Utility method to format duration
        static formatDuration(duration) {
            if (!duration) return '';

            // If it's in ISO 8601 format (PT1M30S), convert it
            const iso8601Match = duration.match(/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/);
            if (iso8601Match) {
                const hours = parseInt(iso8601Match[1] || 0);
                const minutes = parseInt(iso8601Match[2] || 0);
                const seconds = parseInt(iso8601Match[3] || 0);

                if (hours > 0) {
                    return hours + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                } else {
                    return minutes + ':' + String(seconds).padStart(2, '0');
                }
            }

            return duration;
        }

        // Note: YouTube Iframe API is loaded by default for better video control
        // This allows us to handle pause/resume states and prevent related videos
    }

    // Responsive video handling
    function handleResponsiveVideos() {
        document.querySelectorAll('.ekwa-video-wrapper, .ekv-wrapper').forEach(function(wrapper) {
            const player = wrapper.querySelector('.ekwa-video-player, .player');
            const container = player ? player.querySelector('.ekwa-video-iframe-container') : null;

            if (container) {
                // Calculate proper height based on current wrapper width and 16:9 aspect ratio
                const wrapperWidth = wrapper.offsetWidth;
                const aspectRatio = 9 / 16; // height / width for 16:9
                const calculatedHeight = wrapperWidth * aspectRatio;

                // Always update container height (whether loaded or not)
                container.style.height = calculatedHeight + 'px';
                container.style.paddingBottom = '0';

                // Force a style recalculation
                container.offsetHeight;


                // If there's an iframe inside, make sure it fills the container
                const iframe = container.querySelector('iframe, div[id^="player-"]');
                if (iframe) {
                    iframe.style.width = '100%';
                    iframe.style.height = '100%';
                }
            }
        });
    }

    // Add function to reposition thumbnail overlays after resize
    function repositionThumbnailOverlays() {
        // Find all videos that have thumbnail overlays active
        document.querySelectorAll('.ekwa-video-player, .player').forEach(function(player) {
            const container = player.querySelector('.ekwa-video-iframe-container');
            const thumbnail = player.querySelector('.ekwa-video-thumbnail');

            if (container && thumbnail && thumbnail.dataset.overlayActive === 'true') {
                // This means the thumbnail is currently overlaying the video
                const containerRect = container.getBoundingClientRect();

                // Update thumbnail dimensions to match current container size
                thumbnail.style.height = containerRect.height + 'px';

                // Ensure positioning is still correct
                thumbnail.style.position = 'absolute';
                thumbnail.style.top = '0';
                thumbnail.style.left = '0';
                thumbnail.style.width = '100%';
                thumbnail.style.zIndex = '10';

            }
        });
    }

    // Handle window resize with debounce
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            // console.log('Window resized, updating videos...');
            handleResponsiveVideos();
            // Fix thumbnail overlays after resize
            repositionThumbnailOverlays();
        }, 100); // Reduced from 250ms for more responsive feel
    });

    // Initialize responsive handling
    handleResponsiveVideos();

    // Expose functions for debugging
    window.EkwaVideoDebug = {
        handleResponsiveVideos: handleResponsiveVideos,
        repositionThumbnailOverlays: repositionThumbnailOverlays,
        forceUpdate: function() {
            // console.log('=== FORCING VIDEO UPDATE ===');
            handleResponsiveVideos();
            repositionThumbnailOverlays();
        }
    };

    // Run initialization function
    function initEkwaVideoPlayer() {
        // Initialize the video player
        const ekwaVideoPlayer = new EkwaVideoPlayer();

        // Expose to global scope for debugging and external use
        window.EkwaVideoPlayer = EkwaVideoPlayer;
        
        // Expose rebind function for use after carousel initialization
        window.ekwaRebindTranscriptButtons = function() {
            ekwaVideoPlayer.rebindAllTranscriptButtons();
        };

        // Handle lazy loading if intersection observer is available
        if ('IntersectionObserver' in window) {
            const lazyVideoObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('ekwa-video-in-view');
                        lazyVideoObserver.unobserve(entry.target);
                    }
                });
            });

            document.querySelectorAll('.ekwa-video-wrapper, .ekv-wrapper').forEach(function(wrapper) {
                lazyVideoObserver.observe(wrapper);
            });
        }

        // Analytics tracking (optional)
        document.addEventListener('ekwaVideoLoaded', function(e) {
            // Track video load event
            if (typeof gtag !== 'undefined') {
                gtag('event', 'video_load', {
                    'video_type': e.detail.videoType,
                    'video_id': e.detail.videoId
                });
            }

            // Custom tracking hook
            const analyticsEvent = new CustomEvent('ekwa_video_analytics', {
                detail: e.detail
            });
            document.dispatchEvent(analyticsEvent);
        });

        // Initialize responsive handling
        handleResponsiveVideos();
    }

    // Check if DOM is already ready (for lazy-loaded scripts)
    if (document.readyState === 'loading') {
        // DOM not ready yet, wait for it
        document.addEventListener('DOMContentLoaded', initEkwaVideoPlayer);
    } else {
        // DOM is already ready, initialize immediately
        initEkwaVideoPlayer();
    }

})();

/**
 * GLightbox Integration for Ekwa Video Block
 * Lazy loads GLightbox library on user interaction
 */
(function() {
    'use strict';

    let glightboxLoaded = false;
    let glightboxInstance = null;

    /**
     * Load GLightbox library and initialize
     */
    function loadGLightbox() {
        if (glightboxLoaded) return Promise.resolve();

        return new Promise(function(resolve, reject) {
            // Load CSS
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = ekwaVideoData.pluginUrl + 'assets/vendor/glightbox.min.css';
            link.onload = function() {
                // Load JS
                const script = document.createElement('script');
                script.src = ekwaVideoData.pluginUrl + 'assets/vendor/glightbox.min.js';
                script.onload = function() {
                    glightboxLoaded = true;
                    initializeGLightbox();
                    resolve();
                };
                script.onerror = reject;
                document.head.appendChild(script);
            };
            link.onerror = reject;
            document.head.appendChild(link);
        });
    }

    /**
     * Initialize GLightbox
     */
    function initializeGLightbox() {
        if (typeof GLightbox !== 'undefined' && !glightboxInstance) {
            // Initialize with clean configuration - no transcript in lightbox
            glightboxInstance = GLightbox({
                selector: '.glightbox',
                touchNavigation: true,
                loop: false,
                autoplayVideos: true,
                videosWidth: '90vw',
                descPosition: 'bottom',
                moreText: '',
                moreLength: 0
            });

            // console.log('GLightbox initialized successfully');
        }
    }    /**
     * Setup lazy loading triggers
     */
    function setupLazyLoading() {
        let hasTriggered = false;
        let clickHandlerAdded = false;

        function triggerLoad() {
            if (hasTriggered) return;
            hasTriggered = true;

            loadGLightbox().catch(function(error) {
                console.error('Failed to load GLightbox:', error);
            });

            // Remove event listeners
            window.removeEventListener('scroll', triggerLoad);
            window.removeEventListener('mousemove', triggerLoad);
            window.removeEventListener('touchstart', triggerLoad);
        }

        function handleLightboxClick(event) {
            const lightboxTrigger = event.target.closest('.glightbox');
            if (lightboxTrigger && !glightboxLoaded) {
                event.preventDefault();
                event.stopPropagation();

                // console.log('Loading GLightbox...');

                // Show loading indicator
                lightboxTrigger.style.opacity = '0.7';

                loadGLightbox().then(function() {
                    // console.log('GLightbox loaded, opening lightbox...');
                    // Restore original content
                    lightboxTrigger.style.opacity = '1';

                    // Remove this click handler to prevent loop
                    document.removeEventListener('click', handleLightboxClick);

                    // Give GLightbox a moment to initialize, then trigger manually
                    setTimeout(function() {
                        if (glightboxInstance) {
                            // Find the index of this element in all glightbox elements
                            const allLightboxElements = document.querySelectorAll('.glightbox');
                            const elementIndex = Array.from(allLightboxElements).indexOf(lightboxTrigger);
                            // console.log('Opening lightbox at index:', elementIndex);
                            glightboxInstance.openAt(elementIndex);
                        } else {
                            // Fallback: try normal click
                            lightboxTrigger.click();
                        }
                    }, 200);
                }).catch(function(error) {
                    lightboxTrigger.style.opacity = '1';
                });
            }
        }

        // Check if lightbox videos exist on page
        if (document.querySelector('.glightbox')) {
            // Add event listeners for lazy loading
            window.addEventListener('scroll', triggerLoad, { passive: true });
            window.addEventListener('mousemove', triggerLoad, { passive: true });
            window.addEventListener('touchstart', triggerLoad, { passive: true });

            // Handle immediate clicks on lightbox triggers only if GLightbox not loaded
            if (!clickHandlerAdded) {
                document.addEventListener('click', handleLightboxClick);
                clickHandlerAdded = true;
            }
        }
    }

    /**
     * Initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupLazyLoading);
    } else {
        setupLazyLoading();
    }

})();
