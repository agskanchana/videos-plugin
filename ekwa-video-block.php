<?php
/**
 * Plugin Name: Ekwa Video Block
 * Plugin URI: https://www.ekwa.com
 * Description: A Gutenberg block for embedding YouTube and Vimeo videos with lazy loading and custom thumbnails
 * Version: 1.2.4
 * Author: Ekwa Team
 * Author URI: https://www.ekwa.com
 * Text Domain: ekwa-video-block
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 *
 * @package EkwaVideoBlock
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


require 'includes/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/agskanchana/videos-plugin/',
	__FILE__,
	'ekwa-video-block'
);


// Define plugin constants
define('EKWA_VIDEO_BLOCK_VERSION', '1.1.9
');
define('EKWA_VIDEO_BLOCK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EKWA_VIDEO_BLOCK_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Main plugin class
 */
class EkwaVideoBlock {

    /**
     * Flag to track if video blocks are present on the page
     */
    private static $has_video_blocks = false;

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_head', array($this, 'inline_critical_css'), 5);
        add_action('wp_footer', array($this, 'lazy_load_frontend_script'), 5);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('ekwa-video-block', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Register the block
        $this->register_block();

        // Register shortcode
        add_shortcode('ekwa_video', array($this, 'render_video_shortcode'));

        // Enqueue block editor assets
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));

        // Add admin menu for settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));

        // Check for video blocks early in the process
        add_action('wp', array($this, 'check_for_video_blocks'));
    }

    /**
     * Check if the current page has video blocks
     */
    public function check_for_video_blocks() {
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        global $post;

        // Check if current post/page has video blocks
        if (is_singular() && $post) {
            // Check for Gutenberg blocks
            if (has_blocks($post->post_content)) {
                if (has_block('ekwa/video-block', $post)) {
                    self::$has_video_blocks = true;
                    return;
                }
            }

            // Check for shortcodes
            if (has_shortcode($post->post_content, 'ekwa_video')) {
                self::$has_video_blocks = true;
                return;
            }
        }

        // For archive pages, check all posts in the loop
        if (is_home() || is_archive() || is_search()) {
            $posts = get_posts(array(
                'post_type' => 'any',
                'posts_per_page' => 10, // Check first 10 posts for performance
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => '_',
                        'value' => 'ekwa/video-block',
                        'compare' => 'LIKE'
                    )
                )
            ));

            foreach ($posts as $archive_post) {
                if (has_block('ekwa/video-block', $archive_post) || has_shortcode($archive_post->post_content, 'ekwa_video')) {
                    self::$has_video_blocks = true;
                    return;
                }
            }
        }
    }

    /**
     * Register the Gutenberg block
     */
    public function register_block() {
        register_block_type('ekwa/video-block', array(
            'attributes' => array(
                'videoUrl' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'videoType' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'videoId' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'embedUrl' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'videoTitle' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'videoDescription' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'videoDuration' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'uploadDate' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'thumbnailUrl' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'customThumbnail' => array(
                    'type' => 'object',
                    'default' => array(),
                ),
                'showTitle' => array(
                    'type' => 'boolean',
                    'default' => true,
                ),
                'showDescription' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
                'autoplay' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
                'transcript' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'showTranscript' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
                'manualInfo' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
                'openInLightbox' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => '',
                ),
            ),
            'render_callback' => array($this, 'render_block'),
            'supports' => array(
                'html' => false,
                'className' => true,
                'customClassName' => true,
            ),
        ));
    }

    /**
     * Render the block (server-side rendering)
     */
    public function render_block($attributes, $content) {
        // Extract attributes
        $video_url = isset($attributes['videoUrl']) ? $attributes['videoUrl'] : '';
        $video_type = isset($attributes['videoType']) ? $attributes['videoType'] : '';
        $video_id = isset($attributes['videoId']) ? $attributes['videoId'] : '';
        $embed_url = isset($attributes['embedUrl']) ? $attributes['embedUrl'] : '';
        $video_title = isset($attributes['videoTitle']) ? $attributes['videoTitle'] : '';
        $video_description = isset($attributes['videoDescription']) ? $attributes['videoDescription'] : '';
        $video_duration = isset($attributes['videoDuration']) ? $attributes['videoDuration'] : '';
        $upload_date = isset($attributes['uploadDate']) ? $attributes['uploadDate'] : '';
        $thumbnail_url = isset($attributes['thumbnailUrl']) ? $attributes['thumbnailUrl'] : '';
        $custom_thumbnail = isset($attributes['customThumbnail']) ? $attributes['customThumbnail'] : array();
        $show_title = isset($attributes['showTitle']) ? $attributes['showTitle'] : true;
        $show_description = isset($attributes['showDescription']) ? $attributes['showDescription'] : false;
        $autoplay = isset($attributes['autoplay']) ? $attributes['autoplay'] : false;
        $transcript = isset($attributes['transcript']) ? $attributes['transcript'] : '';
        $show_transcript = isset($attributes['showTranscript']) ? $attributes['showTranscript'] : false;
        $manual_info = isset($attributes['manualInfo']) ? $attributes['manualInfo'] : false;
        $open_in_lightbox = isset($attributes['openInLightbox']) ? $attributes['openInLightbox'] : false;
        $class_name = isset($attributes['className']) ? $attributes['className'] : '';

        // Build shortcode attributes
        $shortcode_atts = array(
            'video_url' => $video_url,
            'video_type' => $video_type,
            'video_id' => $video_id,
            'embed_url' => $embed_url,
            'video_title' => $video_title,
            'video_description' => $video_description,
            'video_duration' => $video_duration,
            'upload_date' => $upload_date,
            'thumbnail_url' => $thumbnail_url,
            'show_title' => $show_title ? 'true' : 'false',
            'show_description' => $show_description ? 'true' : 'false',
            'autoplay' => $autoplay ? 'true' : 'false',
            'transcript' => $transcript,
            'show_transcript' => $show_transcript ? 'true' : 'false',
            'manual_info' => $manual_info ? 'true' : 'false',
            'open_in_lightbox' => $open_in_lightbox ? 'true' : 'false',
            'class_name' => $class_name,
        );

        // Add custom thumbnail if exists
        if (!empty($custom_thumbnail) && isset($custom_thumbnail['url'])) {
            $shortcode_atts['custom_thumbnail'] = $custom_thumbnail['url'];
            $shortcode_atts['custom_thumbnail_alt'] = isset($custom_thumbnail['alt']) ? $custom_thumbnail['alt'] : '';
        }

        // Render using shortcode
        $output = $this->render_video_shortcode($shortcode_atts);

        // Mark that we have video blocks on this page
        if (!empty($output)) {
            self::$has_video_blocks = true;
        }

        return $output;
    }

    /**
     * Render video shortcode
     */
    public function render_video_shortcode($atts) {
        $attributes = shortcode_atts(array(
            'video_url' => '',
            'video_type' => '',
            'video_id' => '',
            'embed_url' => '',
            'video_title' => '',
            'video_description' => '',
            'video_duration' => '',
            'upload_date' => '',
            'thumbnail_url' => '',
            'custom_thumbnail' => '',
            'custom_thumbnail_alt' => '',
            'show_title' => 'true',
            'show_description' => 'false',
            'autoplay' => 'false',
            'transcript' => '',
            'show_transcript' => 'false',
            'manual_info' => 'false',
            'open_in_lightbox' => 'false',
            'class_name' => '',
        ), $atts, 'ekwa_video');

        // Build embed URL with autoplay for lightbox
        if ($attributes['open_in_lightbox'] === 'true' && !empty($attributes['embed_url'])) {
            $separator = strpos($attributes['embed_url'], '?') !== false ? '&' : '?';
            $attributes['embed_url'] = $attributes['embed_url'] . $separator . 'autoplay=1';
        }

        // If no video URL, return empty
        if (empty($attributes['video_url'])) {
            return '';
        }

        // Extract video information if not provided
        if (empty($attributes['video_type']) || empty($attributes['video_id'])) {
            $video_info = $this->extract_video_info($attributes['video_url']);
            $attributes = array_merge($attributes, $video_info);
        }

        // Get video metadata from API only for missing fields
        // Check if we need any metadata from API
        $needs_metadata = empty($attributes['video_title']) || 
                         empty($attributes['video_description']) || 
                         empty($attributes['thumbnail_url']) ||
                         empty($attributes['video_duration']) ||
                         empty($attributes['upload_date']);
        
        if ($needs_metadata) {
            $metadata = $this->get_video_metadata($attributes['video_type'], $attributes['video_id']);
            if ($metadata) {
                // Only fill in the fields that are empty - don't overwrite provided values
                foreach ($metadata as $key => $value) {
                    if (empty($attributes[$key])) {
                        $attributes[$key] = $value;
                    }
                }
            }
        }

        // Use custom thumbnail if provided, otherwise use video thumbnail
        $thumbnail_url = !empty($attributes['custom_thumbnail']) ? $attributes['custom_thumbnail'] : $attributes['thumbnail_url'];
        $thumbnail_alt = !empty($attributes['custom_thumbnail_alt']) ? $attributes['custom_thumbnail_alt'] : $attributes['video_title'];

        // Build CSS classes
        $css_classes = array('ekwa-video-wrapper');
        if (!empty($attributes['class_name'])) {
            $css_classes[] = $attributes['class_name'];
        }
        $css_classes[] = 'ekwa-video-' . $attributes['video_type'];

        if ($attributes['open_in_lightbox'] === 'true') {
            $css_classes[] = 'ekwa-video-lightbox';
        }

        // Generate unique ID for this video instance
        $unique_id = 'ekwa-video-' . md5($attributes['video_url'] . time());

        // Mark that we have video blocks on this page
        self::$has_video_blocks = true;

        // If we're past wp_head and CSS hasn't been inlined yet, add it inline here
        if (did_action('wp_head') && !did_action('wp_footer')) {
            static $css_already_output = false;
            if (!$css_already_output) {
                echo $this->get_inline_css_output();
                $css_already_output = true;
            }
        }

        // Start output buffering
        ob_start();
        ?>
        <div itemprop="video" itemscope="" itemtype="http://schema.org/VideoObject" class="<?php echo esc_attr(implode(' ', $css_classes)); ?> ekv-wrapper" id="<?php echo esc_attr($unique_id); ?>">

            <!-- Schema.org meta tags -->
            <?php if (!empty($attributes['video_title'])): ?>
                <meta itemprop="name" content="<?php echo esc_attr($attributes['video_title']); ?>">
            <?php endif; ?>

            <?php if (!empty($attributes['video_duration'])): ?>
                <meta itemprop="duration" content="<?php echo esc_attr($attributes['video_duration']); ?>">
            <?php endif; ?>

            <?php if (!empty($attributes['upload_date'])): ?>
                <meta itemprop="uploadDate" content="<?php echo esc_attr($attributes['upload_date']); ?>">
            <?php endif; ?>

            <?php if (!empty($thumbnail_url)): ?>
                <meta itemprop="thumbnailURL" content="<?php echo esc_url($thumbnail_url); ?>">
            <?php endif; ?>

            <meta itemprop="interactionCount" content="1">

            <?php if (!empty($attributes['embed_url'])): ?>
                <meta itemprop="embedURL" content="<?php echo esc_url($attributes['embed_url']); ?>">
            <?php endif; ?>

            <?php if ($attributes['show_title'] === 'true' && !empty($attributes['video_title'])): ?>
                <h3 class="ekwa-video-title"><?php echo esc_html($attributes['video_title']); ?></h3>
            <?php endif; ?>

            <div class="player-wrap plugin-responsive">
                <?php if ($attributes['open_in_lightbox'] === 'true'): ?>
                    <!-- Lightbox Thumbnail -->
                    <a href="<?php echo esc_url($attributes['embed_url']); ?>"
                       class="ekwa-video-lightbox-trigger glightbox"
                       data-video-description="<?php echo esc_attr($attributes['video_description']); ?>"
                       data-video-transcript="<?php echo esc_attr($attributes['transcript']); ?>"
                       data-show-description="<?php echo esc_attr($attributes['show_description']); ?>"
                       data-show-transcript="<?php echo esc_attr($attributes['show_transcript']); ?>"
                       data-video-id="<?php echo esc_attr($attributes['video_id']); ?>">
                        <?php if (!empty($thumbnail_url)): ?>
                            <?php
                            // Get thumbnail dimensions for better performance (prevent layout shift)
                            $thumb_dimensions = $this->get_thumbnail_dimensions($thumbnail_url, $attributes['video_type'], $attributes['custom_thumbnail']);

                            // Check if active theme is "ekwa"
                            $current_theme = wp_get_theme();
                            $is_ekwa_theme = (strtolower($current_theme->get('TextDomain')) === 'ekwa' || strtolower($current_theme->get('Name')) === 'ekwa');

                            // Use data-src and lazyload class only for ekwa theme
                            $img_src_attr = $is_ekwa_theme ? 'data-src' : 'src';
                            $img_classes = $is_ekwa_theme ? 'image-responsive ekwa-video-thumb-img lazyload' : 'image-responsive ekwa-video-thumb-img';
                            ?>
                            <img <?php echo $img_src_attr; ?>="<?php echo esc_url($thumbnail_url); ?>"
                                 alt="<?php echo esc_attr($thumbnail_alt); ?>"
                                 width="<?php echo esc_attr($thumb_dimensions['width']); ?>"
                                 height="<?php echo esc_attr($thumb_dimensions['height']); ?>"
                                 class="<?php echo esc_attr($img_classes); ?>">
                            <span class="playicon ekwa-video-play-button">
                                <svg width="68" height="48" viewBox="0 0 68 48">
                                    <path d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.63-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26z" fill="#f00"></path>
                                    <path d="M45 24L27 14v20" fill="#fff"></path>
                                </svg>
                            </span>
                            <?php if (!empty($attributes['video_duration'])): ?>
                                <div class="ekwa-video-duration"><?php echo esc_html($this->format_duration($attributes['video_duration'])); ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="ekwa-video-placeholder">
                                <p><?php _e('Video thumbnail not available', 'ekwa-video-block'); ?></p>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <!-- Regular Inline Player -->
                    <div class="player ekwa-video-player" data-id="<?php echo esc_attr($attributes['video_id']); ?>" data-provider="<?php echo esc_attr($attributes['video_type']); ?>" data-video-type="<?php echo esc_attr($attributes['video_type']); ?>" data-video-id="<?php echo esc_attr($attributes['video_id']); ?>" data-autoplay="<?php echo esc_attr($attributes['autoplay']); ?>">
                        <?php if (!empty($thumbnail_url)): ?>
                            <?php
                            // Get thumbnail dimensions for better performance (prevent layout shift)
                            $thumb_dimensions = $this->get_thumbnail_dimensions($thumbnail_url, $attributes['video_type'], $attributes['custom_thumbnail']);
                            ?>
                            <div class="ekwa-video-thumbnail" data-embed-url="<?php echo esc_attr($attributes['embed_url']); ?>">
                                <img decoding="async"
                                     class="image-responsive ls-is-cached lazyloaded ekwa-video-thumb-img"
                                     src="<?php echo esc_url($thumbnail_url); ?>"
                                     alt="<?php echo esc_attr($thumbnail_alt); ?>"
                                     width="<?php echo esc_attr($thumb_dimensions['width']); ?>"
                                     height="<?php echo esc_attr($thumb_dimensions['height']); ?>">
                                <span class="playicon ekwa-video-play-button">
                                    <svg width="68" height="48" viewBox="0 0 68 48">
                                        <path d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.63-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26z" fill="#f00"></path>
                                        <path d="M45 24L27 14v20" fill="#fff"></path>
                                    </svg>
                                </span>
                                <?php if (!empty($attributes['video_duration'])): ?>
                                    <div class="ekwa-video-duration"><?php echo esc_html($this->format_duration($attributes['video_duration'])); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="ekwa-video-placeholder">
                                <p><?php _e('Video thumbnail not available', 'ekwa-video-block'); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="ekwa-video-iframe-container" style="display: none;"></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Show description below thumbnail for lightbox videos -->
            <?php if ($attributes['open_in_lightbox'] === 'true' && $attributes['show_description'] === 'true' && !empty($attributes['video_description'])): ?>
                <div class="ekwa-video-description">
                    <p><?php echo esc_html($attributes['video_description']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($attributes['video_description'])): ?>
                <meta itemprop="description" content="<?php echo esc_attr($attributes['video_description']); ?>">
            <?php endif; ?>

            <?php if ($attributes['open_in_lightbox'] !== 'true' && $attributes['show_description'] === 'true' && !empty($attributes['video_description'])): ?>
                <div class="ekwa-video-description">
                    <p><?php echo esc_html($attributes['video_description']); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($attributes['open_in_lightbox'] !== 'true' && $attributes['show_transcript'] === 'true' && !empty($attributes['transcript'])): ?>
                <div class="video_transcript_btn">
                    <button type="button" data-target="#transcript-<?php echo esc_attr($attributes['video_id']); ?>" class="btn-standard btn-vdo-trans btn-transcript ekv-button">
                        Video Transcript
                        <span class="trans-icon"></span>
                    </button>
                </div>

                <div id="transcript-<?php echo esc_attr($attributes['video_id']); ?>" class="transcript-wrapper-del transcript" style="display: none;">
                    <div class="transcript-box">
                        <div class="transcript-container ekv-transcript">
                            <?php echo wpautop(wp_kses_post($attributes['transcript'])); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($attributes['open_in_lightbox'] === 'true' && $attributes['show_transcript'] === 'true' && !empty($attributes['transcript'])): ?>
                <div class="video_transcript_btn">
                    <button type="button" data-target="#transcript-<?php echo esc_attr($attributes['video_id']); ?>" class="btn-standard btn-vdo-trans btn-transcript ekv-button">
                        Video Transcript
                        <span class="trans-icon"></span>
                    </button>
                </div>

                <div id="transcript-<?php echo esc_attr($attributes['video_id']); ?>" class="transcript-wrapper-del transcript" style="display: none;">
                    <div class="transcript-box">
                        <div class="transcript-container ekv-transcript">
                            <?php echo wpautop(wp_kses_post($attributes['transcript'])); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Extract video information from URL
     */
    public function extract_video_info($url) {
        $info = array(
            'video_type' => '',
            'video_id' => '',
            'embed_url' => '',
        );

        // YouTube URL patterns
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
            $info['video_type'] = 'youtube';
            $info['video_id'] = $matches[1];
            $info['embed_url'] = 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0';
        }
       // Vimeo URL patterns - UPDATED REGEX
    elseif (preg_match('/(?:www\.)?vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/[^\/]*\/videos\/|album\/\d+\/video\/|video\/|)(\d+)(?:$|\/|\?)/', $url, $matches)) {
        $info['video_type'] = 'vimeo';
        $info['video_id'] = $matches[1];
        $info['embed_url'] = 'https://player.vimeo.com/video/' . $matches[1];
    }
    // Alternative simpler Vimeo pattern as fallback
    elseif (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        $info['video_type'] = 'vimeo';
        $info['video_id'] = $matches[1];
        $info['embed_url'] = 'https://player.vimeo.com/video/' . $matches[1];
    }

        return $info;
    }

    /**
     * Get video metadata from API
     */
    public function get_video_metadata($video_type, $video_id) {
        if (empty($video_type) || empty($video_id)) {
            return false;
        }

        $metadata = array();

        if ($video_type === 'youtube') {
            $metadata = $this->get_youtube_metadata($video_id);
        } elseif ($video_type === 'vimeo') {
            $metadata = $this->get_vimeo_metadata($video_id);
        }

        return $metadata;
    }

    /**
     * Get YouTube video metadata
     */
    private function get_youtube_metadata($video_id) {
        // Try to get YouTube API key from WordPress options or filter
        $api_key = apply_filters('ekwa_video_youtube_api_key', get_option('ekwa_video_youtube_api_key', ''));

        if (!empty($api_key)) {
            // Use YouTube Data API v3
            $api_url = "https://www.googleapis.com/youtube/v3/videos?id={$video_id}&key={$api_key}&part=snippet,contentDetails,statistics";

            $response = wp_remote_get($api_url);

            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (isset($data['items']) && !empty($data['items'])) {
                    $video = $data['items'][0];
                    $snippet = $video['snippet'];
                    $content_details = $video['contentDetails'];

                    return array(
                        'video_title' => isset($snippet['title']) ? $snippet['title'] : '',
                        'video_description' => isset($snippet['description']) ? wp_trim_words($snippet['description'], 30) : '',
                        'video_duration' => isset($content_details['duration']) ? $content_details['duration'] : '',
                        'upload_date' => isset($snippet['publishedAt']) ? $snippet['publishedAt'] : '',
                        'thumbnail_url' => $this->get_best_youtube_thumbnail($video_id, $snippet),
                    );
                }
            }
        }

        // Fallback: Try to get basic metadata without API
        $fallback_data = $this->get_youtube_metadata_fallback($video_id);

        return array(
            'video_title' => $fallback_data['title'],
            'video_description' => $fallback_data['description'],
            'video_duration' => '',
            'upload_date' => '',
            'thumbnail_url' => 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg',
        );
    }

    /**
     * Get the best available YouTube thumbnail
     */
    private function get_best_youtube_thumbnail($video_id, $snippet = null) {
        // If we have snippet data from API, use it
        if ($snippet && isset($snippet['thumbnails'])) {
            $thumbnails = $snippet['thumbnails'];

            // Prefer higher quality thumbnails
            if (isset($thumbnails['maxres'])) {
                return $thumbnails['maxres']['url'];
            } elseif (isset($thumbnails['high'])) {
                return $thumbnails['high']['url'];
            } elseif (isset($thumbnails['medium'])) {
                return $thumbnails['medium']['url'];
            } elseif (isset($thumbnails['default'])) {
                return $thumbnails['default']['url'];
            }
        }

        // Fallback to standard YouTube thumbnail URLs
        return 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
    }

    /**
     * Fallback method to get basic YouTube metadata without API
     */
    private function get_youtube_metadata_fallback($video_id) {
        // Try to get title from oEmbed (limited but doesn't require API key)
        $oembed_url = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$video_id}&format=json";
        $response = wp_remote_get($oembed_url);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['title'])) {
                return array(
                    'title' => $data['title'],
                    'description' => isset($data['author_name']) ? 'By ' . $data['author_name'] : '',
                );
            }
        }

        return array(
            'title' => '',
            'description' => '',
        );
    }

    /**
     * Get Vimeo video metadata
     */
    private function get_vimeo_metadata($video_id) {
        $response = wp_remote_get("https://vimeo.com/api/oembed.json?url=https://vimeo.com/{$video_id}");

        if (is_wp_error($response)) {
            return array(
                'thumbnail_url' => '',
                'video_title' => '',
                'video_description' => '',
                'video_duration' => '',
                'upload_date' => '',
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data) {
            return array(
                'thumbnail_url' => '',
                'video_title' => '',
                'video_description' => '',
                'video_duration' => '',
                'upload_date' => '',
            );
        }

        return array(
            'video_title' => isset($data['title']) ? $data['title'] : '',
            'video_description' => isset($data['description']) ? $data['description'] : '',
            'video_duration' => isset($data['duration']) ? $this->seconds_to_iso8601($data['duration']) : '',
            'upload_date' => isset($data['upload_date']) ? $this->convert_vimeo_date_to_iso($data['upload_date']) : '',
            'thumbnail_url' => isset($data['thumbnail_url']) ? $data['thumbnail_url'] : '',
        );
    }

    /**
     * Convert seconds to ISO 8601 duration format
     */
    private function seconds_to_iso8601($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        $duration = 'PT';
        if ($hours > 0) $duration .= $hours . 'H';
        if ($minutes > 0) $duration .= $minutes . 'M';
        if ($seconds > 0) $duration .= $seconds . 'S';

        return $duration;
    }

    /**
     * Convert Vimeo date format to ISO 8601 format like YouTube
     */
    private function convert_vimeo_date_to_iso($vimeo_date) {
        // Vimeo format: "2016-01-01 01:43:02"
        // YouTube format: "2018-07-31T14:28:58Z"

        if (empty($vimeo_date)) {
            return '';
        }

        // Try to parse the Vimeo date
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $vimeo_date);

        if ($date === false) {
            // If that fails, try with timezone info
            $date = DateTime::createFromFormat('Y-m-d H:i:s T', $vimeo_date);
        }

        if ($date === false) {
            // If still fails, return original
            return $vimeo_date;
        }

        // Convert to ISO 8601 format with Z suffix (UTC)
        return $date->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Get thumbnail dimensions for width/height attributes
     * Returns array with width and height for 16:9 aspect ratio
     */
    private function get_thumbnail_dimensions($thumbnail_url, $video_type, $custom_thumbnail) {
        $dimensions = array(
            'width' => 1280,
            'height' => 720
        );

        // If custom thumbnail is used and has dimensions stored
        if (!empty($custom_thumbnail) && is_array($custom_thumbnail)) {
            if (isset($custom_thumbnail['width']) && isset($custom_thumbnail['height'])) {
                $dimensions['width'] = $custom_thumbnail['width'];
                $dimensions['height'] = $custom_thumbnail['height'];
                return $dimensions;
            }

            // If custom thumbnail has ID, try to get dimensions from attachment
            if (isset($custom_thumbnail['id'])) {
                $attachment_id = $custom_thumbnail['id'];
                $image_meta = wp_get_attachment_metadata($attachment_id);
                if ($image_meta && isset($image_meta['width']) && isset($image_meta['height'])) {
                    $dimensions['width'] = $image_meta['width'];
                    $dimensions['height'] = $image_meta['height'];
                    return $dimensions;
                }
            }
        }

        // For YouTube thumbnails
        if ($video_type === 'youtube') {
            // YouTube maxresdefault is 1280x720
            if (strpos($thumbnail_url, 'maxresdefault') !== false) {
                $dimensions['width'] = 1280;
                $dimensions['height'] = 720;
            }
            // YouTube hqdefault is 480x360
            elseif (strpos($thumbnail_url, 'hqdefault') !== false) {
                $dimensions['width'] = 480;
                $dimensions['height'] = 360;
            }
            // YouTube sddefault is 640x480
            elseif (strpos($thumbnail_url, 'sddefault') !== false) {
                $dimensions['width'] = 640;
                $dimensions['height'] = 480;
            }
            // Default YouTube thumbnail
            else {
                $dimensions['width'] = 1280;
                $dimensions['height'] = 720;
            }
        }
        // For Vimeo thumbnails - extract dimensions from URL
        elseif ($video_type === 'vimeo') {
            // Vimeo thumbnail URLs often contain dimensions like: _640 or _1280x720
            // Example: https://i.vimeocdn.com/video/xxxxx-d_640
            // Example: https://i.vimeocdn.com/video/xxxxx_1280x720

            // Try to extract dimensions from URL pattern like _640 or _1280x720
            if (preg_match('/_(\d+)x(\d+)/', $thumbnail_url, $matches)) {
                // Found explicit width x height in URL
                $dimensions['width'] = (int)$matches[1];
                $dimensions['height'] = (int)$matches[2];
            }
            elseif (preg_match('/_d?_?(\d+)(?:\?|$)/', $thumbnail_url, $matches)) {
                // Found width only (like _640 or _d_640)
                // Calculate height based on 16:9 ratio
                $width = (int)$matches[1];
                $dimensions['width'] = $width;
                $dimensions['height'] = round($width * 9 / 16);
            }
            elseif (preg_match('/(\d+)x(\d+)/', $thumbnail_url, $matches)) {
                // Fallback: any WIDTHxHEIGHT pattern in URL
                $dimensions['width'] = (int)$matches[1];
                $dimensions['height'] = (int)$matches[2];
            }
            else {
                // Default to standard HD resolution if no dimensions found
                $dimensions['width'] = 640;
                $dimensions['height'] = 360;
            }
        }

        return $dimensions;
    }

    /**
     * Format duration for display
     */
    private function format_duration($duration) {
        // Convert ISO 8601 duration to human readable format
        if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches)) {
            $hours = isset($matches[1]) ? (int)$matches[1] : 0;
            $minutes = isset($matches[2]) ? (int)$matches[2] : 0;
            $seconds = isset($matches[3]) ? (int)$matches[3] : 0;

            if ($hours > 0) {
                return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
            } else {
                return sprintf('%d:%02d', $minutes, $seconds);
            }
        }

        return $duration;
    }

    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'ekwa-video-block-editor',
            EKWA_VIDEO_BLOCK_PLUGIN_URL . 'assets/js/block.js',
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-block-editor'),
            EKWA_VIDEO_BLOCK_VERSION,
            true
        );

        wp_enqueue_style(
            'ekwa-video-block-editor',
            EKWA_VIDEO_BLOCK_PLUGIN_URL . 'assets/css/editor.css',
            array('wp-edit-blocks'),
            EKWA_VIDEO_BLOCK_VERSION
        );

        // Localize script for AJAX
        wp_localize_script('ekwa-video-block-editor', 'ekwaVideoBlock', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ekwa_video_block_nonce'),
        ));
    }

    /**
     * Inline critical CSS in the header if video blocks are present
     */
    public function inline_critical_css() {
        // Don't run in admin or AJAX requests
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        // Only inline CSS if we have video blocks on the page
        if (!self::$has_video_blocks) {
            return;
        }

        echo $this->get_inline_css_output();
    }

    /**
     * Get inline CSS output
     */
    private function get_inline_css_output() {
        $css_file_path = EKWA_VIDEO_BLOCK_PLUGIN_PATH . 'assets/css/frontend.css';

        if (file_exists($css_file_path)) {
            $css_content = file_get_contents($css_file_path);

            if ($css_content !== false) {
                $css_content = $this->minify_css($css_content);

                // Check if we're in wp_head or later
                $css_id = did_action('wp_head') ? 'ekwa-video-block-inline-css-fallback' : 'ekwa-video-block-inline-css';
                $css_comment = did_action('wp_head') ? '/* Ekwa Video Block - Inline CSS (Fallback) */' : '/* Ekwa Video Block - Inline CSS */';

                return '<style id="' . $css_id . '">' . "\n" .
                       $css_comment . "\n" .
                       $css_content . "\n" .
                       '</style>' . "\n";
            }
        }

        return '';
    }

    /**
     * Simple CSS minification
     */
    private function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

        // Remove unnecessary whitespace
        $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
        $css = str_replace('{ ', '{', $css);
        $css = str_replace(' }', '}', $css);
        $css = str_replace('; ', ';', $css);
        $css = str_replace(', ', ',', $css);
        $css = str_replace(' {', '{', $css);
        $css = str_replace('} ', '}', $css);
        $css = str_replace(': ', ':', $css);
        $css = str_replace(' ;', ';', $css);

        return trim($css);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Don't enqueue CSS file since we're inlining it
        // Only enqueue if we absolutely need to fall back
        if (!self::$has_video_blocks && !is_admin()) {
            wp_enqueue_style(
                'ekwa-video-block-frontend',
                EKWA_VIDEO_BLOCK_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                EKWA_VIDEO_BLOCK_VERSION
            );
        }

        // Frontend.js will be lazy loaded via inline script in footer
        // No need to enqueue or localize here
    }

    /**
     * Lazy load frontend script in footer if video blocks are present
     */
    public function lazy_load_frontend_script() {
        // Don't run in admin or AJAX requests
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        // Only output if we have video blocks on the page
        if (!self::$has_video_blocks) {
            return;
        }

        $frontend_js_url = EKWA_VIDEO_BLOCK_PLUGIN_URL . 'assets/js/frontend.js';
        $ga4_tracking_url = EKWA_VIDEO_BLOCK_PLUGIN_URL . 'assets/js/ga4-tracking.js';
        $ga4_enabled = get_option('ekwa_video_ga4_tracking', false) ? 'true' : 'false';
        $version = EKWA_VIDEO_BLOCK_VERSION;
        ?>
        <script id="ekwa-video-lazy-loader">
        (function() {
            'use strict';

            let scriptsLoaded = false;

            // Configuration
            const config = {
                frontendJsUrl: '<?php echo esc_js($frontend_js_url); ?>?ver=<?php echo esc_js($version); ?>',
                ga4TrackingUrl: '<?php echo esc_js($ga4_tracking_url); ?>?ver=<?php echo esc_js($version); ?>',
                ga4Enabled: <?php echo $ga4_enabled; ?>,
                pluginUrl: '<?php echo esc_js(EKWA_VIDEO_BLOCK_PLUGIN_URL); ?>'
            };

            // Set up ekwaVideoData globally before loading scripts
            window.ekwaVideoData = {
                pluginUrl: config.pluginUrl
            };

            function loadScript(url, callback) {
                const script = document.createElement('script');
                script.src = url;
                script.async = true;
                script.onload = callback || function() {};
                script.onerror = function() {
                    console.error('Failed to load script:', url);
                };
                document.head.appendChild(script);
            }

            // Override DOMContentLoaded behavior for lazy-loaded scripts
            function wrapDOMContentLoaded() {
                // Store the original addEventListener
                const originalAddEventListener = Document.prototype.addEventListener;

                // Override addEventListener temporarily
                Document.prototype.addEventListener = function(type, listener, options) {
                    if (type === 'DOMContentLoaded') {
                        if (document.readyState === 'loading') {
                            // DOM is still loading, use normal behavior
                            originalAddEventListener.call(this, type, listener, options);
                        } else {
                            // DOM is already ready, execute immediately
                            console.log('🔄 DOM already ready, executing listener immediately');
                            if (typeof listener === 'function') {
                                setTimeout(listener, 0);
                            }
                        }
                    } else {
                        // For all other events, use normal behavior
                        originalAddEventListener.call(this, type, listener, options);
                    }
                };

                // Restore original addEventListener after a short delay
                setTimeout(function() {
                    Document.prototype.addEventListener = originalAddEventListener;
                    console.log('🔧 Restored original addEventListener');
                }, 1000);
            }

            function loadScripts() {
                if (scriptsLoaded) return;
                scriptsLoaded = true;

                console.log('🚀 Loading Ekwa Video scripts...');

                // Override DOMContentLoaded behavior before loading scripts
                wrapDOMContentLoaded();

                // Load frontend.js first
                loadScript(config.frontendJsUrl, function() {
                    console.log('✅ Frontend.js loaded');

                    // Load GA4 tracking if enabled
                    if (config.ga4Enabled) {
                        loadScript(config.ga4TrackingUrl, function() {
                            console.log('✅ GA4 tracking loaded');
                        });
                    }
                });                // Remove event listeners to prevent multiple loads
                removeEventListeners();

                // Remove this script element
                const lazyLoader = document.getElementById('ekwa-video-lazy-loader');
                if (lazyLoader) {
                    lazyLoader.remove();
                }
            }

            function removeEventListeners() {
                window.removeEventListener('scroll', loadScripts);
                window.removeEventListener('mousemove', loadScripts);
                window.removeEventListener('touchstart', loadScripts);
                window.removeEventListener('keydown', loadScripts);
                document.removeEventListener('click', loadScripts);
            }

            // Check if video blocks exist on the page
            function hasVideoBlocks() {
                return document.querySelector('.ekwa-video-wrapper, .ekv-wrapper, .ekwa-video-player, .glightbox');
            }

            // Only set up lazy loading if video blocks are present
            if (hasVideoBlocks()) {
                // Add event listeners for user interaction
                window.addEventListener('scroll', loadScripts, { passive: true });
                window.addEventListener('mousemove', loadScripts, { passive: true });
                window.addEventListener('touchstart', loadScripts, { passive: true });
                window.addEventListener('keydown', loadScripts, { passive: true });
                document.addEventListener('click', loadScripts, { passive: true });

                // Fallback: Load after 5 seconds if no interaction
                setTimeout(function() {
                    if (!scriptsLoaded) {
                        console.log('⏰ Loading scripts after timeout (no user interaction)');
                        loadScripts();
                    }
                }, 5000);

                console.log('🎬 Ekwa Video lazy loader initialized - scripts will load on user interaction');
            } else {
                console.log('ℹ️ No video blocks found, skipping script loading');
            }
        })();
        </script>
        <?php
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Ekwa Video Block Settings', // Page title
            'Ekwa Video Block',         // Menu title
            'manage_options',           // Capability
            'ekwa-video-block',         // Menu slug
            array($this, 'admin_page'), // Callback
            'dashicons-format-video',   // Icon
            60                          // Position
        );
    }

    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('ekwa_video_block_settings', 'ekwa_video_youtube_api_key');
        register_setting('ekwa_video_block_settings', 'ekwa_video_ga4_tracking');

        add_settings_section(
            'ekwa_video_block_main',
            'YouTube API Settings',
            array($this, 'settings_section_callback'),
            'ekwa-video-block'
        );

        add_settings_field(
            'ekwa_video_youtube_api_key',
            'YouTube API Key',
            array($this, 'youtube_api_key_callback'),
            'ekwa-video-block',
            'ekwa_video_block_main'
        );

        add_settings_section(
            'ekwa_video_block_tracking',
            'Google Analytics 4 Tracking',
            array($this, 'tracking_section_callback'),
            'ekwa-video-block'
        );

        add_settings_field(
            'ekwa_video_ga4_tracking',
            'Enable GA4 Video Tracking',
            array($this, 'ga4_tracking_callback'),
            'ekwa-video-block',
            'ekwa_video_block_tracking'
        );
    }

    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>Configure your YouTube API key to fetch video metadata like title, description, and duration.</p>';
        echo '<p><strong>How to get a YouTube API key:</strong></p>';
        echo '<ol>';
        echo '<li>Go to <a href="https://console.developers.google.com/" target="_blank">Google Cloud Console</a></li>';
        echo '<li>Create a new project or select an existing one</li>';
        echo '<li>Enable the YouTube Data API v3</li>';
        echo '<li>Create credentials (API Key)</li>';
        echo '<li>Copy the API key and paste it below</li>';
        echo '</ol>';
    }

    /**
     * YouTube API key field callback
     */
    public function youtube_api_key_callback() {
        $api_key = get_option('ekwa_video_youtube_api_key', '');
        echo '<input type="text" id="ekwa_video_youtube_api_key" name="ekwa_video_youtube_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">Enter your YouTube Data API v3 key. Leave empty to use basic functionality without metadata.</p>';
    }

    /**
     * Tracking section callback
     */
    public function tracking_section_callback() {
        echo '<p>Configure Google Analytics 4 video tracking to monitor video engagement.</p>';
        echo '<p><strong>Features:</strong></p>';
        echo '<ul>';
        echo '<li>Track video start events</li>';
        echo '<li>Track video progress milestones (25%, 50%, 75%)</li>';
        echo '<li>Track video completion</li>';
        echo '<li>Track video pause events</li>';
        echo '<li>Support for both YouTube and Vimeo videos</li>';
        echo '</ul>';
        echo '<p><em>Note: Make sure Google Analytics 4 (gtag) is installed on your website for tracking to work.</em></p>';
    }

    /**
     * GA4 tracking field callback
     */
    public function ga4_tracking_callback() {
        $ga4_tracking = get_option('ekwa_video_ga4_tracking', false);
        echo '<input type="checkbox" id="ekwa_video_ga4_tracking" name="ekwa_video_ga4_tracking" value="1" ' . checked(1, $ga4_tracking, false) . ' />';
        echo '<label for="ekwa_video_ga4_tracking">Enable Google Analytics 4 video tracking</label>';
        echo '<p class="description">When enabled, video interactions will be tracked and sent to Google Analytics 4.</p>';
    }

    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Ekwa Video Block Settings</h1>

            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Settings saved successfully!</strong></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('ekwa_video_block_settings');
                do_settings_sections('ekwa-video-block');
                submit_button();
                ?>
            </form>

            <div class="card" style="margin-top: 20px;">
                <h2>Test Your Settings</h2>
                <p>You can test if your YouTube API key is working by trying these sample URLs in the block editor:</p>
                <ul>
                    <li><strong>YouTube:</strong> https://www.youtube.com/watch?v=dQw4w9WgXcQ</li>
                    <li><strong>Vimeo:</strong> https://vimeo.com/148751763</li>
                </ul>
                <p>If the API key is working, you should see the video title, description, and duration automatically populate in the block editor.</p>
            </div>

            <?php
            $ga4_tracking = get_option('ekwa_video_ga4_tracking', false);
            if ($ga4_tracking):
            ?>
            <div class="card" style="margin-top: 20px;">
                <h2>GA4 Video Tracking</h2>
                <p><strong>✅ GA4 video tracking is enabled!</strong></p>
                <p>The following events will be tracked:</p>
                <ul>
                    <li><strong>video_start:</strong> When a video begins playing</li>
                    <li><strong>video_progress:</strong> At 25%, 50%, and 75% completion milestones</li>
                    <li><strong>video_pause:</strong> When a video is paused</li>
                    <li><strong>video_complete:</strong> When a video finishes playing</li>
                </ul>
                <p><em>Make sure Google Analytics 4 (gtag) is properly installed on your website.</em></p>

                <h3>Testing GA4 Events</h3>
                <p>To test if events are being sent:</p>
                <ol>
                    <li>Open your browser's Developer Tools (F12)</li>
                    <li>Go to the Console tab</li>
                    <li>Play a video on your site</li>
                    <li>Look for "📊 GA4 Event (Ekwa Video):" messages in the console</li>
                </ol>
            </div>
            <?php else: ?>
            <div class="card" style="margin-top: 20px;">
                <h2>GA4 Video Tracking</h2>
                <p><strong>❌ GA4 video tracking is disabled.</strong></p>
                <p>Enable it above to start tracking video engagement events in Google Analytics 4.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Initialize the plugin
