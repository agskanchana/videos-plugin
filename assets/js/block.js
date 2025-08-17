const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { PanelBody, TextControl, TextareaControl, ToggleControl, Button, Spinner, Notice } = wp.components;
const { useState } = wp.element;
const { createElement: el } = wp.element;

// Debug check
console.log('WordPress dependencies check:', {
    i18n: !!wp.i18n,
    blocks: !!wp.blocks,
    blockEditor: !!wp.blockEditor,
    components: !!wp.components,
    element: !!wp.element
});

/**
 * Register the Ekwa Video Block
 */
console.log('Registering Ekwa Video Block...');

registerBlockType('ekwa/video-block', {
    title: __('Ekwa Video', 'ekwa-video-block'),
    description: __('Embed YouTube and Vimeo videos with custom thumbnails and lazy loading', 'ekwa-video-block'),
    category: 'media',
    icon: 'video-alt',
    keywords: [
        __('video', 'ekwa-video-block'),
        __('youtube', 'ekwa-video-block'),
        __('vimeo', 'ekwa-video-block'),
        __('embed', 'ekwa-video-block'),
    ],
    supports: {
        html: false,
        className: true,
        customClassName: true,
    },
    attributes: {
        videoUrl: {
            type: 'string',
            default: '',
        },
        videoType: {
            type: 'string',
            default: '',
        },
        videoId: {
            type: 'string',
            default: '',
        },
        embedUrl: {
            type: 'string',
            default: '',
        },
        videoTitle: {
            type: 'string',
            default: '',
        },
        videoDescription: {
            type: 'string',
            default: '',
        },
        videoDuration: {
            type: 'string',
            default: '',
        },
        uploadDate: {
            type: 'string',
            default: '',
        },
        thumbnailUrl: {
            type: 'string',
            default: '',
        },
        customThumbnail: {
            type: 'object',
            default: {},
        },
        showTitle: {
            type: 'boolean',
            default: true,
        },
        showDescription: {
            type: 'boolean',
            default: false,
        },
        autoplay: {
            type: 'boolean',
            default: false,
        },
        transcript: {
            type: 'string',
            default: '',
        },
        showTranscript: {
            type: 'boolean',
            default: false,
        },
    },

    edit: function(props) {
        const { attributes, setAttributes, className } = props;
        const {
            videoUrl,
            videoType,
            videoId,
            embedUrl,
            videoTitle,
            videoDescription,
            videoDuration,
            uploadDate,
            thumbnailUrl,
            customThumbnail,
            showTitle,
            showDescription,
            autoplay,
            transcript,
            showTranscript,
        } = attributes;

        const [isLoading, setIsLoading] = useState(false);
        const [error, setError] = useState('');

        /**
         * Fetch video metadata when URL changes
         */
        const fetchVideoMetadata = function(url) {
            if (!url) return;

            setIsLoading(true);
            setError('');

            const formData = new FormData();
            formData.append('action', 'ekwa_get_video_metadata');
            formData.append('video_url', url);
            formData.append('nonce', ekwaVideoBlock.nonce);

            fetch(ekwaVideoBlock.ajaxUrl, {
                method: 'POST',
                body: formData,
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    setAttributes({
                        videoType: data.data.video_type || '',
                        videoId: data.data.video_id || '',
                        embedUrl: data.data.embed_url || '',
                        videoTitle: data.data.video_title || '',
                        videoDescription: data.data.video_description || '',
                        videoDuration: data.data.video_duration || '',
                        uploadDate: data.data.upload_date || '',
                        thumbnailUrl: data.data.thumbnail_url || '',
                    });
                } else {
                    setError(data.data || 'Failed to fetch video metadata');
                }
            })
            .catch(function(err) {
                setError('Error fetching video metadata: ' + err.message);
            })
            .finally(function() {
                setIsLoading(false);
            });
        };

        /**
         * Handle video URL change
         */
        const onVideoUrlChange = function(url) {
            setAttributes({ videoUrl: url });
            if (url) {
                fetchVideoMetadata(url);
            } else {
                // Clear all video-related attributes
                setAttributes({
                    videoType: '',
                    videoId: '',
                    embedUrl: '',
                    videoTitle: '',
                    videoDescription: '',
                    videoDuration: '',
                    uploadDate: '',
                    thumbnailUrl: '',
                });
            }
        };

        /**
         * Handle custom thumbnail selection
         */
        const onSelectCustomThumbnail = function(media) {
            setAttributes({
                customThumbnail: {
                    id: media.id,
                    url: media.url,
                    alt: media.alt,
                }
            });
        };

        /**
         * Remove custom thumbnail
         */
        const onRemoveCustomThumbnail = function() {
            setAttributes({ customThumbnail: {} });
        };

        // Get the thumbnail to display (custom or video thumbnail)
        const displayThumbnail = customThumbnail.url || thumbnailUrl;

        // Inspector Controls
        const inspectorControls = el(InspectorControls, {},
            el(PanelBody, {
                title: __('Video Settings', 'ekwa-video-block'),
                initialOpen: true
            },
                el(TextControl, {
                    label: __('Video URL', 'ekwa-video-block'),
                    value: videoUrl,
                    onChange: onVideoUrlChange,
                    placeholder: __('Enter YouTube or Vimeo URL', 'ekwa-video-block'),
                    help: __('Paste a YouTube or Vimeo video URL', 'ekwa-video-block')
                }),

                isLoading && el('div', { style: { textAlign: 'center', padding: '20px' } },
                    el(Spinner),
                    el('p', {}, __('Fetching video metadata...', 'ekwa-video-block'))
                ),

                error && el(Notice, {
                    status: 'error',
                    isDismissible: false
                }, error),

                videoUrl && videoType && [
                    el(TextControl, {
                        key: 'video-title',
                        label: __('Video Title', 'ekwa-video-block'),
                        value: videoTitle,
                        onChange: function(value) { setAttributes({ videoTitle: value }); }
                    }),

                    el(TextControl, {
                        key: 'video-description',
                        label: __('Video Description', 'ekwa-video-block'),
                        value: videoDescription,
                        onChange: function(value) { setAttributes({ videoDescription: value }); }
                    }),

                    el(ToggleControl, {
                        key: 'show-title',
                        label: __('Show Video Title', 'ekwa-video-block'),
                        checked: showTitle,
                        onChange: function(value) { setAttributes({ showTitle: value }); }
                    }),

                    el(ToggleControl, {
                        key: 'show-description',
                        label: __('Show Video Description', 'ekwa-video-block'),
                        checked: showDescription,
                        onChange: function(value) { setAttributes({ showDescription: value }); }
                    }),

                    el(ToggleControl, {
                        key: 'autoplay',
                        label: __('Autoplay Video', 'ekwa-video-block'),
                        checked: autoplay,
                        onChange: function(value) { setAttributes({ autoplay: value }); },
                        help: __('Note: Many browsers block autoplay videos with sound', 'ekwa-video-block')
                    }),

                    el(ToggleControl, {
                        key: 'show-transcript',
                        label: __('Show Transcript Button', 'ekwa-video-block'),
                        checked: showTranscript,
                        onChange: function(value) { setAttributes({ showTranscript: value }); }
                    }),

                    showTranscript && el(TextareaControl, {
                        key: 'transcript',
                        label: __('Video Transcript', 'ekwa-video-block'),
                        value: transcript,
                        onChange: function(value) { setAttributes({ transcript: value }); },
                        placeholder: __('Enter the video transcript here...', 'ekwa-video-block'),
                        help: __('Add the transcript text that will be shown when users click the transcript button', 'ekwa-video-block'),
                        rows: 8
                    })
                ]
            ),

            videoUrl && videoType && el(PanelBody, {
                title: __('Video Information', 'ekwa-video-block'),
                initialOpen: false
            },
                el('p', {}, el('strong', {}, __('Type:', 'ekwa-video-block')), ' ', videoType),
                el('p', {}, el('strong', {}, __('Video ID:', 'ekwa-video-block')), ' ', videoId),
                videoDuration && el('p', {}, el('strong', {}, __('Duration:', 'ekwa-video-block')), ' ', videoDuration),
                uploadDate && el('p', {}, el('strong', {}, __('Upload Date:', 'ekwa-video-block')), ' ', uploadDate)
            )
        );

        // Main editor content
        let mainContent;

        if (!videoUrl) {
            mainContent = el('div', {
                className: 'ekwa-video-placeholder'
            },
                el('div', {
                    className: 'ekwa-video-placeholder-icon'
                },
                    el('svg', {
                        width: '24',
                        height: '24',
                        viewBox: '0 0 24 24',
                        fill: 'none'
                    },
                        el('path', {
                            d: 'M8 5v14l11-7z',
                            fill: 'currentColor'
                        })
                    )
                ),
                el('h3', {}, __('Ekwa Video Block', 'ekwa-video-block')),
                el('p', {}, __('Enter a YouTube or Vimeo URL in the sidebar to get started.', 'ekwa-video-block'))
            );
        } else if (videoUrl && !videoType && !isLoading) {
            mainContent = el('div', {
                className: 'ekwa-video-placeholder'
            },
                el('p', {}, __('Invalid video URL. Please enter a valid YouTube or Vimeo URL.', 'ekwa-video-block'))
            );
        } else if (videoUrl && videoType) {
            const previewElements = [];

            if (showTitle && videoTitle) {
                previewElements.push(
                    el('h3', {
                        key: 'title',
                        className: 'ekwa-video-title'
                    }, videoTitle)
                );
            }

            if (displayThumbnail) {
                previewElements.push(
                    el('div', {
                        key: 'thumbnail',
                        className: 'ekwa-video-thumbnail-wrapper'
                    },
                        el('img', {
                            src: displayThumbnail,
                            alt: customThumbnail.alt || videoTitle,
                            style: { width: '100%', height: 'auto' }
                        }),
                        el('div', {
                            className: 'ekwa-video-play-overlay'
                        },
                            el('svg', {
                                width: '68',
                                height: '48',
                                viewBox: '0 0 68 48'
                            },
                                el('path', {
                                    d: 'M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.63-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26z',
                                    fill: '#f00'
                                }),
                                el('path', {
                                    d: 'M45 24L27 14v20',
                                    fill: '#fff'
                                })
                            )
                        ),
                        videoDuration && el('div', {
                            className: 'ekwa-video-duration-preview'
                        }, videoDuration)
                    )
                );
            }

            if (showDescription && videoDescription) {
                previewElements.push(
                    el('div', {
                        key: 'description',
                        className: 'ekwa-video-description'
                    },
                        el('p', {}, videoDescription)
                    )
                );
            }

            mainContent = el('div', {
                className: 'ekwa-video-preview'
            }, ...previewElements);
        }

        return el('div', {
            className: className
        },
            inspectorControls,
            el('div', {
                className: 'ekwa-video-block-editor'
            }, mainContent)
        );
    },

    save: function() {
        // Server-side rendering - return null
        return null;
    },
});

console.log('Ekwa Video Block registered successfully');
