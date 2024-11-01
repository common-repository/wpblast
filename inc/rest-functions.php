<?php

use Smartfire\Wordpress\WPBlast\Settings;

add_action('rest_api_init', 'wpblast_register_routes');

function wpblast_register_routes()
{
    register_rest_route('wpblast/v1', '/generateCacheItem', [
        'methods' => 'GET',
        'callback' => 'wpblast_generate_cache_item_action',
        'permission_callback' => 'wpblast_permission_check',
        'show_in_index' => false,
    ]);
    register_rest_route('wpblast/v1', '/updatePluginData', [
        'methods' => 'GET',
        'callback' => 'wpblast_update_plugin_data',
        'permission_callback' => 'wpblast_permission_check',
        'show_in_index' => false,
    ]);
    register_rest_route('wpblast/v1', '/updateUserAccount', [
        'methods' => 'GET',
        'callback' => 'wpblast_update_user_account',
        'permission_callback' => 'wpblast_permission_check',
        'show_in_index' => false,
    ]);
    register_rest_route('wpblast/v1', '/updateCrawlerList', [
        'methods' => 'GET',
        'callback' => 'wpblast_update_crawler_list',
        'permission_callback' => 'wpblast_permission_check',
        'show_in_index' => false,
    ]);
    register_rest_route('wpblast/v1', '/getWPBlastStatus', [
        'methods' => 'GET',
        'callback' => 'wpblast_get_status',
        'permission_callback' => '__return_true',
        'show_in_index' => false,
    ]);
    register_rest_route('wpblast/v1', '/getSitemap', [
        'methods' => 'POST',
        'callback' => 'wpblast_get_sitemap',
        'permission_callback' => 'wpblast_permission_check',
        'args' => [
            'items' => [
                'description' => esc_html__('Items to update using getSitemap method.', 'wpblast'),
                'type'        => 'array',
                'sanitize_callback' => 'wpblast_get_sitemap_arg_items_sanitize_callback',
            ],
        ],
        'show_in_index' => false,
    ]);
    register_rest_route('wpblast/v1', '/updateActivePages', [
        'methods' => 'POST',
        'callback' => 'wpblast_update_active_pages',
        'permission_callback' => 'wpblast_permission_check',
        'args' => [
            'activeUrls' => [
                'description' => esc_html__('Items to set as active for pages.', 'wpblast'),
                'type'        => 'array',
                'sanitize_callback' => 'wpblast_get_sitemap_arg_items_sanitize_callback',
            ],
        ],
        'show_in_index' => false,
    ]);
}

// Cache generation
function wpblast_generate_cache_item_action()
{
    global $smartfire_wpblast_settings;
    if (isset($_GET['wpblast_nonce']) && isset($_GET['url']) && $_GET['url'] !== '') {
        $nonce = sanitize_text_field(wp_unslash($_GET['wpblast_nonce']));
        if (wp_verify_nonce($nonce, 'wp_rest')) {
            $url = urldecode(esc_url_raw(wp_unslash($_GET['url'])));
            $response = wp_remote_get($url, [
                'user-agent' => $smartfire_wpblast_settings->getCrawlerCacheGen(), // use WP Blast Crawler to force cache gen
                'headers' => [
                    'WPBLAST-FORCE-GENERATION' => '1',
                    'WPBLAST-TOKEN' => base64_encode($smartfire_wpblast_settings->getUsername() . ':' . $smartfire_wpblast_settings->getPassword()),
                ],
                'timeout'     => $smartfire_wpblast_settings->getTimeout(),
            ]);
            $httpCode = wp_remote_retrieve_response_code($response);
            $res = wp_remote_retrieve_body($response);
            if ($httpCode === 200) {
                return new WP_REST_Response(
                    [
                        'status' => 200,
                        'response' => 'Cache generated for ' . $url,
                    ]
                );
            } else {
                return new WP_Error($httpCode, 'Error while generating: ' . $url . '; statusCode=' . $httpCode, ['status' => $httpCode]);
            }
        } else {
            return new WP_Error(400, 'Invalid request', ['status' => 400]);
        }
    } else {
        return new WP_Error(403, 'Forbidden access', ['status' => 403]);
    }
}

