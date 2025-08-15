# Ekwa Video Block Plugin - Quick Start Guide

## Installation Steps

1. **Upload the plugin**: 
   - Copy the entire `ekwa-video-block` folder to your WordPress `/wp-content/plugins/` directory

2. **Activate the plugin**:
   - Go to WordPress Admin → Plugins
   - Find "Ekwa Video Block" and click "Activate"

3. **Test the block**:
   - Create a new post or page
   - Click the '+' button to add a block
   - Search for "Ekwa Video" and select it
   - Enter a YouTube or Vimeo URL (e.g., `https://www.youtube.com/watch?v=dQw4w9WgXcQ`)
   - Watch as the plugin automatically fetches video metadata

## Quick Test URLs

### YouTube
- https://www.youtube.com/watch?v=dQw4w9WgXcQ
- https://youtu.be/dQw4w9WgXcQ

### Vimeo
- https://vimeo.com/148751763
- https://player.vimeo.com/video/148751763

## Testing the Shortcode

Add this shortcode to any post or page:

```
[ekwa_video video_url="https://www.youtube.com/watch?v=dQw4w9WgXcQ" show_title="true"]
```

## Troubleshooting

### Plugin Not Showing in Block Editor
- Make sure you're using WordPress 5.0+ with Gutenberg enabled
- Clear any caching plugins
- Check browser console for JavaScript errors

### Video Metadata Not Loading
- Ensure your WordPress site can make external HTTP requests
- Check if your server blocks API calls to YouTube/Vimeo
- For Vimeo, the oEmbed API should work out of the box
- For YouTube, basic thumbnail fetching works without API key

### Styling Issues
- The plugin includes responsive CSS that should work with most themes
- If you need custom styling, target the `.ekwa-video-wrapper` class and its children

## Plugin Features Checklist

After installation, verify these features work:

- [ ] Block appears in block inserter
- [ ] Video URL input fetches metadata automatically
- [ ] Custom thumbnail upload works
- [ ] Preview shows correctly in editor
- [ ] Frontend displays video with click-to-play
- [ ] Responsive design works on mobile
- [ ] Shortcode renders correctly
- [ ] Schema.org markup is present in source code

## File Structure Check

Your plugin folder should contain:

```
ekwa-video-block/
├── ekwa-video-block.php
├── assets/
│   ├── js/
│   │   ├── block.js
│   │   └── frontend.js
│   └── css/
│       ├── editor.css
│       └── frontend.css
├── README.md
└── INSTALLATION.md (this file)
```

## Next Steps

1. **Customize Styling**: Edit `assets/css/frontend.css` to match your theme
2. **Add YouTube API**: For enhanced YouTube metadata, add your API key
3. **Test Performance**: Use browser dev tools to verify lazy loading works
4. **SEO Check**: View page source to confirm schema.org markup is present

## Support

If you encounter any issues, check the WordPress debug log and browser console for error messages. The plugin follows WordPress best practices and should be compatible with most themes and plugins.
