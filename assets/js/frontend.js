document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    /**
     * Ekwa Video Block Frontend JavaScript - Vanilla JS
     */
    class EkwaVideoPlayer {
        constructor() {
            this.players = new Map(); // Store player instances and states
            this.init();
        }

        init() {
            this.bindEvents();
            this.loadYouTubeAPI(); // Load API for better control
        }

        bindEvents() {
            // Handle thumbnail click
            document.addEventListener('click', (e) => {
                if (e.target.closest('.ekwa-video-thumbnail')) {
                    this.handleThumbnailClick(e);
                }
            });

            // Handle play button click on loaded videos
            document.addEventListener('click', (e) => {
                if (e.target.closest('.ekwa-video-play-button')) {
                    this.handlePlayButtonClick(e);
                }
            });

            // Handle play icon click (for schema markup structure)
            document.addEventListener('click', (e) => {
                if (e.target.closest('.playicon')) {
                    this.handlePlayIconClick(e);
                }
            });

            // Handle transcript toggle
            document.addEventListener('click', (e) => {
                if (e.target.closest('.btn-transcript')) {
                    this.handleTranscriptToggle(e);
                }
            });
        }

        loadYouTubeAPI() {
            if (window.YT && window.YT.Player) {
                return Promise.resolve(window.YT);
            }

            return new Promise((resolve) => {
                if (!window.ekwaYTLoading) {
                    window.ekwaYTLoading = true;

                    window.onYouTubeIframeAPIReady = () => {
                        console.log('YouTube API loaded');
                        resolve(window.YT);
                    };

                    const tag = document.createElement('script');
                    tag.src = 'https://www.youtube.com/iframe_api';
                    tag.async = true;
                    document.head.appendChild(tag);
                } else {
                    // Wait for existing load
                    const checkLoaded = setInterval(() => {
                        if (window.YT && window.YT.Player) {
                            clearInterval(checkLoaded);
                            resolve(window.YT);
                        }
                    }, 100);
                }
            });
        }

        handleThumbnailClick(e) {
            e.preventDefault();
            const thumbnail = e.target.closest('.ekwa-video-thumbnail');

            // Support both old and new structure
            let player = thumbnail.closest('.ekwa-video-player');
            if (!player) {
                player = thumbnail.closest('.player');
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
                console.error('Missing video data');
                return;
            }

            if (videoType === 'youtube') {
                // Check if we have an existing player for this video
                const existingPlayerState = this.players.get(videoId);

                if (existingPlayerState && existingPlayerState.player) {
                    // Resume existing player
                    this.resumeYouTubePlayer(existingPlayerState, thumbnail, player);
                } else {
                    // Create new player
                    const playerId = `player-${videoId}-${Date.now()}`;
                    this.loadYouTubePlayer(player, container, thumbnail, videoId, playerId);
                }
            } else if (videoType === 'vimeo') {
                this.loadVimeoPlayer(player, container, thumbnail, embedUrl);
            }
        }

        resumeYouTubePlayer(playerState, thumbnail, playerElement) {
            const { player: ytPlayer, container, currentTime } = playerState;

            console.log('Resuming YouTube player from:', currentTime);

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

        loadYouTubePlayer(player, container, thumbnail, videoId, playerId) {
            // Get stored time if video was paused before
            const playerState = this.players.get(videoId) || { currentTime: 0 };

            console.log('Creating new YouTube player for:', videoId);

            // Calculate proper height based on wrapper width and 16:9 aspect ratio
            const wrapper = player.closest('.ekwa-video-wrapper, .ekv-wrapper');
            const wrapperWidth = wrapper ? wrapper.offsetWidth : player.offsetWidth;
            const aspectRatio = 9 / 16; // height / width for 16:9
            const calculatedHeight = wrapperWidth * aspectRatio;

            // Set container to match calculated dimensions
            Object.assign(container.style, {
                position: 'relative',
                width: '100%',
                height: calculatedHeight + 'px',
                background: '#000',
                display: 'none' // Keep hidden until ready
            });

            // Create iframe element
            const iframe = document.createElement('div');
            iframe.id = playerId;
            iframe.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            `;

            container.innerHTML = '';
            container.appendChild(iframe);

            // Hide thumbnail and show iframe
            this.fadeOut(thumbnail, 300, () => {
                // Show container with exact same dimensions
                container.style.display = 'block';

                // Initialize YouTube player with API for better control
                this.loadYouTubeAPI().then(() => {
                    const ytPlayer = new YT.Player(playerId, {
                        videoId: videoId,
                        playerVars: {
                            autoplay: 1,
                            rel: 0, // Don't show related videos
                            modestbranding: 1,
                            fs: 1,
                            cc_load_policy: 0,
                            iv_load_policy: 3,
                            autohide: 0,
                            start: Math.floor(playerState.currentTime), // Resume from where it was paused
                            origin: window.location.origin // Fix origin issues
                        },
                        events: {
                            onReady: (readyEvent) => {
                                console.log('YouTube player ready');
                                // Store player reference
                                this.players.set(videoId, {
                                    ...playerState,
                                    player: ytPlayer,
                                    container: container,
                                    thumbnail: thumbnail,
                                    playerId: playerId
                                });

                                // Add loaded class to player
                                player.classList.add('ekwa-video-loaded');

                                // Trigger custom event on document for GA4 tracking
                                const customEvent = new CustomEvent('ekwaVideoLoaded', {
                                    detail: {
                                        videoType: 'youtube',
                                        videoId: videoId,
                                        player: ytPlayer,
                                        playerElement: player // Also pass the player element
                                    }
                                });
                                document.dispatchEvent(customEvent);
                            },
                            onStateChange: (stateEvent) => {
                                this.handleYouTubeStateChange(stateEvent, videoId, player);
                            },
                            onError: (errorEvent) => {
                                console.error('YouTube player error:', errorEvent.data);
                            }
                        }
                    });
                });
            });
        }        handleYouTubeStateChange(event, videoId, playerElement) {
            const playerState = this.players.get(videoId);
            if (!playerState) return;

            const { player, container, thumbnail } = playerState;

            switch (event.data) {
                case YT.PlayerState.PLAYING:
                    console.log('Video playing');
                    break;

                case YT.PlayerState.PAUSED:
                    console.log('Video paused');
                    // Store current time
                    const currentTime = player.getCurrentTime();
                    this.players.set(videoId, {
                        ...playerState,
                        currentTime: currentTime
                    });

                    // Show thumbnail back
                    this.showThumbnailBack(container, thumbnail, playerElement);
                    break;

                case YT.PlayerState.ENDED:
                    console.log('Video ended');
                    // Reset time and show thumbnail
                    this.players.set(videoId, {
                        ...playerState,
                        currentTime: 0
                    });

                    this.showThumbnailBack(container, thumbnail, playerElement);
                    break;
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
            iframeSrc += separator + 'autoplay=1&title=0&byline=0&portrait=0&loop=0';

            // Create iframe
            const iframe = document.createElement('iframe');
            iframe.src = iframeSrc;
            iframe.frameBorder = '0';
            iframe.allowFullscreen = true;
            iframe.allow = 'autoplay; encrypted-media';
            iframe.width = '100%';
            iframe.height = '100%';
            iframe.className = 'ekwa-video-iframe';

            // Calculate proper height based on wrapper width and 16:9 aspect ratio
            const wrapper = player.closest('.ekwa-video-wrapper, .ekv-wrapper');
            const wrapperWidth = wrapper ? wrapper.offsetWidth : player.offsetWidth;
            const aspectRatio = 9 / 16; // height / width for 16:9
            const calculatedHeight = wrapperWidth * aspectRatio;

            // Set container to match calculated dimensions
            Object.assign(container.style, {
                position: 'relative',
                width: '100%',
                height: calculatedHeight + 'px',
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

            // Hide thumbnail and show iframe with fade effect
            this.fadeOut(thumbnail, 300, () => {
                container.innerHTML = '';
                container.appendChild(iframe);
                container.style.display = 'block';

                // Add loaded class to player
                player.classList.add('ekwa-video-loaded');

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

        handleTranscriptToggle(e) {
            e.preventDefault();
            const button = e.target.closest('.btn-transcript');
            const targetId = button.dataset.target;
            const transcript = document.querySelector(targetId);

            if (transcript) {
                if (transcript.classList.contains('open')) {
                    transcript.classList.remove('open');
                    this.slideUp(transcript, 300);
                    button.textContent = button.textContent.replace('Hide', 'Video');
                } else {
                    transcript.classList.add('open');
                    this.slideDown(transcript, 300);
                    button.textContent = button.textContent.replace('Video', 'Hide');
                }
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
            element.style.transition = `max-height ${duration}ms ease-out, padding ${duration}ms ease-out`;
            element.style.overflow = 'hidden';
            element.style.maxHeight = element.scrollHeight + 'px';

            requestAnimationFrame(() => {
                element.style.maxHeight = '0';
                element.style.paddingTop = '0';
                element.style.paddingBottom = '0';
            });

            setTimeout(() => {
                element.style.display = 'none';
                element.style.transition = '';
                element.style.overflow = '';
                element.style.maxHeight = '';
                element.style.paddingTop = '';
                element.style.paddingBottom = '';
            }, duration);
        }

        slideDown(element, duration) {
            element.style.display = 'block';
            element.style.transition = `max-height ${duration}ms ease-out, padding ${duration}ms ease-out`;
            element.style.overflow = 'hidden';
            element.style.maxHeight = '0';
            element.style.paddingTop = '0';
            element.style.paddingBottom = '0';

            requestAnimationFrame(() => {
                element.style.maxHeight = element.scrollHeight + 'px';
                element.style.paddingTop = '';
                element.style.paddingBottom = '';
            });

            setTimeout(() => {
                element.style.transition = '';
                element.style.overflow = '';
                element.style.maxHeight = '';
            }, duration);
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

    // Initialize the video player
    const ekwaVideoPlayer = new EkwaVideoPlayer();

    // Expose to global scope for debugging
    window.EkwaVideoPlayer = EkwaVideoPlayer;

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

                console.log('Responsive update - Wrapper Width:', wrapperWidth, 'Calculated Height:', calculatedHeight, 'Applied Height:', container.style.height);

                // If there's an iframe inside, make sure it fills the container
                const iframe = container.querySelector('iframe, div[id^="player-"]');
                if (iframe) {
                    iframe.style.width = '100%';
                    iframe.style.height = '100%';
                    console.log('Updated iframe dimensions');
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

                console.log('Repositioned thumbnail overlay - Height:', containerRect.height);
            }
        });
    }

    // Handle window resize with debounce
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            console.log('Window resized, updating videos...');
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
            console.log('=== FORCING VIDEO UPDATE ===');
            handleResponsiveVideos();
            repositionThumbnailOverlays();
        }
    };
});

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

            console.log('GLightbox initialized successfully');
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

                console.log('Loading GLightbox...');

                // Show loading indicator
                lightboxTrigger.style.opacity = '0.7';

                loadGLightbox().then(function() {
                    console.log('GLightbox loaded, opening lightbox...');
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
                            console.log('Opening lightbox at index:', elementIndex);
                            glightboxInstance.openAt(elementIndex);
                        } else {
                            console.error('GLightbox instance not available');
                            // Fallback: try normal click
                            lightboxTrigger.click();
                        }
                    }, 200);
                }).catch(function(error) {
                    console.error('Failed to load GLightbox:', error);
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
