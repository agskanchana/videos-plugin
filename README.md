
# Ekwa Video Block

A modern WordPress Gutenberg block for embedding YouTube and Vimeo videos with automatic metadata, custom thumbnails, lazy loading, analytics, lightbox playback, and enhanced user experience.


## Features

- **Gutenberg Block Integration:** Native WordPress block editor support
- **Automatic Metadata Fetching:** Instantly pulls video title, thumbnail, duration, and upload date from YouTube or Vimeo—no manual entry required
- **Custom Thumbnails:** Easily upload your own thumbnail if desired
- **Lazy Loading:** Videos only load when clicked, improving page speed and Core Web Vitals
- **Responsive Design:** Videos look great on all devices
- **Schema.org Structured Data:** Boosts SEO with rich video markup for both inline and lightbox videos
- **Accessibility:** Keyboard navigation, alt text, and screen reader support
- **GA4 Analytics Integration:** Track video engagement (start, pause, progress, complete) in Google Analytics 4
- **Transcript Support:** Add and display video transcripts for accessibility and SEO
- **Lightbox Video Playback:** Open videos in a popup lightbox with custom thumbnail. Even in lightbox mode, the plugin outputs full Schema.org structured data for SEO
- **Performance Optimization:** Inline critical CSS, lazy JS loading, and image optimization
- **Server-Side Rendering:** Reliable, SEO-friendly, and update-safe


## Installation

1. Download the plugin from [GitHub](https://github.com/agskanchana/videos-plugin)
2. Upload the plugin files to `/wp-content/plugins/ekwa-video-block/`
3. Activate the plugin through the 'Plugins' screen in WordPress
4. (Optional) Configure YouTube API key and GA4 tracking in plugin settings


## How to Use

### Gutenberg Block

1. In the block editor, click the '+' button to add a new block
2. Search for 'Ekwa Video' and select it
3. Paste a YouTube or Vimeo URL in the sidebar settings
4. The plugin automatically fetches video metadata (title, thumbnail, duration, upload date)
5. Optionally upload a custom thumbnail image
6. Configure display settings (show title, description, autoplay, transcript, lightbox)
7. **To enable lightbox playback:** Toggle the "Open in Lightbox" option. The video will open in a popup when the thumbnail is clicked, and all Schema.org structured data is included for SEO
8. Publish your post/page


## Analytics Integration

- **Google Analytics 4 (GA4):**
    Enable GA4 tracking in plugin settings to track:
    - `video_start`
    - `video_progress` (25%, 50%, 75%)
    - `video_pause`
    - `video_complete`
- **Custom Dimensions:**
    Register `video_title`, `video_provider`, etc. in GA4 to see which videos are played

## Accessibility Features

- Keyboard navigation
- Screen reader support
- Alt text for thumbnails
- Transcript support

## Performance Optimization

- Lazy loading for videos and scripts
- Inline critical CSS
- Responsive images and thumbnails

## Changelog

### Version 1.1.0
- Automatic metadata fetching for YouTube and Vimeo
- GA4 analytics integration
- Transcript support
- Lightbox video playback with Schema.org structured data
- Improved lazy loading and performance
- Accessibility enhancements

## Support

For support, feature requests, or to report issues, visit:
- [Plugin GitHub Repository](https://github.com/agskanchana/videos-plugin)
- [Submit Issues or Suggestions](https://github.com/agskanchana/videos-plugin/issues)

## License

GPL v2 or later.

---

*Developed by Ekwa Team - https://www.ekwa.com*
