<?php

defined('ABSPATH') || exit;

include_once ABSPATH . 'wp-admin/includes/plugin.php';

if (is_plugin_active('hummingbird-performance/wp-hummingbird.php')) {

    add_action('wpblast_actions_before_start', 'wpblast_actions_before_start_hummingbird');
    function wpblast_actions_before_start_hummingbird()
    {
        // Avoid Hummingbird Cache https://wpmudev.com/forums/topic/hummingbird-definedonotcachepage-true-for-specific-page/
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
    }

    /**
     * Avoid Hummingbird to cache the request. We are forced to disable static caching as hummingbird doesn't allow to have a way to decide whether or not serve cache based on user agents list
     */
    function wpblast_hummingbird_should_cache_request_pre($shouldCache)
    {
        return false;
    }
    add_filter('wphb_should_cache_request_pre', 'wpblast_hummingbird_should_cache_request_pre');

    function wpblast_wphb_clear_cache()
    {
        // Clear all the page cache to avoid having hummingbird serve static cache files
        do_action('wphb_clear_page_cache');
    }

    add_action('wpblast_plugin_updated', 'wpblast_wphb_clear_cache');
    add_action('wpblast_deactivated', 'wpblast_wphb_clear_cache');
    add_action('wpblast_purge_cache_third_party', 'wpblast_wphb_clear_cache');
    add_action('wpblast_updated_crawler_list', 'wpblast_wphb_clear_cache');
    add_action('wpblast_updated_options', 'wpblast_wphb_clear_cache');
}
