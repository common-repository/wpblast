<?php

namespace Smartfire\Wordpress\WPBlast;

class PageRender
{
    // Should be update in JS for cache section
    const WPBLAST_CRAWLER = '__wpblast_crawler';

    /**
     * @var Settings
     */
    private $settings;

    private $static = true;
    private $forced = false;
    private $withScroll = true;
    private $withImages = true;
    private $injectCss = false;

    public function __construct($settings, $static = true, $withScroll = true, $withImages = true, $injectCss = false)
    {
        $this->static = $static;
        $this->withScroll = $withScroll;
        $this->withImages = $withImages;
        $this->injectCss = $injectCss;
        $this->settings = $settings;
    }

    private function get_wpblast_url()
    {
        return $this->settings->getServer();
    }

    private function isOnDisabledWPBlastPage()
    {
        $isOnDisabledWPBlastPage = apply_filters(
            'wpblast_render_disabled_on_current_page',
            // Avoid API Requests
            defined('REST_REQUEST') ||
                // Never pre-render Admin pages
                is_admin() ||
                // Do not pre-render not found pages
                is_404() ||
                // Avoid rendering for robots
                is_robots() ||
                // Do not render search pages as content is fully dynamic
                is_search() ||
                // Disable native wordpress sitemap
                get_query_var('sitemap') !== '' ||
                // Do not render favicon request
                function_exists('is_favicon') && is_favicon() ||
                // Do not render xml feed
                is_feed() ||
                // Do not render attachment page
                is_attachment() ||
                // Do not render any preview
                is_preview()
        );

        return $isOnDisabledWPBlastPage;
    }

    private function isHomeUrl($url)
    {
        // use site_url instead of home_url to avoid having multiple home_url due to multilanguages
        $homeUrl = untrailingslashit(get_site_url());
        if ((($url === $homeUrl
            || $url === $homeUrl . '/'
            || $url === $homeUrl . '/index.php'
            || $url === $homeUrl . '/index.html'
        ))) {
            return true;
        } else {
            return false;
        }
    }

    private function isUserPlanAuthorized($sitemapItem)
    {
        $isAuthorized = false;

        $currentPage = $this->get_current_page_url();
        if ($this->isHomeUrl($currentPage)) {
            // allow every homepage
            $isAuthorized = true;
        } else if (isset($sitemapItem) && $sitemapItem['active'] === '1') {
            // allow pages that have been enabled
            $isAuthorized = true;
        } else if (!isset($sitemapItem)) { // new item
            $defaultActiveValue = $this->defaultActiveValueForUrl($currentPage);
            $isAuthorized = $defaultActiveValue === 1;
        } else {
            $isAuthorized = false;
        }
        return apply_filters('wpblast_render_is_user_authorized', $isAuthorized);
    }

