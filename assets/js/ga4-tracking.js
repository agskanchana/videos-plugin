/**
 * Enhanced Ekwa Video Plugin with GA4 Tracking
 * Handles both Vimeo and YouTube video tracking for Google Analytics 4
 * Based on the original ekwa-video-script.js
 */

(function() {
    'use strict';

    // Global GA4 event tracking function
    function sendGA4Event(eventName, parameters) {
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, parameters);
            console.log('📊 GA4 Event (Ekwa Video):', eventName, parameters);
        } else if (typeof ga !== 'undefined') {
            // Fallback for Universal Analytics
            ga('send', 'event', 'Video', eventName, parameters.video_title);
            console.log('📊 UA Event (Ekwa Video):', eventName, parameters);
        } else {
            console.warn('⚠️ Google Analytics not detected. Video tracking disabled.');
        }
    }

    // Track video milestones
    function trackVideoProgress(player, videoData, provider) {
        const milestones = [25, 50, 75];
        const triggered = videoData.triggered || {};

        if (provider === 'youtube') {
            const duration = player.getDuration();
            const currentTime = player.getCurrentTime();
            const percent = (currentTime / duration) * 100;

            milestones.forEach(function(milestone) {
                if (percent >= milestone && !triggered[milestone]) {
                    triggered[milestone] = true;
                    sendGA4Event('video_progress', {
                        video_title: videoData.title,
                        video_provider: 'youtube',
                        video_id: videoData.id,
                        video_url: window.location.href,
                        progress_percentage: milestone,
                        video_current_time: Math.round(currentTime),
                        video_duration: Math.round(duration)
                    });
                }
            });
        } else if (provider === 'vimeo' && videoData.duration) {
            const percent = (videoData.currentTime / videoData.duration) * 100;

            milestones.forEach(function(milestone) {
                if (percent >= milestone && !triggered[milestone]) {
                    triggered[milestone] = true;
                    sendGA4Event('video_progress', {
                        video_title: videoData.title,
                        video_provider: 'vimeo',
                        video_id: videoData.id,
                        video_url: window.location.href,
                        progress_percentage: milestone,
                        video_current_time: Math.round(videoData.currentTime),
                        video_duration: Math.round(videoData.duration)
                    });
                }
            });
        }

        videoData.triggered = triggered;
    }

    // Extract video title from various sources
    function extractVideoTitle(element) {
        // Try to get video title from various sources
        let wrapper = element.closest('.ekwa-video-wrapper') || element.closest('.ekv-wrapper');

        if (wrapper) {
            // Check for title in h3 element
            let titleElement = wrapper.querySelector('.ekwa-video-title');
            if (titleElement && titleElement.textContent.trim()) {
                return titleElement.textContent.trim();
            }

            // Check for meta title
            let metaTitle = wrapper.querySelector('meta[itemprop="name"]');
            if (metaTitle && metaTitle.content) {
                return metaTitle.content;
            }

            // Check for h2 span with itemprop
            titleElement = wrapper.querySelector('h2 span[itemprop="name"]');
            if (titleElement && titleElement.textContent.trim()) {
                return titleElement.textContent.trim();
            }
        }

        return 'Unknown Video Title';
    }

    // Extract video ID from URL
    function extractYouTubeVideoId(url) {
        const match = url.match(/embed\/([^?&]+)/);
        return match ? match[1] : '';
    }

    function extractVimeoVideoId(url) {
        const match = url.match(/video\/(\d+)/);
        return match ? match[1] : '';
    }

    // Setup YouTube tracking
    function setupYouTubeTracking(player, videoId, videoTitle) {
        const videoData = {
            id: videoId,
            title: videoTitle,
            triggered: {}
        };

        // Track video start
        let hasStarted = false;

        // Track video pause
        let progressInterval;

        player.addEventListener('onStateChange', function(event) {
            switch (event.data) {
                case YT.PlayerState.PLAYING:
                    if (!hasStarted) {
                        hasStarted = true;
                        sendGA4Event('video_start', {
                            video_title: videoData.title,
                            video_provider: 'youtube',
                            video_id: videoData.id,
                            video_url: window.location.href,
                            video_duration: Math.round(player.getDuration())
                        });
                    }

                    // Set up progress tracking
                    progressInterval = setInterval(function() {
                        trackVideoProgress(player, videoData, 'youtube');
                    }, 1000);
                    break;

                case YT.PlayerState.PAUSED:
                    clearInterval(progressInterval);
                    sendGA4Event('video_pause', {
                        video_title: videoData.title,
                        video_provider: 'youtube',
                        video_id: videoData.id,
                        video_url: window.location.href,
                        video_current_time: Math.round(player.getCurrentTime()),
                        video_duration: Math.round(player.getDuration())
                    });
                    break;

                case YT.PlayerState.ENDED:
                    clearInterval(progressInterval);
                    sendGA4Event('video_complete', {
                        video_title: videoData.title,
                        video_provider: 'youtube',
                        video_id: videoData.id,
                        video_url: window.location.href,
                        video_duration: Math.round(player.getDuration())
                    });
                    break;
            }
        });
    }

    // Setup Vimeo tracking
    function setupVimeoTracking(player, videoId, videoTitle) {
        console.log('🎯 Setting up Vimeo tracking for:', videoId, videoTitle);

        const videoData = {
            id: videoId,
            title: videoTitle,
            triggered: {},
            duration: 0,
            currentTime: 0
        };

        let hasStarted = false;

        // Get video duration
        player.getDuration().then(function(duration) {
            videoData.duration = duration;
            console.log('📏 Vimeo video duration:', duration);
        }).catch(function(error) {
            console.error('❌ Failed to get Vimeo video duration:', error);
        });

        // Track video start
        player.on('play', function() {
            console.log('▶️ Vimeo video started playing');
            if (!hasStarted) {
                hasStarted = true;
                sendGA4Event('video_start', {
                    video_title: videoData.title,
                    video_provider: 'vimeo',
                    video_id: videoData.id,
                    video_url: window.location.href,
                    video_duration: Math.round(videoData.duration)
                });
            }
        });

        // Track video progress
        player.on('timeupdate', function(data) {
            videoData.currentTime = data.seconds;
            trackVideoProgress(null, videoData, 'vimeo');
        });

        // Track video completion
        player.on('ended', function() {
            console.log('🏁 Vimeo video ended');
            sendGA4Event('video_complete', {
                video_title: videoData.title,
                video_provider: 'vimeo',
                video_id: videoData.id,
                video_url: window.location.href,
                video_duration: Math.round(videoData.duration)
            });
        });

        // Track video pause
        player.on('pause', function() {
            console.log('⏸️ Vimeo video paused');
            sendGA4Event('video_pause', {
                video_title: videoData.title,
                video_provider: 'vimeo',
                video_id: videoData.id,
                video_url: window.location.href,
                video_current_time: Math.round(videoData.currentTime),
                video_duration: Math.round(videoData.duration)
            });
        });

        console.log('✅ Vimeo tracking setup complete');
    }

    // Initialize tracking for existing videos
    function initializeVideoTracking() {
        console.log('🎯 GA4 Video Tracking: Initializing...');

        // Listen for the custom video loaded event from the main frontend.js
        document.addEventListener('ekwaVideoLoaded', function(e) {
            const { videoType, videoId, player, embedUrl } = e.detail;
            const videoElement = e.target;
            const videoTitle = extractVideoTitle(videoElement);

            console.log('🎯 GA4 Video Tracking: Video loaded event received');
            console.log('📹 Video Type:', videoType);
            console.log('🆔 Video ID:', videoId);
            console.log('📝 Video Title:', videoTitle);
            console.log('🎬 Player Object:', player);

            if (videoType === 'youtube' && player) {
                console.log('▶️ Setting up YouTube tracking...');
                setupYouTubeTracking(player, videoId, videoTitle);
            } else if (videoType === 'vimeo') {
                console.log('▶️ Setting up Vimeo tracking...');

                // For Vimeo, we need to wait for the Vimeo Player API to be available
                if (typeof Vimeo !== 'undefined') {
                    try {
                        console.log('✅ Vimeo API available, creating player...');
                        const vimeoPlayer = new Vimeo.Player(player);
                        setupVimeoTracking(vimeoPlayer, videoId, videoTitle);
                    } catch (error) {
                        console.error('❌ Error setting up Vimeo tracking:', error);
                    }
                } else {
                    console.log('⏳ Vimeo API not available, loading...');
                    // Load Vimeo API if not available
                    loadVimeoAPI().then(() => {
                        try {
                            console.log('✅ Vimeo API loaded, creating player...');
                            const vimeoPlayer = new Vimeo.Player(player);
                            setupVimeoTracking(vimeoPlayer, videoId, videoTitle);
                        } catch (error) {
                            console.error('❌ Error setting up Vimeo tracking after API load:', error);
                        }
                    }).catch(error => {
                        console.error('❌ Failed to load Vimeo API:', error);
                    });
                }
            }
        });

        // Also check for existing embedded videos
        initializeEmbeddedVideoTracking();
    }

    // Load Vimeo API if not available
    function loadVimeoAPI() {
        return new Promise((resolve, reject) => {
            if (typeof Vimeo !== 'undefined') {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://player.vimeo.com/api/player.js';
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Failed to load Vimeo API'));
            document.head.appendChild(script);
        });
    }

    // Initialize tracking for embedded videos
    function initializeEmbeddedVideoTracking() {
        // YouTube embedded videos
        const youtubeIframes = document.querySelectorAll('iframe[src*="youtube.com/embed"]');
        youtubeIframes.forEach(function(iframe) {
            if (iframe.id && window.YT && window.YT.Player) {
                try {
                    const videoId = extractYouTubeVideoId(iframe.src);
                    const videoTitle = extractVideoTitle(iframe);

                    const player = new YT.Player(iframe, {
                        events: {
                            'onReady': function(event) {
                                setupYouTubeTracking(event.target, videoId, videoTitle);
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error setting up YouTube tracking for embedded video:', error);
                }
            }
        });

        // Vimeo embedded videos
        const vimeoIframes = document.querySelectorAll('iframe[src*="vimeo.com"]');
        if (typeof Vimeo !== 'undefined') {
            vimeoIframes.forEach(function(iframe) {
                try {
                    const videoId = extractVimeoVideoId(iframe.src);
                    const videoTitle = extractVideoTitle(iframe);

                    const player = new Vimeo.Player(iframe);
                    setupVimeoTracking(player, videoId, videoTitle);
                } catch (error) {
                    console.error('Error setting up Vimeo tracking for embedded video:', error);
                }
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeVideoTracking);
    } else {
        initializeVideoTracking();
    }

    // Also initialize when YouTube API is ready
    if (window.YT && window.YT.Player) {
        initializeVideoTracking();
    } else {
        window.onYouTubeIframeAPIReady = function() {
            initializeVideoTracking();
        };
    }

})();
