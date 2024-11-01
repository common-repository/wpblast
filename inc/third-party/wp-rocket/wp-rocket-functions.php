<?php

defined('ABSPATH') || exit;

include_once ABSPATH . 'wp-admin/includes/plugin.php';

if (is_plugin_active('wp-rocket/wp-rocket.php')) {

    add_action('wpblast_actions_before_start', 'wpblast_actions_before_start_wprocket');
    function wpblast_actions_before_start_wprocket()
    {
        // Avoid WP Rocket Cache https://fr.docs.wp-rocket.me/article/206-outrepasser-donotcachepage-via-un-filtre
        // Avoid having WP Blast result stored in WP Rocket cache
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
    }

    function wpblast_rocket_advanced_cache_file($content)
    {
        return apply_filters('wpblast_advanced_cache_content', $content, "define( 'WP_ROCKET_ADVANCED_CACHE', true );", 'wpblast-wprocket-addon');
    }
    // As we are hooked on wprocket advanced_cache content, wprocket will be in charge of checking for error in filesystem in case we cannot write the advanced-cache.php file
    // in case of error in wprocket advanced cache, it will be recreated and our addon will be added again
    // in case of upgrade, they will recreate advanced-cache.php file and call again this function
    add_filter('rocket_advanced_cache_file', 'wpblast_rocket_advanced_cache_file');


    function wpblast_advanced_cache_content_error()
    {
        return "
        // Trigger constant of wprocket to indicate a problem with advanced-cache
        if(!defined('WP_ROCKET_ADVANCED_CACHE_PROBLEM')) {
            define( 'WP_ROCKET_ADVANCED_CACHE_PROBLEM', true );
            return;
        }";
    }
    add_filter('wpblast_advanced_cache_content_error', 'wpblast_advanced_cache_content_error');

    /**
     * Avoid WP Rocket to handle the request for these user agents
     */
    function wpblast_rocket_user_agent_reject($ua)
    {
        $wpblastCrawlers = apply_filters('wpblast_crawlers_list', []);
        $wpblastCrawlers = array_map(function ($value) {
            if (isset($value['pattern'])) {
                if (strpos($value['pattern'], '^') !== 0) {
                    return '.*' . $value['pattern'];
                } else {
                    return $value['pattern'];
                }
            } else {
                return '';
            }
        }, $wpblastCrawlers);
        $wpblastCrawlers = array_filter($wpblastCrawlers, function ($value) {
            return $value !== '';
        });
        return array_merge($ua, $wpblastCrawlers);
    }
    add_filter('rocket_cache_reject_ua', 'wpblast_rocket_user_agent_reject');

    function wpblast_rocket_refresh()
    {
        if (function_exists('flush_rocket_htaccess')) {
            flush_rocket_htaccess();
        }
        if (function_exists('rocket_generate_config_file')) {
            rocket_generate_config_file();
        }
        if (function_exists('rocket_generate_advanced_cache_file')) {
            rocket_generate_advanced_cache_file();
        }
    }

    add_action('wpblast_plugin_updated', function () {
        add_action('init', 'wpblast_rocket_refresh');
    });
    // advanced-cache should be remove at the start of the deactivation to prevent concurrent requests bugs
    add_action('wpblast_deactivate', function () {
        remove_filter('rocket_advanced_cache_file', 'wpblast_rocket_advanced_cache_file');
        remove_filter('rocket_cache_reject_ua', 'wpblast_rocket_user_agent_reject');
        wpblast_rocket_refresh();
    });
    // should clean afterward to clean what could have been added during uninstallation process
    add_action('wpblast_deactivated', function () {
        remove_filter('rocket_advanced_cache_file', 'wpblast_rocket_advanced_cache_file');
        remove_filter('rocket_cache_reject_ua', 'wpblast_rocket_user_agent_reject');
        wpblast_rocket_refresh();
    });
    add_action('wpblast_updated_crawler_list', 'wpblast_rocket_refresh');
    add_action('wpblast_updated_options', 'wpblast_rocket_refresh');
}
