<?php

namespace Smartfire\Wordpress\WPBlast;

class Bootstrap
{
    public static function is_cache_gen()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- We can't use wordpress sanitizing function here as WP is not loaded yet. This variable is only called in this function with strict check and is safe.
        $ua = $_SERVER['HTTP_USER_AGENT'];

        global $smartfire_wpblast_config;

        $config = $smartfire_wpblast_config->get_configs();
        if (is_array($config)) {
            if (
                ($config['crawler_ua_self'] === $ua) // check for cache gen UA, whatever its enable for other crawler
            ) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    public static function should_blast() // if true, takes the request in charge by wpblast
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- We can't use wordpress sanitizing function here as WP is not loaded yet. This variable is only called in this function with strict check and is safe.
        $ua = $_SERVER['HTTP_USER_AGENT'];

        if (\strpos($ua, 'Smartfire-Wext') !== false) {
            return false;
        }

        if (isset($_GET[PageRender::WPBLAST_CRAWLER])) {
            return true;
        } else {

            global $smartfire_wpblast_config;

            $config = $smartfire_wpblast_config->get_configs();
            if (is_array($config)) {
                if (
                    ($config['crawler_ua_self'] === $ua) // check for cache gen UA, whatever its enable for other crawler
                    ||
                    (!empty($config['crawler_ua_regex']) && preg_match($config['crawler_ua_regex'], $ua)
                        || !empty($config['crawler_ua_regex_auto']) && preg_match('(' . $config['crawler_ua_regex_auto'] . ')', $ua)
                    )
                ) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        return false;
    }
}
