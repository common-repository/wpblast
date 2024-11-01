<?php

/**
 * Plugin Name: WP Blast
 * Plugin URI: https://www.wp-blast.com
 * Description: Improve your Wordpress SEO and performance by using dynamic rendering. Prerender your website and generate an easy-to-crawl website.
 * Version: 1.8.6
 * Requires at least: 4.9
 * Requires PHP: 5.6
 * Author: WP Blast
 * License: Apache 2.0
 * License URI: http://www.apache.org/licenses/LICENSE-2.0
 * Text Domain: wpblast
 * Domain Path: /languages
 */

use Smartfire\Wordpress\WPBlast\LinkPrerender;
use Smartfire\Wordpress\WPBlast\PageRender;
use Smartfire\Wordpress\WPBlast\Settings;

define('WPBLAST_DB_VERSION', '1.2.1'); // This is used to upgrade database scheme or force cleanup caches and new crawl
define('WPBLAST_PLUGIN_VERSION', '1.8.6');

require 'globals.php';

require_once 'inc/generate-config-functions.php';
require_once 'inc/roles-functions.php';
require_once 'inc/rest-functions.php';
require_once 'inc/mu-plugins-functions.php';
require_once 'inc/advanced-cache-functions.php';
require_once 'inc/third-party/index.php';

add_action('admin_init', [new LinkPrerender($smartfire_wpblast_settings), 'adminInit']);
add_action('init', [$smartfire_wpblast_settings, 'init']);

add_action('template_redirect', function () use ($smartfire_wpblast_settings) {
    global $smartfire_wordpress_wpblast_rendering;
    $smartfire_wordpress_wpblast_rendering = new PageRender($smartfire_wpblast_settings);
    $smartfire_wordpress_wpblast_rendering->start();
});

add_action('smartfire_sapi_init', function () use ($smartfire_wpblast_settings) {
    sapi_on_deployment(
        function ($resources) use ($smartfire_wpblast_settings) {
            try {
                $smartfire_wpblast_settings->init();
                sapi_deployment_log('Purge WPBlast Cache');
                do_action('wpblast_purge_cache');
            } catch (Exception $e) {
                sapi_deployment_log('Failed to cleanup WPBlast Cache', 'ERROR');
            }
        }
    );
}, 99);

add_action('admin_enqueue_scripts', 'wpblast_stylesheet');
function wpblast_stylesheet($page)
{
    if ('toplevel_page_wpblast' !== $page) {
        return;
    }
    // Add css style for settings page
    wp_enqueue_style('prefix-style', plugins_url('css/wpblast.css', __FILE__), [], defined('WPBLAST_PLUGIN_VERSION') ? WPBLAST_PLUGIN_VERSION : false);
}

// Registration hook
function wpblast_register_activation()
{
    // First install DB
    wpblast_install();
    // Then trigger update hook for third-party gen
    try {
        // also used to trigger regeneration of config or third party file updates
        do_action('wpblast_plugin_updated', get_option('wpblast_plugin_version'), WPBLAST_PLUGIN_VERSION);

        // only update option at the end so that it can be trigger again in case of error
        update_option('wpblast_plugin_version', WPBLAST_PLUGIN_VERSION);
    }
    catch (\Throwable $e) {} // fail proof
    catch (\Exception $e) {} // fail proof
}

// Update check
function wpblast_update_check()
{
    // Update DB check
    if (get_option('wpblast_db_version') !== WPBLAST_DB_VERSION) {
        wpblast_install();
    }
    // Update plugin code check
    if (get_option('wpblast_plugin_version') !== WPBLAST_PLUGIN_VERSION) {
        try {
            // also used to trigger regeneration of config or third party file updates
            do_action('wpblast_plugin_updated', get_option('wpblast_plugin_version'), WPBLAST_PLUGIN_VERSION);

            // only update option at the end so that it can be trigger again in case of error
            update_option('wpblast_plugin_version', WPBLAST_PLUGIN_VERSION);
        }
        catch (\Throwable $e) {} // fail proof
        catch (\Exception $e) {} // fail proof
    }
}

