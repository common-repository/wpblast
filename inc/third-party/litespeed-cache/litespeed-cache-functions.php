<?php

defined('ABSPATH') || exit;

include_once ABSPATH . 'wp-admin/includes/plugin.php';

if (
    is_plugin_active('litespeed-cache/litespeed-cache.php')
) {

    add_action('after_setup_theme', function() {
        global $smartfire_wpblast_config, $smartfire_wpblast_settings;

        if(isset($smartfire_wpblast_config) && class_exists("Smartfire\Wordpress\WPBlast\Bootstrap") && Smartfire\Wordpress\WPBlast\Bootstrap::should_blast()) {
            do_action( 'litespeed_tag_add', 'wpblast_crawler' );
            if(isset($smartfire_wpblast_settings)) {
                $expire = $smartfire_wpblast_settings->getCacheExpirationCrawlers();
                do_action( 'litespeed_control_set_ttl', $expire );
            }
        }
    });

    add_action('template_redirect', function() {
        global $smartfire_wpblast_settings;
        // Check for CrawlerCacheGen, if it's our crawler, bypass cache litespeed to force trigger php code
        // As there is a Vary header, we should have our own cache item and therefore not being cache
        if(isset($smartfire_wpblast_settings) && isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));
            if($smartfire_wpblast_settings->getCrawlerCacheGen() === $ua) {
                do_action( 'litespeed_control_set_nocache', 'nocache for wpblast crawler. This allow auto cache generation.' );
            }
        }
    }, 99999);

    add_action('wpblast_actions_before_start', 'wpblast_actions_before_start_litespeed');
    function wpblast_actions_before_start_litespeed()
    {
        // Tag the request as wpblast tag so that we can purge every wpblast cache on litespeed side https://docs.litespeedtech.com/lscache/lscwp/api/
        do_action( 'litespeed_tag_add', 'wpblast_content' );
    }

    function wpblast_litespeed_clear_cache()
    {
        // Clear all the page cache from litespeed
        do_action( 'litespeed_purge_all' );
    }
    add_action('wpblast_purge_cache_third_party', 'wpblast_litespeed_clear_cache');

    function wpblast_litespeed_activate()
    {
        // Clear all cache so that litespeed can have new tags and rules to manage its cache
        wpblast_litespeed_clear_cache();
        // Display admin notice for openlitespeed
        if (LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_OLS') {
            wpblast_display_warning(__('If using OpenLiteSpeed, the server must be restarted once for the changes to take effect.', 'litespeed-cache'));
        }
    }
    add_action('wpblast_plugin_updated', 'wpblast_litespeed_activate');

    function wpblast_litespeed_deactivated()
    {
        // Clear all cache so that litespeed can get new cache to serve
        wpblast_litespeed_clear_cache();
    }
    add_action('wpblast_deactivated', 'wpblast_litespeed_deactivated');

    function wpblast_litespeed_updated_plan()
    {
        // Clear all cache so that litespeed can get new cache to serve
        wpblast_litespeed_clear_cache();
    }
    add_action('wpblast_updated_plan', 'wpblast_litespeed_updated_plan');

    function wpblast_litespeed_clear_cache_wpblast()
    {
        // Clear the page cache from litespeed with tag wpblast_crawler
        do_action( 'litespeed_purge', 'wpblast_crawler' );
    }
    add_action('wpblast_purge_cache', 'wpblast_litespeed_clear_cache_wpblast'); // in case of purge cache action, purge also tag wpblast in litespeed
    add_action('wpblast_purge_sitemap', 'wpblast_litespeed_clear_cache_wpblast'); // in case of purge cache action, purge also tag wpblast in litespeed

    add_action('wpblast_updated_options', 'wpblast_litespeed_clear_cache'); // changes in option will trigger reset of whole cache has a lot of things could have changed
}
else if( // in case we detect a litespeed but with no plugin litespeed-cache enabled
    isset( $_SERVER['HTTP_X_LSCACHE'] )
    || isset( $_SERVER['LSWS_EDITION'] )
    || (isset( $_SERVER['SERVER_SOFTWARE'] ) && $_SERVER['SERVER_SOFTWARE'] == 'LiteSpeed')
    || isset( $_SERVER['X-LSCACHE'] )
    || isset( $_SERVER[ 'LSCACHE_VARY_VALUE' ]) // necessary?
    || isset( $_SERVER[ 'HTTP_X_LSCACHE_VARY_VALUE' ] ) // necessary?
    || isset( $_SERVER[ 'ESI_REFERER' ] )
    || isset( $_SERVER[ 'LSCACHE_VARY_COOKIE' ] ) // necessary?
    || isset( $_SERVER[ 'HTTP_X_LSCACHE_VARY_COOKIE' ] ) // necessary?
) {
    // This is a fallback for hosting provider like hostinger or cloudflare that uses LiteSpeed tech undercover

}