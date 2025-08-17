<?php
/**
 * Plugin Name: Ekwa Video Block
 * Plugin URI: https://www.ekwa.com
 * Description: A Gutenberg block for embedding YouTube and Vimeo videos with lazy loading and custom thumbnails
 * Version: 1.0.0
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

// Define plugin constants
define('EKWA_VIDEO_BLOCK_VERSION', '1.0.0');
define('EKWA_VIDEO_BLOCK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EKWA_VIDEO_BLOCK_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Main plugin class
 */
class EkwaVideoBlock {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
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
            'class_name' => $class_name,
        );

        // Add custom thumbnail if exists
        if (!empty($custom_thumbnail) && isset($custom_thumbnail['url'])) {
            $shortcode_atts['custom_thumbnail'] = $custom_thumbnail['url'];
            $shortcode_atts['custom_thumbnail_alt'] = isset($custom_thumbnail['alt']) ? $custom_thumbnail['alt'] : '';
        }

        // Render using shortcode
        return $this->render_video_shortcode($shortcode_atts);
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
            'class_name' => '',
        ), $atts, 'ekwa_video');

        // If no video URL, return empty
        if (empty($attributes['video_url'])) {
            return '';
        }

        // Extract video information if not provided
        if (empty($attributes['video_type']) || empty($attributes['video_id'])) {
            $video_info = $this->extract_video_info($attributes['video_url']);
            $attributes = array_merge($attributes, $video_info);
        }

        // Get video metadata if not provided
        if (empty($attributes['video_title']) || empty($attributes['thumbnail_url'])) {
            $metadata = $this->get_video_metadata($attributes['video_type'], $attributes['video_id']);
            if ($metadata) {
                $attributes = array_merge($attributes, $metadata);
            }
        }

        // Use custom thumbnail if provided, otherwise use video thumbnail
        $thumbnail_url = !empty($attributes['custom_thumbnail']) ? $attributes['custom_thumbnail'] : $attributes['thumbnail_url'];
        $thumbnail_alt = !empty($attributes['custom_thumbnail_alt']) ? $attributes['custom_thumbnail_alt'] : $attributes['video_title'];

        // Generate unique ID for this video instance
        $unique_id = 'ekwa-video-' . md5($attributes['video_url'] . time());

        // Build CSS classes
        $css_classes = array('ekwa-video-wrapper');
        if (!empty($attributes['class_name'])) {
            $css_classes[] = $attributes['class_name'];
        }
        $css_classes[] = 'ekwa-video-' . $attributes['video_type'];

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
                <div class="player ekwa-video-player" data-id="<?php echo esc_attr($attributes['video_id']); ?>" data-provider="<?php echo esc_attr($attributes['video_type']); ?>" data-video-type="<?php echo esc_attr($attributes['video_type']); ?>" data-video-id="<?php echo esc_attr($attributes['video_id']); ?>" data-autoplay="<?php echo esc_attr($attributes['autoplay']); ?>">
                    <?php if (!empty($thumbnail_url)): ?>
                        <div class="ekwa-video-thumbnail" data-embed-url="<?php echo esc_attr($attributes['embed_url']); ?>">
                            <img decoding="async" class="image-responsive ls-is-cached lazyloaded ekwa-video-thumb-img" src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($thumbnail_alt); ?>">
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
            </div>

            <?php if (!empty($attributes['video_description'])): ?>
                <meta itemprop="description" content="<?php echo esc_attr($attributes['video_description']); ?>">
            <?php endif; ?>

            <?php if ($attributes['show_description'] === 'true' && !empty($attributes['video_description'])): ?>
                <div class="ekwa-video-description">
                    <p><?php echo esc_html($attributes['video_description']); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($attributes['show_transcript'] === 'true' && !empty($attributes['transcript'])): ?>
                <div class="video_transcript_btn">
                    <a data-target="#transcript-<?php echo esc_attr($attributes['video_id']); ?>" class="btn-standard btn-vdo-trans btn-transcript ekv-button" href="javascript:void(0);">
                        Video Transcript
                        <span class="trans-icon"></span>
                    </a>
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
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'ekwa-video-block-frontend',
            EKWA_VIDEO_BLOCK_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            EKWA_VIDEO_BLOCK_VERSION
        );

        wp_enqueue_script(
            'ekwa-video-block-frontend',
            EKWA_VIDEO_BLOCK_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            EKWA_VIDEO_BLOCK_VERSION,
            true
        );
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
        add_options_page(
            'Ekwa Video Block Settings',
            'Ekwa Video Block',
            'manage_options',
            'ekwa-video-block',
            array($this, 'admin_page')
        );
    }

    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('ekwa_video_block_settings', 'ekwa_video_youtube_api_key');

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
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Ekwa Video Block Settings</h1>
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
        </div>
        <?php
    }
}

// Initialize the plugin
new EkwaVideoBlock();

// AJAX handler for fetching video metadata
add_action('wp_ajax_ekwa_get_video_metadata', 'ekwa_get_video_metadata_ajax');
add_action('wp_ajax_nopriv_ekwa_get_video_metadata', 'ekwa_get_video_metadata_ajax');

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