new EkwaVideoBlock();

// AJAX handler for fetching video metadata
add_action('wp_ajax_ekwa_get_video_metadata', 'ekwa_get_video_metadata_ajax');
add_action('wp_ajax_nopriv_ekwa_get_video_metadata', 'ekwa_get_video_metadata_ajax');

// AJAX handler for uploading cropped thumbnail
add_action('wp_ajax_ekwa_upload_cropped_thumbnail', 'ekwa_upload_cropped_thumbnail_ajax');

function ekwa_get_video_metadata_ajax() {
    check_ajax_referer('ekwa_video_block_nonce', 'nonce');

    $video_url = sanitize_url($_POST['video_url']);

    if (empty($video_url)) {
        wp_send_json_error('Invalid video URL');
        return;
    }

    $plugin = new EkwaVideoBlock();
    $video_info = $plugin->extract_video_info($video_url);

    if (empty($video_info['video_type']) || empty($video_info['video_id'])) {
        wp_send_json_error('Could not extract video information');
        return;
    }

    $metadata = $plugin->get_video_metadata($video_info['video_type'], $video_info['video_id']);
    $response = array_merge($video_info, $metadata);

    wp_send_json_success($response);
}

/**
 * AJAX handler for uploading cropped thumbnail
 */
function ekwa_upload_cropped_thumbnail_ajax() {
    check_ajax_referer('ekwa_video_block_nonce', 'nonce');

    if (!current_user_can('upload_files')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    if (!isset($_FILES['cropped_image'])) {
        wp_send_json_error('No image file provided');
        return;
    }

    $original_id = intval($_POST['original_id']);
    $file = $_FILES['cropped_image'];

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('Upload error: ' . $file['error']);
        return;
    }

    // Check file type
    $allowed_types = array('image/jpeg', 'image/jpg', 'image/png');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        wp_send_json_error('Invalid file type. Only JPEG and PNG are allowed.');
        return;
    }

    // Get original attachment data
    $original_attachment = get_post($original_id);
    if (!$original_attachment) {
        wp_send_json_error('Original attachment not found');
        return;
    }

    // Generate unique filename
    $upload_dir = wp_upload_dir();
    $original_filename = basename(get_attached_file($original_id));
    $file_info = pathinfo($original_filename);
    $new_filename = $file_info['filename'] . '-cropped-' . time() . '.jpg';
    $new_file_path = $upload_dir['path'] . '/' . $new_filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $new_file_path)) {
        wp_send_json_error('Failed to save cropped image');
        return;
    }

    // Create attachment
    $attachment_data = array(
        'post_mime_type' => 'image/jpeg',
        'post_title' => $original_attachment->post_title . ' (Cropped)',
        'post_content' => '',
        'post_status' => 'inherit'
    );

    $attachment_id = wp_insert_attachment($attachment_data, $new_file_path);

    if (is_wp_error($attachment_id)) {
        wp_send_json_error('Failed to create attachment: ' . $attachment_id->get_error_message());
        return;
    }

    // Generate attachment metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $new_file_path);
    wp_update_attachment_metadata($attachment_id, $attachment_metadata);

    // Copy alt text and other metadata from original
    $alt_text = get_post_meta($original_id, '_wp_attachment_image_alt', true);
    if ($alt_text) {
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
    }

    $response = array(
        'id' => $attachment_id,
        'url' => wp_get_attachment_url($attachment_id),
        'alt' => $alt_text
    );

    wp_send_json_success($response);
}
