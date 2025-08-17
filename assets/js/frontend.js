jQuery(document).ready(function($) {
    'use strict';

    /**
     * Ekwa Video Block Frontend JavaScript
     */
    class EkwaVideoPlayer {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            // Removed loadYouTubeAPI() - not needed for iframe embedding
        }

        bindEvents() {
            // Handle thumbnail click
            $(document).on('click', '.ekwa-video-thumbnail', this.handleThumbnailClick.bind(this));

            // Handle play button click on loaded videos
            $(document).on('click', '.ekwa-video-play-button', this.handlePlayButtonClick.bind(this));

            // Handle play icon click (for schema markup structure)
            $(document).on('click', '.playicon', this.handlePlayIconClick.bind(this));

            // Handle transcript toggle
            $(document).on('click', '.btn-transcript', this.handleTranscriptToggle.bind(this));
        }

        handleThumbnailClick(e) {
            e.preventDefault();
            const $thumbnail = $(e.currentTarget);

            // Support both old and new structure
            let $player = $thumbnail.closest('.ekwa-video-player');
            if (!$player.length) {
                $player = $thumbnail.closest('.player');
            }

            let $container = $player.find('.ekwa-video-iframe-container');
            if (!$container.length) {
                // Create container if it doesn't exist
                $container = $('<div class="ekwa-video-iframe-container" style="display: none;"></div>');
                $player.append($container);
            }

            const embedUrl = $thumbnail.data('embed-url');
            let videoType = $player.data('video-type') || $player.data('provider');
            let videoId = $player.data('video-id') || $player.data('id');
            const autoplay = $player.data('autoplay');

            if (!embedUrl || !videoType || !videoId) {
                console.error('Missing video data');
                return;
            }

            // Build iframe source with autoplay
            let iframeSrc = embedUrl;
            const separator = embedUrl.includes('?') ? '&' : '?';

            if (videoType === 'youtube') {
                iframeSrc += separator + 'autoplay=1&rel=0&modestbranding=1';
            } else if (videoType === 'vimeo') {
                iframeSrc += separator + 'autoplay=1&title=0&byline=0&portrait=0';
            }

            // Create iframe
            const $iframe = $('<iframe>', {
                src: iframeSrc,
                frameborder: '0',
                allowfullscreen: true,
                allow: 'autoplay; encrypted-media',
                width: '100%',
                height: '100%',
                class: 'ekwa-video-iframe'
            });

            // Set proper aspect ratio for container
            $container.css({
                'position': 'relative',
                'width': '100%',
                'padding-bottom': '56.25%',
                'height': '0',
                'background': '#000'
            });

            $iframe.css({
                'position': 'absolute',
                'top': '0',
                'left': '0',
                'width': '100%',
                'height': '100%',
                'border': 'none'
            });

            // Hide thumbnail and show iframe
            $thumbnail.fadeOut(300, function() {
                $container.html($iframe).show();

                // Add loaded class to player
                $player.addClass('ekwa-video-loaded');

                // Trigger custom event
                $player.trigger('ekwaVideoLoaded', {
                    videoType: videoType,
                    videoId: videoId,
                    embedUrl: embedUrl
                });
            });
        }

        handlePlayButtonClick(e) {
            e.preventDefault();
            e.stopPropagation();

            // This will bubble up to thumbnail click
            $(e.target).closest('.ekwa-video-thumbnail').trigger('click');
        }

        handlePlayIconClick(e) {
            e.preventDefault();
            const $playIcon = $(e.currentTarget);
            const $thumbnail = $playIcon.closest('.ekwa-video-thumbnail');

            if ($thumbnail.length) {
                $thumbnail.trigger('click');
            }
        }

        handleTranscriptToggle(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const targetId = $button.data('target');
            const $transcript = $(targetId);

            if ($transcript.length) {
                if ($transcript.hasClass('open')) {
                    $transcript.removeClass('open').slideUp(300);
                    $button.text($button.text().replace('Hide', 'Video'));
                } else {
                    $transcript.addClass('open').slideDown(300);
                    $button.text($button.text().replace('Video', 'Hide'));
                }
            }
        }        // Utility method to get video ID from URL
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

        // Note: YouTube Iframe API is NOT loaded by default for performance
        // The plugin uses simple iframe embedding which is faster and doesn't require the API
        // If you need advanced YouTube API features in the future, use loadYouTubeAPIOnDemand()
        static loadYouTubeAPIOnDemand() {
            return new Promise((resolve) => {
                if (window.YT && window.YT.Player) {
                    resolve(window.YT);
                    return;
                }

                if (!window.ekwaYTLoading) {
                    window.ekwaYTLoading = true;

                    window.onYouTubeIframeAPIReady = () => {
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
                    const $video = $(entry.target);
                    $video.addClass('ekwa-video-in-view');
                    lazyVideoObserver.unobserve(entry.target);
                }
            });
        });

        $('.ekwa-video-wrapper').each(function() {
            lazyVideoObserver.observe(this);
        });
    }

    // Analytics tracking (optional)
    $(document).on('ekwaVideoLoaded', function(e, data) {
        // Track video load event
        if (typeof gtag !== 'undefined') {
            gtag('event', 'video_load', {
                'video_type': data.videoType,
                'video_id': data.videoId
            });
        }

        // Custom tracking hook
        $(document).trigger('ekwa_video_analytics', data);
    });

    // Responsive video handling
    function handleResponsiveVideos() {
        $('.ekwa-video-wrapper').each(function() {
            const $wrapper = $(this);
            const $player = $wrapper.find('.ekwa-video-player');
            const $iframe = $player.find('iframe');

            if ($iframe.length) {
                const aspectRatio = $iframe.attr('height') / $iframe.attr('width') * 100;
                $player.css('padding-bottom', aspectRatio + '%');
            }
        });
    }

    // Handle window resize
    $(window).on('resize', debounce(handleResponsiveVideos, 250));

    // Debounce utility function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialize responsive handling
    handleResponsiveVideos();
});
