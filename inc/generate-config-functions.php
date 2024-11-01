<?php

use Smartfire\Wordpress\WPBlast\Utils as Utils;

defined('ABSPATH') || exit;

function wpblast_generate_config_file()
{
    global $smartfire_wpblast_settings;
    global $smartfire_wpblast_config;

    $smartfire_wpblast_settings->init();

    $config = [
        'crawler_enabled' => $smartfire_wpblast_settings->isEnableCrawler(),
        'crawler_ua_self' => $smartfire_wpblast_settings->getCrawlerCacheGen(),
        'crawler_ua_regex_auto' => $smartfire_wpblast_settings->getCrawlerAutoRegexp(),
        'crawler_ua_regex' => $smartfire_wpblast_settings->getCrawlerRegexp(),
    ];

    $smartfire_wpblast_config->write_configuration($config);
}

add_action('wpblast_plugin_updated', function () {
    add_action('init', 'wpblast_generate_config_file');
});
add_action('wpblast_updated_crawler_list', 'wpblast_generate_config_file');
add_action('wpblast_updated_options', 'wpblast_generate_config_file');

add_action('wpblast_deactivated', function () {
    global $smartfire_wpblast_config;
    $path = $smartfire_wpblast_config->get_config_file_path()['path'];
    if (file_exists($path)) {
        unlink($path);
    }
    if (is_dir($smartfire_wpblast_config->get_config_dir_path())) {
        Utils::rmdir_recursive($smartfire_wpblast_config->get_config_dir_path());
    }
});
