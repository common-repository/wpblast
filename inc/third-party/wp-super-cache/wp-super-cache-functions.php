<?php

defined('ABSPATH') || exit;

include_once ABSPATH . 'wp-admin/includes/plugin.php';

if (is_plugin_active('wp-super-cache/wp-cache.php')) {

    // We are forced to use template_redirect as the plugin would take precedence otherwise
    add_action('template_redirect', 'wpblast_actions_before_start_wpsc');
    function wpblast_actions_before_start_wpsc()
    {
        // Avoid WPSC to cache the page
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
    }

    function wpblast_wpsc_clear_cache()
    {
        global $file_prefix;
        // Clear all the page cache to avoid having plugin serve static cache files
        if (function_exists('wp_cache_clean_cache') && isset($file_prefix)) {
            wp_cache_clean_cache($file_prefix, true);
        }
    }

    add_action('wpblast_plugin_updated', 'wpblast_wpsc_clear_cache');
    add_action('wpblast_deactivated', 'wpblast_wpsc_clear_cache');
    add_action('wpblast_purge_cache_third_party', 'wpblast_wpsc_clear_cache');
    add_action('wpblast_updated_crawler_list', 'wpblast_wpsc_clear_cache');
    add_action('wpblast_updated_options', 'wpblast_wpsc_clear_cache');
}
