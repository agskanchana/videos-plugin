# Ekwa Video Block - Feature Guide

## Manual Video Information Entry

When the YouTube/Vimeo APIs are unavailable or fail, you can manually enter video information:

### How to Enable Manual Input:
1. Add a video URL in the Video Settings panel
2. If API fails, manual input will be auto-enabled
3. Or manually check "Enter Video Information Manually"

### Manual Fields Available:
- **Video ID**: Extract from URL (YouTube: `dQw4w9WgXcQ`, Vimeo: `123456789`)
- **Video Type**: Enter `youtube` or `vimeo`
- **Video Duration**:
  - ISO 8601 format: `PT4M30S` (4 minutes 30 seconds)
  - Simple format: `4:30`
- **Upload Date**: ISO 8601 format: `2023-12-01T10:30:00Z`
- **Thumbnail URL**: Direct URL to thumbnail image

## GLightbox Integration

Videos can open in a beautiful lightbox popup instead of inline player.

### Features:
- **Lazy Loading**: GLightbox library loads only on user interaction (scroll, mouse move, touch)
- **Performance Optimized**: No extra network calls until needed
- **Mobile Friendly**: Touch navigation and responsive design
- **Video Support**: Works with YouTube and Vimeo embeds
- **Description & Transcript Support**: Shows description and transcript inside lightbox

### How to Enable:
1. Check "Open in Lightbox" in Video Settings
2. Video will show thumbnail with play button
3. Clicking opens video in popup lightbox
4. Description and transcript (if enabled) appear below video in lightbox

### Lightbox Content Features:
- **Video Description**: Automatically included if "Show Video Description" is enabled
- **Video Transcript**: Interactive transcript with toggle button if "Show Transcript Button" is enabled
- **Professional Layout**: Clean design with proper spacing and styling
- **Dark Mode Support**: Automatic dark/light theme detection

### Fixed Issues:
- **Infinite Loop Fix**: Resolved click handler causing endless "open fails" messages
- **Smart Loading**: GLightbox loads only once and prevents duplicate initialization
- **Content Integration**: Description and transcript now properly show inside lightbox
- **Performance**: Optimized loading prevents multiple library loads

## Performance Optimizations

### GLightbox Lazy Loading:
- CSS and JS files load only when needed
- Triggered by: scroll, mousemove, touchstart
- Once loaded, applies to all lightbox videos on page
- No impact on initial page load speed
- Prevents infinite loading loops

### Manual Input Benefits:
- Works when APIs are down
- No API rate limits
- Full control over video metadata
- Faster for known video details

## Usage Examples

### Manual Entry Example:
```
Video URL: https://www.youtube.com/watch?v=dQw4w9WgXcQ
Manual Info: ✓ Enabled
Video ID: dQw4w9WgXcQ
Video Type: youtube
Duration: PT3M33S
Upload Date: 2009-10-25T12:00:00Z
Thumbnail: https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg
```

### Lightbox with Content Example:
```
Video URL: https://vimeo.com/123456789
Open in Lightbox: ✓ Enabled
Show Title: ✓ Enabled
Show Description: ✓ Enabled
Show Transcript Button: ✓ Enabled
Transcript: "This is the video transcript content..."
```

## Lightbox Content Layout

When lightbox is enabled, the content structure is:
1. **Video Player** (YouTube/Vimeo embed)
2. **Video Description** (if enabled) - styled with blue accent border
3. **Transcript Section** (if enabled):
   - Toggle button to show/hide transcript
   - Scrollable transcript content (max 300px height)
   - Color-coded: Blue (closed), Green (open)

## File Structure

### New Files Added:
- `assets/vendor/glightbox.min.css` - GLightbox styles
- `assets/vendor/glightbox.min.js` - GLightbox library
- `assets/vendor/glightbox.js` - Unminified version for development
- `assets/js/lightbox-init.js` - Lazy loading initialization

### Updated Files:
- `ekwa-video-block.php` - Added manual input & lightbox support with content
- `assets/js/block.js` - Added manual input controls
- `assets/css/frontend.css` - Added lightbox styling and content styles
- `assets/css/editor.css` - Added cropping modal styles

## Browser Support

- **GLightbox**: Modern browsers (IE11+)
- **Manual Input**: All browsers
- **Lazy Loading**: All browsers with fallback
- **Touch Navigation**: Mobile devices

## Best Practices

1. **Use Manual Input When**:
   - API keys are not available
   - Working in development environment
   - Need faster setup for known videos
   - API rate limits are exceeded

2. **Use Lightbox When**:
   - Want to keep users on page
   - Have multiple videos
   - Want professional presentation
   - Mobile-first design
   - Need description/transcript in popup

3. **Performance Tips**:
   - Enable lightbox only when needed
   - Use compressed thumbnails
   - Keep video descriptions concise
   - Test on mobile devices

## Troubleshooting

### Lightbox Not Opening:
- Check browser console for errors
- Ensure GLightbox files are properly loaded
- Verify data attributes are correctly set

### Infinite Loading Issue:
- **Fixed in latest version**: Click handler now properly prevents loops
- GLightbox initializes only once per page load
- Loading state shows during library download

### Content Not Showing:
- Ensure "Show Description" or "Show Transcript" options are enabled
- Verify content is properly saved in block settings
- Check that lightbox mode is enabled