<?php

use Smartfire\Wordpress\WPBlast\Utils as Utils;

defined('ABSPATH') || exit;

include_once ABSPATH . 'wp-admin/includes/plugin.php';

if (is_plugin_active('wp-fastest-cache/wpFastestCache.php')) {

    wpblast_require_mu_plugin([
        'slug' => 'wpblast-wpfc-addon',
        'name' => 'WP Blast Addon - WP Fastest Cache',
        'code' => [
            'deactivationCondition' => "!is_plugin_active('wp-fastest-cache/wpFastestCache.php')",
            'body' => '
// Use this mu-plugin to inject a cookie in the request that will make wpfc ignore the request in case we blast the request
$wpblast_globals_file = "' . __DIR__ . '/../../../globals.php' . '";
$wpblast_abspath = "' . ABSPATH . '";

// Fail proof check that the WP installation hasnt been moved to another server or other folder
// In case path is wrong, the addon will not be detected and therefore will be installed again
if (file_exists($wpblast_globals_file) && ABSPATH == $wpblast_abspath) {
    require $wpblast_globals_file;
    if (Smartfire\Wordpress\WPBlast\Bootstrap::should_blast()) {
        $_SERVER[\'HTTP_COOKIE\'] = "wpblast=1; " . (isset($_SERVER[\'HTTP_COOKIE\']) ? $_SERVER[\'HTTP_COOKIE\'] : "");
    }
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

    function wpblast_wpfc_remove_exclude_rules()
    {
        remove_filter('pre_update_option_WpFastestCacheExclude', 'wpblast_wpfc_inject_option_WpFastestCacheExclude'); // remove filter so our plugin wouldn't add the rule again
        remove_action('delete_option_WpFastestCacheExclude', 'wpblast_wpfc_add_exclude_rules'); // remove filter so our plugin wouldn't add the rule again
        $rules_json = get_option('WpFastestCacheExclude');
        if (isset($rules_json) && $rules_json !== false && $rules_json !== '') {
            $parsedContent = json_decode($rules_json);
            $uaIndex = [];
            $cookieIndex = [];
            foreach ($parsedContent as $key => $value) {
                if (isset($value->type) && $value->type === 'useragent' && isset($value->content) && strpos($value->content, 'WP-BLAST') !== false) {
                    array_push($uaIndex, $key);
                }
                if (isset($value->type) && $value->type === 'cookie' && isset($value->content) && strpos($value->content, 'wpblast') !== false) {
                    array_push($cookieIndex, $key);
                }
            }
            // Remove the keys
            // This should only have one single key but in case an error happened, this will clean every rules
            if (isset($uaIndex)) {
                foreach ($uaIndex as $uaIdx) {
                    array_splice($parsedContent, $uaIdx, 1);
                }
            }
            if (isset($cookieIndex)) {
                foreach ($cookieIndex as $cookieIdx) {
                    array_splice($parsedContent, $cookieIdx, 1);
                }
            }

            if (count($parsedContent) > 0) {
                update_option('WpFastestCacheExclude', Utils::format_json_encode($parsedContent));
            } else {
                delete_option('WpFastestCacheExclude');
            }
        }

        // Force update of htaccess on wpfc side
        try {
            include_once(__DIR__ . '/../../../../wp-fastest-cache/inc/admin.php');
            if (class_exists('WpFastestCacheAdmin')) {
                $wpfc = new WpFastestCacheAdmin();
                // only way of retriggering it is to simulate activation
                $options = get_option('WpFastestCache');
                if (isset($options) && $options !== false) {
                    $post = json_decode($options, true);
                    $wpfc->modifyHtaccess($post);
                }
            }
        }
        catch (\Throwable $e) {} // fail proof
        catch (\Exception $e) {} // fail proof
    }

    function wpblast_wpfc_clear_cache()
    {
        // Clear all the page cache to avoid having plugin serve static cache files
        do_action('wpfc_clear_all_cache');
        do_action('wpfc_clear_all_site_cache');
    }
    add_action('wpblast_purge_cache_third_party', 'wpblast_wpfc_clear_cache');

    function wpblast_wpfc_activate()
    {
        wpblast_wpfc_add_exclude_rules();
    }
    add_action('wpblast_plugin_updated', function($oldVersion, $newVersion) {
        // Compatibility upgrader to reset possible cache with WP Blast content
        try {
            if ($oldVersion === false || $oldVersion === '1.8.4') {
                wpblast_wpfc_clear_cache();
            }
        }
        catch (\Throwable $e) {} // fail proof
        catch (\Exception $e) {} // fail proof
        try {
            wpblast_wpfc_activate();
        }
        catch (\Throwable $e) {} // fail proof
        catch (\Exception $e) {} // fail proof
    }, 10, 2);

    add_action('wpblast_updated_crawler_list', 'wpblast_wpfc_add_exclude_rules'); // should update exclude rules
    add_action('wpblast_updated_options', 'wpblast_wpfc_add_exclude_rules');

    function wpblast_wpfc_deactivated()
    {
        // should be executed with deactivated to have the plugin being deactivated and no more requests
        wpblast_wpfc_remove_exclude_rules();
    }
    add_action('wpblast_deactivated', 'wpblast_wpfc_deactivated'); // should disable exclude rules and mu-plugin
}

// Should be added whether the plugin is active or not so that in case wpblast is already enabled and wpfc is activated, we can inject our rules
// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid -- WpFastestCacheExclude is the name of the option
function wpblast_wpfc_inject_option_WpFastestCacheExclude($content)
{
    $parsedContent = [];
    try {
        if (isset($content) && $content !== '') {
            $parsedContent = json_decode($content);
        }
    }
    catch (\Throwable $e) {} // fail proof
    catch (\Exception $e) {} // fail proof
    if (!is_array($parsedContent)) {
        $parsedContent = [];
    }
    $ruleUAExist = false;
    $ruleCookieExist = false;
    $crawlers = apply_filters('wpblast_crawlers_full', '');
    foreach ($parsedContent as $key => $value) {
        if (isset($value->type) && $value->type === 'useragent' && isset($value->content) && strpos($value->content, 'WP-BLAST') !== false) {
            $ruleUAExist = true;
            $parsedContent[$key]->content = '^(' . str_replace([' ', '\\\\ '], '\\ ', $crawlers) . ').*'; // force update with new value
            $parsedContent[$key]->editable = false; // re-add editable false, otherwise, when the user save, this property gets stripped
        }
        if (isset($value->type) && $value->type === 'cookie' && isset($value->content) && strpos($value->content, 'wpblast') !== false) {
            $ruleCookieExist = true;
            $parsedContent[$key]->content = 'wpblast'; // force update with new value
            $parsedContent[$key]->editable = false; // re-add editable false, otherwise, when the user save, this property gets stripped
        }
    }
    if ($ruleUAExist === false) {
        if ($crawlers !== '') {
            $new_rule = new stdClass();
            $new_rule->prefix = 'contain';
            $new_rule->content = '^(' . str_replace([' ', '\\\\ '], '\\ ', $crawlers) . ').*';
            $new_rule->type = 'useragent';
            $new_rule->editable = false;
            array_push($parsedContent, $new_rule);
        }
    }
    if ($ruleCookieExist === false) {
        $new_rule = new stdClass();
        $new_rule->prefix = 'contain';
        $new_rule->content = 'wpblast';
        $new_rule->type = 'cookie';
        $new_rule->editable = false;
        array_push($parsedContent, $new_rule);
    }

    return Utils::format_json_encode($parsedContent);
}
add_filter('pre_update_option_WpFastestCacheExclude', 'wpblast_wpfc_inject_option_WpFastestCacheExclude');

function wpblast_wpfc_add_exclude_rules()
{
    $rules_json = get_option('WpFastestCacheExclude');
    if ($rules_json === false) {
        add_option('WpFastestCacheExclude', wpblast_wpfc_inject_option_WpFastestCacheExclude('[]'), '', 'yes'); // add our rules if no option exists
    } else {
        update_option('WpFastestCacheExclude', $rules_json); // will then trigger hook that will inject our rule
    }

    // Force update of htaccess on wpfc side
    try {
        include_once(__DIR__ . '/../../../../wp-fastest-cache/inc/admin.php');
        if (class_exists('WpFastestCacheAdmin')) {
            $wpfc = new WpFastestCacheAdmin();
            // only way of retriggering it is to simulate activation
            $options = get_option('WpFastestCache');
            if (isset($options) && $options !== false) {
                $post = json_decode($options, true);
                $wpfc->modifyHtaccess($post);
            }
        }
    }
    catch (\Throwable $e) {} // fail proof
    catch (\Exception $e) {} // fail proof
}
add_action('delete_option_WpFastestCacheExclude', 'wpblast_wpfc_add_exclude_rules'); // in case the plugin tries to delete the option force it to stay

// Should be check after check of activation of plugin
function wpblast_wpfc_activation($plugin)
{
    if ($plugin == 'wp-fastest-cache/wpFastestCache.php') {
        wpblast_wpfc_add_exclude_rules(); // add exclude rules in case of activation of wpfc when wpblast is already active
    }
}
add_action('activated_plugin', 'wpblast_wpfc_activation');