function wpblast_clean_upgrade_db()
{
    global $wpdb;
    $wpdb->wpblast_sitemap = $wpdb->prefix . Settings::WPBLAST_SITEMAP_TABLE;
    if (get_option('wpblast_db_version') === '1.0.0') {
        // clean up cache item using transient
        $prefix = 'wpblast_pages';

        $prefixToSearch = $wpdb->esc_like('_transient_' . $prefix);
        $keys   = $wpdb->get_results($wpdb->prepare("SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE %s ORDER BY option_id DESC LIMIT 0, 999999", $prefixToSearch . '%'), ARRAY_A);

        if (is_wp_error($keys)) {
            $keysToDelete = [];
        }

        $keysToDelete = array_map(function ($key) {
            // Remove '_transient_' from the option name.
            return substr($key['option_name'], 11);
        }, $keys);

        foreach ($keysToDelete as $key) {
            delete_transient($key);
        }
    } else {
        // New format
        if (count($wpdb->get_results("SHOW TABLES LIKE '{$wpdb->wpblast_sitemap}'")) > 0) {
            $wpdb->query("UPDATE {$wpdb->wpblast_sitemap} SET cache = '', cacheExpiration = 0, lastGen = NULL");
        }
    }

    // Purge plugin cache
    $prefix = Settings::PLUGIN_CACHE_PREFIX;

    $prefixToSearch = $wpdb->esc_like('_transient_' . $prefix);
    $keys   = $wpdb->get_results($wpdb->prepare("SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE %s ORDER BY option_id DESC LIMIT 0, 999999", $prefixToSearch . '%'), ARRAY_A);

    if (is_wp_error($keys)) {
        $keysToDelete = [];
    }

    $keysToDelete = array_map(function ($key) {
        // Remove '_transient_' from the option name.
        return substr($key['option_name'], 11);
    }, $keys);

    foreach ($keysToDelete as $key) {
        delete_transient($key);
    }
    // End purge plugin cache
}

add_action('plugins_loaded', 'wpblast_update_check');
register_activation_hook(__FILE__, 'wpblast_register_activation');
function wpblast_install()
{
    global $wpdb;

    try {
        wpblast_clean_upgrade_db();

        // Setup transient for first activation
        // This is also used to trigger again a crawl in case of upgrade
        set_transient(Settings::PLUGIN_CACHE_PREFIX . '_firstActivation', time(), apply_filters('wpblast_settings_first_activation_expiration', 60 * 60 * 24 * 30));

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        $table_name_sitemap = $wpdb->prefix . Settings::WPBLAST_SITEMAP_TABLE;
        $wpdb->wpblast_sitemap = $table_name_sitemap;
        $sql_sitemap = "CREATE TABLE $table_name_sitemap (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            active tinyint(4) unsigned NOT NULL,
            hash varchar(255) DEFAULT '' NOT NULL,
            hashVariables longtext NOT NULL,
            url varchar(255) DEFAULT '' NOT NULL,
            dateAdd datetime DEFAULT NOW() NOT NULL,
            dateUpdate datetime DEFAULT NOW() NOT NULL,
            lastRequest datetime DEFAULT NULL NULL,
            nbRequest int(10) unsigned NOT NULL,
            lastGen datetime DEFAULT NULL NULL,
            cacheExpiration int(11) DEFAULT 0 NOT NULL,
            cache longtext NOT NULL,
            scores longtext NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta($sql_sitemap);

        // Add indexes if needed AFTER that the table has been created
        $resultPages = $wpdb->get_row("SHOW INDEXES FROM {$wpdb->wpblast_sitemap} WHERE key_name = 'WPBlast_Sitemap_Hash'", ARRAY_A);
        if (!isset($resultPages)) {
            $wpdb->query("ALTER TABLE {$wpdb->wpblast_sitemap} ADD CONSTRAINT WPBlast_Sitemap_Hash UNIQUE (hash)");
        }

        $resultPages = $wpdb->get_row("SHOW INDEXES FROM {$wpdb->wpblast_sitemap} WHERE key_name = 'WPBlast_Sitemap_CacheExpiration'", ARRAY_A);
        if (!isset($resultPages)) {
            $wpdb->query("ALTER TABLE {$wpdb->wpblast_sitemap} ADD INDEX `WPBlast_Sitemap_CacheExpiration` (`cacheExpiration`)");
        }

        $resultPages = $wpdb->get_row("SHOW INDEXES FROM {$wpdb->wpblast_sitemap} WHERE key_name = 'WPBlast_Sitemap_Url'", ARRAY_A);
        if (!isset($resultPages)) {
            $wpdb->query("ALTER TABLE {$wpdb->wpblast_sitemap} ADD INDEX `WPBlast_Sitemap_Url` (`url`)");
        }

        $resultPages = $wpdb->get_row("SHOW INDEXES FROM {$wpdb->wpblast_sitemap} WHERE key_name = 'WPBlast_Sitemap_Active'", ARRAY_A);
        if (!isset($resultPages)) {
            $wpdb->query("ALTER TABLE {$wpdb->wpblast_sitemap} ADD INDEX `WPBlast_Sitemap_Active` (`active`)");
        }

        // also used to trigger regeneration of config or third party file updates
        do_action('wpblast_activate', get_option('wpblast_db_version'), WPBLAST_DB_VERSION);

        // only update option at the end so that it can be trigger again in case of error
        update_option('wpblast_db_version', WPBLAST_DB_VERSION);
    } catch (\Throwable $e) {
        // hardcode url so that it's as pure as possible

        wp_remote_post('https://www.wp-blast.com/?method=reportInstallError', [
            'body' => serialize([
                'server' => $_SERVER,
                'message' => $e->getMessage(),
                'error' => $e,
            ]),
            'timeout' => '60',
            'method'      => 'POST',
            'data_format' => 'body',
        ]);

        throw $e; // still crash so that WP indicates it to the user
    } catch (\Exception $e) {
        // hardcode url so that it's as pure as possible

        wp_remote_post('https://www.wp-blast.com/?method=reportInstallError', [
            'body' => serialize([
                'server' => $_SERVER,
                'message' => $e->getMessage(),
                'error' => $e,
            ]),
            'timeout' => '60',
            'method'      => 'POST',
            'data_format' => 'body',
        ]);

        throw $e; // still crash so that WP indicates it to the user
    }
}

