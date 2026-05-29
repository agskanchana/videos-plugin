<?php
/**
 * Plugin Name: Ekwa Video Block
 * Plugin URI: https://www.ekwa.com
 * Description: A Gutenberg block for embedding YouTube and Vimeo videos with lazy loading and custom thumbnails
 * Version: 1.6.3
 * Author: Ekwa Team
 * Author URI: https://www.ekwa.com
 * Text Domain: ekwa-video-block
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.6
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

/*
 * Authenticate GitHub API requests. Unauthenticated requests are limited to
 * 60/hour per server IP; an authenticated token raises this to 5,000/hour.
 * The token can be defined in wp-config.php (preferred, keeps it out of the DB)
 * or saved on the plugin settings page.
 */
$ekwa_github_token = defined('EKWA_VIDEO_GITHUB_TOKEN')
	? EKWA_VIDEO_GITHUB_TOKEN
	: get_option('ekwa_video_github_token', '');
if (!empty($ekwa_github_token)) {
	$myUpdateChecker->setAuthentication($ekwa_github_token);
}

/*
 * Detect when the GitHub API rate limit is exhausted. PUC fires this action
 * whenever an update check fails. We store the reset timestamp so the admin
 * page can tell the user when it is safe to check again.
 */
add_action('puc_api_error', 'ekwa_video_handle_puc_api_error', 10, 4);
function ekwa_video_handle_puc_api_error($error, $httpResponse = null, $url = null, $slug = null) {
	if ($slug !== 'ekwa-video-block' || empty($httpResponse) || is_wp_error($httpResponse)) {
		return;
	}

	$code      = (int) wp_remote_retrieve_response_code($httpResponse);
	$remaining = wp_remote_retrieve_header($httpResponse, 'x-ratelimit-remaining');
	$reset     = wp_remote_retrieve_header($httpResponse, 'x-ratelimit-reset');

	// GitHub returns 403 (or 429) with x-ratelimit-remaining: 0 when exhausted.
	if (($code === 403 || $code === 429) && $remaining === '0') {
		update_option('ekwa_video_gh_rate_limited', array(
			'reset'        => (int) $reset, // Unix timestamp when the limit resets
			'detected'     => time(),
			'authenticated' => (bool) (defined('EKWA_VIDEO_GITHUB_TOKEN') ? EKWA_VIDEO_GITHUB_TOKEN : get_option('ekwa_video_github_token', '')),
		), false);
	}
}

    
// Define plugin constants
define('EKWA_VIDEO_BLOCK_VERSION', '1.6.0');
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
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Add a "Settings" link to the row on the Plugins listing screen.
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=ekwa-video-block')) . '">' . esc_html__('Settings', 'ekwa-video-block') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
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

        // For archive pages, check the first few posts in the loop
        if (is_home() || is_archive() || is_search()) {
            $posts = get_posts(array(
                'post_type' => 'any',
                'posts_per_page' => 10, // Limit for performance
                'no_found_rows' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
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

        // Generate unique ID for this video instance (stable across renders so caching works)
        $unique_id = 'ekwa-video-' . md5($attributes['video_url'] . '|' . $attributes['video_id']);

        // Mark that we have video blocks on this page
        self::$has_video_blocks = true;

        // If we're past wp_head and CSS hasn't been inlined yet, add it inline here
        if (did_action('wp_head') && !did_action('wp_footer') && get_option('ekwa_video_inline_frontend_css', true)) {
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
                        <?php if (!empty($thumbnail_url)):
                            $thumb_dimensions = $this->get_thumbnail_dimensions($thumbnail_url, $attributes['video_type'], $attributes['custom_thumbnail']);
                            echo $this->render_thumbnail_inner($thumbnail_url, $thumbnail_alt, $thumb_dimensions, $attributes['video_duration']);
                        else: ?>
                            <div class="ekwa-video-placeholder">
                                <p><?php _e('Video thumbnail not available', 'ekwa-video-block'); ?></p>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <!-- Regular Inline Player -->
                    <div class="player ekwa-video-player" data-id="<?php echo esc_attr($attributes['video_id']); ?>" data-provider="<?php echo esc_attr($attributes['video_type']); ?>" data-video-type="<?php echo esc_attr($attributes['video_type']); ?>" data-video-id="<?php echo esc_attr($attributes['video_id']); ?>">
                        <?php if (!empty($thumbnail_url)):
                            $thumb_dimensions = $this->get_thumbnail_dimensions($thumbnail_url, $attributes['video_type'], $attributes['custom_thumbnail']);
                            // Calculate aspect ratio for JS height calculation
                            $aspect_ratio = $thumb_dimensions['height'] / $thumb_dimensions['width'];
                            $play_label = !empty($attributes['video_title'])
                                ? sprintf(__('Play video: %s', 'ekwa-video-block'), $attributes['video_title'])
                                : __('Play video', 'ekwa-video-block');
                            ?>
                            <button type="button"
                                    class="ekwa-video-thumbnail"
                                    data-embed-url="<?php echo esc_attr($attributes['embed_url']); ?>"
                                    data-aspect-ratio="<?php echo esc_attr($aspect_ratio); ?>"
                                    aria-label="<?php echo esc_attr($play_label); ?>">
                                <?php echo $this->render_thumbnail_inner($thumbnail_url, $thumbnail_alt, $thumb_dimensions, $attributes['video_duration']); ?>
                            </button>
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
                    <button type="button"
                            data-target="#transcript-<?php echo esc_attr($attributes['video_id']); ?>"
                            class="btn-standard btn-vdo-trans btn-transcript ekv-button"
                            aria-expanded="false"
                            aria-controls="transcript-<?php echo esc_attr($attributes['video_id']); ?>">
                        <?php esc_html_e('Video Transcript', 'ekwa-video-block'); ?>
                        <span class="trans-icon" aria-hidden="true"></span>
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
                    <button type="button"
                            data-target="#transcript-<?php echo esc_attr($attributes['video_id']); ?>"
                            class="btn-standard btn-vdo-trans btn-transcript ekv-button"
                            aria-expanded="false"
                            aria-controls="transcript-<?php echo esc_attr($attributes['video_id']); ?>">
                        <?php esc_html_e('Video Transcript', 'ekwa-video-block'); ?>
                        <span class="trans-icon" aria-hidden="true"></span>
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

        $cache_key = 'ekwa_video_meta_' . md5($video_type . '|' . $video_id);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $metadata = array();

        if ($video_type === 'youtube') {
            $metadata = $this->get_youtube_metadata($video_id);
        } elseif ($video_type === 'vimeo') {
            $metadata = $this->get_vimeo_metadata($video_id);
        }

        // Cache positive results for a day, negative/empty results for 5 minutes
        // so a transient API hiccup doesn't poison the cache.
        $has_useful_data = is_array($metadata) && !empty($metadata['video_title']);
        set_transient($cache_key, $metadata, $has_useful_data ? DAY_IN_SECONDS : 5 * MINUTE_IN_SECONDS);

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
     * Fetch a YouTube video's transcript.
     *
     * First tries scraping ytInitialPlayerResponse out of the watch page;
     * if that yields no caption tracks (common on hosting IPs that get a
     * stripped/consent-gated response) it falls back to the InnerTube
     * youtubei/v1/player endpoint.
     *
     * Returns array('text' => string, 'source' => 'human'|'auto') on success,
     * or false when no usable captions exist.
     *
     * Caches hits for 30 days and misses for 5 minutes so a transient
     * upstream failure does not lock the video out for an hour.
     */
    public function fetch_youtube_transcript($video_id, $force_refresh = false, &$debug = null) {
        if (!is_array($debug)) {
            $debug = array();
        }
        $debug['stages'] = array();

        if (empty($video_id)) {
            $debug['stages'][] = 'empty_video_id';
            return false;
        }

        $cache_key = 'ekwa_video_transcript_' . md5($video_id);
        if ($force_refresh) {
            delete_transient($cache_key);
            $debug['stages'][] = 'cache_busted';
        } else {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                $debug['stages'][] = ($cached === '__none__') ? 'cache_hit_none' : 'cache_hit';
                return $cached === '__none__' ? false : $cached;
            }
        }

        $tracks = $this->get_youtube_caption_tracks_from_watch_page($video_id, $debug);
        if (empty($tracks)) {
            $tracks = $this->get_youtube_caption_tracks_from_innertube($video_id, $debug);
        }

        if (empty($tracks)) {
            $debug['stages'][] = 'no_tracks_from_any_source';
            set_transient($cache_key, '__none__', 5 * MINUTE_IN_SECONDS);
            return false;
        }

        $pick = $this->pick_youtube_caption_track($tracks);
        if (!$pick || empty($pick['baseUrl'])) {
            $debug['stages'][] = 'pick_failed_or_no_baseurl';
            set_transient($cache_key, '__none__', 5 * MINUTE_IN_SECONDS);
            return false;
        }

        $debug['picked_lang'] = $pick['languageCode'] ?? '';
        $debug['picked_kind'] = $pick['kind'] ?? '';

        $text = $this->fetch_caption_text_json3($pick['baseUrl'], $debug);
        if ($text === null) {
            // json3 endpoint failed or returned no events — fall back to XML/srv1.
            $text = $this->fetch_caption_text_xml($pick['baseUrl'], $debug);
        }

        if ($text === null) {
            return false; // network/HTTP errors already recorded; don't cache
        }
        if ($text === '') {
            $debug['stages'][] = 'caption_text_empty';
            set_transient($cache_key, '__none__', 5 * MINUTE_IN_SECONDS);
            return false;
        }

        $debug['stages'][] = 'success';
        $result = array(
            'text'   => $text,
            'source' => (($pick['kind'] ?? '') === 'asr') ? 'auto' : 'human',
        );
        set_transient($cache_key, $result, 30 * DAY_IN_SECONDS);
        return $result;
    }

    /**
     * Scrape captionTracks out of the public watch page.
     */
    private function get_youtube_caption_tracks_from_watch_page($video_id, &$debug = null) {
        $watch_url = 'https://www.youtube.com/watch?v=' . rawurlencode($video_id);
        $response = wp_remote_get($watch_url, array(
            'timeout' => 15,
            'redirection' => 5,
            'headers' => array(
                'Accept-Language' => 'en-US,en;q=0.9',
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Cookie'          => 'CONSENT=YES+cb',
            ),
        ));

        if (is_wp_error($response)) {
            if (is_array($debug)) { $debug['stages'][] = 'watch_wp_error: ' . $response->get_error_message(); }
            return null;
        }
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            if (is_array($debug)) { $debug['stages'][] = 'watch_http_' . $code; }
            return null;
        }

        $html = wp_remote_retrieve_body($response);
        if (is_array($debug)) { $debug['watch_html_len'] = strlen($html); }

        if (!preg_match('/ytInitialPlayerResponse\s*=\s*(\{.+?\})\s*;\s*(?:var|<\/script>)/s', $html, $m)) {
            if (is_array($debug)) {
                $debug['stages'][] = 'watch_regex_no_match';
                $debug['watch_has_ytInitialPlayerResponse'] = (strpos($html, 'ytInitialPlayerResponse') !== false);
                $debug['watch_has_captionTracks'] = (strpos($html, 'captionTracks') !== false);
            }
            return null;
        }

        $data = json_decode($m[1], true);
        $tracks = isset($data['captions']['playerCaptionsTracklistRenderer']['captionTracks'])
            ? $data['captions']['playerCaptionsTracklistRenderer']['captionTracks']
            : null;

        if (is_array($debug)) {
            $debug['watch_playability'] = isset($data['playabilityStatus']['status']) ? $data['playabilityStatus']['status'] : '?';
            $debug['watch_track_count'] = is_array($tracks) ? count($tracks) : 0;
            $debug['stages'][] = 'watch_parsed';
        }

        return (is_array($tracks) && !empty($tracks)) ? $tracks : null;
    }

    /**
     * Fallback: ask YouTube's InnerTube player endpoint directly. Datacenter
     * IPs that get a stripped watch page usually still get a full response
     * here, and the response shape (captions.playerCaptionsTracklistRenderer.captionTracks)
     * is the same as the watch-page payload.
     */
    private function get_youtube_caption_tracks_from_innertube($video_id, &$debug = null) {
        $endpoint = 'https://www.youtube.com/youtubei/v1/player?prettyPrint=false';
        $body = wp_json_encode(array(
            'videoId' => $video_id,
            'context' => array(
                'client' => array(
                    'clientName'    => 'WEB',
                    'clientVersion' => '2.20240101.00.00',
                    'hl'            => 'en',
                    'gl'            => 'US',
                ),
            ),
        ));

        $response = wp_remote_post($endpoint, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type'      => 'application/json',
                'Accept'            => 'application/json',
                'Accept-Language'   => 'en-US,en;q=0.9',
                'User-Agent'        => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'X-Youtube-Client-Name'    => '1',
                'X-Youtube-Client-Version' => '2.20240101.00.00',
                'Origin'            => 'https://www.youtube.com',
                'Referer'           => 'https://www.youtube.com/',
            ),
            'body' => $body,
        ));

        if (is_wp_error($response)) {
            if (is_array($debug)) { $debug['stages'][] = 'innertube_wp_error: ' . $response->get_error_message(); }
            return null;
        }
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            if (is_array($debug)) { $debug['stages'][] = 'innertube_http_' . $code; }
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $tracks = isset($data['captions']['playerCaptionsTracklistRenderer']['captionTracks'])
            ? $data['captions']['playerCaptionsTracklistRenderer']['captionTracks']
            : null;

        if (is_array($debug)) {
            $debug['innertube_playability'] = isset($data['playabilityStatus']['status']) ? $data['playabilityStatus']['status'] : '?';
            $debug['innertube_track_count'] = is_array($tracks) ? count($tracks) : 0;
            $debug['stages'][] = 'innertube_parsed';
        }

        return (is_array($tracks) && !empty($tracks)) ? $tracks : null;
    }

    /**
     * Preference order: English human → en-* human → English auto → first human → first track.
     */
    private function pick_youtube_caption_track($tracks) {
        foreach ($tracks as $t) {
            if (($t['languageCode'] ?? '') === 'en' && empty($t['kind'])) { return $t; }
        }
        foreach ($tracks as $t) {
            if (strpos($t['languageCode'] ?? '', 'en') === 0 && empty($t['kind'])) { return $t; }
        }
        foreach ($tracks as $t) {
            if (($t['languageCode'] ?? '') === 'en' && ($t['kind'] ?? '') === 'asr') { return $t; }
        }
        foreach ($tracks as $t) {
            if (empty($t['kind'])) { return $t; }
        }
        return $tracks[0] ?? null;
    }

    /**
     * Fetch a caption track in json3 format and return the joined text.
     * Returns:
     *   - string text on success (may be '' if all events were empty),
     *   - null on network/HTTP failure OR when json3 has no usable events
     *     (so the caller can fall back to XML).
     */
    private function fetch_caption_text_json3($base_url, &$debug = null) {
        $url = $this->build_caption_url($base_url, 'json3');
        $resp = wp_remote_get($url, array('timeout' => 15));
        if (is_wp_error($resp)) {
            if (is_array($debug)) { $debug['stages'][] = 'caption_json3_wp_error: ' . $resp->get_error_message(); }
            return null;
        }
        $code = wp_remote_retrieve_response_code($resp);
        if ($code !== 200) {
            if (is_array($debug)) { $debug['stages'][] = 'caption_json3_http_' . $code; }
            return null;
        }

        $body = wp_remote_retrieve_body($resp);
        if (is_array($debug)) { $debug['caption_json3_body_len'] = strlen($body); }

        $json = json_decode($body, true);
        if (empty($json['events']) || !is_array($json['events'])) {
            if (is_array($debug)) { $debug['stages'][] = 'caption_json3_no_events'; }
            return null;
        }

        $text = '';
        foreach ($json['events'] as $event) {
            if (empty($event['segs']) || !is_array($event['segs'])) {
                continue;
            }
            foreach ($event['segs'] as $seg) {
                $piece = isset($seg['utf8']) ? $seg['utf8'] : '';
                if ($piece === '' || $piece === "\n") {
                    continue;
                }
                $trimmed = trim($piece);
                if ($trimmed !== '' && preg_match('/^[\[\(].*[\]\)]$/', $trimmed)) {
                    continue;
                }
                $text .= $piece;
            }
            $text .= ' ';
        }

        if (is_array($debug)) { $debug['stages'][] = 'caption_json3_ok'; }
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Fallback: fetch caption track as YouTube's default XML (srv1) and
     * extract the text from <text>…</text> nodes. Used when json3 returns
     * 200 but no events — a known quirk with some ASR tracks.
     */
    private function fetch_caption_text_xml($base_url, &$debug = null) {
        $url = $this->build_caption_url($base_url, null); // default = XML
        $resp = wp_remote_get($url, array('timeout' => 15));
        if (is_wp_error($resp)) {
            if (is_array($debug)) { $debug['stages'][] = 'caption_xml_wp_error: ' . $resp->get_error_message(); }
            return null;
        }
        $code = wp_remote_retrieve_response_code($resp);
        if ($code !== 200) {
            if (is_array($debug)) { $debug['stages'][] = 'caption_xml_http_' . $code; }
            return null;
        }

        $body = wp_remote_retrieve_body($resp);
        if (is_array($debug)) { $debug['caption_xml_body_len'] = strlen($body); }

        if ($body === '' || stripos($body, '<text') === false) {
            if (is_array($debug)) { $debug['stages'][] = 'caption_xml_empty_or_no_text_nodes'; }
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            if (is_array($debug)) { $debug['stages'][] = 'caption_xml_parse_failed'; }
            return null;
        }

        $text = '';
        foreach ($xml->text as $node) {
            $piece = html_entity_decode((string) $node, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $piece = trim($piece);
            if ($piece === '') { continue; }
            if (preg_match('/^[\[\(].*[\]\)]$/', $piece)) { continue; }
            $text .= $piece . ' ';
        }

        if (is_array($debug)) { $debug['stages'][] = 'caption_xml_ok'; }
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Apply a desired fmt to a caption baseUrl, stripping any existing
     * fmt= so we don't double up.
     */
    private function build_caption_url($base_url, $fmt) {
        $url = preg_replace('/([?&])fmt=[^&]*(&|$)/', '$1', $base_url);
        $url = rtrim($url, '?&');
        if ($fmt === null || $fmt === '') {
            return $url;
        }
        $sep = (strpos($url, '?') !== false) ? '&' : '?';
        return $url . $sep . 'fmt=' . rawurlencode($fmt);
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
     * Whether to emit lazysizes-compatible markup (data-src + lazyload class)
     * for thumbnail images. True when the user opted in via settings, or when
     * the active theme is "ekwa" (which already ships lazysizes).
     */
    /**
     * Render the inner thumbnail markup (img + play icon + duration badge).
     * Shared by the lightbox and inline-player branches so they cannot drift apart.
     */
    private function render_thumbnail_inner($thumbnail_url, $thumbnail_alt, $thumb_dimensions, $video_duration) {
        $use_lazysizes = $this->should_use_lazysizes();
        $img_src_attr = $use_lazysizes ? 'data-src' : 'src';
        $img_classes = $use_lazysizes ? 'image-responsive ekwa-video-thumb-img lazyload' : 'image-responsive ekwa-video-thumb-img';
        $img_loading = $use_lazysizes ? '' : ' loading="lazy"';

        ob_start();
        ?>
        <img decoding="async"<?php echo $img_loading; ?>
             class="<?php echo esc_attr($img_classes); ?>"
             <?php echo $img_src_attr; ?>="<?php echo esc_url($thumbnail_url); ?>"
             alt="<?php echo esc_attr($thumbnail_alt); ?>"
             width="<?php echo esc_attr($thumb_dimensions['width']); ?>"
             height="<?php echo esc_attr($thumb_dimensions['height']); ?>">
        <span class="playicon ekwa-video-play-button" aria-hidden="true">
            <svg width="68" height="48" viewBox="0 0 68 48" focusable="false" aria-hidden="true">
                <path d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.63-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26z" fill="#f00"></path>
                <path d="M45 24L27 14v20" fill="#fff"></path>
            </svg>
        </span>
        <?php if (!empty($video_duration)) :
            $formatted = $this->format_duration($video_duration);
            $aria = sprintf(__('Duration: %s', 'ekwa-video-block'), $formatted);
        ?>
            <div class="ekwa-video-duration" aria-label="<?php echo esc_attr($aria); ?>"><?php echo esc_html($formatted); ?></div>
        <?php endif;
        return ob_get_clean();
    }

    private function should_use_lazysizes() {
        static $result = null;
        if ($result !== null) {
            return $result;
        }

        if (get_option('ekwa_video_lazysizes_enabled', false)) {
            $result = true;
            return $result;
        }

        $current_theme = wp_get_theme();
        $result = strtolower($current_theme->get('TextDomain')) === 'ekwa'
            || strtolower($current_theme->get('Name')) === 'ekwa';

        return $result;
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
        $block_js_path = EKWA_VIDEO_BLOCK_PLUGIN_PATH . 'assets/js/block.js';
        $editor_css_path = EKWA_VIDEO_BLOCK_PLUGIN_PATH . 'assets/css/editor.css';
        $block_js_ver = file_exists($block_js_path) ? filemtime($block_js_path) : EKWA_VIDEO_BLOCK_VERSION;
        $editor_css_ver = file_exists($editor_css_path) ? filemtime($editor_css_path) : EKWA_VIDEO_BLOCK_VERSION;

        wp_enqueue_script(
            'ekwa-video-block-editor',
            EKWA_VIDEO_BLOCK_PLUGIN_URL . 'assets/js/block.js',
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-block-editor'),
            $block_js_ver,
            true
        );

        wp_enqueue_style(
            'ekwa-video-block-editor',
            EKWA_VIDEO_BLOCK_PLUGIN_URL . 'assets/css/editor.css',
            array('wp-edit-blocks'),
            $editor_css_ver
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

        // Respect the "Inline frontend.css" setting
        if (!get_option('ekwa_video_inline_frontend_css', true)) {
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
        // When inlining is enabled (default), the CSS is printed inline at render
        // time, so the file is never enqueued. When inlining is disabled, load
        // frontend.css as a separate stylesheet.
        $inline_frontend_css = (bool) get_option('ekwa_video_inline_frontend_css', true);

        if (!is_admin() && !$inline_frontend_css) {
            wp_enqueue_style(
                'ekwa-video-block-frontend',
                EKWA_VIDEO_BLOCK_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                EKWA_VIDEO_BLOCK_VERSION
            );
        }

        if (self::$has_video_blocks
            && !is_admin()
            && get_option('ekwa_video_lazysizes_load_script', false)
        ) {
            wp_enqueue_script(
                'lazysizes',
                EKWA_VIDEO_BLOCK_PLUGIN_URL . 'assets/js/lazysizes.min.js',
                array(),
                '5.3.2',
                true
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
        $defer_until_interaction = get_option('ekwa_video_defer_until_interaction', false) ? 'true' : 'false';
        $inline_frontend_js = (bool) get_option('ekwa_video_inline_frontend_js', false);

        $frontend_js_inline = 'null';
        if ($inline_frontend_js) {
            $frontend_js_path = EKWA_VIDEO_BLOCK_PLUGIN_PATH . 'assets/js/frontend.js';
            if (is_readable($frontend_js_path)) {
                $code = file_get_contents($frontend_js_path);
                if ($code !== false) {
                    // Guard against any literal </script> inside the source (none expected,
                    // but be defensive so we never break the page).
                    $code = str_replace('</script>', '<\/script>', $code);
                    $frontend_js_inline = wp_json_encode($code);
                }
            }
        }
        ?>
        <script id="ekwa-video-lazy-loader">
        (function() {
            'use strict';

            var scriptsLoaded = false;
            var pendingClickTarget = null;

            var config = {
                frontendJsUrl: '<?php echo esc_js($frontend_js_url); ?>?ver=<?php echo esc_js($version); ?>',
                ga4TrackingUrl: '<?php echo esc_js($ga4_tracking_url); ?>?ver=<?php echo esc_js($version); ?>',
                ga4Enabled: <?php echo $ga4_enabled; ?>,
                pluginUrl: '<?php echo esc_js(EKWA_VIDEO_BLOCK_PLUGIN_URL); ?>',
                deferUntilInteraction: <?php echo $defer_until_interaction; ?>,
                frontendJsInline: <?php echo $frontend_js_inline; ?>
            };

            window.ekwaVideoData = {
                pluginUrl: config.pluginUrl
            };

            function loadScript(url, callback) {
                var s = document.createElement('script');
                s.src = url;
                s.async = true;
                s.onload = callback || function() {};
                s.onerror = function() { console.error('Failed to load script:', url); };
                document.head.appendChild(s);
            }

            function executeInline(code, callback) {
                var s = document.createElement('script');
                s.textContent = code + '\n//# sourceURL=ekwa-video-frontend-inline.js';
                document.head.appendChild(s);
                if (callback) callback();
            }

            function loadFrontend(callback) {
                if (config.frontendJsInline) {
                    executeInline(config.frontendJsInline, callback);
                } else {
                    loadScript(config.frontendJsUrl, callback);
                }
            }

            function loadScripts() {
                if (scriptsLoaded) return;
                scriptsLoaded = true;
                removeInteractionListeners();
                loadFrontend(function() {
                    if (config.ga4Enabled) {
                        loadScript(config.ga4TrackingUrl);
                    }
                    if (pendingClickTarget) {
                        var target = pendingClickTarget;
                        pendingClickTarget = null;
                        // Replay the click that triggered the load so the user's play action is honored.
                        setTimeout(function() {
                            try {
                                target.dispatchEvent(new MouseEvent('click', {
                                    bubbles: true, cancelable: true, view: window
                                }));
                            } catch (e) {
                                if (typeof target.click === 'function') target.click();
                            }
                        }, 30);
                    }
                });
            }

            var PLAYABLE_SELECTOR = '.ekwa-video-thumbnail, .ekwa-video-play-button, .ekwa-video-lightbox-trigger, .playicon';
            var interactionEvents = ['scroll', 'mousemove', 'touchstart', 'keydown', 'click'];

            function interactionHandler(e) {
                if (e && (e.type === 'click' || e.type === 'touchstart')) {
                    var playable = e.target && e.target.closest ? e.target.closest(PLAYABLE_SELECTOR) : null;
                    if (playable) {
                        // Hold the click — replay it once frontend.js is in place.
                        e.preventDefault();
                        e.stopPropagation();
                        pendingClickTarget = playable;
                    }
                }
                loadScripts();
            }

            function addInteractionListeners() {
                for (var i = 0; i < interactionEvents.length; i++) {
                    var ev = interactionEvents[i];
                    var passive = (ev === 'click' || ev === 'touchstart') ? false : true;
                    window.addEventListener(ev, interactionHandler, { capture: true, passive: passive });
                }
            }

            function removeInteractionListeners() {
                for (var i = 0; i < interactionEvents.length; i++) {
                    window.removeEventListener(interactionEvents[i], interactionHandler, { capture: true });
                }
            }

            function hasVideoBlocks() {
                return document.querySelector('.ekwa-video-wrapper, .ekv-wrapper, .ekwa-video-player, .glightbox');
            }

            if (!hasVideoBlocks()) {
                return;
            }

            if (config.deferUntilInteraction) {
                addInteractionListeners();
            } else {
                loadScripts();
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
        $sanitize_bool = function($v) { return $v ? 1 : 0; };

        register_setting('ekwa_video_block_settings', 'ekwa_video_youtube_api_key', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ));
        register_setting('ekwa_video_block_settings', 'ekwa_video_ga4_tracking', array(
            'sanitize_callback' => $sanitize_bool,
            'default' => 0,
        ));
        register_setting('ekwa_video_block_settings', 'ekwa_video_lazysizes_enabled', array(
            'sanitize_callback' => $sanitize_bool,
            'default' => 0,
        ));
        register_setting('ekwa_video_block_settings', 'ekwa_video_lazysizes_load_script', array(
            'sanitize_callback' => $sanitize_bool,
            'default' => 0,
        ));
        register_setting('ekwa_video_block_settings', 'ekwa_video_defer_until_interaction', array(
            'sanitize_callback' => $sanitize_bool,
            'default' => 0,
        ));
        register_setting('ekwa_video_block_settings', 'ekwa_video_inline_frontend_js', array(
            'sanitize_callback' => $sanitize_bool,
            'default' => 0,
        ));
        register_setting('ekwa_video_block_settings', 'ekwa_video_inline_frontend_css', array(
            'sanitize_callback' => $sanitize_bool,
            'default' => 1,
        ));
        register_setting('ekwa_video_block_settings', 'ekwa_video_github_token', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ));

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

        add_settings_section(
            'ekwa_video_block_performance',
            'Performance',
            array($this, 'performance_section_callback'),
            'ekwa-video-block'
        );

        add_settings_field(
            'ekwa_video_lazysizes_enabled',
            'Enable lazysizes lazyloading',
            array($this, 'lazysizes_enabled_callback'),
            'ekwa-video-block',
            'ekwa_video_block_performance'
        );

        add_settings_field(
            'ekwa_video_lazysizes_load_script',
            'Load lazysizes script from plugin',
            array($this, 'lazysizes_load_script_callback'),
            'ekwa-video-block',
            'ekwa_video_block_performance'
        );

        add_settings_field(
            'ekwa_video_defer_until_interaction',
            'Defer scripts until user interaction',
            array($this, 'defer_until_interaction_callback'),
            'ekwa-video-block',
            'ekwa_video_block_performance'
        );

        add_settings_field(
            'ekwa_video_inline_frontend_js',
            'Inline frontend.js',
            array($this, 'inline_frontend_js_callback'),
            'ekwa-video-block',
            'ekwa_video_block_performance'
        );

        add_settings_field(
            'ekwa_video_inline_frontend_css',
            'Inline frontend.css',
            array($this, 'inline_frontend_css_callback'),
            'ekwa-video-block',
            'ekwa_video_block_performance'
        );

        add_settings_section(
            'ekwa_video_block_updates',
            'Plugin Updates',
            array($this, 'updates_section_callback'),
            'ekwa-video-block'
        );

        add_settings_field(
            'ekwa_video_github_token',
            'GitHub Access Token',
            array($this, 'github_token_callback'),
            'ekwa-video-block',
            'ekwa_video_block_updates'
        );
    }

    /**
     * Plugin Updates section callback
     */
    public function updates_section_callback() {
        echo '<p>This plugin checks GitHub for updates. Unauthenticated requests are limited by GitHub to <strong>60 per hour per server IP</strong>. Adding a personal access token raises this to <strong>5,000 per hour</strong> and prevents the update check from being rate-limited.</p>';
        echo '<p><strong>How to create a token:</strong></p>';
        echo '<ol>';
        echo '<li>Go to <a href="https://github.com/settings/personal-access-tokens/new" target="_blank">GitHub &rarr; Settings &rarr; Developer settings &rarr; Fine-grained tokens</a></li>';
        echo '<li>Give it read-only access to <strong>Contents</strong> for the plugin repository</li>';
        echo '<li>Copy the token and paste it below</li>';
        echo '</ol>';
        echo '<p><em>For better security you can instead define <code>EKWA_VIDEO_GITHUB_TOKEN</code> in <code>wp-config.php</code>; if defined, it overrides this field.</em></p>';
    }

    /**
     * GitHub token field callback
     */
    public function github_token_callback() {
        $defined = defined('EKWA_VIDEO_GITHUB_TOKEN') && EKWA_VIDEO_GITHUB_TOKEN;
        if ($defined) {
            echo '<p><strong>' . esc_html__('A token is defined in wp-config.php and is being used.', 'ekwa-video-block') . '</strong> ';
            echo esc_html__('Remove the constant from wp-config.php to manage the token here instead.', 'ekwa-video-block') . '</p>';
            return;
        }

        $token = get_option('ekwa_video_github_token', '');
        // Show a masked placeholder rather than echoing the stored token.
        $display = $token ? str_repeat('•', 8) . substr($token, -4) : '';
        echo '<input type="password" id="ekwa_video_github_token" name="ekwa_video_github_token" value="' . esc_attr($token) . '" class="regular-text" autocomplete="off" placeholder="' . esc_attr($display) . '" />';
        echo '<p class="description">' . esc_html__('Optional. Leave empty to use unauthenticated checks (60/hour limit).', 'ekwa-video-block') . '</p>';
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
     * Performance section callback
     */
    public function performance_section_callback() {
        echo '<p>Control how thumbnail images are lazy-loaded on the frontend.</p>';
        echo '<p>The plugin does <strong>not</strong> bundle the <a href="https://github.com/aFarkas/lazysizes" target="_blank" rel="noopener">lazysizes</a> library. Enable the option below only if your theme (or another plugin) already loads lazysizes.</p>';
    }

    /**
     * Lazysizes field callback
     */
    public function lazysizes_enabled_callback() {
        $enabled = get_option('ekwa_video_lazysizes_enabled', false);
        echo '<input type="hidden" name="ekwa_video_lazysizes_enabled" value="0" />';
        echo '<input type="checkbox" id="ekwa_video_lazysizes_enabled" name="ekwa_video_lazysizes_enabled" value="1" ' . checked(1, $enabled, false) . ' />';
        echo '<label for="ekwa_video_lazysizes_enabled">Output lazysizes-compatible markup for video thumbnails</label>';
        echo '<p class="description">When enabled, thumbnail images use <code>data-src</code> and the <code>lazyload</code> class so lazysizes can handle them. Leave disabled to use the browser&rsquo;s native <code>loading="lazy"</code> attribute instead. (Auto-enabled when the active theme is &ldquo;ekwa&rdquo;.)</p>';
    }

    /**
     * Lazysizes "load script from plugin" field callback
     */
    public function lazysizes_load_script_callback() {
        $enabled = get_option('ekwa_video_lazysizes_load_script', false);
        echo '<input type="hidden" name="ekwa_video_lazysizes_load_script" value="0" />';
        echo '<input type="checkbox" id="ekwa_video_lazysizes_load_script" name="ekwa_video_lazysizes_load_script" value="1" ' . checked(1, $enabled, false) . ' />';
        echo '<label for="ekwa_video_lazysizes_load_script">Load the bundled lazysizes script on the frontend</label>';
        echo '<p class="description">Only enable this if your theme does <strong>not</strong> already load lazysizes. The plugin ships lazysizes v5.3.2 locally (no CDN). The script is only enqueued on pages that contain a video block.</p>';
    }

    /**
     * "Defer scripts until interaction" field callback
     */
    public function defer_until_interaction_callback() {
        $enabled = get_option('ekwa_video_defer_until_interaction', false);
        echo '<input type="hidden" name="ekwa_video_defer_until_interaction" value="0" />';
        echo '<input type="checkbox" id="ekwa_video_defer_until_interaction" name="ekwa_video_defer_until_interaction" value="1" ' . checked(1, $enabled, false) . ' />';
        echo '<label for="ekwa_video_defer_until_interaction">Wait for scroll / mousemove / touch / click / keypress before loading frontend.js and the YouTube iframe API</label>';
        echo '<p class="description">Reduces initial requests on pages with videos. The first click on a thumbnail before any other interaction is captured and replayed once the script finishes loading, so the play action is not lost.</p>';
    }

    /**
     * "Inline frontend.js" field callback
     */
    public function inline_frontend_js_callback() {
        $enabled = get_option('ekwa_video_inline_frontend_js', false);
        echo '<input type="hidden" name="ekwa_video_inline_frontend_js" value="0" />';
        echo '<input type="checkbox" id="ekwa_video_inline_frontend_js" name="ekwa_video_inline_frontend_js" value="1" ' . checked(1, $enabled, false) . ' />';
        echo '<label for="ekwa_video_inline_frontend_js">Embed frontend.js inside the page instead of loading it as a separate file</label>';
        echo '<p class="description">Removes one HTTP request. Adds ~60&nbsp;KB (uncompressed; ~15&nbsp;KB gzipped) to the page HTML. Combine with &ldquo;Defer scripts until user interaction&rdquo; for best results &mdash; the inlined code only runs after the first interaction.</p>';
    }

    /**
     * "Inline frontend.css" field callback
     */
    public function inline_frontend_css_callback() {
        $enabled = get_option('ekwa_video_inline_frontend_css', true);
        echo '<input type="hidden" name="ekwa_video_inline_frontend_css" value="0" />';
        echo '<input type="checkbox" id="ekwa_video_inline_frontend_css" name="ekwa_video_inline_frontend_css" value="1" ' . checked(1, $enabled, false) . ' />';
        echo '<label for="ekwa_video_inline_frontend_css">Embed frontend.css inside the page instead of loading it as a separate file</label>';
        echo '<p class="description">Recommended (default on). Inlines the critical CSS in the page head to avoid render-blocking and layout shift. Turn this off to load <code>frontend.css</code> as a normal stylesheet (cacheable separate file).</p>';
    }

    /**
     * Show a notice if the GitHub API update check is currently rate-limited.
     */
    public function render_rate_limit_notice() {
        $state = get_option('ekwa_video_gh_rate_limited', false);
        if (empty($state) || empty($state['reset'])) {
            return;
        }

        $reset = (int) $state['reset'];

        // Limit has reset — clear the stored state and stop showing the notice.
        if ($reset <= time()) {
            delete_option('ekwa_video_gh_rate_limited');
            return;
        }

        $human = human_time_diff(time(), $reset);
        $when  = wp_date(get_option('time_format', 'H:i'), $reset);
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('Plugin update check is rate-limited by GitHub.', 'ekwa-video-block'); ?></strong>
                <?php
                printf(
                    /* translators: 1: relative time, 2: clock time */
                    esc_html__('The hourly request limit is exhausted. It resets in about %1$s (around %2$s). Update checks will resume automatically after that.', 'ekwa-video-block'),
                    esc_html($human),
                    esc_html($when)
                );
                ?>
            </p>
            <?php if (empty($state['authenticated'])): ?>
                <p><?php esc_html_e('Tip: add a GitHub Access Token under "Plugin Updates" below to raise the limit from 60 to 5,000 requests per hour.', 'ekwa-video-block'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Ekwa Video Block Settings', 'ekwa-video-block'); ?></h1>

            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php esc_html_e('Settings saved successfully!', 'ekwa-video-block'); ?></strong></p>
                </div>
            <?php endif; ?>

            <?php $this->render_rate_limit_notice(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('ekwa_video_block_settings');
                do_settings_sections('ekwa-video-block');
                submit_button();
                ?>
            </form>

            <div class="card" style="margin-top: 20px;">
                <h2><?php esc_html_e('Test Your Settings', 'ekwa-video-block'); ?></h2>
                <p><?php esc_html_e('You can test if your YouTube API key is working by trying these sample URLs in the block editor:', 'ekwa-video-block'); ?></p>
                <ul>
                    <li><strong>YouTube:</strong> https://www.youtube.com/watch?v=dQw4w9WgXcQ</li>
                    <li><strong>Vimeo:</strong> https://vimeo.com/148751763</li>
                </ul>
                <p><?php esc_html_e('If the API key is working, you should see the video title, description, and duration automatically populate in the block editor.', 'ekwa-video-block'); ?></p>
            </div>

            <?php
            $ga4_tracking = get_option('ekwa_video_ga4_tracking', false);
            if ($ga4_tracking):
            ?>
            <div class="card" style="margin-top: 20px;">
                <h2><?php esc_html_e('GA4 Video Tracking', 'ekwa-video-block'); ?></h2>
                <p><strong><?php esc_html_e('GA4 video tracking is enabled.', 'ekwa-video-block'); ?></strong></p>
                <p><?php esc_html_e('The following events will be tracked:', 'ekwa-video-block'); ?></p>
                <ul>
                    <li><strong>video_start:</strong> <?php esc_html_e('When a video begins playing', 'ekwa-video-block'); ?></li>
                    <li><strong>video_progress:</strong> <?php esc_html_e('At 25%, 50%, and 75% completion milestones', 'ekwa-video-block'); ?></li>
                    <li><strong>video_pause:</strong> <?php esc_html_e('When a video is paused', 'ekwa-video-block'); ?></li>
                    <li><strong>video_complete:</strong> <?php esc_html_e('When a video finishes playing', 'ekwa-video-block'); ?></li>
                </ul>
                <p><em><?php esc_html_e('Make sure Google Analytics 4 (gtag) is properly installed on your website.', 'ekwa-video-block'); ?></em></p>
            </div>
            <?php else: ?>
            <div class="card" style="margin-top: 20px;">
                <h2><?php esc_html_e('GA4 Video Tracking', 'ekwa-video-block'); ?></h2>
                <p><strong><?php esc_html_e('GA4 video tracking is disabled.', 'ekwa-video-block'); ?></strong></p>
                <p><?php esc_html_e('Enable it above to start tracking video engagement events in Google Analytics 4.', 'ekwa-video-block'); ?></p>
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

// AJAX handler for fetching a YouTube transcript (editor-only — no nopriv).
add_action('wp_ajax_ekwa_fetch_youtube_transcript', 'ekwa_fetch_youtube_transcript_ajax');

function ekwa_fetch_youtube_transcript_ajax() {
    check_ajax_referer('ekwa_video_block_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('code' => 'forbidden', 'message' => __('Insufficient permissions.', 'ekwa-video-block')));
        return;
    }

    $video_url = isset($_POST['video_url']) ? esc_url_raw(wp_unslash($_POST['video_url'])) : '';
    if (empty($video_url)) {
        wp_send_json_error(array('code' => 'invalid_url', 'message' => __('Missing video URL.', 'ekwa-video-block')));
        return;
    }

    $plugin = new EkwaVideoBlock();
    $info = $plugin->extract_video_info($video_url);

    if (empty($info['video_type']) || empty($info['video_id'])) {
        wp_send_json_error(array('code' => 'invalid_url', 'message' => __('Could not parse video URL.', 'ekwa-video-block')));
        return;
    }

    if ($info['video_type'] !== 'youtube') {
        wp_send_json_error(array('code' => 'not_youtube', 'message' => __('Transcript fetching is only supported for YouTube videos.', 'ekwa-video-block')));
        return;
    }

    $force_refresh = !empty($_POST['force_refresh']);
    $debug = array();
    $result = $plugin->fetch_youtube_transcript($info['video_id'], $force_refresh, $debug);
    if (!$result) {
        // Distinguish "captions tracklist existed but YouTube returned empty
        // bodies for every format" (locked content) from "no tracks at all".
        $tracks_existed = (isset($debug['watch_track_count']) && $debug['watch_track_count'] > 0)
            || (isset($debug['innertube_track_count']) && $debug['innertube_track_count'] > 0);
        if ($tracks_existed) {
            $code = 'captions_locked';
            $message = __('This video lists captions but YouTube is not serving them through its public API. They are visible only inside the YouTube player itself, so the transcript cannot be fetched automatically — please paste it in manually.', 'ekwa-video-block');
        } else {
            $code = 'no_captions';
            $message = __('No captions available for this video.', 'ekwa-video-block');
        }
        wp_send_json_error(array(
            'code'    => $code,
            'message' => $message,
            'debug'   => $debug,
        ));
        return;
    }

    wp_send_json_success(array(
        'transcript' => $result['text'],
        'source'     => $result['source'],
    ));
}

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
