<?php

defined('ABSPATH') || exit;

include_once ABSPATH . 'wp-admin/includes/plugin.php';

if (is_plugin_active('nitropack/main.php')) {

    // In case nitropack had disabled advanced-cache, we still need to add something so that we can't avoid cache being served
    // This is due to the fact that nitropack doesn't use filter and that our filters are registered after they served the cache due to alphabetical order of the plugin names
    // Only way of adding the filter before the plugin is executed is by using the advanced-cache or a mu-plugin
    // Use mu-plugins instead of advanced-cache to avoid clash between multiple plugins and to avoid having to manage the WP_CACHE option in wp-config.php
    wpblast_require_mu_plugin([
        'slug' => 'wpblast-nitropack-addon',
        'name' => 'WP Blast Addon - Nitropack',
        'code' => [
            'deactivationCondition' => "!is_plugin_active('nitropack/main.php')",
            'body' => '
// Even if advanced-cache is not used, nitropack needs this filter added prior its execution
// This will be needed for both usecase: with or without advanced-cache with nitropack
$wpblast_globals_file = "' . __DIR__ . '/../../../globals.php' . '";
$wpblast_abspath = "' . ABSPATH . '";

// Fail proof check that the WP installation hasnt been moved to another server or other folder
// In case path is wrong, the addon will not be detected and therefore will be installed again
if (file_exists($wpblast_globals_file) && ABSPATH == $wpblast_abspath) {
    require $wpblast_globals_file;
    function wpblast_nitropack_can_serve_cache() {
        return !Smartfire\Wordpress\WPBlast\Bootstrap::should_blast();
    }
    add_filter("nitropack_can_serve_cache", "wpblast_nitropack_can_serve_cache");
}
else {
    // will recreate the mu-plugin addon in case of error
    if(!defined("WPBLAST_MU_PLUGIN_ERROR")) {
        define("WPBLAST_MU_PLUGIN_ERROR", true);
        return;
    }
}',
        ],
    ]);

    add_action('wpblast_actions_before_start', 'wpblast_actions_before_start_nitropack');
    function wpblast_actions_before_start_nitropack()
    {
        // Indicates to nitropack to not cache the result
        // This may have to be tested again in different scenarios
        if (!isset($_SERVER['HTTP_X_NITROPACK_REQUEST'])) {
            $_SERVER['HTTP_X_NITROPACK_REQUEST'] = 1;
        }
        header('X-Nitro-Disabled: 1');
    }

    // we don't do anything if nitropack advanced cache is not active
    if (function_exists('nitropack_has_advanced_cache') && nitropack_has_advanced_cache()) {
        wpblast_require_advanced_cache([
            'slug' => 'wpblast-nitropack-addon',
            'delimiter' => 'if (defined("NITROPACK_VERSION") && defined("NITROPACK_ADVANCED_CACHE_VERSION") && NITROPACK_VERSION == NITROPACK_ADVANCED_CACHE_VERSION && nitropack_is_dropin_cache_allowed()) {',
        ]);
    } else if (function_exists('nitropack_has_advanced_cache') && !nitropack_has_advanced_cache()) {
        // force uninstall of advanced-cache even if cache plugin is being let active
        wpblast_uninstall_advanced_cache([
            'slug' => 'wpblast-nitropack-addon',
        ]);
    }

    // No need to refresh the cache of nitropack at activation as we'll skip it only when necessary with advanced-cache
    function wpblast_nitro_clear_cache()
    {
        do_action('nitropack_execute_purge_all');
    }
    add_action('wpblast_purge_cache_third_party', 'wpblast_nitro_clear_cache');

    add_action('wpblast_plugin_updated', function($oldVersion, $newVersion) {
        // Compatibility upgrader before we used markers to replace our addons
        // This will remove advanced cache, so that nitropack can add it again and we'll add then our addon
        try {
            if ($oldVersion === false || $oldVersion === '1.8.4') {
                wpblast_clear_advanced_cache_file();
            }
        }
        catch (\Throwable $e) {} // fail proof
        catch (\Exception $e) {} // fail proof
    }, 10, 2);
}