function wpblast_update_plugin_data()
{
    global $smartfire_wpblast_settings;
    if (isset($_GET['wpblast_nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_GET['wpblast_nonce']));
        if (wp_verify_nonce($nonce, 'wp_rest')) {
            $smartfire_wpblast_settings->getPluginData(true); // force update to wp blast
            return new WP_REST_Response(
                [
                    'status' => 200,
                    'response' => 'Plugin data updated.',
                ]
            );
        } else {
            return new WP_Error(403, 'Forbidden access', ['status' => 403]);
        }
    } else {
        return new WP_Error(403, 'Forbidden access', ['status' => 403]);
    }
}

function wpblast_update_user_account()
{
    global $smartfire_wpblast_settings;
    if (isset($_GET['wpblast_nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_GET['wpblast_nonce']));
        if (wp_verify_nonce($nonce, 'wp_rest')) {
            $smartfire_wpblast_settings->getAccount(true); // force update of user account
            return new WP_REST_Response(
                [
                    'status' => 200,
                    'response' => 'User account updated.',
                ]
            );
        } else {
            return new WP_Error(403, 'Forbidden access', ['status' => 403]);
        }
    } else {
        return new WP_Error(403, 'Forbidden access', ['status' => 403]);
    }
}

function wpblast_update_crawler_list()
{
    global $smartfire_wpblast_settings;
    if (isset($_GET['wpblast_nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_GET['wpblast_nonce']));
        if (wp_verify_nonce($nonce, 'wp_rest')) {
            $smartfire_wpblast_settings->getCrawlerAutoRegexp(true); // force update of crawler list
            return new WP_REST_Response(
                [
                    'status' => 200,
                    'response' => 'Crawler list updated.',
                ]
            );
        } else {
            return new WP_Error(403, 'Forbidden access', ['status' => 403]);
        }
    } else {
        return new WP_Error(403, 'Forbidden access', ['status' => 403]);
    }
}

function wpblast_get_status()
{
    global $smartfire_wpblast_settings;
    header('Content-Type: application/javascript');
    // Call WP Blast server for status

    $response = wp_remote_get($smartfire_wpblast_settings->getWebsite() . '/?method=getWpBlastStatus&domain=' . urlencode($smartfire_wpblast_settings->getUsername()) . '&plugin_token=' . $smartfire_wpblast_settings->getPassword(), [
        'user-agent' => Settings::WPBLAST_UA_PLUGIN,
        'timeout' => 15,
    ]);

    $httpCode = wp_remote_retrieve_response_code($response);
    $res = wp_remote_retrieve_body($response);
    if ($httpCode === 200) {
        echo wp_kses_post($res);
    }

    exit();
}

function wpblast_get_sitemap_arg_items_sanitize_callback($value, $request, $param)
{
    if (is_array($value)) {
        return $value;
    } else {
        return [];
    }
}

function wpblast_get_sitemap($request)
{
    global $smartfire_wpblast_settings;
    if (isset($_GET['wpblast_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['wpblast_nonce'])), 'wp_rest')) {

        if (isset($request['items'])) {
            $items = $request['items'];
            $smartfire_wpblast_settings->updateSitemapScores($items);
        }

        $sitemap = $smartfire_wpblast_settings->getWebsiteSitemap('active');

        $smartfire_wpblast_settings->cleanExpiredCache(); // delete expired cache item

        $response = [
            'sitemap' => $sitemap,
        ];

        return new WP_REST_Response(
            [
                'status' => 200,
                'response' => $response,
            ]
        );
    } else {
        return new WP_Error(403, 'Forbidden access', ['status' => 403]);
    }
}

function wpblast_update_active_pages($request)
{
    global $smartfire_wpblast_settings;
    if (isset($_GET['wpblast_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['wpblast_nonce'])), 'wp_rest')) {

        if (isset($request['activeUrls'])) {
            $activeUrls = $request['activeUrls'];
            $smartfire_wpblast_settings->updateActivePages($activeUrls);
        }

        $sitemap = $smartfire_wpblast_settings->getWebsiteSitemap('active');

        $smartfire_wpblast_settings->cleanExpiredCache(); // delete expired cache item

        $response = [
            'sitemap' => $sitemap,
        ];

        return new WP_REST_Response(
            [
                'status' => 200,
                'response' => $response,
            ]
        );
    } else {
        return new WP_Error(403, 'Forbidden access', ['status' => 403]);
    }
}
