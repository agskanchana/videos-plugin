<?php
/**
 * Uninstall handler for Ekwa Video Block.
 * Deletes all plugin options and cached metadata transients.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$options = array(
    'ekwa_video_youtube_api_key',
    'ekwa_video_ga4_tracking',
    'ekwa_video_lazysizes_enabled',
    'ekwa_video_lazysizes_load_script',
    'ekwa_video_defer_until_interaction',
    'ekwa_video_inline_frontend_js',
);

foreach ($options as $option) {
    delete_option($option);
    delete_site_option($option);
}

global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_ekwa_video_meta_%'
        OR option_name LIKE '_transient_timeout_ekwa_video_meta_%'"
);