function wpblast_activation_redirect($plugin)
{
    if ($plugin == plugin_basename(__FILE__)) {
        // Add role
        wpblast_add_capability();
        // Redirect to WPBlast settings page to trigger every update and to show the user the admin page so he can register
        wp_redirect(admin_url('admin.php?page=wpblast'));
        exit();
    }
}
add_action('activated_plugin', 'wpblast_activation_redirect');

// This is done in two steps to prevent concurrent requests bugs
// In case the uninstall method takes a few seconds to process and that requests are still coming, the plugin could create new data that will not be cleaned
// This results in pluginData, file config or third party actions not cleaned (mainly with the action wpblast_updated_crawler_list)
register_deactivation_hook(__FILE__, 'wpblast_uninstall');
function wpblast_uninstall()
{
    global $smartfire_wpblast_settings;
    // Ping wp-blast.com to be aware of deactivation and not trigger auto gen cache
    try {
        wp_remote_get($smartfire_wpblast_settings->getWebsite() . '/?method=deactivatePlugin&domain=' . urlencode($smartfire_wpblast_settings->getUsername()) . '&plugin_token=' . $smartfire_wpblast_settings->getPassword(), [
            'user-agent' => Settings::WPBLAST_UA_PLUGIN,
            'timeout' => 15,
        ]);
    }
    catch (\Throwable $e) {} // fail proof
    catch (\Exception $e) {} // fail proof
    // should remove every advanced-cache addon or mu-plugins before cleaning everything to prevent concurrent requests bugs
    // otherwise concurrent requests could trigger new cache that won't be cleaned
    try {
        do_action('wpblast_deactivate');
    }
    catch (\Throwable $e) {} // fail proof
    catch (\Exception $e) {} // fail proof
}

function wpblast_uninstalled($plugin, $network_deactivating)
{
    global $wpdb;
    if ($plugin === plugin_basename(__FILE__)) {
        $wpdb->wpblast_sitemap = $wpdb->prefix . Settings::WPBLAST_SITEMAP_TABLE;
        // Don't clean db version and settings to keep it in case of renabling but purge every other cache
        try {
            wpblast_remove_capability(); // clean capability
        }
        catch (\Throwable $e) {} // fail proof
        catch (\Exception $e) {} // fail proof
        try {
            do_action('wpblast_purge_plugin_cache', false);
        }
        catch (\Throwable $e) {} // fail proof
        catch (\Exception $e) {} // fail proof
        try {
            // Remove table for clean uninstallation
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->wpblast_sitemap}");
        }
        catch (\Throwable $e) {} // fail proof
        catch (\Exception $e) {} // fail proof
        try {
            do_action('wpblast_deactivated');
        }
        catch (\Throwable $e) {} // fail proof
        catch (\Exception $e) {} // fail proof
    }
}
add_action('deactivated_plugin', 'wpblast_uninstalled', 10, 2);

function wpblast_action_links($links)
{
    global $smartfire_wpblast_settings;
    $links = array_merge([
        '<a href="' . esc_url(menu_page_url($smartfire_wpblast_settings->getPluginName(), false)) . '">' . __('Settings', 'wpblast') . '</a>',
    ], $links);
    return $links;
}
add_action('plugin_action_links_' . plugin_basename(__FILE__), 'wpblast_action_links');

function wpblast_load_textdomain()
{
    // Load translations from the languages directory.
    $locale = get_locale();

    // This filter is documented in /wp-includes/l10n.php.
    $locale = apply_filters('plugin_locale', $locale, 'wpblast');
    load_textdomain('wpblast', WP_LANG_DIR . '/plugins/wpblast-' . $locale . '.mo');

    load_plugin_textdomain('wpblast', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'wpblast_load_textdomain');
