<?php

defined('ABSPATH') || exit;

function wpblast_display_error($message)
{
    $class = 'notice notice-error';
    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

function wpblast_display_warning($message)
{
    $class = 'notice notice-warning';
    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

function wpblast_require_mu_plugin_dir()
{
    $muPluginDir = realpath(WP_CONTENT_DIR) . '/mu-plugins/';
    $success = true;
    if (!is_dir($muPluginDir)) {
        if (!@mkdir($muPluginDir, 0700, true)) {
            $success = false;
            wpblast_display_error(__('Error while writing mu-plugins addon for wpblast. Please make your wp-content folder writable.', 'wpblast') . ' ' . $muPluginDir);
        }
    }
    return $success;
}

function wpblast_remove_mu_plugin_dir()
{
    $muPluginDir = realpath(WP_CONTENT_DIR) . '/mu-plugins/';
    if (wpblast_is_dir_empty($muPluginDir)) {
        // delete the folder if empty
        rmdir($muPluginDir);
    }
}

function wpblast_is_dir_empty($dir)
{
    if (!is_readable($dir)) {
        return null;
    }
    return (count(scandir($dir)) == 2);
}

function wpblast_require_mu_plugin($data)
{
    // In case of update
    add_action('wpblast_plugin_updated', function () use ($data) {
        wpblast_install_mu_plugin($data);
    });

    // In case a difference exist between installed version and current version or in case the mu-plugin triggered an error
    add_action('admin_init', function () use ($data) {
        if (isset($data['slug'])) {
            if (defined('WPBLAST_MU_PLUGIN_ERROR') || !defined('WPBLAST_MU_PLUGIN-' . $data['slug']) || !defined('WPBLAST_MU_PLUGIN_VERSION-' . $data['slug']) || WPBLAST_PLUGIN_VERSION !== constant('WPBLAST_MU_PLUGIN_VERSION-' . $data['slug'])) {
                wpblast_install_mu_plugin($data);
            }
        }
    });

    // Uninstaller
    add_action('wpblast_deactivate', function () use ($data) {
        wpblast_uninstall_mu_plugin($data);
    });
}

function wpblast_install_mu_plugin($data)
{
    if (isset($data['slug'])) {
        $slug = $data['slug'];
        $name = isset($data['name']) ? $data['name'] : 'WP Blast Addon';
        $description = isset($data['description']) ? $data['description'] : 'Add-on auto-generated by wpblast. This file can be removed and will be generated again if necessary by the plugin.';
        $version = isset($data['version']) ? $data['version'] : WPBLAST_PLUGIN_VERSION;
        $codeHeader = isset($data['code']) && isset($data['code']['header']) ? $data['code']['header'] : '';
        $codeBody = isset($data['code']) && isset($data['code']['body']) ? $data['code']['body'] : '';
        $codeDeactivationCondition = '';
        if (isset($data['code']) && isset($data['code']['deactivationCondition'])) {
            $codeDeactivationCondition = '
add_action(\'admin_init\', function () {
    if(' . $data['code']['deactivationCondition'] . ') {
        wpblast_uninstall_mu_plugin([
            \'slug\' => \'' . $slug . '\'
        ]);
    }
});';
        }
        wpblast_require_mu_plugin_dir(); // create mu-plugins directory if needed
        $muPluginFile = realpath(WP_CONTENT_DIR) . '/mu-plugins/' . $slug . '.php';
        $code = '<?php
/*
' . 'Plugin Name' . ':  ' . $name . '
' . 'Plugin URI' . ':   https://www.wp-blast.com
' . 'Description' . ':  ' . $description . '
' . 'Version' . ':      ' . $version . '
' . 'Author' . ': WP Blast
' . 'License' . ': Apache 2.0
' . 'License URI' . ': http://www.apache.org/licenses/LICENSE-2.0
*/
// THIS FILE HAS BEEN AUTO-GENERATED

// WPBLAST-HEADER-START
' . $codeHeader . '
// WPBLAST-HEADER-END

defined( \'ABSPATH\' ) || exit;

// WPBLAST-DEACTIVATION-HOOK-START
' . $codeDeactivationCondition . '
// WPBLAST-DEACTIVATION-HOOK-END

if(!defined(\'WPBLAST_MU_PLUGIN-' . $slug . '\')) {
    define(\'WPBLAST_MU_PLUGIN-' . $slug . '\', true);
}

if(!defined(\'WPBLAST_MU_PLUGIN_VERSION-' . $slug . '\')) {
    define(\'WPBLAST_MU_PLUGIN_VERSION-' . $slug . '\', \'' . $version . '\');
}

// WPBLAST-BODY-START
try {
    ' . $codeBody . '
}
catch (\Throwable $e) {} // fail proof
catch (\Exception $e) {} // fail proof
// WPBLAST-BODY-END
';

        if (@file_put_contents($muPluginFile, $code) === false) {
            wpblast_display_error(__('Error while writing addon file for wpblast. Please make your wp-content folder writable.', 'wpblast') . ' ' . $muPluginFile);
        }
    }
}

function wpblast_uninstall_mu_plugin($data)
{
    if (isset($data['slug']) && $data['slug'] !== '') {
        $muPluginFile = realpath(WP_CONTENT_DIR) . '/mu-plugins/' . $data['slug'] . '.php';
        unlink($muPluginFile);
        wpblast_remove_mu_plugin_dir(); // remove mu-plugins dir if empty
    }
}