    public function start()
    {
        if ($this->isOnDisabledWPBlastPage()) {
            return;
        }

        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            // No UA available
            return;
        }

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
            // only GET method can use WP Blast
            return;
        }

        $ua = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));

        if (\strpos($ua, 'Smartfire-Wext') !== false) {
            return;
        }

        if (isset($_GET[self::WPBLAST_CRAWLER])) {
            $this->static = true;
            $this->forced = true;
        } else {
            if (
                ($this->settings->getCrawlerCacheGen() === $ua) // check for cache gen UA, whatever its enable for other crawler
                ||
                ($this->settings->getCrawlerRegexp() !== '' && preg_match($this->settings->getCrawlerRegexp(), $ua)
                    || $this->settings->getCrawlerAutoRegexp() !== '' && preg_match('(' . $this->settings->getCrawlerAutoRegexp() . ')', $ua)
                )
            ) {
                $this->static = true;
            } else {
                return;
            }
        }

        // Setup images and scroll settings
        if ($this->settings->isWithScroll()) {
            $this->withScroll = true;
        } else {
            $this->withScroll = false;
        }

        if ($this->settings->isWithImages()) {
            $this->withImages = true;
        } else {
            $this->withImages = false;
        }

        if ($this->settings->shouldInjectCss()) {
            $this->injectCss = true;
        } else {
            $this->injectCss = false;
        }

        $allow = apply_filters('wpblast_render_should_render', true, [
            'static' => $this->static,
            'forced' => $this->forced,
            'withScroll' => $this->withScroll,
            'withImages' => $this->withImages,
            'injectCss' => $this->injectCss,
        ]);

        if (!$allow) {
            return;
        }

        ob_start([$this, 'render']);
    }

    private function get_current_page_url()
    {
        global $wp;
        return add_query_arg(isset($_SERVER['QUERY_STRING']) ? substr(esc_url_raw('?' . wp_unslash($_SERVER['QUERY_STRING'])), 1) : '', '', home_url($wp->request));
    }

    public function render($page)
    {
        try {
            return $this->renderFor($this->get_current_page_url(), $page, $this->static, $this->withScroll, $this->withImages, $this->injectCss);
        } catch (\Throwable $e) {
            header('HTTP/1.1 500 Internal Server Error');
            return $e->getMessage();
        }
    }

    public function end()
    {
        ob_end_clean();
    }

    public function hashKey($body, $static, $userId, $url, $withScroll, $withImages, $injectCss)
    {
        global $wp;
        global $wp_query;
        if ($wp_query) {
            $posts = array_map(function ($post) {
                return [
                    'post' => $post->ID,
                    'date' => $post->post_modified_gmt,
                ];
            }, $wp_query->posts);
        } else {
            $posts = [];
        }
        $hashVariables = apply_filters('wpblast_render_cache_variables', [
            'query' => $wp->query_vars,
            'extra_query' => $wp->extra_query_vars,
            'static' => $static,
            'withScroll' => $withScroll,
            'withImages' => $withImages,
            'injectCss' => $injectCss,
            'current_user_id' => $userId,
            'posts' => $posts,
        ], $body, $static);
        $data_to_hash = md5(Utils::format_json_encode($hashVariables));
        $hash = apply_filters('wpblast_render_hash_compute', $data_to_hash, $hashVariables);

        return [
            'hash' => $hash,
            'variables' => $hashVariables,
        ];
    }

    public function isValidForceGeneration($hashCompute, $body)
    {
        try {
            if (isset($_SERVER['HTTP_WPBLAST_FORCE_GENERATION']) && isset($_SERVER['HTTP_WPBLAST_TOKEN']) && $_SERVER['HTTP_WPBLAST_TOKEN'] === base64_encode($this->settings->getUsername() . ':' . $this->settings->getPassword())) {
                return true;
            } else {
                return false;
            }
        } catch (\Throwable $e) {
            return $this->handleError($e, $body, true);
        }
    }

    public function isForceGeneration($hashCompute, $body)
    {
        try {
            if (isset($_SERVER['HTTP_WPBLAST_FORCE_GENERATION']) && isset($_SERVER['HTTP_WPBLAST_TOKEN'])) {
                return true;
            } else {
                return false;
            }
        } catch (\Throwable $e) {
            return $this->handleError($e, $body, true);
        }
    }

    public function isInGracePeriod($hashCompute, $body)
    {
        try {
            // Check if it's in the grace period
            $cacheGrace = $this->settings->getCacheGrace();
            $hash = $hashCompute['hash'];
            $item = $this->settings->getSitemapItem($hash);
            if (!isset($item) || !isset($item['cacheExpiration']) || (isset($item['cacheExpiration']) && time() >= $item['cacheExpiration'] - $cacheGrace)) {
                return true;
            } else {
                return false;
            }
        } catch (\Throwable $e) {
            return $this->handleError($e, $body, true);
        }
    }

    public function getCacheItem($hash)
    {
        $item = $this->settings->getSitemapItem($hash);
        if (isset($item) && isset($item['cache'])) {
            return $item['cache'];
        } else {
            return null;
        }
    }

    public function defaultActiveValueForUrl($url)
    {
        $userAccount = $this->settings->getAccount();
        if (isset($userAccount) && isset($userAccount['features']) && isset($userAccount['features']['maxPages'])) {
            $maxPagesAllowed = $userAccount['features']['maxPages'];
            $currentUse = $this->settings->getNumberPagesUsed();
            if (isset($currentUse)) {
                if ($currentUse >= $maxPagesAllowed) {
                    // max usage for the plan has been reached, only allow to be active if the same url is already active
                    // home url should always be active
                    if ($this->settings->isUrlActive($url) || $this->isHomeUrl($url)) {
                        return 1;
                    } else {
                        return 0;
                    }
                } else {
                    return 1; // set default to active in case the user has a plan that allows it
                }
            } else {
                return 0; // fallback to inactive page in case we cannot determine the usage of the plan
            }
        }
    }

    /**
     * This method will be executed after EVERY code php has finished
     * This is executed after wp has shutdown
     */
    public function renderFor($url, $body, $static, $withScroll, $withImages, $injectCss)
    {
        try {
            $userId = get_current_user_id();
            $hashCompute = $this->hashKey($body, $static, $userId, $url, $withScroll, $withImages, $injectCss);
            $hash = $hashCompute['hash'];
            $isValidForceGeneration = $this->isValidForceGeneration($hashCompute, $body);
            $isForceGeneration = $this->isForceGeneration($hashCompute, $body);
            $isInGracePeriod = $this->isInGracePeriod($hashCompute, $body);
            $sitemapItem = $this->settings->getSitemapItem($hash);

            if (!$this->settings->isEnableCrawler() || !$this->isUserPlanAuthorized($sitemapItem)) {
                // don't blast the content but still save the page in sitemap tables
                if ($sitemapItem === null || (!$isForceGeneration && !isset($_GET[self::WPBLAST_CRAWLER]))) { //either first wext gen or a real crawler
                    $toUpdate = [
                        'hashVariables' => Utils::format_json_encode($hashCompute['variables']),
                        'url' => $url,
                    ];
                    if ($sitemapItem === null) {
                        $toUpdate['active'] = $this->defaultActiveValueForUrl($url);
                    }
                    if (!$isForceGeneration && !isset($_GET[self::WPBLAST_CRAWLER])) {
                        if (!isset($sitemapItem) || !isset($sitemapItem['nbRequest']) || $sitemapItem['nbRequest'] < 1) {
                            $toUpdate['nbRequest'] = 1;
                            $toUpdate['lastRequest'] = current_time('mysql', 1);
                        } else {
                            $toUpdate['nbRequest'] = $sitemapItem['nbRequest'] + 1;
                            $toUpdate['lastRequest'] = current_time('mysql', 1);
                        }
                    }
                    $this->settings->setSitemapItem($hash, $toUpdate);
                    if (isset($toUpdate['active']) && $toUpdate['active'] === 1) {
                        // clean up with potential max value reached
                        $this->settings->purgeExceededPlanPages();
                    }
                }
                return $body;
            }

            // Request can be blast
            // Use third party contribution point so that they can take action
            // This may avoid having cache plugins cache wpblast results
            // This avoid having to stop caching in case the request won't be taken in charge by wpblast (case where it's disabled or if the page isn't allowed)
            // This may create a bug in case of upgrade of plan with cache plugins had cached the request
            do_action('wpblast_actions_before_start');

            if ($sitemapItem === null) {
                // save in db new hash before launching blasting
                // don't save lastRequest or nbRequest as this will be update later
                $toUpdate = [
                    'hashVariables' => Utils::format_json_encode($hashCompute['variables']),
                    'url' => $url,
                ];
                $toUpdate['active'] = $this->defaultActiveValueForUrl($url);
                $this->settings->setSitemapItem($hash, $toUpdate);
                if (isset($toUpdate['active']) && $toUpdate['active'] === 1) {
                    // clean up with potential max value reached
                    $this->settings->purgeExceededPlanPages();
                }
            }
            if (isset($_GET[self::WPBLAST_CRAWLER]) && (!$isValidForceGeneration || ($isValidForceGeneration && !$isInGracePeriod))) {
                $hashComputeUser = $this->hashKey($body, $static, 0, $url, $withScroll, $withImages, $injectCss);
                $hashUser = $hashComputeUser['hash'];
                $cachedUser = $this->getCacheItem($hashUser);
                if ($cachedUser) {
                    // don't update cache sitemap in case its a user connected
                    return $cachedUser;
                }
            }
            $cached = $this->getCacheItem($hash);
            if ($cached && (!$isValidForceGeneration || ($isValidForceGeneration && !$isInGracePeriod))) {
                // don't count request of force generation, whatever if the token is valid
                if (!$isForceGeneration && !isset($_GET[self::WPBLAST_CRAWLER])) {
                    $this->settings->setSitemapItem($hash, [
                        'url' => $url, // update url to allow change of url but keeping the same hashkey
                        'lastRequest' => current_time('mysql', 1),
                        'nbRequest' => $sitemapItem['nbRequest'] + 1,
                    ]);
                }
                return $cached;
            }
            $content = $this->blast($url, $body, $static, $withScroll, $withImages, $injectCss, $hash);

            $expire = $this->getExpiration($static, $withScroll, $withImages, $injectCss, $url, $body, $hash, $content);
            if ($expire === 0) {
                $expire = 1; // Disable default no expiration feature of wordpress by setting 0
            }

            // In case the hash doesn't change, the item will be updated and set a new expiration date
            $toUpdate = [
                'hashVariables' => Utils::format_json_encode($hashCompute['variables']),
                'url' => $url,
                'lastGen' => current_time('mysql', 1),
                'cacheExpiration' => time() + $expire,
                'cache' => $content,
            ];
            // reactive home url if disabled
            if (isset($sitemapItem) && $sitemapItem['active'] !== '1' && $this->isHomeUrl($url)) {
                $toUpdate['active'] = 1;
            }
            if (!$isForceGeneration && !isset($_GET[self::WPBLAST_CRAWLER])) {
                if (!isset($sitemapItem) || !isset($sitemapItem['nbRequest']) || $sitemapItem['nbRequest'] < 1) {
                    if (!isset($sitemapItem['active'])) { // only hydrate active field if doesn't exist
                        $toUpdate['active'] = $this->defaultActiveValueForUrl($url);
                    }
                    $toUpdate['nbRequest'] = 1;
                    $toUpdate['lastRequest'] = current_time('mysql', 1);
                } else {
                    $toUpdate['nbRequest'] = $sitemapItem['nbRequest'] + 1;
                    $toUpdate['lastRequest'] = current_time('mysql', 1);
                }
            }
            $this->settings->setSitemapItem($hash, $toUpdate);
            if (isset($toUpdate['active']) && $toUpdate['active'] === 1) {
                // clean up with potential max value reached
                $this->settings->purgeExceededPlanPages();
            }

            $this->settings->purgeExceededItemsCache(); // remove transitient exceeding max_cache_items
            $this->settings->cleanExpiredCache(); // clean up expired cache items

            do_action('wpblast_actions_before_render', $hash, $toUpdate);

            return $content;
        } catch (\Throwable $e) {

            // In case of error, we'll only keep track of the request of the crawler to add it to the sitemap of the website
            $sitemapItem = $this->settings->getSitemapItem($hash);
            $isForceGeneration = $this->isForceGeneration($hashCompute, $body);
            $toUpdate = [
                'hashVariables' => Utils::format_json_encode($hashCompute['variables']),
                'url' => $url,
            ];
            // reactive home url if disabled
            if (isset($sitemapItem) && $sitemapItem['active'] !== '1' && $this->isHomeUrl($url)) {
                $toUpdate['active'] = 1;
            }
            if (!$isForceGeneration && !isset($_GET[self::WPBLAST_CRAWLER])) {
                if (!isset($sitemapItem) || !isset($sitemapItem['nbRequest']) || $sitemapItem['nbRequest'] < 1) {
                    if (!isset($sitemapItem['active'])) { // only hydrate active field if doesn't exist
                        $toUpdate['active'] = $this->defaultActiveValueForUrl($url);
                    }
                    $toUpdate['nbRequest'] = 1;
                    $toUpdate['lastRequest'] = current_time('mysql', 1);
                } else {
                    $toUpdate['nbRequest'] = $sitemapItem['nbRequest'] + 1;
                    $toUpdate['lastRequest'] = current_time('mysql', 1);
                }
            }
            $this->settings->setSitemapItem($hash, $toUpdate);
            if (isset($toUpdate['active']) && $toUpdate['active'] === 1) {
                // clean up with potential max value reached
                $this->settings->purgeExceededPlanPages();
            }

            $this->settings->purgeExceededItemsCache(); // remove exceeding size
            $this->settings->cleanExpiredCache(); // clean up expired cache items

            return $this->handleError($e, $body, true);
        }
    }

    public function blast($url, $body, $static, $withScroll, $withImages, $injectCss, $hash)
    {
        $array_merge = array_merge(
            [
                'Content-Type' => 'application/json; charset=utf-8',
                'User-Agent' => Settings::WPBLAST_UA_PLUGIN,
            ],
            strlen($this->settings->getUsername()) > 0 ? [
                'Authorization' => 'Basic ' . base64_encode($this->settings->getUsername() . ':' . $this->settings->getPassword()),
            ] : []
        );

        $userAccount = $this->settings->getAccount(); // update user account only if transient has expired
        $plans = [];
        if (isset($userAccount['plans'])) {
            $plans = $userAccount['plans'];
        }

        $response = wp_remote_post($this->get_wpblast_url() . '/generator', [
            'headers'     => $array_merge,
            'body' => Utils::format_json_encode([
                'url' => $url,
                'body' => $body,
                'static' => $static,
                'withScroll' => $withScroll,
                'withImages' => $withImages,
                'injectCss' => $injectCss,
                'hash' => $hash,
                'plans' => $plans, // this is not used for plan check but only to flag a loose of sync of database
            ]),
            'timeout' => $this->settings->getTimeout(),
            'method'      => 'POST',
            'data_format' => 'body',
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) { // Safe failure
            throw new \Error('<h1>WP Blast Failure</h1>Unable to generate a static pre-render with http code ' . wp_remote_retrieve_response_code($response));
        }
        $bodyResponse = wp_remote_retrieve_body($response);
        if (isset($bodyResponse) && $bodyResponse !== '') {
            $rendering = json_decode($bodyResponse);
            if (isset($rendering) && isset($rendering->body)) {
                return $rendering->body;
            } else {
                throw new \Error('<h1>WP Blast Failure</h1>Invalid JSON parsing. Unable to generate a static pre-render with http code ' . wp_remote_retrieve_response_code($response));
            }
        } else {
            throw new \Error('<h1>WP Blast Failure</h1>Invalid body. Unable to generate a static pre-render with http code ' . wp_remote_retrieve_response_code($response));
        }
    }

    public function handleError($error, $failSafeValue, $return = true)
    {
        if ($this->forced) {
            if ($return) {
                header('HTTP/1.1 500 Internal Server Error');
                return $error;
            } else {
                throw $error;
            }
        } else {
            if (isset($error) && $error->getMessage() && $error->getMessage() !== '') {
                error_log($error->getMessage());
            }
            return $failSafeValue;
        }
    }

    /**
     * @param $url
     * @param $body
     * @param $hash
     * @param $content
     * @return mixed|void
     */
    private function getExpiration($static, $withScroll, $withImages, $injectCss, $url, $body, $hash, $content)
    {
        return apply_filters(
            'wpblast_render_cache_expiration',
            $this->settings->getCacheExpirationCrawlers(),
            [
                'url' => $url,
                'body' => $body,
                'static' => $static,
                'hash' => $hash,
                'withScroll' => $withScroll,
                'withImages' => $withImages,
                'injectCss' => $injectCss,
            ],
            $content
        );
    }
}
