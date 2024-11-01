<?php

defined('ABSPATH') || exit;

function wpblast_require_advanced_cache($data)
{
    if (isset($data['slug']) && isset($data['delimiter'])) {
        if (!defined('WPBLAST_ADVANCED_CACHE_SLUG_REQUIRED')) {
            define('WPBLAST_ADVANCED_CACHE_SLUG_REQUIRED', $data['slug']);
        }
        // Only one addon allowed in advanced-cache
        if (WPBLAST_ADVANCED_CACHE_SLUG_REQUIRED === $data['slug']) {
            if (!defined('WPBLAST_ADVANCED_CACHE') || !defined('WPBLAST_ADVANCED_CACHE_VERSION') || !defined('WPBLAST_ADVANCED_CACHE_SLUG') || WPBLAST_PLUGIN_VERSION !== WPBLAST_ADVANCED_CACHE_VERSION || WPBLAST_ADVANCED_CACHE_SLUG !== $data['slug']) {
                add_action('admin_init', function () use ($data) {
                    wpblast_write_advanced_cache_addon($data['delimiter'], $data['slug']);
                });
            }
        }
        // should be remove at the start of the deactivation to prevent concurrent requests bugs
        add_action('wpblast_deactivate', 'wpblast_remove_advanced_cache_addon');
    }
}

function wpblast_uninstall_advanced_cache($data)
{
    if (isset($data['slug'])) {
        if (!defined('WPBLAST_ADVANCED_CACHE_SLUG_REQUIRED')) {
            define('WPBLAST_ADVANCED_CACHE_SLUG_REQUIRED', $data['slug']);
        }
        // Only one addon allowed in advanced-cache
        if (WPBLAST_ADVANCED_CACHE_SLUG_REQUIRED === $data['slug']) {
            // If we are installed but this function was called, we should uninstall our advanced-cache addon if needed
            if (defined('WPBLAST_ADVANCED_CACHE') || defined('WPBLAST_ADVANCED_CACHE_VERSION') || defined('WPBLAST_ADVANCED_CACHE_SLUG') ) {
                add_action('admin_init', 'wpblast_remove_advanced_cache_addon');
            }
        }
    }
}

function wpblast_advanced_cache_content($content, $delimiter, $slug)
{
    // Only insert addon in case there is a content and that addon is not existent, otherwise remove advanced cache to trigger re-generation
    if ($content !== '' && isset($delimiter) && $delimiter !== '' && isset($slug) && $slug !== '') {
        // Check if markers exist
        if (strpos($content, '//-WP-BLAST-ADDON-START-//') !== false && strpos($content, '//-WP-BLAST-ADDON-END-//') !== false) {
            // If exists, remove everything in it before adding the new addon code
            $content = preg_replace('#([\r\n|\r|\n]//-WP-BLAST-ADDON-START-//.*//-WP-BLAST-ADDON-END-//[\r\n|\r|\n)])#s', '', $content);
        }
        else if ((strpos($content, '//-WP-BLAST-ADDON-START-//') !== false && strpos($content, '//-WP-BLAST-ADDON-END-//') === false) || (strpos($content, '//-WP-BLAST-ADDON-START-//') === false && strpos($content, '//-WP-BLAST-ADDON-END-//') !== false)) {
            // only one marker exists so delete the whole content so that it can be generated again, we'll add the addon after that
            return '';
        }

        $contentArray = explode($delimiter, $content);
        if (isset($contentArray[0])) {

            $errorContent = apply_filters('wpblast_advanced_cache_content_error', '');
            $errorCode = '';
            if (isset($errorContent) && $errorContent !== '') {
                $errorCode = 'else {
            ' . $errorContent . '
    }';
            }

            $start = $contentArray[0];
            $newContent = $start . '
//-WP-BLAST-ADDON-START-//
try {
    $wpblast_advanced_cache_file = "' . __DIR__ . '/third-party/advanced-cache.php' . '";
    $wpblast_abspath = "' . ABSPATH .'";

    // Fail proof check that the WP installation hasnt been moved to another server or other folder
    // In case path is wrong, the addon will not be detected and therefore will be installed again
    if (file_exists($wpblast_advanced_cache_file) && ABSPATH == $wpblast_abspath) {
        if (!defined("WPBLAST_ADVANCED_CACHE")) {
            define("WPBLAST_ADVANCED_CACHE", true);
        }

        if(!defined("WPBLAST_ADVANCED_CACHE_VERSION")) {
            define( "WPBLAST_ADVANCED_CACHE_VERSION", "' . WPBLAST_PLUGIN_VERSION . '");
        }

        if(!defined("WPBLAST_ADVANCED_CACHE_SLUG")) {
            define( "WPBLAST_ADVANCED_CACHE_SLUG", "' . $slug . '");
        }

        include_once $wpblast_advanced_cache_file;

        if(defined("WPBLAST_SKIP_ADVANCED_CACHE") && WPBLAST_SKIP_ADVANCED_CACHE) {
            return;
        }
    }
    ' . $errorCode . '
}
catch (\Throwable $e) {} // fail proof
catch (\Exception $e) {} // fail proof
//-WP-BLAST-ADDON-END-//
';

            // In case delimiter exists at the end of the file
            if (count($contentArray) === 1) {
                if (strpos($content, $delimiter) !== false) {
                    $newContent = $newContent . $delimiter;
                }
            }
            else {
                // Add existing content
                // In case delimiter split the file in more than one piece, addon is added at the first match of code
                $contentArrayLength = count($contentArray);
                for ($i = 1; $i < $contentArrayLength; $i++) {
                    if (isset($contentArray[$i])) {
                        $newContent = $newContent . $delimiter . $contentArray[$i];
                    }
                }
            }
            return $newContent;
        }
        else {
            // should not happen
            return $content;
        }
    } else {
        // Only insert addon in case there is an existing content
        return $content;
    }
}

