<?php

use Smartfire\Wordpress\WPBlast\Config;
use Smartfire\Wordpress\WPBlast\Settings;

// This is to allow to use require instead of require_once but still have the file loaded once
// This happened because when the plugin is activated by wordpress, the variables scope is empty but still the file has been loaded
// This happened when activating the plugin after loading was left in the advanced-cache file
if (!isset($wpblast_globals_loaded)) {

    if (!defined('WPBLAST_PLUGIN_DIR')) {
        define('WPBLAST_PLUGIN_DIR', __DIR__ . '/../../..');
    }

    $autoloader = require('autoload.php');
    $autoloader('Smartfire\\Wordpress\\WPBlast\\', __DIR__ . '/src/Smartfire/Wordpress/WPBlast/');

    $smartfire_wpblast_config = new Config(['config_dir_path' => WP_CONTENT_DIR . '/wpblast-config']);
    $smartfire_wpblast_settings = new Settings();

    $wpblast_globals_loaded = true;
}
