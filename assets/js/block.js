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
        manualInfo: {
            type: 'boolean',
            default: false,
        },
        openInLightbox: {
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
            manualInfo,
            openInLightbox,
        } = attributes;

        const [isLoading, setIsLoading] = useState(false);
        const [error, setError] = useState('');

        /**
         * Fetch video metadata when URL changes
         */
        const fetchVideoMetadata = function(url) {
            if (!url || manualInfo) return; // Skip if manual info is enabled

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
                    // Auto-enable manual info when API fails
                    setAttributes({ manualInfo: true });
                }
            })
            .catch(function(err) {
                setError('Error fetching video metadata: ' + err.message);
                // Auto-enable manual info when API fails
                setAttributes({ manualInfo: true });
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
            if (url && !manualInfo) {
                // Extract basic video info first
                const basicInfo = extractBasicVideoInfo(url);
                if (basicInfo.video_type && basicInfo.video_id) {
                    setAttributes({
                        videoType: basicInfo.video_type,
                        videoId: basicInfo.video_id,
                        embedUrl: basicInfo.embed_url,
                    });
                    fetchVideoMetadata(url);
                } else {
                    setError('Invalid video URL format');
                    setAttributes({ manualInfo: true });
                }
            } else if (!url) {
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
         * Extract basic video info from URL (client-side)
         */
        const extractBasicVideoInfo = function(url) {
            const info = {
                video_type: '',
                video_id: '',
                embed_url: '',
            };

            // YouTube URL patterns
            const youtubeMatch = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
            if (youtubeMatch) {
                info.video_type = 'youtube';
                info.video_id = youtubeMatch[1];
                info.embed_url = 'https://www.youtube.com/embed/' + youtubeMatch[1] + '?rel=0';
                return info;
            }

            // Vimeo URL patterns
            const vimeoMatch = url.match(/(?:www\.)?vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/[^\/]*\/videos\/|album\/\d+\/video\/|video\/|)(\d+)(?:$|\/|\?)/);
            if (vimeoMatch) {
                info.video_type = 'vimeo';
                info.video_id = vimeoMatch[1];
                info.embed_url = 'https://player.vimeo.com/video/' + vimeoMatch[1];
                return info;
            }

            return info;
        };

        /**
         * Handle custom thumbnail selection with cropping
         */
        const onSelectCustomThumbnail = function(media) {
            // Create cropping modal
            showCroppingModal(media);
        };

        /**
         * Show image cropping modal
         */
        const showCroppingModal = function(media) {
            // Create modal overlay
            const modalOverlay = document.createElement('div');
            modalOverlay.className = 'ekwa-cropper-modal-overlay';
            modalOverlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                z-index: 99999;
                display: flex;
                align-items: center;
                justify-content: center;
            `;

            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.className = 'ekwa-cropper-modal-content';
            modalContent.style.cssText = `
                background: white;
                border-radius: 8px;
                padding: 20px;
                max-width: 90vw;
                max-height: 90vh;
                position: relative;
            `;

            // Create modal header
            const modalHeader = document.createElement('div');
            modalHeader.innerHTML = `
                <h3 style="margin: 0 0 20px 0;">Crop Thumbnail Image</h3>
                <p style="margin: 0 0 20px 0; color: #666;">Crop your image to 16:9 aspect ratio (1280x720 or 640x360)</p>
            `;

            // Create image container
            const imageContainer = document.createElement('div');
            imageContainer.style.cssText = `
                max-width: 800px;
                max-height: 500px;
                margin-bottom: 20px;
            `;

            // Create image element
            const cropImage = document.createElement('img');
            cropImage.src = media.url;
            cropImage.style.cssText = `
                max-width: 100%;
                height: auto;
                display: block;
            `;

            // Create buttons container
            const buttonsContainer = document.createElement('div');
            buttonsContainer.style.cssText = `
                display: flex;
                gap: 10px;
                justify-content: flex-end;
            `;

            const cancelButton = document.createElement('button');
            cancelButton.textContent = 'Cancel';
            cancelButton.className = 'button';
            cancelButton.onclick = function() {
                document.body.removeChild(modalOverlay);
            };

            const cropButton = document.createElement('button');
            cropButton.textContent = 'Crop & Use';
            cropButton.className = 'button button-primary';

            // Assemble modal
            imageContainer.appendChild(cropImage);
            buttonsContainer.appendChild(cancelButton);
            buttonsContainer.appendChild(cropButton);
            modalContent.appendChild(modalHeader);
            modalContent.appendChild(imageContainer);
            modalContent.appendChild(buttonsContainer);
            modalOverlay.appendChild(modalContent);
            document.body.appendChild(modalOverlay);

            // Initialize Cropper.js when image loads
            cropImage.onload = function() {
                // Load Cropper.js if not already loaded
                if (!window.Cropper) {
                    loadCropperJS().then(function() {
                        initializeCropper();
                    });
                } else {
                    initializeCropper();
                }

                function initializeCropper() {
                    const cropper = new Cropper(cropImage, {
                        aspectRatio: 16 / 9, // 16:9 aspect ratio
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });

                    cropButton.onclick = function() {
                        const canvas = cropper.getCroppedCanvas({
                            width: 1280,
                            height: 720,
                            imageSmoothingEnabled: true,
                            imageSmoothingQuality: 'high',
                        });

                        canvas.toBlob(function(blob) {
                            // Create FormData to upload cropped image
                            const formData = new FormData();
                            formData.append('action', 'ekwa_upload_cropped_thumbnail');
                            formData.append('nonce', ekwaVideoBlock.nonce);
                            formData.append('original_id', media.id);
                            formData.append('cropped_image', blob, 'cropped-thumbnail.jpg');

                            // Upload cropped image
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
                                        customThumbnail: {
                                            id: data.data.id,
                                            url: data.data.url,
                                            alt: media.alt || '',
                                        }
                                    });
                                    document.body.removeChild(modalOverlay);
                                } else {
                                    alert('Error uploading cropped image: ' + (data.data || 'Unknown error'));
                                }
                            })
                            .catch(function(error) {
                                alert('Error uploading cropped image: ' + error.message);
                            });
                        }, 'image/jpeg', 0.9);
                    };
                }
            };

            // Close modal when clicking overlay
            modalOverlay.onclick = function(e) {
                if (e.target === modalOverlay) {
                    document.body.removeChild(modalOverlay);
                }
            };
        };

        /**
         * Load Cropper.js library
         */
        const loadCropperJS = function() {
            return new Promise(function(resolve) {
                if (window.Cropper) {
                    resolve();
                    return;
                }

                // Load CSS
                const cssLink = document.createElement('link');
                cssLink.rel = 'stylesheet';
                cssLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css';
                document.head.appendChild(cssLink);

                // Load JS
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js';
                script.onload = function() {
                    resolve();
                };
                document.head.appendChild(script);
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
                        key: 'open-lightbox',
                        label: __('Open in Lightbox', 'ekwa-video-block'),
                        checked: openInLightbox,
                        onChange: function(value) { setAttributes({ openInLightbox: value }); },
                        help: __('Open video in a popup lightbox instead of inline player', 'ekwa-video-block')
                    }),

                    el(ToggleControl, {
                        key: 'manual-info',
                        label: __('Enter Video Information Manually', 'ekwa-video-block'),
                        checked: manualInfo,
                        onChange: function(value) {
                            setAttributes({ manualInfo: value });
                            if (!value && videoUrl) {
                                fetchVideoMetadata(videoUrl);
                            }
                        },
                        help: __('Enable this to manually enter video details when API is unavailable', 'ekwa-video-block')
                    }),

                    // Manual input fields when manualInfo is enabled
                    manualInfo && [
                        el(TextControl, {
                            key: 'manual-video-id',
                            label: __('Video ID', 'ekwa-video-block'),
                            value: videoId,
                            onChange: function(value) { setAttributes({ videoId: value }); },
                            help: __('Enter the video ID (e.g., YouTube: dQw4w9WgXcQ, Vimeo: 123456789)', 'ekwa-video-block')
                        }),

                        el(TextControl, {
                            key: 'manual-video-type',
                            label: __('Video Type', 'ekwa-video-block'),
                            value: videoType,
                            onChange: function(value) {
                                setAttributes({
                                    videoType: value,
                                    embedUrl: value === 'youtube'
                                        ? 'https://www.youtube.com/embed/' + videoId + '?rel=0'
                                        : value === 'vimeo'
                                        ? 'https://player.vimeo.com/video/' + videoId
                                        : ''
                                });
                            },
                            help: __('Enter "youtube" or "vimeo"', 'ekwa-video-block')
                        }),

                        el(TextControl, {
                            key: 'manual-duration',
                            label: __('Video Duration', 'ekwa-video-block'),
                            value: videoDuration,
                            onChange: function(value) { setAttributes({ videoDuration: value }); },
                            placeholder: __('e.g., PT4M30S or 4:30', 'ekwa-video-block'),
                            help: __('Duration in ISO 8601 format (PT4M30S) or simple format (4:30)', 'ekwa-video-block')
                        }),

                        el(TextControl, {
                            key: 'manual-upload-date',
                            label: __('Upload Date', 'ekwa-video-block'),
                            value: uploadDate,
                            onChange: function(value) { setAttributes({ uploadDate: value }); },
                            placeholder: __('e.g., 2023-12-01T10:30:00Z', 'ekwa-video-block'),
                            help: __('Upload date in ISO 8601 format (YYYY-MM-DDTHH:MM:SSZ)', 'ekwa-video-block')
                        }),

                        el(TextControl, {
                            key: 'manual-thumbnail',
                            label: __('Thumbnail URL', 'ekwa-video-block'),
                            value: thumbnailUrl,
                            onChange: function(value) { setAttributes({ thumbnailUrl: value }); },
                            placeholder: __('https://example.com/thumbnail.jpg', 'ekwa-video-block'),
                            help: __('Direct URL to video thumbnail image', 'ekwa-video-block')
                        })
                    ],

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
                title: __('Custom Thumbnail', 'ekwa-video-block'),
                initialOpen: false
            },
                el('p', {}, __('Upload a custom thumbnail image to replace the default video thumbnail.', 'ekwa-video-block')),

                !customThumbnail.url && el(MediaUploadCheck, {},
                    el(MediaUpload, {
                        onSelect: onSelectCustomThumbnail,
                        allowedTypes: ['image'],
                        value: customThumbnail.id,
                        render: function(obj) {
                            return el(Button, {
                                className: 'button button-large',
                                onClick: obj.open
                            }, __('Upload Custom Thumbnail', 'ekwa-video-block'));
                        }
                    })
                ),

                customThumbnail.url && [
                    el('div', {
                        key: 'thumbnail-preview',
                        style: { marginBottom: '10px' }
                    },
                        el('img', {
                            src: customThumbnail.url,
                            alt: customThumbnail.alt || '',
                            style: { maxWidth: '100%', height: 'auto' }
                        })
                    ),
                    el('div', {
                        key: 'thumbnail-actions',
                        style: { display: 'flex', gap: '10px' }
                    },
                        el(MediaUploadCheck, {},
                            el(MediaUpload, {
                                onSelect: onSelectCustomThumbnail,
                                allowedTypes: ['image'],
                                value: customThumbnail.id,
                                render: function(obj) {
                                    return el(Button, {
                                        onClick: obj.open,
                                        variant: 'secondary'
                                    }, __('Replace Thumbnail', 'ekwa-video-block'));
                                }
                            })
                        ),
                        el(Button, {
                            onClick: onRemoveCustomThumbnail,
                            variant: 'secondary',
                            isDestructive: true
                        }, __('Remove Custom Thumbnail', 'ekwa-video-block'))
                    )
                ]
            ),

            videoUrl && videoType && el(PanelBody, {
                title: __('Video Information', 'ekwa-video-block'),
                initialOpen: false
            },
                el('p', {}, el('strong', {}, __('Type:', 'ekwa-video-block')), ' ', videoType),
                el('p', {}, el('strong', {}, __('Video ID:', 'ekwa-video-block')), ' ', videoId),
                videoDuration && el('p', {}, el('strong', {}, __('Duration:', 'ekwa-video-block')), ' ', videoDuration),
                uploadDate && el('p', {}, el('strong', {}, __('Upload Date:', 'ekwa-video-block')), ' ', uploadDate),

                // Shortcode Generator
                el('div', {
                    style: { marginTop: '20px', padding: '15px', background: '#f0f0f1', borderRadius: '4px' }
                },
                    el('p', { style: { marginTop: '0', fontWeight: 'bold' } }, __('Shortcode:', 'ekwa-video-block')),
                    el('textarea', {
                        readOnly: true,
                        value: (() => {
                            // Build shortcode based on current settings
                            let shortcode = '[ekwa_video';
                            shortcode += ' video_url="' + videoUrl + '"';

                            if (showTitle !== true) {
                                shortcode += ' show_title="false"';
                            }
                            if (showDescription === true) {
                                shortcode += ' show_description="true"';
                            }
                            if (autoplay === true) {
                                shortcode += ' autoplay="true"';
                            }
                            if (openInLightbox === true) {
                                shortcode += ' open_in_lightbox="true"';
                            }
                            if (showTranscript === true && transcript) {
                                shortcode += ' show_transcript="true"';
                            }
                            if (customThumbnail && customThumbnail.url) {
                                shortcode += ' custom_thumbnail="' + customThumbnail.url + '"';
                            }

                            shortcode += ']';
                            return shortcode;
                        })(),
                        style: {
                            width: '100%',
                            minHeight: '80px',
                            fontFamily: 'monospace',
                            fontSize: '12px',
                            padding: '8px',
                            border: '1px solid #ccc',
                            borderRadius: '3px',
                            resize: 'vertical'
                        },
                        onClick: (e) => {
                            e.target.select();
                            document.execCommand('copy');
                        }
                    }),
                    el('p', {
                        style: {
                            marginBottom: '0',
                            fontSize: '12px',
                            color: '#666',
                            fontStyle: 'italic'
                        }
                    }, __('Click to select and copy', 'ekwa-video-block'))
                )
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