// As we are checking that a content exist in the advanced-cache, no writing error should happen
// In case the cache plugin generate a new version without our addon, our variable WPBLAST_ADVANCED_CACHE_VERSION will miss and we'll add our addon in the advanced cache again
// In case of upgrade of wpblast, a new version will be triggered that will force to update the advanced cache file
add_filter('wpblast_advanced_cache_content', 'wpblast_advanced_cache_content', 10, 3);

function wpblast_write_advanced_cache_addon($delimiter, $slug)
{
    try {
        $advancedCacheFile = realpath(WP_CONTENT_DIR) . '/advanced-cache.php';
        $advancedCacheFileContent = @file_get_contents($advancedCacheFile);
        $newContent = apply_filters('wpblast_advanced_cache_content', $advancedCacheFileContent, $delimiter, $slug);
        if (WP_DEBUG) {
            return file_put_contents($advancedCacheFile, $newContent);
        } else {
            return @file_put_contents($advancedCacheFile, $newContent);
        }
    }
    catch (\Throwable $e) {} // fail proof
    catch (\Exception $e) {} // fail proof
}

function wpblast_remove_advanced_cache_addon()
{
    try {
        // Remove addon from advanced-cache
        $advancedCacheFile = realpath(WP_CONTENT_DIR) . '/advanced-cache.php';
        $advancedCacheFileContent = @file_get_contents($advancedCacheFile);
        $advancedCacheFileContent = preg_replace('#([\r\n|\r|\n]//-WP-BLAST-ADDON-START-//.*//-WP-BLAST-ADDON-END-//[\r\n|\r|\n)])#s', '', $advancedCacheFileContent);
        if (WP_DEBUG) {
            return file_put_contents($advancedCacheFile, $advancedCacheFileContent);
        } else {
            return @file_put_contents($advancedCacheFile, $advancedCacheFileContent);
        }
    }
    catch (\Throwable $e) {} // fail proof
    catch (\Exception $e) {} // fail proof
}

function wpblast_clear_advanced_cache_file()
{
    try {
        // Remove addon from advanced-cache
        $advancedCacheFile = realpath(WP_CONTENT_DIR) . '/advanced-cache.php';
        $advancedCacheFileContent = '';
        if (WP_DEBUG) {
            return file_put_contents($advancedCacheFile, $advancedCacheFileContent);
        } else {
            return @file_put_contents($advancedCacheFile, $advancedCacheFileContent);
        }
    }
    catch (\Throwable $e) {} // fail proof
    catch (\Exception $e) {} // fail proof
}
