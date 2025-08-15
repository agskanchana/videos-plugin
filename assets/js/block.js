const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { PanelBody, TextControl, ToggleControl, Button, Spinner, Notice } = wp.components;
const { useState, useEffect } = wp.element;
const { apiFetch } = wp;

/**
 * Register the Ekwa Video Block
 */
registerBlockType('ekwa/video-block', {
    title: __('Ekwa Video', 'ekwa-video-block'),
    description: __('Embed YouTube and Vimeo videos with custom thumbnails and lazy loading', 'ekwa-video-block'),
    category: 'embed',
    icon: 'video-alt3',
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
        } = attributes;

        const [isLoading, setIsLoading] = useState(false);
        const [error, setError] = useState('');

        /**
         * Fetch video metadata when URL changes
         */
        const fetchVideoMetadata = async (url) => {
            if (!url) return;

            setIsLoading(true);
            setError('');

            try {
                const formData = new FormData();
                formData.append('action', 'ekwa_get_video_metadata');
                formData.append('video_url', url);
                formData.append('nonce', ekwaVideoBlock.nonce);

                const response = await fetch(ekwaVideoBlock.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

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
            } catch (err) {
                setError('Error fetching video metadata: ' + err.message);
            } finally {
                setIsLoading(false);
            }
        };

        /**
         * Handle video URL change
         */
        const onVideoUrlChange = (url) => {
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
        const onSelectCustomThumbnail = (media) => {
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
        const onRemoveCustomThumbnail = () => {
            setAttributes({ customThumbnail: {} });
        };

        // Get the thumbnail to display (custom or video thumbnail)
        const displayThumbnail = customThumbnail.url || thumbnailUrl;

        return (
            <div className={className}>
                <InspectorControls>
                    <PanelBody title={__('Video Settings', 'ekwa-video-block')} initialOpen={true}>
                        <TextControl
                            label={__('Video URL', 'ekwa-video-block')}
                            value={videoUrl}
                            onChange={onVideoUrlChange}
                            placeholder={__('Enter YouTube or Vimeo URL', 'ekwa-video-block')}
                            help={__('Paste a YouTube or Vimeo video URL', 'ekwa-video-block')}
                        />

                        {isLoading && (
                            <div style={{ textAlign: 'center', padding: '20px' }}>
                                <Spinner />
                                <p>{__('Fetching video metadata...', 'ekwa-video-block')}</p>
                            </div>
                        )}

                        {error && (
                            <Notice status="error" isDismissible={false}>
                                {error}
                            </Notice>
                        )}

                        {videoUrl && videoType && (
                            <>
                                <TextControl
                                    label={__('Video Title', 'ekwa-video-block')}
                                    value={videoTitle}
                                    onChange={(value) => setAttributes({ videoTitle: value })}
                                />

                                <TextControl
                                    label={__('Video Description', 'ekwa-video-block')}
                                    value={videoDescription}
                                    onChange={(value) => setAttributes({ videoDescription: value })}
                                />

                                <MediaUploadCheck>
                                    <MediaUpload
                                        onSelect={onSelectCustomThumbnail}
                                        allowedTypes={['image']}
                                        value={customThumbnail.id}
                                        render={({ open }) => (
                                            <div>
                                                <p><strong>{__('Custom Thumbnail', 'ekwa-video-block')}</strong></p>
                                                {displayThumbnail && (
                                                    <div style={{ marginBottom: '10px' }}>
                                                        <img 
                                                            src={displayThumbnail} 
                                                            alt={customThumbnail.alt || videoTitle}
                                                            style={{ maxWidth: '100%', height: 'auto' }}
                                                        />
                                                    </div>
                                                )}
                                                <Button 
                                                    onClick={open} 
                                                    variant="secondary"
                                                    style={{ marginRight: '10px' }}
                                                >
                                                    {customThumbnail.url ? __('Change Thumbnail', 'ekwa-video-block') : __('Upload Custom Thumbnail', 'ekwa-video-block')}
                                                </Button>
                                                {customThumbnail.url && (
                                                    <Button 
                                                        onClick={onRemoveCustomThumbnail} 
                                                        variant="secondary"
                                                        isDestructive
                                                    >
                                                        {__('Remove Custom Thumbnail', 'ekwa-video-block')}
                                                    </Button>
                                                )}
                                                <p style={{ fontSize: '12px', color: '#666', marginTop: '5px' }}>
                                                    {__('Upload a custom thumbnail or leave empty to use the video thumbnail', 'ekwa-video-block')}
                                                </p>
                                            </div>
                                        )}
                                    />
                                </MediaUploadCheck>

                                <ToggleControl
                                    label={__('Show Video Title', 'ekwa-video-block')}
                                    checked={showTitle}
                                    onChange={(value) => setAttributes({ showTitle: value })}
                                />

                                <ToggleControl
                                    label={__('Show Video Description', 'ekwa-video-block')}
                                    checked={showDescription}
                                    onChange={(value) => setAttributes({ showDescription: value })}
                                />

                                <ToggleControl
                                    label={__('Autoplay Video', 'ekwa-video-block')}
                                    checked={autoplay}
                                    onChange={(value) => setAttributes({ autoplay: value })}
                                    help={__('Note: Many browsers block autoplay videos with sound', 'ekwa-video-block')}
                                />
                            </>
                        )}
                    </PanelBody>

                    {videoUrl && videoType && (
                        <PanelBody title={__('Video Information', 'ekwa-video-block')} initialOpen={false}>
                            <p><strong>{__('Type:', 'ekwa-video-block')}</strong> {videoType}</p>
                            <p><strong>{__('Video ID:', 'ekwa-video-block')}</strong> {videoId}</p>
                            {videoDuration && <p><strong>{__('Duration:', 'ekwa-video-block')}</strong> {videoDuration}</p>}
                            {uploadDate && <p><strong>{__('Upload Date:', 'ekwa-video-block')}</strong> {uploadDate}</p>}
                        </PanelBody>
                    )}
                </InspectorControls>

                <div className="ekwa-video-block-editor">
                    {!videoUrl && (
                        <div className="ekwa-video-placeholder">
                            <div className="ekwa-video-placeholder-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 5v14l11-7z" fill="currentColor"/>
                                </svg>
                            </div>
                            <h3>{__('Ekwa Video Block', 'ekwa-video-block')}</h3>
                            <p>{__('Enter a YouTube or Vimeo URL in the sidebar to get started.', 'ekwa-video-block')}</p>
                        </div>
                    )}

                    {videoUrl && !videoType && !isLoading && (
                        <div className="ekwa-video-placeholder">
                            <p>{__('Invalid video URL. Please enter a valid YouTube or Vimeo URL.', 'ekwa-video-block')}</p>
                        </div>
                    )}

                    {videoUrl && videoType && (
                        <div className="ekwa-video-preview">
                            {showTitle && videoTitle && (
                                <h3 className="ekwa-video-title">{videoTitle}</h3>
                            )}
                            
                            <div className="ekwa-video-thumbnail-wrapper">
                                {displayThumbnail && (
                                    <img 
                                        src={displayThumbnail} 
                                        alt={customThumbnail.alt || videoTitle}
                                        style={{ width: '100%', height: 'auto' }}
                                    />
                                )}
                                <div className="ekwa-video-play-overlay">
                                    <svg width="68" height="48" viewBox="0 0 68 48">
                                        <path d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.63-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26z" fill="#f00"></path>
                                        <path d="M45 24L27 14v20" fill="#fff"></path>
                                    </svg>
                                </div>
                                {videoDuration && (
                                    <div className="ekwa-video-duration-preview">
                                        {videoDuration}
                                    </div>
                                )}
                            </div>

                            {showDescription && videoDescription && (
                                <div className="ekwa-video-description">
                                    <p>{videoDescription}</p>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        );
    },

    save: function() {
        // Server-side rendering - return null
        return null;
    },
});
