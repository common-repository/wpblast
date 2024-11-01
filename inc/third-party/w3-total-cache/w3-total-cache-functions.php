<?php

defined('ABSPATH') || exit;

include_once ABSPATH . 'wp-admin/includes/plugin.php';

if (is_plugin_active('w3-total-cache/w3-total-cache.php')) {

    add_action('wpblast_actions_before_start', 'wpblast_actions_before_start_w3tc');
    function wpblast_actions_before_start_w3tc()
    {
        // Avoid W3TC https://wordpress.org/support/topic/disable-caching-for-a-specific-page/
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
    }

    /**
     * Avoid W3TC to cache page.
     */
    function wpblast_w3tc_can_cache($enable)
    {
        return false;
    }
    add_filter('w3tc_can_cache', 'wpblast_w3tc_can_cache');

    function wpblast_w3tc_clear_cache()
    {
        // Clear all the page cache to avoid having w3tc serve static cache files
        do_action('w3tc_flush_all');
        do_action('w3tc_flush_group');
        do_action('w3tc_flush_url');
        do_action('w3_pgcache_cleanup');
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
    }

    add_action('wpblast_plugin_updated', 'wpblast_w3tc_clear_cache');
    add_action('wpblast_purge_cache_third_party', 'wpblast_w3tc_clear_cache');
    add_action('wpblast_deactivated', 'wpblast_w3tc_clear_cache');
    add_action('wpblast_updated_crawler_list', 'wpblast_w3tc_clear_cache');
    add_action('wpblast_updated_options', 'wpblast_w3tc_clear_cache');
}
