/**
 * Enhanced Ekwa Video Plugin with GA4 Tracking
 * Handles both Vimeo and YouTube video tracking for Google Analytics 4
 */

(function($) {
    'use strict';

    // Global GA4 event tracking function
    function sendGA4Event(eventName, parameters) {
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, parameters);
            console.log('📊 GA4 Event (External Script):', eventName, parameters);
        } else if (typeof ga !== 'undefined') {
            // Fallback for Universal Analytics
            ga('send', 'event', 'Video', eventName, parameters.video_title || 'Unknown Video');
            console.log('📊 UA Event (External Script):', eventName, parameters.video_title || 'Unknown Video');
        } else {
            console.log('📊 Analytics not available for event:', eventName, parameters);
        }
    }

    // YouTube API ready flag
    var youtubeAPIReady = false;
    var vimeoAPIReady = typeof Vimeo !== 'undefined';

    // Load YouTube API if not already loaded
    if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
        var tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    } else {
        youtubeAPIReady = true;
    }

    // YouTube API Ready callback
    window.onYouTubeIframeAPIReady = function() {
        youtubeAPIReady = true;
        initializeYouTubeTracking();
    };

    // Load Vimeo API if not already loaded
    if (typeof Vimeo === 'undefined') {
        var vimeoScript = document.createElement('script');
        vimeoScript.src = 'https://player.vimeo.com/api/player.js';
        vimeoScript.onload = function() {
            vimeoAPIReady = true;
            initializeVimeoTracking();
        };
        document.head.appendChild(vimeoScript);
    } else {
        initializeVimeoTracking();
    }

    // Initialize YouTube tracking
    function initializeYouTubeTracking() {
        // Handle embedded YouTube videos
        var youtubeIframes = document.querySelectorAll('iframe[src*="youtube.com/embed"]');
        youtubeIframes.forEach(function(iframe, index) {
            if (iframe.id && iframe.id.includes('youtube-player')) {
                setupYouTubePlayer(iframe);
            }
        });

        // Handle onClick YouTube videos
        setupYouTubeOnClickPlayers();
    }

    // Initialize Vimeo tracking
    function initializeVimeoTracking() {
        // Handle embedded Vimeo videos
        var vimeoIframes = document.querySelectorAll('iframe[src*="vimeo.com"]');
        vimeoIframes.forEach(function(iframe) {
            if (iframe.id && (iframe.id.includes('vimeo-player') || iframe.id.includes('vimeo-embedded'))) {
                setupVimeoPlayer(iframe);
            }
        });

        // Handle onClick Vimeo videos
        setupVimeoOnClickPlayers();
    }

    // Setup YouTube player tracking
    function setupYouTubePlayer(iframe) {
        if (!youtubeAPIReady) return;

        try {
            // Extract video info from iframe
            var videoTitle = extractVideoTitle(iframe);
            var videoId = extractYouTubeVideoId(iframe.src);
            
            var player = new YT.Player(iframe, {
                events: {
                    'onReady': function(event) {
                        console.log('YouTube player ready for tracking:', videoTitle);
                    },
                    'onStateChange': function(event) {
                        handleYouTubeStateChange(event, videoTitle, videoId, player);
                    }
                }
            });
        } catch (error) {
            console.error('Error setting up YouTube tracking:', error);
        }
    }

    // Setup Vimeo player tracking
    function setupVimeoPlayer(iframe) {
        if (!vimeoAPIReady) return;

        try {
            var videoTitle = extractVideoTitle(iframe);
            var videoId = extractVimeoVideoId(iframe.src);
            
            var player = new Vimeo.Player(iframe);
            var milestones = [25, 50, 75];
            var triggered = {};

            // Track video start
            player.on('play', function() {
                sendGA4Event('video_start', {
                    video_title: videoTitle,
                    video_provider: 'vimeo',
                    video_id: videoId,
                    video_url: window.location.href
                });
            });

            // Track video progress
            player.on('timeupdate', function(data) {
                var percent = Math.round(data.percent * 100);
                if (milestones.includes(percent) && !triggered[percent]) {
                    triggered[percent] = true;
                    sendGA4Event('video_progress', {
                        video_title: videoTitle,
                        video_provider: 'vimeo',
                        video_id: videoId,
                        video_url: window.location.href,
                        video_percent: percent
                    });
                }
            });

            // Track video completion
            player.on('ended', function() {
                sendGA4Event('video_complete', {
                    video_title: videoTitle,
                    video_provider: 'vimeo',
                    video_id: videoId,
                    video_url: window.location.href
                });
            });

            // Track video pause
            player.on('pause', function() {
                sendGA4Event('video_pause', {
                    video_title: videoTitle,
                    video_provider: 'vimeo',
                    video_id: videoId,
                    video_url: window.location.href
                });
            });

        } catch (error) {
            console.error('Error setting up Vimeo tracking:', error);
        }
    }

    // Handle YouTube state changes
    function handleYouTubeStateChange(event, videoTitle, videoId, player) {
        var milestones = [25, 50, 75];
        var triggered = {};

        if (event.data === YT.PlayerState.PLAYING) {
            sendGA4Event('video_start', {
                video_title: videoTitle,
                video_provider: 'youtube',
                video_id: videoId,
                video_url: window.location.href
            });

            // Set up progress tracking
            var progressInterval = setInterval(function() {
                if (player.getPlayerState() === YT.PlayerState.PLAYING) {
                    var currentTime = player.getCurrentTime();
                    var duration = player.getDuration();
                    var percent = Math.round((currentTime / duration) * 100);

                    if (milestones.includes(percent) && !triggered[percent]) {
                        triggered[percent] = true;
                        sendGA4Event('video_progress', {
                            video_title: videoTitle,
                            video_provider: 'youtube',
                            video_id: videoId,
                            video_url: window.location.href,
                            video_percent: percent
                        });
                    }
                } else {
                    clearInterval(progressInterval);
                }
            }, 1000);
        }

        if (event.data === YT.PlayerState.PAUSED) {
            sendGA4Event('video_pause', {
                video_title: videoTitle,
                video_provider: 'youtube',
                video_id: videoId,
                video_url: window.location.href
            });
        }

        if (event.data === YT.PlayerState.ENDED) {
            sendGA4Event('video_complete', {
                video_title: videoTitle,
                video_provider: 'youtube',
                video_id: videoId,
                video_url: window.location.href
            });
        }
    }

    // Setup onClick players
    function setupYouTubeOnClickPlayers() {
        var onClickPlayers = document.querySelectorAll('.player[data-provider="youtube"]');
        onClickPlayers.forEach(function(playerElement) {
            if (!playerElement.hasAttribute('data-tracking-setup')) {
                playerElement.setAttribute('data-tracking-setup', 'true');
                // Additional onClick tracking setup can be added here if needed
            }
        });
    }

    function setupVimeoOnClickPlayers() {
        var onClickPlayers = document.querySelectorAll('.player[data-provider="vimeo"]');
        onClickPlayers.forEach(function(playerElement) {
            if (!playerElement.hasAttribute('data-tracking-setup')) {
                playerElement.setAttribute('data-tracking-setup', 'true');
                // Additional onClick tracking setup can be added here if needed
            }
        });
    }

    // Utility functions
    function extractVideoTitle(iframe) {
        // Try to get video title from various sources
        var wrapper = iframe.closest('.ekv-wrapper');
        if (wrapper) {
            var titleElement = wrapper.querySelector('h2 span[itemprop="name"]');
            if (titleElement) {
                return titleElement.textContent.trim();
            }
            
            var metaTitle = wrapper.querySelector('meta[itemprop="name"]');
            if (metaTitle) {
                return metaTitle.getAttribute('content');
            }
        }
        
        return 'Unknown Video Title';
    }

    function extractYouTubeVideoId(url) {
        var match = url.match(/embed\/([^?&]+)/);
        return match ? match[1] : '';
    }

    function extractVimeoVideoId(url) {
        var match = url.match(/video\/(\d+)/);
        return match ? match[1] : '';
    }

    // Handle transcript toggle functionality
    $(document).ready(function() {
        $('.btn-transcript').click(function(e) {
            e.preventDefault();
            var target = $(this).data('target');
            var targetDiv = $(target);
            
            if (targetDiv.hasClass('transcript-wrapper-del')) {
                targetDiv.removeClass('transcript-wrapper-del').addClass('transcript-wrapper-in');
                $(this).attr('aria-expanded', 'true');
            } else {
                targetDiv.removeClass('transcript-wrapper-in').addClass('transcript-wrapper-del');
                $(this).attr('aria-expanded', 'false');
            }
        });
        
        // Handle onClick video players
        $('.player[data-provider="vimeo"], .player[data-provider="youtube"]').click(function() {
            var $this = $(this);
            var provider = $this.data('provider');
            var videoId = $this.data('id');
            
            console.log('Video player clicked:', provider, videoId);
            
            // Set up a flag to track if we've already initialized tracking for this video
            if (!$this.data('tracking-initialized')) {
                $this.data('tracking-initialized', true);
                
                if (provider === 'vimeo') {
                    setupVimeoOnClickTracking($this, videoId);
                } else if (provider === 'youtube') {
                    setupYouTubeOnClickTracking($this, videoId);
                }
            }
        });

        // Initialize tracking when document is ready
        if (youtubeAPIReady) {
            initializeYouTubeTracking();
        }
        
        if (vimeoAPIReady) {
            initializeVimeoTracking();
        }
    });
    
    // Function to set up Vimeo onClick tracking
    function setupVimeoOnClickTracking($playerElement, videoId) {
        console.log('Setting up Vimeo onClick tracking for video:', videoId);
        
        var videoTitle = extractVideoTitleFromPlayer($playerElement);
        
        // Wait for iframe to be created and then set up tracking
        var checkForIframe = setInterval(function() {
            var iframe = $playerElement.find('iframe[src*="player.vimeo.com"]')[0];
            if (iframe) {
                clearInterval(checkForIframe);
                console.log('Vimeo iframe found, initializing tracking...');
                
                setTimeout(function() {
                    try {
                        var vimeoPlayer = new Vimeo.Player(iframe);
                        var milestones = [25, 50, 75];
                        var triggered = {};
                        
                        // Track video start
                        vimeoPlayer.on('play', function() {
                            sendGA4Event('video_start', {
                                video_title: videoTitle,
                                video_provider: 'vimeo',
                                video_id: videoId,
                                video_url: window.location.href
                            });
                        });
                        
                        // Track video progress
                        vimeoPlayer.on('timeupdate', function(data) {
                            var percent = Math.round(data.percent * 100);
                            if (milestones.includes(percent) && !triggered[percent]) {
                                triggered[percent] = true;
                                sendGA4Event('video_progress', {
                                    video_title: videoTitle,
                                    video_provider: 'vimeo',
                                    video_id: videoId,
                                    video_url: window.location.href,
                                    video_percent: percent
                                });
                            }
                        });
                        
                        // Track video completion
                        vimeoPlayer.on('ended', function() {
                            sendGA4Event('video_complete', {
                                video_title: videoTitle,
                                video_provider: 'vimeo',
                                video_id: videoId,
                                video_url: window.location.href
                            });
                        });
                        
                        // Track video pause
                        vimeoPlayer.on('pause', function() {
                            sendGA4Event('video_pause', {
                                video_title: videoTitle,
                                video_provider: 'vimeo',
                                video_id: videoId,
                                video_url: window.location.href
                            });
                        });
                        
                    } catch (error) {
                        console.error('Error setting up Vimeo onClick tracking:', error);
                    }
                }, 1000);
            }
        }, 500);
        
        // Stop checking after 30 seconds
        setTimeout(function() {
            clearInterval(checkForIframe);
        }, 30000);
    }
    
    // Function to set up YouTube onClick tracking
    function setupYouTubeOnClickTracking($playerElement, videoId) {
        console.log('Setting up YouTube onClick tracking for video:', videoId);
        
        var videoTitle = extractVideoTitleFromPlayer($playerElement);
        
        // Wait for iframe to be created and then set up tracking
        var checkForIframe = setInterval(function() {
            var iframe = $playerElement.find('iframe[src*="youtube.com/embed"]')[0];
            if (iframe && youtubeAPIReady) {
                clearInterval(checkForIframe);
                console.log('YouTube iframe found, initializing tracking...');
                
                setTimeout(function() {
                    setupYouTubePlayer(iframe, videoTitle, videoId);
                }, 1000);
            }
        }, 500);
        
        // Stop checking after 30 seconds
        setTimeout(function() {
            clearInterval(checkForIframe);
        }, 30000);
    }
    
    // Enhanced YouTube player setup with custom parameters
    function setupYouTubePlayer(iframe, videoTitle, videoId) {
        if (!youtubeAPIReady) return;

        try {
            var player = new YT.Player(iframe, {
                events: {
                    'onReady': function(event) {
                        console.log('YouTube player ready for tracking:', videoTitle);
                    },
                    'onStateChange': function(event) {
                        handleYouTubeStateChange(event, videoTitle, videoId, player);
                    }
                }
            });
        } catch (error) {
            console.error('Error setting up YouTube tracking:', error);
        }
    }
    
    // Extract video title from player element
    function extractVideoTitleFromPlayer($playerElement) {
        var wrapper = $playerElement.closest('.ekv-wrapper');
        if (wrapper.length) {
            var titleElement = wrapper.find('h2 span[itemprop="name"]');
            if (titleElement.length) {
                return titleElement.text().trim();
            }
            
            var metaTitle = wrapper.find('meta[itemprop="name"]');
            if (metaTitle.length) {
                return metaTitle.attr('content');
            }
        }
        
        return 'Unknown Video Title';
    }

})(jQuery);
