<?php


namespace Smartfire\Wordpress\WPBlast;

class Settings
{
    const CRAWLER_DEFAULT_VALUE = ''; // default user defined crawler regexp is empty
    const WPBLAST_PURGE_CACHE = '__wpblast_purge_cache';
    const WPBLAST_GENERATE_CACHE = '__wpblast_generate_cache';
    const WPBLAST_PURGE_PLUGIN_CACHE = '__wpblast_purge_plugin_cache';
    const WPBLAST_PURGE_SITEMAP = '__wpblast_purge_sitemap';
    const WPBLAST_PURGE_PAGES_SCORES = '__wpblast_purge_pages_scores';
    const WPBLAST_REGISTERED_PLUGIN = '__wpblast_registeredPlugin';
    const WPBLAST_REGISTERED_PLUGIN_ERROR = '__wpblast_registeredPlugin_error';
    const PLUGIN_CACHE_PREFIX = 'wpblast_plugin';
    const WPBLAST_SITEMAP_TABLE = 'wpblast_sitemap';
    const WPBLAST_UA_PLUGIN = 'WP-BLAST-Bot-Plugin 1.8.6';

    private $menu_name = 'wpblast';
    private $plugin_name = 'wpblast';

    private $server = 'https://api.wp-blast.com';
    private $website = 'https://www.wp-blast.com';
    private $username = '';
    private $usernameHydrated;
    private $password = '';
    private $enableCrawler = true;
    private $enableAutoGenCache = true;
    private $crawlerRegexp = self::CRAWLER_DEFAULT_VALUE;
    private $crawlerAutoRegexp;
    private $crawlerListAuto;
    private $crawlerCacheGen = 'WP-BLAST-Bot-CacheGen';
    private $crawlerUserRegexp = '(^.+@smartfire\\.pro$)';
    private $api;
    private $cacheExpirationCrawlers = 60 * 60 * 24;
    private $cacheGrace = 60 * 15; // 15 min
    private $maxCacheItemsCrawlers = 10000;
    private $maxSitemapItems = 20000;
    private $cacheCrawlerList = 60 * 60 * 24;
    private $cacheUserAccount = 60 * 60 * 24;
    private $updatePluginDataFrequency = 60 * 60;
    private $cacheItemGarbageExpiration = 60 * 60 * 24 * 7; // a week
    private $purgeExceededItemsCacheRateLimit = 60; // 1 min
    private $cleanExpiredCacheRateLimit = 60; // 1 min
    private $withImages = true;
    private $withScroll = true;
    private $injectCss = false;
    private $rootPluginFile = __DIR__ . '/../../../../plugin.php';
    private $account;
    private $pluginData;
    private $sitemapItems = [];
    private $tablesExist = [];
    private $sitemap = []; // cache instance of sitemap to avoid reloading it multiple times in the same query
    /**
     * @var string
     */
    private $timeout = 30;
    private $formView = 'user-non-connected';

    public function __construct()
    {
        global $wpdb;

        if (isset($wpdb)) {
            $wpdb->wpblast_sitemap = $wpdb->prefix . self::WPBLAST_SITEMAP_TABLE;
        }
        $this->api = new SettingsApi();
    }


    public function registerMenus()
    {
        $menu_slug = $this->getPluginName();
        add_menu_page($this->menu_name, 'WP Blast', apply_filters('wpblast_permissions_admin_menu', 'wpblast_admin'), $menu_slug, [$this, 'displayPluginAdminDashboard'], $this->getSVGiconMenu(), 100);
        add_submenu_page($this->menu_name, $this->getPluginName(), 'Settings', apply_filters('wpblast_permissions_admin_menu', 'wpblast_admin'), $menu_slug, [$this, 'displayPluginAdminDashboard']);
    }

    public function getSVGiconMenu()
    {
        $icon = 'PHN2ZyB3aWR0aD0iNDg1IiBoZWlnaHQ9IjU2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2ZXJzaW9uPSIxLjEiPgogPHRpdGxlPldQQmxhc3Q8L3RpdGxlPgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJLnN0MHtmaWxsOiAjYTdhYWFkO30KPC9zdHlsZT4KIDxnPgogIDxwYXRoIGNsYXNzPSJzdDAiIGQ9Im0xODUuMDk0MzIsNDEyLjgxOTgybC00Ny40NTcsMi4yNTc4Yy0xLjg5NDUsMCAtMy41MjczLC0xLjA5NzcgLTQuMzA4NiwtMi42OTE0bC00My44NTUsLTk0LjEwMmw1Mi4wOTQsMzYuNjEzbC0zNS4yNzcsLTEwNS4yM2w1NS40MTgsNTMuNjg0bC01Ljc4NTIsLTEyOC45N2w2OC40OTYsMTAxLjM0bDYuNjk5MiwtNjMuMjVsMjAuMjgxLDU1LjAxMmwzNi43OTMsLTEyMi42M2wzLjM2NzIsMTM5LjQ1bDMwLjc1OCwtMzUuNTQzbC0zLjEzNjcsNDkuNDQ1bDc2LjMwMSwtNzQuMzMybC01MS45NTMsMTUzLjU2bDQ5LjU2MiwtMTguNDFsLTMxLjM0NCw1My42NmwwLjAxNTYzLDAuMDA3ODFjLTAuOTAyMzQsMS41MzkxIC0yLjUzMTIsMi4zOTA2IC00LjE5OTIsMi4zNzExbC01Mi4yOTMsLTEuMjY5NWw5LjU3NDIsLTU3LjU2MmwtMzkuNTk4LDM1LjI0NmwtNS45OTYxLC00MS43OTNsLTYuOTQxNCwyMi40OTZsLTE0LjIxMSwtMjIuMzc5bC0zLjUwMzksMjcuNTYybC0zMy40NDUsLTQ0Ljc0MmwtMS4xMjg5LDYzLjcyM2wtMzAuNjM3LC0yMS40MDZsNS43MDcsMzcuODU5bDAuMDAzNzcsMC4wMjMxOXoiIGlkPSJzdmdfMTUiLz4KICA8cGF0aCBjbGFzcz0ic3QwIiBkPSJtMjQ2Ljc5ODI5LDIuNDkyMmwxMTYuOTIsNjcuNTA0bC0wLjAwNzgxLDAuMDE1NjNsMTE2Ljg5LDY3LjQ4OGw0LjM1NTUsMi41MTU2bDAsMjc5LjlsLTQuMzU1NSwyLjUxNTZsLTExNi44OSw2Ny40ODhsMC4wMDc4MSwwLjAxNTYybC0xMTYuOTIsNjcuNTA0bC00LjMyMDMsMi40OTIybC00LjMyMDMsLTIuNDkyMmwtMTE2LjkyLC02Ny41MDRsMC4wMDc4MSwtMC4wMTU2MmwtMTE2Ljg5LC02Ny40ODhsLTQuMzU1NSwtMi41MTU2bDAsLTI3OS45bDQuMzU1NSwtMi41MTU2bDExNi44OSwtNjcuNDg4bC0wLjAwNzgxLC0wLjAxNTYzbDExNi45MiwtNjcuNTA0bDQuMzIwMywtMi40OTIybDQuMzIwMywyLjQ5MjJ6bS00LjMyMDMsMjcuNDM4bC04NS43ODksNDkuNTMxbDAuMDA3ODEsMC4wMTU2M2wtMTMwLjc3LDc1LjUwNGwwLDI0OS45Njk5OWwxMzAuNzcsNzUuNTA0MDFsLTAuMDA3ODEsMC4wMTU2Mmw4NS43ODksNDkuNTMxbDg1Ljc4OSwtNDkuNTMxbC0wLjAwNzgxLC0wLjAxNTYzbDEzMC43NywtNzUuNTA0bDAsLTI0OS45N2wtMTMwLjc3LC03NS41MDRsMC4wMDc4MSwtMC4wMTU2MmwtODUuNzg5LC00OS41MzF6IiBpZD0ic3ZnXzE2Ii8+CiA8L2c+Cjwvc3ZnPg==';
        return 'data:image/svg+xml;base64,' . $icon;
    }

    public function onCrawlerOptionChange($oldValues, $newValues)
    {
        if (!isset($oldValues) || $oldValues === '') {
            $oldValues = [];
        }
        if (!isset($newValues) || $newValues === '') {
            $newValues = [];
        }
        // Check difference
        $optionsChanged = [];
        foreach ($oldValues as $oldKey => $oldValue) {
            if (isset($newValues[$oldKey])) {
                if ($oldValue !== $newValues[$oldKey]) {
                    if (!in_array($oldKey, $optionsChanged)) {
                        array_push($optionsChanged, $oldKey);
                    }
                }
            } else {
                if (!in_array($oldKey, $optionsChanged)) {
                    array_push($optionsChanged, $oldKey);
                }
            }
        }
        foreach ($newValues as $newKey => $newValue) {
            if (isset($oldValues[$newKey])) {
                if ($newValue !== $oldValues[$newKey]) {
                    if (!in_array($newKey, $optionsChanged)) {
                        array_push($optionsChanged, $newKey);
                    }
                }
            } else {
                if (!in_array($newKey, $optionsChanged)) {
                    array_push($optionsChanged, $newKey);
                }
            }
        }
        // Only get options that would need to regenerate the cache because the cache generated would be different
        $purgeCacheOptions = [
            'cache_expiration',
            'withImages',
            'withScroll',
            'injectCss',
        ];
        $needPurgeCache = false;
        foreach ($optionsChanged as $optionChange) {
            if (in_array($optionChange, $purgeCacheOptions)) {
                $needPurgeCache = true;
            }
        }
        if ($needPurgeCache) {
            $this->purgeCache();
        }
        // Only get option that would need to check for max cache items
        $maxCacheItemsOptions = [
            'max_cache_items',
        ];
        $needMaxItemsCheck = false;
        foreach ($optionsChanged as $optionChange) {
            if (in_array($optionChange, $maxCacheItemsOptions)) {
                $needMaxItemsCheck = true;
            }
        }
        if ($needMaxItemsCheck) {
            // Update value of maxItemsCache
            $this->maxCacheItemsCrawlers = intval($this->api->get_option('max_cache_items', 'wpblast_crawler', $this->maxCacheItemsCrawlers));
            $this->purgeExceededItemsCache();
        }
        // Only get option that would need to check for max sitemap items
        $maxSitemapItemsOptions = [
            'max_sitemap_items',
        ];
        $needMaxSitemapItemsCheck = false;
        foreach ($optionsChanged as $optionChange) {
            if (in_array($optionChange, $maxSitemapItemsOptions)) {
                $needMaxSitemapItemsCheck = true;
            }
        }
        if ($needMaxSitemapItemsCheck) {
            $this->maxSitemapItems = intval($this->api->get_option('max_sitemap_items', 'wpblast_crawler', $this->maxSitemapItems));
            $this->purgeExceededItemsCache();
        }

        // Option that needs to request a new crawl
        $requestCrawlOptions = [
            'enabled_auto_gen_cache',
        ];
        $needRequestCrawl = false;
        foreach ($optionsChanged as $optionChange) {
            if (in_array($optionChange, $requestCrawlOptions)) {
                $needRequestCrawl = true;
            }
        }
        if ($needRequestCrawl) {
            $this->enableAutoGenCache = $this->api->get_option('enabled_auto_gen_cache', 'wpblast_crawler', $this->enableAutoGenCache ? 'on' : '') === 'on';
            if ($this->isEnableAutoGenCache()) {
                $this->getPluginData(true);
                $this->requestCrawl();
            }
        }
        // do action, param $optionsChanged
        do_action('wpblast_updated_options', $optionsChanged, $this);

        return $oldValues;
    }

    private $has_been_init = false;

    public function init()
    {
        global $wpdb;
        if ($this->has_been_init) {
            return;
        }

        // Init tables in case wpdb wasn't available when call of constructor happened
        if (isset($wpdb)) {
            $wpdb->wpblast_sitemap = $wpdb->prefix . self::WPBLAST_SITEMAP_TABLE;
        }

        // Add Vary header to indicates to cache system like Varnish to update cache regarding user-agent
        header('Vary: User-Agent');

        add_action('admin_menu', [$this, 'registerMenus'], 9);
        add_action('admin_init', [$this, 'registerSettings']);
        add_filter('removable_query_args', function ($args) {
            $args[] = self::WPBLAST_PURGE_CACHE;
            $args[] = self::WPBLAST_PURGE_PLUGIN_CACHE;
            $args[] = self::WPBLAST_PURGE_PAGES_SCORES;
            $args[] = self::WPBLAST_PURGE_SITEMAP;
            $args[] = self::WPBLAST_GENERATE_CACHE;
            $args[] = self::WPBLAST_REGISTERED_PLUGIN;
            $args[] = self::WPBLAST_REGISTERED_PLUGIN_ERROR;
            return $args;
        });
        add_filter('update_option_wpblast_crawler', [$this, 'onCrawlerOptionChange'], 10, 2);

        add_filter('wpblast_crawlers_cachegen', function ($regexp) {
            return $this->getCrawlerCacheGen();
        });

        add_filter('wpblast_crawlers_regexp', function ($regexp) {
            return $this->getCrawlerRegexp();
        });

        add_filter('wpblast_crawlers_autoregexp', function ($regexp) {
            return $this->getCrawlerAutoRegexp();
        });

        add_filter('wpblast_crawlers_full', function ($regexp) {
            $crawlerCacheGen = $this->getCrawlerCacheGen();
            $crawlerRegexp = $this->getCrawlerRegexp();
            $crawlerAutoRegexp = $this->getCrawlerAutoRegexp();
            $returnValue = ''; // Security in case the filter is called twice
            if (isset($crawlerCacheGen) && $crawlerCacheGen !== '') {
                $returnValue .= $crawlerCacheGen;
            }
            if (isset($crawlerRegexp) && $crawlerRegexp !== '') {
                if ($returnValue !== '') {
                    $returnValue .= '|';
                }
                $returnValue .= $crawlerRegexp;
            }
            if (isset($crawlerAutoRegexp) && $crawlerAutoRegexp !== '') {
                if ($returnValue !== '') {
                    $returnValue .= '|';
                }
                $returnValue .= $crawlerAutoRegexp;
            }
            return $returnValue;
        });

        add_filter('wpblast_crawlers_list', function ($ua) {
            array_push($ua, [
                'pattern' => $this->getCrawlerCacheGen(),
                'type' => 'regexp',
            ]);
            $crawlerUserDefined = $this->getCrawlerRegexp();
            if ($crawlerUserDefined !== '') {
                array_push($ua, [
                    'pattern' => $crawlerUserDefined,
                    'type' => 'regexp',
                ]);
            }
            $crawlerAuto = $this->getCrawlerListAuto();
            if (is_array($crawlerAuto) && count($crawlerAuto) > 0) {
                foreach ($crawlerAuto as $c) {
                    if (isset($c['pattern']) && $c['pattern'] !== '') {
                        array_push($ua, [
                            'pattern' => $c['pattern'],
                            'type' => 'regexp',
                        ]);
                    }
                }
            }
            return $ua;
        });

        // Purge all cache on Widget updates because they can be on any page
        add_action('save_post_widget', function () {
            do_action('wpblast_purge_cache');
        });

        $this->cacheCrawlerList = apply_filters('wpblast_settings_crawler_list_expiration', $this->cacheCrawlerList);
        $this->cacheUserAccount = apply_filters('wpblast_settings_user_account_expiration', $this->cacheUserAccount);
        $this->updatePluginDataFrequency = apply_filters('wpblast_settings_plugin_data_expiration', $this->updatePluginDataFrequency);
        $this->cacheItemGarbageExpiration = apply_filters('wpblast_settings_cache_garbage_expiration', $this->cacheItemGarbageExpiration);

        $this->purgeExceededItemsCacheRateLimit = apply_filters('wpblast_settings_purge_exceeded_items_cache_rate_limit', $this->purgeExceededItemsCacheRateLimit);
        $this->cleanExpiredCacheRateLimit = apply_filters('wpblast_settings_clean_expired_cache_rate_limit', $this->cleanExpiredCacheRateLimit);

        $this->server = $this->api->get_option('server', 'wpblast_home', $this->server);
        $this->website = $this->api->get_option('website', 'wpblast_home', $this->website);
        $this->username = $this->api->get_option('username', 'wpblast_home', $this->username, '');
        // Generate password if doesn't exist
        $password = $this->api->get_option('password', 'wpblast_home', '');
        if ($password === '') {
            $optionToSave = get_option('wpblast_home');
            if (!is_array($optionToSave)) {
                $optionToSave = [];
            }
            $optionToSave['password'] = wp_generate_password(25, false, false);
            update_option('wpblast_home', $optionToSave);
        }
        $this->password = $this->api->get_option('password', 'wpblast_home', '');
        $this->enableCrawler = $this->api->get_option('enabled', 'wpblast_crawler', $this->enableCrawler ? 'on' : '') === 'on';
        $this->enableAutoGenCache = $this->api->get_option('enabled_auto_gen_cache', 'wpblast_crawler', $this->enableAutoGenCache ? 'on' : '') === 'on';
        $this->withImages = $this->api->get_option('withImages', 'wpblast_crawler', $this->withImages ? 'on' : '') === 'on';
        $this->withScroll = $this->api->get_option('withScroll', 'wpblast_crawler', $this->withScroll ? 'on' : '') === 'on';
        $this->injectCss = $this->api->get_option('injectCss', 'wpblast_crawler', $this->injectCss ? 'on' : '') === 'on';
        $this->crawlerRegexp = $this->api->get_option('regex', 'wpblast_crawler', $this->crawlerRegexp);
        $this->crawlerUserRegexp = $this->api->get_option('user_regex', 'wpblast_crawler', $this->crawlerUserRegexp);
        $this->timeout = intval($this->api->get_option('timeout', 'wpblast_home', $this->timeout));
        $this->cacheExpirationCrawlers = intval($this->api->get_option('cache_expiration', 'wpblast_crawler', $this->cacheExpirationCrawlers));
        $this->cacheGrace = intval($this->api->get_option('cache_grace', 'wpblast_crawler', $this->cacheGrace));
        $this->maxCacheItemsCrawlers = intval($this->api->get_option('max_cache_items', 'wpblast_crawler', $this->maxCacheItemsCrawlers));
        $this->maxSitemapItems = intval($this->api->get_option('max_sitemap_items', 'wpblast_crawler', $this->maxSitemapItems));

        add_action('wpblast_purge_cache', [$this, 'purgeCache']);
        add_action('wpblast_purge_plugin_cache', [$this, 'purgePluginCache']);
        add_action('wpblast_purge_sitemap', [$this, 'purgeSitemap']);
        add_action('wpblast_purge_exceeded_items_cache', [$this, 'purgeExceededItemsCache']);
        add_action('wpblast_clean_expired_cache', [$this, 'cleanExpiredCache']);
        add_action('wpblast_purge_pages_scores', [$this, 'purgePagesScores']);

        $this->has_been_init = true;

        // Protection against lock of requests when there is no plugin cache yet and we need to generate it
        // This is a protection but this case shouldn't happen
        // This has been moved after the has_been_init flag because it would trigger a second init otherwise due to trigger of getCrawlerAutoRegexp that could then trigger a generation of config and then a new init
        if (!isset($_SERVER['HTTP_WPBLAST_SITEMAP_GENERATION'])) {
            $this->getCrawlerAutoRegexp(); // update crawler list if expired
            $this->getAccount(); // update user account if expired
            $this->getPluginData(); // update plugin data to WP Blast at the given frequency
        }
    }

    // Force check that the plugin is still active
    // This is used to avoid concurrent requests bugs
    // Bug would be to not have a cleaned uninstall in case a request finish after the uninstallation
    public function isPluginActive()
    {
        global $wpdb;
        $pluginName = plugin_basename(realpath($this->rootPluginFile));
        $suppress = $wpdb->suppress_errors();
		$row      = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'active_plugins' ) );
		$wpdb->suppress_errors( $suppress );
		if ( is_object( $row ) ) {
			$value = maybe_unserialize($row->option_value);
		}
        return in_array( $pluginName, $value, true ) || is_plugin_active_for_network( $pluginName );
    }

    public function updatePluginData($force = false)
    {
        global $wp_version;
        // Update plugin data to wpblast
        $pluginData = get_transient(self::PLUGIN_CACHE_PREFIX . '_plugindata');
        if (!isset($_GET['method']) && (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') && (!$pluginData || $force)) {
            $toSend = [];

            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }

            $pluginMetaData = get_plugin_data($this->rootPluginFile);
            if (isset($pluginMetaData) && isset($pluginMetaData['Version'])) {
                $toSend['version'] = $pluginMetaData['Version'];
            }

            $plugins = get_plugins();
            $toSend['plugins'] = $plugins;

            $toSend['wpVersion'] = $wp_version;

            $crawlerSettings = get_option('wpblast_crawler');
            if ($crawlerSettings) {
                $toSend['settings'] = $crawlerSettings;
            }

            // use site_url instead of home_url to avoid having multiple home_url due to multilanguages
            $homeUrl = untrailingslashit(get_site_url());
            if (isset($homeUrl) && $homeUrl !== '') {
                $toSend['homeUrl'] = $homeUrl;
            }

            $toSend['sitemapUrls'] = $this->getWebsiteSitemap();

            $response = wp_remote_post($this->getWebsite() . '/?method=updatePluginData&domain=' . urlencode($this->getUsername()) . '&plugin_token=' . $this->getPassword(), [
                'user-agent' => self::WPBLAST_UA_PLUGIN,
                'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
                'body'        => Utils::format_json_encode($toSend),
                'method'      => 'POST',
                'data_format' => 'body',
                'timeout' => 30,
            ]);
            $res = wp_remote_retrieve_body($response);

            if (isset($res)) {
                $res = json_decode($res, true);
                // check that the plugin hasn't been deactivated while we were waiting for the result of the request
                // otherwise, we could save in database and trigger an action while the plugin shouldn't be activated
                if ($res && $this->isPluginActive()) {
                    $data = current_time('mysql', 1);
                    set_transient(self::PLUGIN_CACHE_PREFIX . '_plugindata', $data, $this->updatePluginDataFrequency);

                    do_action('wpblast_updated_plugin_data', $this);

                    return $data;
                } else {
                    $this->setFormView('error');
                }
            } else {
                $this->setFormView('error');
            }
        } else if (isset($pluginData) && $pluginData !== false) {
            return $pluginData;
        }
    }

    public function updateCrawlerList($force = false)
    {
        // Update crawler list if needed
        $crawlerList = get_transient(self::PLUGIN_CACHE_PREFIX . '_autoregexp');
        if (!isset($_GET['method']) && (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') && (!$crawlerList || $force)) {
            $response = wp_remote_get($this->getWebsite() . '/?method=updateCrawlerList&domain=' . urlencode($this->getUsername()) . '&plugin_token=' . $this->getPassword(), [
                'user-agent' => self::WPBLAST_UA_PLUGIN,
                'timeout' => 15,
            ]);
            if (is_wp_error($response)) { // safe fail
                $this->setFormView('error');
                return;
            }
            $res = wp_remote_retrieve_body($response);
            // check that the plugin hasn't been deactivated while we were waiting for the result of the request
            // otherwise, we could save in database and trigger an action while the plugin shouldn't be activated
            if (isset($res) && $res !== '' && $this->isPluginActive()) {
                $res = json_decode($res, true);
                if (isset($res['crawlers']) && $res['crawlers'] !== '') {
                    set_transient(self::PLUGIN_CACHE_PREFIX . '_autoregexp', $res['crawlers'], $this->cacheCrawlerList);

                    do_action('wpblast_updated_crawler_list', $this->cacheCrawlerList, $this);

                    return $res['crawlers'];
                } else if (isset($res['error']) && isset($res['error']['type']) && $res['error']['type'] === 'not-found') {
                    set_transient(self::PLUGIN_CACHE_PREFIX . '_autoregexp', '', $this->cacheCrawlerList);
                    return '';
                } else {
                    $this->setFormView('error');
                }
            } else {
                $this->setFormView('error');
            }
        } else if (isset($crawlerList) && $crawlerList !== false) {
            return $crawlerList;
        }
    }

    public function updateUserAccount($force = false)
    {
        global $wpdb;
        // Update account if needed
        $userAccount = get_transient(self::PLUGIN_CACHE_PREFIX . '_user');
        if (!isset($_GET['method']) && (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') && (!$userAccount || $force)) {
            // get user account from WP Blast website
            $response = wp_remote_get($this->getWebsite() . '/?method=getUserAccount&domain=' . urlencode($this->getUsername()) . '&plugin_token=' . $this->getPassword(), [
                'user-agent' => self::WPBLAST_UA_PLUGIN,
                'timeout' => 15,
            ]);
            if (is_wp_error($response)) { // safe fail
                $this->setFormView('error');
                return;
            }
            $res = wp_remote_retrieve_body($response);
            // check that the plugin hasn't been deactivated while we were waiting for the result of the request
            // otherwise, we could save in database and trigger an action while the plugin shouldn't be activated
            if (isset($res) && $res !== '' && $this->isPluginActive()) {
                $res = json_decode($res, true);
                if (
                    (isset($res['user']) && isset($res['user']['domains']) && isset($res['user']['plans']) && isset($res['user']['email']))
                    || (isset($res['error']) && isset($res['error']['type']) && $res['error']['type'] === 'not-found')
                ) {
                    $defaultUserAccount = [
                        'email' => '',
                        'plans' => [],
                        'domains' => [],
                        'features' => [
                            'maxPages' => 1, // guest plan has only one page allowed
                        ],
                    ];
                    if (isset($res['user']) && isset($res['user']['domains']) && isset($res['user']['plans']) && isset($res['user']['email'])) {
                        // Update current user account on the current website for later use
                        set_transient(self::PLUGIN_CACHE_PREFIX . '_user', $res['user'], $this->cacheUserAccount);
                        $this->account = $res['user'];
                    } else if (isset($res['error']) && isset($res['error']['type']) && $res['error']['type'] === 'not-found') {
                        set_transient(self::PLUGIN_CACHE_PREFIX . '_user', $defaultUserAccount, $this->cacheUserAccount);
                        $this->account = $defaultUserAccount;
                    }
                    // if change of feature maxPages, update active pages
                    if (
                        isset($res['user'])
                        && isset($res['user']['features'])
                        && isset($res['user']['features']['maxPages'])
                    ) {
                        $newMaxPagesAllowed = intval($res['user']['features']['maxPages']);
                        $maxPagesAllowed = $newMaxPagesAllowed > 0 ? $newMaxPagesAllowed : 1;
                    } else {
                        $maxPagesAllowed = 1;
                    }
                    if (
                        isset($userAccount)
                        && isset($userAccount['features'])
                        && isset($userAccount['features']['maxPages'])
                        && $userAccount['features']['maxPages'] !== $maxPagesAllowed
                    ) {
                        if ($this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {
                            $urls = $wpdb->get_col($wpdb->prepare("SELECT url FROM (SELECT MAX(active) AS maxActive, REPLACE(url, %s, '') as url, MAX(lastRequest) AS maxLastRequest FROM {$wpdb->wpblast_sitemap} GROUP BY REPLACE(url, %s, '')) AS tmp ORDER BY maxActive DESC, maxLastRequest DESC LIMIT 0, %d", '?' . PageRender::WPBLAST_CRAWLER, '?' . PageRender::WPBLAST_CRAWLER, $maxPagesAllowed));
                            $this->updateActivePages($urls);
                        }
                    }
                    // Generate cache if it's a first connection
                    if (get_transient(self::PLUGIN_CACHE_PREFIX . '_firstActivation')) {
                        // enable the plugin on wp-blast.com so that it can be crawled
                        $this->getPluginData(true);
                        $this->requestCrawl();
                        delete_transient(self::PLUGIN_CACHE_PREFIX . '_firstActivation');
                    }

                    do_action('wpblast_updated_user_account', $this->cacheUserAccount, $this);
                    if (isset($res['user']) && isset($res['user']['domains']) && isset($res['user']['plans']) && isset($res['user']['email'])) {
                        return $res['user'];
                    } else if (isset($res['error']) && isset($res['error']['type']) && $res['error']['type'] === 'not-found') {
                        return $defaultUserAccount;
                    }
                } else {
                    $this->setFormView('error');
                }
            } else {
                $this->setFormView('error');
            }
        } else if (isset($userAccount) && $userAccount !== false) {
            return $userAccount;
        }
    }

    public function updateSitemapScores($items = [])
    {
        global $wpdb;

        if (count($items) > 0 && $this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {

            // Send data about hash wanted to be sure it's sync with wp-blast.com server
            $sitemap = $this->getWebsiteSitemap();
            $sitemapIndexed = [];
            foreach ($sitemap as $sitemapItem) {
                $sitemapIndexed[$sitemapItem['hash']] = $sitemapItem;
            }
            $sitemapUrls = [];
            foreach ($items as $item) {
                if (!isset($item['hash']) || !isset($item['type']) || !isset($item['device'])) {
                    continue;
                }
                if (!isset($sitemapUrls[$item['hash']]) && isset($sitemapIndexed[$item['hash']])) {
                    $sitemapUrls[$item['hash']] = $sitemapIndexed[$item['hash']];
                }
            }

            $response = wp_remote_post($this->getWebsite() . '?method=getScores&domain=' . urlencode($this->getUsername()) . '&plugin_token=' . $this->getPassword(), [
                'user-agent' => self::WPBLAST_UA_PLUGIN,
                'method' => 'POST',
                'data_format' => 'body',
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => Utils::format_json_encode([
                    'sitemapUrls' => array_values($sitemapUrls),
                    'items' => $items,
                ]),
                'timeout' => 15,
            ]);

            $res = wp_remote_retrieve_body($response);

            if (isset($res) && $res !== '') {
                $response = json_decode($res, true);
                if (isset($response) && isset($response['response']) && is_array($response['response'])) {
                    $scores = $response['response'];
                    foreach ($scores as $hash => $item) {
                        $toUpdate = [];
                        if (isset($item['scores'])) {
                            $toUpdate['scores'] = $item['scores'];
                        }
                        // query in foreach but is limited by the number of scores that can be retrieve through the request, it depends on the user navigating so should be only tens of scores
                        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->wpblast_sitemap} SET scores = %s WHERE hash = %s", Utils::format_json_encode($toUpdate), $hash));
                    }
                } else {
                    $this->setFormView('error');
                }
            } else {
                $this->setFormView('error');
            }
        }
    }

    public function updateActivePages($activeUrls = [])
    {
        global $wpdb;

        $homeUrl = untrailingslashit(get_site_url());
        if (
            !in_array($homeUrl, $activeUrls)
            && !in_array($homeUrl . '/', $activeUrls)
            && !in_array($homeUrl . '/index.php', $activeUrls)
            && !in_array($homeUrl . '/index.html', $activeUrls)
        ) {
            // add in first position to allow stripping
            array_unshift($activeUrls, $homeUrl);
        }

        if ($this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {
            if (count($activeUrls) > 0) {
                // Update every active state
                // This is done in a single query to allow having a lot of update of pages without doing a lot of query
                // First enable active page
                $activation = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->wpblast_sitemap} SET active = 1 WHERE url IN (" . implode(', ', array_fill(0, count($activeUrls), '%s')) . ')', $activeUrls));

                // Then disable page not in the array
                $deactivation = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->wpblast_sitemap} SET active = 0 WHERE url NOT IN (" . implode(', ', array_fill(0, count($activeUrls), '%s')) . ')', $activeUrls));

                // Clean up in case too much url are active (shouldn't happened unless fraudulent call)
                $userAccount = $this->getAccount();
                if (isset($userAccount) && isset($userAccount['features']) && isset($userAccount['features']['maxPages'])) {
                    $maxPagesAllowed = $userAccount['features']['maxPages'];
                    $currentUse = $this->getNumberPagesUsed();
                    if (isset($currentUse) && $currentUse > $maxPagesAllowed) {
                        // should never be called unless fraud attempt or bug
                        // clean up active pages based on lastRequest, except for home url
                        // get urls to keep active
                        $urls = $wpdb->get_col($wpdb->prepare("SELECT url FROM (SELECT MAX(active) AS maxActive, REPLACE(url, %s, '') as url, MAX(lastRequest) AS maxLastRequest FROM {$wpdb->wpblast_sitemap} GROUP BY REPLACE(url, %s, '')) AS tmp WHERE maxActive >= 1 ORDER BY maxLastRequest DESC LIMIT 0, %d", '?' . PageRender::WPBLAST_CRAWLER, '?' . PageRender::WPBLAST_CRAWLER, $maxPagesAllowed));
                        // First enable active page
                        $activation2 = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->wpblast_sitemap} SET active = 1 WHERE url IN (" . implode(', ', array_fill(0, count($urls), '%s')) . ')', $urls));
                        // Then disable page not in the array
                        $deactivation2 = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->wpblast_sitemap} SET active = 0 WHERE url NOT IN (" . implode(', ', array_fill(0, count($urls), '%s')) . ')', $urls));
                        $activation = $activation !== false && $activation2 !== false;
                        $deactivation = $deactivation !== false && $deactivation2 !== false;
                    }
                }

                if ($activation === false || $deactivation === false) {
                    $this->setFormView('error');
                }
            } else {
                // no active pages wanted, disable every pages
                // should never be called due to at least home url
                $deactivation = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->wpblast_sitemap} SET active = 0"));

                if ($deactivation === false) {
                    $this->setFormView('error');
                }
            }
        }
    }

    public function isLocalMode()
    {
        return isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');
    }

    public function userHasGuestPlan()
    {
        $userAccount = $this->getAccount();
        $isRegistered = true;
        if (isset($userAccount) && isset($userAccount['email']) && $userAccount['email'] !== '') { // guest users are users non-registered, free users are registered users with a free plan or no plan selected
            $isRegistered = false;
        }
        return $isRegistered;
    }

    // User is registered and has no plan or a free plan
    public function userHasFreePlan()
    {
        $userAccount = $this->getAccount();
        $plans = [];
        $isRegistered = false;
        if (isset($userAccount) && isset($userAccount['email']) && $userAccount['email'] !== '') {
            $isRegistered = true;
        }
        if (isset($userAccount) && isset($userAccount['plans'])) {
            $plans = $userAccount['plans'];
        }
        $hasPaidPlan = false;
        foreach ($plans as $plan) {
            if (isset($plan['type']) && $plan['type'] !== 'free') { // user registered with a plan different from free plan
                $hasPaidPlan = true;
                break;
            }
        }
        return $isRegistered && (count($plans) === 0 || !$hasPaidPlan);
    }

    public function userHasPaidPlan()
    {
        $userAccount = $this->getAccount();
        $plans = [];
        if (isset($userAccount) && isset($userAccount['plans'])) {
            $plans = $userAccount['plans'];
        }
        $hasPaidPlan = false;
        foreach ($plans as $plan) {
            if (isset($plan['type']) && $plan['type'] !== 'free') { // except guest user (without any plan) and user registered with free plan
                $hasPaidPlan = true;
                break;
            }
        }
        return $hasPaidPlan;
    }

    public function getWebsiteSitemapPlan()
    {
        $toCrawl = [];
        if ($this->userHasGuestPlan()) {
            $toCrawl = [
                // use site_url instead of home_url to avoid having multiple home_url due to multilanguages
                [
                    'url' => untrailingslashit(get_site_url()),
                ],
            ];
        } else {
            $toCrawl = $this->getWebsiteSitemap();
        }

        // Strip toCrawl to maximum number of max_cache_items
        // should get items with a maximum number
        if ($this->getMaxCacheItemsCrawlers() < count($toCrawl)) {
            $toCrawl = array_slice($toCrawl, 0, $this->getMaxCacheItemsCrawlers());
        }
        // Map value to get only url
        return array_map(function ($a) {
            if (isset($a['url'])) {
                return $a['url'];
            } else {
                '';
            }
        }, $toCrawl);
    }

    public function getWebsiteSitemap($orderBy = 'lastRequest')
    {
        global $wpdb;
        if (!isset($this->sitemap[$orderBy])) {
            // get sitemap from DB
            // avoid getting cache value to avoid loading every cache of the website in ram
            // prioritize by newest url by default
            if ($this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {
                if ($orderBy === 'url') {
                    $sitemap = $wpdb->get_results("SELECT active, hash, url, dateAdd, dateUpdate, lastRequest, nbRequest, lastGen, cacheExpiration FROM {$wpdb->wpblast_sitemap} ORDER BY CHAR_LENGTH(url) ASC", ARRAY_A);
                } else if ($orderBy === 'active') {
                    // hashVariables used for cache section in admin to have details on a row
                    // request used by admin settings page and have specific fields / ! \
                    $sitemap = $wpdb->get_results("SELECT active, hash, hashVariables, url, dateAdd, dateUpdate, lastRequest, nbRequest, lastGen, cacheExpiration, scores FROM {$wpdb->wpblast_sitemap} ORDER BY active DESC, nbRequest DESC, CHAR_LENGTH(url) ASC", ARRAY_A);
                } else {
                    // send scores to wp-blast.com so that the server can know the scores saved in database by the plugin
                    $sitemap = $wpdb->get_results("SELECT active, hash, url, dateAdd, dateUpdate, lastRequest, nbRequest, lastGen, cacheExpiration, scores FROM {$wpdb->wpblast_sitemap} ORDER BY lastRequest DESC, CHAR_LENGTH(url) ASC", ARRAY_A);
                }
            }

            if (isset($sitemap)) {
                $this->sitemap[$orderBy] = $sitemap;
            } else {
                return [];
            }
        }
        return $this->sitemap[$orderBy];
    }

    public function isUrlActive($url)
    {
        global $wpdb;
        if ($this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {
            $urlUntrailingSlashed = untrailingslashit($url);
            $nb = $wpdb->get_var($wpdb->prepare("SELECT MAX(active) AS active FROM {$wpdb->wpblast_sitemap} WHERE (url = %s OR url = CONCAT(%s, '/')) GROUP BY REPLACE(url, %s, '')", [$urlUntrailingSlashed, $urlUntrailingSlashed, '?' . PageRender::WPBLAST_CRAWLER]));
            $nb = intval($nb);
            if ($nb >= 1) {
                return true;
            } else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    public function getSitemapItem($hash)
    {
        global $wpdb;

        if (isset($hash)) {
            if (!isset($this->sitemapItems[$hash])) {
                if ($this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {
                    $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->wpblast_sitemap} WHERE hash = %s", [$hash]), ARRAY_A);
                    if (isset($item)) {
                        $this->sitemapItems[$hash] = $item;
                    } else {
                        return null;
                    }
                }
                else {
                    return null;
                }
            }
            return $this->sitemapItems[$hash];
        } else {
            return [];
        }
    }

    public function setSitemapItem($hash, $data)
    {
        global $wpdb;
        if (isset($hash) && $this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {
            $itemId = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->wpblast_sitemap} WHERE hash = %s", [$hash]));

            $toUpdate = [
                'dateUpdate' => current_time('mysql', 1),
            ];

            if (isset($data['active'])) {
                $toUpdate['active'] = $data['active'];
            }
            if (isset($data['hashVariables'])) {
                $toUpdate['hashVariables'] = $data['hashVariables'];
            }
            if (isset($data['url'])) {
                $toUpdate['url'] = $data['url'];
            }
            if (isset($data['lastRequest'])) {
                $toUpdate['lastRequest'] = $data['lastRequest'];
            }
            if (isset($data['nbRequest'])) {
                $toUpdate['nbRequest'] = $data['nbRequest'];
            }
            if (isset($data['lastGen'])) {
                $toUpdate['lastGen'] = $data['lastGen'];
            }
            if (isset($data['cacheExpiration'])) {
                $toUpdate['cacheExpiration'] = $data['cacheExpiration'];
            }
            if (isset($data['cache'])) {
                $toUpdate['cache'] = $data['cache'];
            }

            if ($itemId) {
                $isSuccess = $wpdb->update(
                    $wpdb->wpblast_sitemap,
                    $toUpdate,
                    [
                        'hash' => $hash,
                    ]
                );
                if ($isSuccess) {
                    if (isset($this->sitemapItems[$hash])) {
                        $this->sitemapItems[$hash] = array_merge($this->sitemapItems[$hash], $data);
                    } else {
                        $this->sitemapItems[$hash] = $data;
                    }
                }
            } else {
                $toInsert = array_merge($toUpdate, [
                    'hash' => $hash,
                ]);
                $isSuccess = $wpdb->insert(
                    $wpdb->wpblast_sitemap,
                    $toInsert
                );
                if ($isSuccess) {
                    $this->sitemapItems[$hash] = $toInsert;
                }
            }
        }
    }

    public function displayPluginAdminDashboard()
    {
        global $smartfire_wpblast_config;
        $wpblast_config_file_path = $smartfire_wpblast_config->get_config_file_path();
        if (isset($wpblast_config_file_path) && isset($wpblast_config_file_path['success']) && $wpblast_config_file_path['success'] === false) {
            if (function_exists('wpblast_generate_config_file')) {
                wpblast_generate_config_file();
            }
        }

        // Add js script for settings page
        wp_enqueue_script('wpblast-admin-settings-js', plugins_url('js/wpblast-settings.js', $this->rootPluginFile), ['jquery'], defined('WPBLAST_PLUGIN_VERSION') ? WPBLAST_PLUGIN_VERSION : false);
        wp_enqueue_script('wpblast-status', get_rest_url(null, '/wpblast/v1/getWpBlastStatus/'), [], defined('WPBLAST_PLUGIN_VERSION') ? WPBLAST_PLUGIN_VERSION : false);

        // Generate nonce to secure the request made by the script
        $nonce = wp_create_nonce('wp_rest');
        wp_add_inline_script('wpblast-admin-settings-js', 'var wpblast_nonce = "' . $nonce . '";', 'before');

        echo '
            <div class="notice notice-warning is-dismissible inline wpblast-result-dynamic-update" style="line-height: 1.4; padding: 11px 15px; font-size: 14px; margin: 25px 20px 0 2px;display: none;"></div>
        ';

        if (isset($_GET[self::WPBLAST_PURGE_CACHE])) {
            unset($_GET[self::WPBLAST_PURGE_CACHE]);
            do_action('wpblast_purge_cache');
            echo '<div>
  <div class="notice notice-success is-dismissible inline" style="line-height: 1.4; padding: 11px 15px; font-size: 14px; margin: 25px 20px 0 2px;">' . esc_html__('Cache has been cleared', 'wpblast') . '</div>
</div>';
        }

        if (isset($_GET[self::WPBLAST_PURGE_PLUGIN_CACHE])) {
            unset($_GET[self::WPBLAST_PURGE_PLUGIN_CACHE]);
            do_action('wpblast_purge_plugin_cache');
            echo '<div>
  <div class="notice notice-success is-dismissible inline" style="line-height: 1.4; padding: 11px 15px; font-size: 14px; margin: 25px 20px 0 2px;">' . esc_html__('Cache has been cleared', 'wpblast') . '</div>
</div>';
        }

        if (isset($_GET[self::WPBLAST_PURGE_SITEMAP])) {
            unset($_GET[self::WPBLAST_PURGE_SITEMAP]);
            do_action('wpblast_purge_sitemap');
            echo '<div>
  <div class="notice notice-success is-dismissible inline" style="line-height: 1.4; padding: 11px 15px; font-size: 14px; margin: 25px 20px 0 2px;">' . esc_html__('Sitemap has been cleared', 'wpblast') . '</div>
</div>';
        }

        if (isset($_GET[self::WPBLAST_PURGE_PAGES_SCORES])) {
            unset($_GET[self::WPBLAST_PURGE_PAGES_SCORES]);
            do_action('wpblast_purge_pages_scores');
            echo '<div>
  <div class="notice notice-success is-dismissible inline" style="line-height: 1.4; padding: 11px 15px; font-size: 14px; margin: 25px 20px 0 2px;">' . esc_html__('Pages scores has been cleared', 'wpblast') . '</div>
</div>';
        }

        if (isset($_GET[self::WPBLAST_REGISTERED_PLUGIN])) {
            unset($_GET[self::WPBLAST_REGISTERED_PLUGIN]);
            $this->getAccount(true); // fix a bug that would force the user to reload the page to update his user account status after connecting
            echo '<script type="text/javascript">window.location.reload();</script>'; // reload the page to take into account the update of account and fix bug due to no refresh of settings sections
        }

        if (isset($_GET[self::WPBLAST_GENERATE_CACHE])) { // Start cache generation
            unset($_GET[self::WPBLAST_GENERATE_CACHE]);

            if ($this->isEnableAutoGenCache()) {
                // Request for a crawl by wp-blast.com
                $isRequestSent = $this->requestCrawl();
                if ($isRequestSent) {
                    echo '<div class="notice notice-success is-dismissible inline wpblast-result-generate-cache" style="line-height: 1.4; padding: 11px 15px; font-size: 14px; margin: 25px 20px 0 2px;">' . esc_html__('Cache is being generated. This will take some time, please wait. You can refresh at will to check status.', 'wpblast') . '</div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible inline wpblast-result-generate-cache" style="line-height: 1.4; padding: 11px 15px; font-size: 14px; margin: 25px 20px 0 2px;">' . esc_html__('Error while requesting crawl.', 'wpblast') . '</div>';
                }
            } else {
                $toCrawl = $this->getWebsiteSitemapPlan();

                wp_enqueue_script('wpblast-admin-manual-cache-generation-js', plugins_url('js/wpblast-manual-cache-generation.js', $this->rootPluginFile), ['jquery', 'wpblast-admin-settings-js'], defined('WPBLAST_PLUGIN_VERSION') ? WPBLAST_PLUGIN_VERSION : false);
                wp_add_inline_script('wpblast-admin-manual-cache-generation-js', 'var toCrawl = ' . Utils::format_json_encode($toCrawl) . ';', 'before');
                echo '<div>
                    <div class="notice notice-warning is-dismissible inline wpblast-result-generate-cache" style="line-height: 1.4; padding: 11px 15px; font-size: 14px; margin: 25px 20px 0 2px;">' . esc_html__('Pending cache generation...', 'wpblast') . '</div>
                </div>';
            }
        }

        echo '<div class="wpblast-admin-container"><div class="wrap">';
        //settings_errors(); // commented because it would create a dual display of the confirmation of save

        $userAccount = $this->getAccount();
        $crawlerList = $this->getCrawlerAutoRegexp();

        if (!$userAccount || (!$crawlerList && $crawlerList !== '')) {
            $this->setFormView('error');
        } else if ($userAccount['email'] === '') {
            $this->setFormView('user-non-connected');
        } else if ($userAccount['email'] !== '') {
            $this->setFormView('user-connected');
        } else {
            // default to error
            $this->setFormView('error');
        }

        $this->displayFormView();

        $this->displayRegistrationForm();

        $this->api->show_navigation();
        $this->api->show_forms();

        echo '</div></div>';
    }

    public function setFormView($view)
    {
        if ($this->formView !== 'error') {
            $this->formView = $view; //error takes precedence if it has already been defined
        }
    }

    public function displayFormView()
    {
        $userAccountVariables = [];
        $registrationFormVariables = [];
        switch ($this->formView) {
            case 'user-non-connected':
            case 'user-connected':
                $userAccount = $this->getAccount();

                $email = $userAccount['email'] ? $userAccount['email'] : 'Non-registered';

                $domainsToDisplay = '';
                if (count($userAccount['domains']) > 0) {
                    foreach ($userAccount['domains'] as $domain => $domainValue) {
                        $domainsToDisplay .= $domain . '<br/>';
                    }
                } else {
                    $domainsToDisplay .= $this->getUsername() . '<br/>';
                }
                $plansToDisplay = '';
                if (count($userAccount['plans']) > 0) {
                    foreach ($userAccount['plans'] as $plan) {
                        $plansToDisplay .= $plan['description'] . '<br/>';
                    }
                } else {
                    if ($this->userHasFreePlan()) {
                        $plansToDisplay .= 'Free<br/>';
                    } else if ($this->userHasGuestPlan()) {
                        $plansToDisplay .= 'Guest<br/>';
                    } else {
                        $plansToDisplay .= '-<br/>';
                    }
                }

                if ($this->userHasGuestPlan()) {
                    $plansToDisplay .= '<br/><i>' . esc_html__('Your current plan only optimize your homepage.', 'wpblast') . '</i><br/><a href="#" class="wpblast-register-form-open">' . esc_html__('Create a free account', 'wpblast') . '</a> ' . esc_html__('to get more pages optimized.', 'wpblast');
                } else if ($this->userHasFreePlan()) {
                    $plansToDisplay .= '<br/><i>' . esc_html__('Your current plan only optimize few pages of your website.', 'wpblast') . '</i><br/><a target="_blank" href="' . $this->getWebsite() . '/pricing/?utm_medium=plugin&utm_source=' . urlencode($this->getUsername()) . '">' . esc_html__('Upgrade to premium', 'wpblast') . '</a> ' . esc_html__('to optimize your whole website.', 'wpblast');
                }

                $userAccountVariables = [
                    'email' => wp_kses_post($email),
                    'domainsToDisplay' => wp_kses_post($domainsToDisplay),
                    'plansToDisplay' => wp_kses_post($plansToDisplay),
                    'rights' => [
                        'hasGuestPlan' => $this->userHasGuestPlan(),
                        'hasFreePlan' => $this->userHasFreePlan(),
                        'hasPaidPlan' => $this->userHasPaidPlan(),
                    ],
                    'features' => [
                        'maxPages' => isset($userAccount) && isset($userAccount['features']) && isset($userAccount['features']['maxPages']) ? $userAccount['features']['maxPages'] : 1,
                    ],
                    'homeUrl' => untrailingslashit(get_site_url()),
                ];

                $currentUser = wp_get_current_user();
                $generatedPassword = wp_generate_password(16);

                $registrationFormVariables = [
                    'firstName' => isset($currentUser) && isset($currentUser->user_firstname) ? wp_kses_post($currentUser->user_firstname) : '',
                    'lastName' => isset($currentUser) && isset($currentUser->user_lastname) ? wp_kses_post($currentUser->user_lastname) : '',
                    'email' => isset($currentUser) && isset($currentUser->user_email) ? wp_kses_post($currentUser->user_email) : '',
                    'password' => wp_kses_post($generatedPassword),
                    'url' => $this->getRegistrationUrl(),
                ];

                break;
            case 'error':
                break;
            default:
                break;
        }

        // Registration error variables
        if (isset($_GET[self::WPBLAST_REGISTERED_PLUGIN_ERROR]) && $_GET[self::WPBLAST_REGISTERED_PLUGIN_ERROR] !== '') {
            switch ($_GET[self::WPBLAST_REGISTERED_PLUGIN_ERROR]) {
                case 'userAlreadyExists':
                    $message = 'User already exists. <a href="' . $this->getRegistrationUrl() . '">Please login.</a>';
                    break;
                case 'userRegistrationError':
                    $message = 'Error while registering user.';
                    break;
                default:
                    $message = 'Unknown error while registering.';
                    break;
            }
            $registrationFormVariables['registrationError'] = [
                'openRegistrationForm' => true,
                'message' => $message,
            ];
        } else {
            $registrationFormVariables['registrationError'] = [
                'openRegistrationForm' => false,
                'message' => '',
            ];
        }

        // Cache section variables
        if ($this->userHasGuestPlan()) {
            $cacheUpgradeMessage = '<i>' . esc_html__('Your current plan only optimize your homepage.', 'wpblast') . '</i><br/><a class="wpblast-register-form-open" href="#">' . esc_html__('Create a free account', 'wpblast') . '</a> ' . esc_html__('to get more pages optimized.', 'wpblast');
        } else if ($this->userHasFreePlan()) {
            $cacheUpgradeMessage = '<i>' . esc_html__('Your current plan only optimize few pages of your website.', 'wpblast') . '</i><br/><a target="_blank" href="' . $this->getWebsite() . '/pricing/?utm_medium=plugin&utm_source=' . urlencode($this->getUsername()) . '">' . esc_html__('Upgrade to premium', 'wpblast') . '</a> ' . esc_html__('to optimize your whole website.', 'wpblast');
        } else {
            $cacheUpgradeMessage =  '';
        }

        // Send variables to frontend
        wp_add_inline_script('wpblast-admin-settings-js', 'var wpBlastSettings = ' . Utils::format_json_encode([
            'restUrls' => [
                'updatePluginData' => get_rest_url(null, '/wpblast/v1/updatePluginData/'),
                'updateUserAccount' => get_rest_url(null, '/wpblast/v1/updateUserAccount/'),
                'updateCrawlerList' => get_rest_url(null, '/wpblast/v1/updateCrawlerList/'),
                'generateCacheItem' => get_rest_url(null, '/wpblast/v1/generateCacheItem/'),
                'getSiteMap' => get_rest_url(null, '/wpblast/v1/getSitemap/'),
                'updateActivePages' => get_rest_url(null, '/wpblast/v1/updateActivePages/'),
            ],
            'userAccount' => $userAccountVariables,
            'registrationForm' => $registrationFormVariables,
            'formView' => $this->formView,
            'debugView' => isset($_GET['debug']) ? true : false,
            'pluginUrl' => plugins_url('', $this->rootPluginFile),
            'isLocalMode' => $this->isLocalMode(),
            'wpblastUrl' => $this->getWebsite(),
            'cache' => [
                'upgradeMessage' => $cacheUpgradeMessage,
            ],
        ]) . ';', 'before');
    }

    public function displayRegistrationForm()
    {
        echo '
        <div class="wpblast-register-form">
            <div class="wpblast-register-form-header">
                <h3>' . esc_html__('Create a Free Account', 'wpblast') . '</h3>
                <button class="wpblast-register-form-close button">' . esc_html__('Close', 'wpblast') . '</button>
            </div>
            <div class="notice notice-warning is-dismissible inline notice-error wpblast-register-account-error" style="line-height: 1.4; padding: 11px 15px; font-size: 14px; margin: 0 0 20px 0; display: none;"></div>
            <div class="wpblast-register-form-body">
                <div class="wpblast-register-form-row">
                    <div class="input-group">
                        <label for="wpblast-register-account-firstname">' . esc_html__('First Name', 'wpblast') . '</label>
                        <input id="wpblast-register-account-firstname" class="wpblast-register-account-firstname" type="text" value="" />
                    </div>
                    <div class="input-group">
                        <label for="wpblast-register-account-lastname"> ' . esc_html__('Last Name', 'wpblast') . '</label>
                        <input id="wpblast-register-account-lastname" class="wpblast-register-account-lastname" type="text" value="" />
                    </div>
                </div>
                <div class="wpblast-register-form-row">
                    <div class="input-group">
                        <label for="wpblast-register-account-email">' . esc_html__('Email', 'wpblast') . '</label>
                        <input id="wpblast-register-account-email" class="wpblast-register-account-email" type="text" value="" />
                        <p class="description">' . esc_html__('By creating an account, you will be able to manage multiple websites with your same WP Blast account.', 'wpblast') . '</p>
                    </div>
                </div>
                <div class="wpblast-register-form-row">
                    <div class="input-group">
                        <label for="wpblast-register-account-password">' . esc_html__('Password', 'wpblast') . '</label>
                        <input id="wpblast-register-account-password" class="wpblast-register-account-password" type="text" value="" />
                        <p class="description">' . esc_html__('A strong password has been generated.', 'wpblast') . ' <a href="#" class="wpblast-show-password">' . esc_html__('Hide', 'wpblast') . '</a></p>
                    </div>
                </div>
                <div class="wpblast-register-form-row">
                        <input id="wpblast-register-account-optin" class="wpblast-register-account-optin" type="checkbox" />
                        <label for="wpblast-register-account-optin">' . esc_html__('I accept to receive updates and advices regarding seo or performance of my website. I can unsubscribe at any time.', 'wpblast') . '</label>
                </div>
            </div>
            <a class="register-button button wpblast-register-account-button" href="#">' . esc_html__('Create free account', 'wpblast') . '</a>
            <p>' . esc_html__('By signing up, I agree to WP BLAST', 'wpblast') . ' <a href="' . esc_url_raw($this->getWebsite()) . '/cgu">' . esc_html__('Terms of Service', 'wpblast') . '</a> ' . esc_html__('and', 'wpblast') . ' <a href="' . esc_url_raw($this->getWebsite()) . '/privacy-policy">' . esc_html__('Privacy Policy', 'wpblast') . '</a>.</p>
            <hr></hr>
            <h3>' . esc_html__('Already have an account?', 'wpblast') . '</h3>
            <a class="button" href="' . esc_url_raw($this->getRegistrationUrl()) . '">' . esc_html__('Login', 'wpblast') . '</a>
        </div><div class="wpblast-registration-form-backdrop"></div>';
    }

    public function isPreviewEnabled()
    {
        $user = wp_get_current_user();
        if (isset($user) && $user->ID !== 0) {
            $default = preg_match('(^.+@smartfire\\.pro$)', $user->user_email);
        } else {
            $default = false;
        }
        return apply_filters('wpblast_settings_preview_enable', $default);
    }

    public function registerSettings()
    {
        $connectField = [
            'name' => 'register_plugin',
            'desc' => '<a class="button" href="' . $this->getRegistrationUrl() . '">' . __('Connect with WP Blast Account', 'wpblast') . '</a>',
            'label' => __('Account', 'wpblast'),
            'type' => 'html',
        ];

        $wpBlastSettings = [
            [
                'name' => 'user_account_email',
                'desc' => '',
                'label' => __('User connected', 'wpblast'),
                'type' => 'html',
            ],
            [
                'name' => 'user_account_domains',
                'desc' => '',
                'label' => __('Domains registered', 'wpblast'),
                'type' => 'html',
            ],
            [
                'name' => 'user_account_plans',
                'desc' => '',
                'label' => __('Plans', 'wpblast'),
                'type' => 'html',
            ],
            $connectField,
            [
                'name' => 'timeout',
                'label' => __('Network Timeout', 'wpblast'),
                'desc' => __('Network timeout for request with WP Blast server in seconds', 'wpblast'),
                'type' => 'number',
                'default' => $this->timeout,
            ],
        ];

        $debugFields = [
            [
                'name' => 'server',
                'label' => __('Server URL', 'wpblast'),
                'type' => 'text',
                'default' => $this->server,
            ],
            [
                'name' => 'website',
                'label' => __('Website URL', 'wpblast'),
                'type' => 'text',
                'default' => $this->website,
            ],
            [
                'name' => 'username',
                'label' => __('Username', 'wpblast'),
                'type' => 'text',
                'default' => $this->username,
            ],
            [
                'name' => 'password',
                'label' => __('Password', 'wpblast'),
                'type' => 'password',
                'default' => $this->password,
            ],
        ];

        // Setup wpblast_crawler
        $fieldsToRegister['wpblast_crawler'] = [
            [
                'name' => 'enabled',
                'label' => __('Static Rendering for Crawlers', 'wpblast'),
                'desc' => '<br/>' . __('The plugin will stop to be active, use it for debug only. Default: enable.', 'wpblast'),
                'type' => 'checkbox',
                'default' => $this->isEnableCrawler() ? 'on' : '',
            ],
            [
                'name' => 'enabled_auto_gen_cache',
                'label' => __('Auto-generation of cache', 'wpblast'),
                'desc' => '<br/>' . __('The plugin will auto generate your cache and manage expiration in order to speed the crawling. Default: enable.', 'wpblast'),
                'type' => 'checkbox',
                'default' => $this->isEnableAutoGenCache() ? 'on' : '',
            ],
            [
                'name' => 'cache_expiration',
                'label' => __('Cache Lifetime', 'wpblast'),
                'desc' => __('Cache any page render for the given seconds. (0 will expire immediately). Default: 86400 (24h).', 'wpblast'),
                'type' => 'number',
                'default' => $this->cacheExpirationCrawlers,
            ],
            [
                'name' => 'cache_grace',
                'label' => __('Cache Grace Period', 'wpblast'),
                'desc' => __('Allow cache to be re-generated before its given expiration. This allows you to always have a cache ready to be served. Default: 900 (15 min).', 'wpblast'),
                'type' => 'number',
                'default' => $this->cacheGrace,
            ],
            [
                'name' => 'max_cache_items',
                'label' => __('Maximum cache items', 'wpblast'),
                'desc' => __('This is the maximum number of cache items you want to keep. If the number is too low, cache efficiency would decrease. If the number is too high, you could end up having a very huge database. Default: 10000.', 'wpblast'),
                'type' => 'number',
                'default' => $this->maxCacheItemsCrawlers,
            ],
            [
                'name' => 'max_sitemap_items',
                'label' => __('Maximum sitemap items', 'wpblast'),
                'desc' => __('This is the maximum number of urls you want to keep. If the number is too low, cache efficiency would decrease. If the number is too high, you could end up having a very huge database. Default: 20000.', 'wpblast'),
                'type' => 'number',
                'default' => $this->maxSitemapItems,
            ],
            [
                'name' => 'withImages',
                'label' => __('Load Images', 'wpblast'),
                'desc' => '<br/>' . __('The generation will be longer but will be more accurate if your code needs to have the images loaded. Default: enable.', 'wpblast'),
                'type' => 'checkbox',
                'default' => $this->withImages ? 'on' : '',
            ],
            [
                'name' => 'withScroll',
                'label' => __('Animation detection', 'wpblast'),
                'desc' => '<br/>' . __('WP Blast will detect animation on your page to generate the final result, after the animation. The generation will take a bit more time but the generation can be more accurate. Default: enable.', 'wpblast'),
                'type' => 'checkbox',
                'default' => $this->withScroll ? 'on' : '',
            ],
            [
                'name' => 'injectCss',
                'label' => __('CSS injection', 'wpblast'),
                'desc' => '<br/>' . __('WP Blast will inject css stylesheets inside your wpblast cache. If you have a few number of css, this could get you a better score. If you have big css stylesheets, this could impact your score negatively. Also, if enable, the space needed for cache in your database will be higher. Please use with caution. Default: disable.', 'wpblast'),
                'type' => 'checkbox',
                'default' => $this->injectCss ? 'on' : '',
            ],
            [
                'name' => 'regex',
                'label' => __('Crawler Bot Regex', 'wpblast'),
                'desc' => __('List of bot that should be considered crawlers. You should use a regexp to identify the bots. Default: empty.', 'wpblast'),
                'type' => 'textarea',
                'default' => self::CRAWLER_DEFAULT_VALUE,
            ],
            $this->isPreviewEnabled() ? [
                'name' => 'user_regex',
                'label' => __('Crawler Preview users', 'wpblast'),
                'desc' => __('Default:', 'wpblast') . ' (^.+@smartfire\\.pro$)',
                'type' => 'textarea',
                'default' => '(^.+@smartfire\\.pro$)',
            ] : [
                'name' => '',
                'type' => 'html',
            ],
        ];

        $desc1 = '
        <div class="wpblast-progress-bar"><div style="width: 0%"></div></div><div class="wpblast-progress-bar-text">-</div>
        <div class="wpblast-progress-explanation" style="display: none;">
            <a href="#" class="action dashicons-before dashicons-arrow-right-alt2 toggleMessageCacheStatus"><i>' . esc_html__("Why my cache bar isn't full?", 'wpblast') . '</i></a>
            <ul class="message" style="display: none;">
                <li>' . esc_html__("If you've just activated the plugin, it can takes some time so that we can generate cache items, just be a bit more patient.", 'wpblast') . '</li>
                <li>' . esc_html__("It's a good practice to have a full cache status but it's not necessary to take most advantages of the plugin. Most of the time, it's perfectly normal, the plugin optimize your cache items and therefore can choose to not keep every item. If you have any question about it, feel free to contact us, we'll be glad to help.", 'wpblast') . '</li>
            </ul>
        </div>
        <div class="wpblast-progress-upgrade"></div>
        <div class="wpblast-details">
            <div class="wpblast-details-table-buttons">
                <a href="#" class="button wpblast-list-save-selected-links">' . esc_html__('Save Selection', 'wpblast') . '</a>
            </div>
            <div class="wpblast-details-table sitemap"></div>
            <div class="wpblast-details-table-buttons">
                <a href="#" class="button wpblast-list-save-selected-links">' . esc_html__('Save Selection', 'wpblast') . '</a>
                <div class="wpblast-details-table-actions">
                    <a href="#" class="button wpblast-list-previous">' . esc_html__('Previous Page', 'wpblast') . '</a>
                    <a href="#" class="button wpblast-list-next">' . esc_html__('Next Page', 'wpblast') . '</a>
                </div>
            </div>
        </div>';

        $fieldsToRegister['wpblast_cache'] = [
            [
                'name' => 'cache_progress',
                'desc' => $desc1,
                'label' => __('Cache status', 'wpblast'),
                'type' => 'html',
            ],
            [
                'name' => 'generate_cache',
                'label' => __('Generate', 'wpblast'),
                'desc' => '<a class="button" href="' . menu_page_url($this->getPluginName(), false) . '&' . self::WPBLAST_GENERATE_CACHE . '">' . __('Generate cache', 'wpblast') . '</a>',
                'type' => 'html',
            ],
            [
                'name' => 'purge_cache',
                'label' => __('Clean', 'wpblast'),
                'desc' => '<a class="button" href="' . menu_page_url($this->getPluginName(), false) . '&' . self::WPBLAST_PURGE_PLUGIN_CACHE . '">' . __('Purge plugin data', 'wpblast') . '</a><a class="button" style="margin-left: 15px;" href="' . menu_page_url($this->getPluginName(), false) . '&' . self::WPBLAST_PURGE_CACHE . '">' . __('Purge cache', 'wpblast') . '</a><a class="button" style="margin-left: 15px;" href="' . menu_page_url($this->getPluginName(), false) . '&' . self::WPBLAST_PURGE_SITEMAP . '">' . __('Purge sitemap', 'wpblast') . '</a><a class="button" style="margin-left: 15px;" href="' . menu_page_url($this->getPluginName(), false) . '&' . self::WPBLAST_PURGE_PAGES_SCORES . '">' . __('Reset scores', 'wpblast') . '</a>',
                'type' => 'html',
            ],
        ];

        //Dashboard Table section variables
        if ($this->userHasGuestPlan()) {
            $incitementUpgradePlan = '<button type="button" class="wpblast-register-form-open button">' . esc_html__('Unlock now!', 'wpblast') . '</button>';
        } else if ($this->userHasFreePlan()) {
            $incitementUpgradePlan = '<a target="_blank" class="button" href="' . $this->getWebsite() . '/pricing/?utm_medium=plugin&utm_source=' . urlencode($this->getUsername()) . '">' . esc_html__('Upgrade to premium', 'wpblast') . '</a>';
        } else {
            $incitementUpgradePlan = '<a class="button" target="_blank" href="' . $this->getWebsite() . '/contact/">' . esc_html__('Upgrade your plan or contact us for quote tailored ', 'wpblast') . '</a>';
        }

        $dashboardTableDesc = '
        <div class="wpblast-details">
            <div class="wpblast-details-table-buttons">
                <a href="#" class="button wpblast-list-save-selected-links">' . esc_html__('Save Selection', 'wpblast') . '</a>
                <a class="wpblast-details-table-page-speed" href="https://pagespeed.web.dev/" target="_blank">
                    <p>' . esc_html__('Scores made with', 'wpblast') . '</p>
                    <img src="' . plugins_url('', $this->rootPluginFile) . '/img/page-speed-insight-logo.png" alt="Logo Page Speed Insight" />
                </a>
            </div>
            <div class="wpblast-details-table dashboard"></div>
            <div class="wpblast-details-table-buttons">
                <a href="#" class="button wpblast-list-save-selected-links">' . esc_html__('Save Selection', 'wpblast') . '</a>
                <div class="wpblast-details-table-actions">
                    <a href="#" class="button wpblast-list-previous">' . esc_html__('Previous Page', 'wpblast') . '</a>
                    <a href="#" class="button wpblast-list-next">' . esc_html__('Next Page', 'wpblast') . '</a>
                </div>
            </div>
            <div class="wpblast-details-reassurance-message">
                <div>
                    <p>' . esc_html__("Don't worry", 'wpblast') . '</p>
                    <div class="wpblast-details-reassurance-message-close"></div>
                </div>
                <p>' . esc_html__('This icon appears on links where the performance score doesn\'t seem to be a lot improved.', 'wpblast') . '<br/>' . esc_html__('This is likely due to a lack of optimisation not doable by the plugin (compression of images, large DOM depth, unefficient cache rules...).', 'wpblast') . '<br/><br/>' . esc_html__('Please first do this modifications and run again the plugin! :)', 'wpblast') . '</p>
                <p>' . esc_html__('You don\'t know how to do it?', 'wpblast') . '<br/><br/><a target="_blank" href="' . $this->getWebsite() . '/contact/">' . esc_html__('Contact us and we will help you improve this score!', 'wpblast') . '</a></p>
            </div>
            <div class="wpblast-details-maximum-pages-reached hidden">
                <div class="wpblast-details-maximum-pages-reached-close"><img src="' . plugins_url('', $this->rootPluginFile) . '/img/cross.svg" alt="Close icon" /></div>
                <div class="wpblast-details-maximum-pages-reached-entete">
                    <div class="wpblast-details-maximum-pages-reached-lock"><img src="' . plugins_url('', $this->rootPluginFile) . '/img/lock.svg" alt="Lock icon" /></div>
                    <h3>' . esc_html__('Maximum pages reached', 'wpblast') . '</h3>
                </div>
                <div class="wpblast-remaining-pages-unoptimized-container">
                    <p>' . esc_html__('Number of remaining unoptimized pages', 'wpblast') . ': <span class="wpblast-remaining-pages-unoptimized">-</span></p>
                </div>
                <p>' . esc_html__('You want to optimize and blast remaining pages?', 'wpblast') . '</p>
                ' . $incitementUpgradePlan . '
            </div>
        </div>';

        // Dashboard section variables
        if ($this->userHasGuestPlan()) {
            $introductionHeadingSitemapLoading = esc_html__('Welcome on WP Blast!', 'wpblast');
            $incitementSiteMap = '
            <div class="wpblast-remaining-pages-unoptimized-container wpblast-introduction-dashboard-text-animation">
                <span>' . esc_html__('Your current plan only optimize your homepage.', 'wpblast') . ' <b>' . esc_html__('You still have', 'wpblast') . '</span>
                <span class="wpblast-remaining-pages-unoptimized">-</span><span> ' . esc_html__('pages not optimized!', 'wpblast') . '</b></span>
            </div>
            <div class="wpblast-introduction-dashboard-text-animation">' . esc_html__('Create your free account to optimize 4 more pages of your choice!', 'wpblast') . '</div>
            <button type="button" class="wpblast-register-form-open button wpblast-introduction-dashboard-text-animation">' . esc_html__('Unlock now!', 'wpblast') . '</button>';
            $incitementSiteMapLoading = $incitementSiteMap;
            $numberPagesOptimized = esc_html__('Your homepage is now optimized!', 'wpblast');
        } else if ($this->userHasFreePlan()) {
            $introductionHeadingSitemapLoading = esc_html__('Nice to see you again!', 'wpblast');
            $incitementSiteMap = '<div class="wpblast-remaining-pages-unoptimized-container wpblast-introduction-dashboard-text-animation">
                <span>' . esc_html__('Your current plan only optimize 5 pages. ', 'wpblast') . ' <b>' . esc_html__('You still have', 'wpblast') . '</span>
                <span class="wpblast-remaining-pages-unoptimized">-</span><span> ' . esc_html__('pages not optimized!', 'wpblast') . '</b></span>
            </div>
            <div class="wpblast-introduction-dashboard-text-animation">' . esc_html__('Go premium to optimize your whole website!', 'wpblast') . '</div>
            <a class="button wpblast-introduction-dashboard-text-animation" target="_blank" href="' . $this->getWebsite() . '/pricing/?utm_medium=plugin&utm_source=' . urlencode($this->getUsername()) . '">' . esc_html__('Upgrade to premium', 'wpblast') . '</a>';
            $incitementSiteMapLoading = $incitementSiteMap;
            $numberPagesOptimized = esc_html__('Your homepage and 4 more pages are now optimized!', 'wpblast');
        } else {
            $introductionHeadingSitemapLoading =  esc_html__('Nice to see you again!', 'wpblast');
            $incitementSiteMapLoading = '';
            $incitementSiteMap = '<div class="wpblast-remaining-pages-unoptimized-container wpblast-introduction-dashboard-text-animation">
                <span>' . esc_html__("Your current plan doesn't fit your website size. ", 'wpblast') . ' <b>' . esc_html__('You still have', 'wpblast') . '</span>
                <span class="wpblast-remaining-pages-unoptimized">-</span><span> ' . esc_html__('pages not optimized!', 'wpblast') . '</b></span>
            </div>
            <p class="wpblast-introduction-dashboard-text-animation">' . esc_html__('You need a bigger plan or to improve even more your pagespeed? Contact us for a quote tailored to your needs and your business.', 'wpblast') . '</p>
            <div class="wpblast-installation-connection wpblast-introduction-dashboard-text-animation">
            <a class="button" target="_blank" href="' . $this->getWebsite() . '/contact/">' . esc_html__('Contact us', 'wpblast') . '</a>
            </div>';
            $numberPagesOptimized = esc_html__('Your homepage is now optimized!', 'wpblast');
        }

        $dashboardintroductionDashboardContentDesc = '
        <div class="wpblast-introduction-dashboard">
            <div class="wpblast-dashboard-sitemap-loading hidden">
                <h3>' . $introductionHeadingSitemapLoading . '</h3>
                <div class="wp-blast-loading-steps">
                    <div class="wpblast-loading-step wpblast-loading-step-1">
                        <div class="wpblast-loading-step-flash"></div>
                        <p>' . esc_html__('Waiting for crawling...', 'wpblast') . '</p>
                    </div>
                    <div class="wpblast-loading-step wpblast-loading-step-2">
                        <div class="wpblast-loading-step-flash"></div>
                        <p>' . esc_html__('Optimizing your performance...', 'wpblast') . '</p>
                    </div>
                    <div class="wpblast-loading-step wpblast-loading-step-3">
                        <div class="wpblast-loading-step-flash"></div>
                        <p>' . esc_html__('Analyzing your native mobile score...', 'wpblast') . '</p>
                    </div>
                    <div class="wpblast-loading-step wpblast-loading-step-4">
                        <div class="wpblast-loading-step-flash"></div>
                        <p>' . esc_html__('Analyzing your native desktop score...', 'wpblast') . '</p>
                    </div>
                    <div class="wpblast-loading-step wpblast-loading-step-5">
                        <div class="wpblast-loading-step-flash"></div>
                        <p>' . esc_html__('Analyzing your blast mobile score...', 'wpblast') . '</p>
                    </div>
                    <div class="wpblast-loading-step wpblast-loading-step-6">
                        <div class="wpblast-loading-step-flash"></div>
                        <p>' . esc_html__('Analyzing your blast desktop score...', 'wpblast') . '</p>
                    </div>
                </div>
                <p>' . esc_html__('We are currently blasting your website, please wait to see the result...', 'wpblast') . '</p>
                ' . $incitementSiteMapLoading . '
            </div>
            <div class="wpblast-introduction-dashboard-sitemap hidden">
                <h3>' . esc_html__('Congrats!', 'wpblast') . ' 🎉</h3>
                <div class="wpblast-introduction-dashboard-scores">
                    <div class="wpblast-introduction-dashboard-scores-result">
                        <p class="wpblast-introduction-dashboard-scores-multiplication wpblast-introduction-dashboard-score-multiplication-mobile"></p>
                        <div class="wpblast-introduction-dashboard-score wpblast-introduction-dashboard-score-animation">
                            <p class="wpblast-introduction-dashboard-score-mobile-before wpblast-introduction-dashboard-score-before"></p>
                            <span>></span>
                            <p class="wpblast-introduction-dashboard-score-mobile-after wpblast-introduction-dashboard-score-after"></p>
                        </div>
                        <p class="wpblast-introduction-dashboard-score-animation">' . esc_html__('Mobile', 'wpblast') . '</p>
                    </div>
                    <div class="wpblast-introduction-dashboard-scores-result">
                        <p class="wpblast-introduction-dashboard-scores-multiplication wpblast-introduction-dashboard-score-multiplication-desktop"></p>
                        <div class="wpblast-introduction-dashboard-score wpblast-introduction-dashboard-score-animation">
                            <p class="wpblast-introduction-dashboard-score-desktop-before wpblast-introduction-dashboard-score-before"></p>
                            <span>></span>
                            <p class="wpblast-introduction-dashboard-score-desktop-after wpblast-introduction-dashboard-score-after"></p>
                        </div>
                        <p class="wpblast-introduction-dashboard-score-animation">' . esc_html('Desktop', 'wpblast') . '</p>
                    </div>
                </div>
                <p class="wpblast-introduction-dashboard-text-animation">' . $numberPagesOptimized . '</p>
                <p class="wpblast-introduction-dashboard-text-animation">' . esc_html__('As you can see above, your performance score has increased! That means your performance score and SEO ranking are improved, and your website will be more appreciated by Google crawlers! This is possible thanks to the dynamic rendering that allows to generate a lighter version of your website for crawlers.', 'wpblast') . '</p>
                ' . $incitementSiteMap . '
            </div>
            <div class="wpblast-introduction-dashboard-localhost hidden">
                <h3>' . esc_html__('Oups!', 'wpblast') . '</h3>
                <p>' . esc_html__('Your website seems to not be publically available. This can happen when you develop your website locally. The good news is it will be fully operational as soon as your website is online!', 'wpblast') . '</p>
            </div>
            <div class="wpblast-introduction-dashboard-image hidden">
                <img src="' . plugins_url('', $this->rootPluginFile) . '/img/background-dashboard.svg" alt="Background dashboard" />
                <div class="wpblast-remote-status"></div>
            </div>
        </div>';

        $fieldsToRegister['wpblast_dashboard'] = [
            [
                'name' => 'dashboard_introduction',
                'desc' => $dashboardintroductionDashboardContentDesc,
                'type' => 'html',
            ],
            [
                'name' => 'dashboard_html',
                'desc' => $dashboardTableDesc,
                'label' => __('Statistics', 'wpblast'),
                'type' => 'html',
            ],
        ];

        $fieldsToRegister['wpblast_home'] = array_merge($wpBlastSettings, $debugFields);

        $this->api->set_sections([
            [
                'id' => 'wpblast_dashboard',
                'title' => __('Dashboard', 'wpblast'),
            ],
            [
                'id' => 'wpblast_home',
                'title' => __('Account', 'wpblast'),
            ],
            [
                'id' => 'wpblast_crawler',
                'title' => __('Settings', 'wpblast'),
            ],
            [
                'id' => 'wpblast_cache',
                'title' => __('Cache', 'wpblast'),
            ],
        ]);
        $this->api->set_fields([
            'wpblast_dashboard' => $fieldsToRegister['wpblast_dashboard'],
            'wpblast_home' => $fieldsToRegister['wpblast_home'],
            'wpblast_crawler' => $fieldsToRegister['wpblast_crawler'],
            'wpblast_cache' => $fieldsToRegister['wpblast_cache'],
        ]);

        //initialize them
        $this->api->admin_init();
    }

    public function requestCrawl()
    {
        // Send a request to wp-blast to ask for a force crawl
        $response = wp_remote_get($this->getWebsite() . '/?method=requestCrawl&domain=' . urlencode($this->getUsername()) . '&plugin_token=' . $this->getPassword(), [
            'user-agent' => self::WPBLAST_UA_PLUGIN,
        ]);

        if (!is_wp_error($response) && isset($response)) {
            $httpCode = wp_remote_retrieve_response_code($response);
            if (isset($httpCode) && $httpCode === 200) {
                return true;
            }
        }
        return false;
    }

    public function purgeCache()
    {
        global $wpdb;
        if ($this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {
            $wpdb->query("UPDATE {$wpdb->wpblast_sitemap} SET cache = '', cacheExpiration = 0, lastGen = NULL");
        }
    }

    public function purgePluginCache($regenerate = true)
    {
        $keys = $this->get_transient_keys(self::PLUGIN_CACHE_PREFIX, 0);
        foreach ($keys as $key) {
            delete_transient($key);
        }
        // Regenerate plugin cache after clean up to avoid multiple parallele request to updatePluginData
        if ($regenerate) {
            /*
                Order of update is important: getPluginData is done at last so that when doing other request (like getting sitemap) userAccount and autoRegexp are already there
            */
            $this->getCrawlerAutoRegexp(true); // force update of crawler list
            $this->getAccount(true); // force update of user account
            $this->getPluginData(true); // force update to wp blast
        }
    }

    public function purgeSitemap()
    {
        global $wpdb;

        // Clean data from sitemap table
        if ($this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->wpblast_sitemap}");
        }
    }

    public function purgePagesScores()
    {
        global $wpdb;

        // Clean scores from sitemap table
        if ($this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {
            $wpdb->query("UPDATE {$wpdb->wpblast_sitemap} SET scores = ''");
        }
    }

    public function getNumberPagesUsed()
    {
        global $wpdb;
        if ($this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {
            // remove view as web crawler item from the group by clause
            $nb = $wpdb->get_var($wpdb->prepare("SELECT SUM(nb) FROM (SELECT MAX(active) AS nb FROM {$wpdb->wpblast_sitemap} WHERE active = 1 GROUP BY REPLACE(url, %s, '')) AS tmp", '?' . PageRender::WPBLAST_CRAWLER));
            if (isset($nb)) {
                return intval($nb);
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public function purgeExceededPlanPages()
    {
        global $wpdb;

        if ($this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {
            $userAccount = $this->getAccount();
            if (isset($userAccount) && isset($userAccount['features']) && isset($userAccount['features']['maxPages'])) {
                $maxPagesAllowed = $userAccount['features']['maxPages'];
                $currentUse = $this->getNumberPagesUsed();
                if (isset($currentUse) && $currentUse > $maxPagesAllowed) {
                    // clean up active pages based on lastRequest, except for home url
                    // get urls to keep active
                    $urls = $wpdb->get_col($wpdb->prepare("SELECT url FROM (SELECT MAX(active) AS maxActive, REPLACE(url, %s, '') as url, MAX(lastRequest) AS maxLastRequest FROM {$wpdb->wpblast_sitemap} GROUP BY REPLACE(url, %s, '')) AS tmp WHERE maxActive >= 1 ORDER BY maxLastRequest DESC LIMIT 0, %d", '?' . PageRender::WPBLAST_CRAWLER, '?' . PageRender::WPBLAST_CRAWLER, $maxPagesAllowed));
                    $this->updateActivePages($urls); // set active for these pages and set inactive for others
                }
            } else {
                return; // abort in case of error to avoid purging falsely pages
            }
        }
    }

    public function purgeExceededItemsCache()
    {
        global $wpdb;

        $purgeExceededItemsCacheTimestamp = get_transient(self::PLUGIN_CACHE_PREFIX . '_purgeExceededItemsCacheTimestamp');
        // Rate limit calls to this method to avoid overloading the server
        if (!$purgeExceededItemsCacheTimestamp || ($purgeExceededItemsCacheTimestamp + $this->purgeExceededItemsCacheRateLimit < time())) {
            if ($this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {
                // Get cache items to delete: use this in two steps to avoid using nested query that could create an incompatibility, avoid using DELETE FROM not in to avoid concurrent requests bugs
                // Limit range is bigint unsigned max value
                $idsToDelete = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->wpblast_sitemap} ORDER BY lastRequest DESC LIMIT %d, 18446744073709551615", [$this->getMaxSitemapItems()]));
    
                // Get items to clear: use this in two steps to avoid using nested query that could create an incompatibility
                if (count($idsToDelete) > 0) {
                    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->wpblast_sitemap} WHERE id IN (" . implode(', ', array_fill(0, count($idsToDelete), '%s')) . ')', $idsToDelete));
                }
    
                // Get cache items to keep: use this in two steps to avoid using nested query that could create an incompatibility, avoid using DELETE FROM not in to avoid concurrent requests bugs
                // Limit range is bigint unsigned max value
                $idsToUpdate = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->wpblast_sitemap} ORDER BY lastRequest DESC LIMIT %d, 18446744073709551615", [$this->getMaxCacheItemsCrawlers()]));
    
                // Clean cache value
                if (count($idsToUpdate) > 0) {
                    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->wpblast_sitemap} SET cache = '', cacheExpiration = 0, lastGen = NULL WHERE id IN (" . implode(', ', array_fill(0, count($idsToUpdate), '%s')) . ')', $idsToUpdate));
                }
            }

            $data = time();
            set_transient(self::PLUGIN_CACHE_PREFIX . '_purgeExceededItemsCacheTimestamp', $data, 60 * 60 * 24 * 7); // transient is kept a week
        }
    }

    public function cleanExpiredCache()
    {
        global $wpdb;

        $cleanExpiredCacheTimestamp = get_transient(self::PLUGIN_CACHE_PREFIX . '_cleanExpiredCacheTimestamp');
        // Rate limit calls to this method to avoid overloading the server
        if (!$cleanExpiredCacheTimestamp || ($cleanExpiredCacheTimestamp + $this->cleanExpiredCacheRateLimit < time())) {

        if ($this->tableExists(self::WPBLAST_SITEMAP_TABLE)) {
            // Remove cache expired or inactive to free some space
            $wpdb->query("UPDATE {$wpdb->wpblast_sitemap} SET cache = '', cacheExpiration = 0, lastGen = NULL WHERE (cacheExpiration != 0 AND cacheExpiration <= UNIX_TIMESTAMP()) OR active = 0");

            // Remove items that have not been requested for a long time: this is a big clean up to avoid the table getting bigger and bigger with no legitimate content
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->wpblast_sitemap} WHERE (lastRequest IS NOT NULL AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(lastRequest) >= %d) OR (lastRequest IS NULL AND dateAdd IS NOT NULL AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(dateAdd) >= %d)", [$this->getCacheItemGarbageExpiration(), $this->getCacheItemGarbageExpiration()]));
        }

        $data = time();
            set_transient(self::PLUGIN_CACHE_PREFIX . '_cleanExpiredCacheTimestamp', $data, 60 * 60 * 24 * 7); // transient is kept a week
        }
    }

    public function tableExists($tableName)
    {
        global $wpdb;
        if (!isset($this->tablesExist[$tableName])) {

            $this->tablesExist[$tableName] = count($wpdb->get_results($wpdb->prepare('SHOW TABLES LIKE %s', [$wpdb->prefix . $tableName]))) > 0;
        }
        return $this->tablesExist[$tableName];
    }

    /**
     * Gets all transient keys in the database with a specific prefix.
     *
     * Note that this doesn't work for sites that use a persistent object
     * cache, since in that case, transients are stored in memory.
     *
     * @return array          Transient keys with prefix, or empty array on error.
     */
    public function get_transient_keys($prefix = self::PLUGIN_CACHE_PREFIX, $offset = 0)
    {
        global $wpdb;

        $prefixToSearch = $wpdb->esc_like('_transient_' . $prefix);
        $keys   = $wpdb->get_results($wpdb->prepare("SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE %s ORDER BY option_id DESC LIMIT %d, 999999", [$prefixToSearch . '%', $offset]), ARRAY_A);

        if (is_wp_error($keys)) {
            return [];
        }

        return array_map(function ($key) {
            // Remove '_transient_' from the option name.
            return substr($key['option_name'], 11);
        }, $keys);
    }

    /**
     * @return string
     */
    public function getPluginName()
    {
        return $this->plugin_name;
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        if (!isset($this->usernameHydrated)) {
            if ($this->username === '') {
                // use site_url instead of home_url to avoid having multiple home_url due to multilanguages
                $homeUrl = get_site_url();
                if (isset($homeUrl) && $homeUrl !== '') {
                    $homeUrlParsed = wp_parse_url($homeUrl);
                    if (isset($homeUrlParsed) && isset($homeUrlParsed['host']) && $homeUrlParsed['host'] !== '') {
                        if (isset($homeUrlParsed['path']) && $homeUrlParsed['path'] !== '') {
                            if (substr($homeUrlParsed['path'], -1) === '/') {
                                $path = substr($homeUrlParsed['path'], 0, -1);
                            } else {
                                $path = $homeUrlParsed['path'];
                            }
                            $this->usernameHydrated = $homeUrlParsed['host'] . $path;
                        } else {
                            $this->usernameHydrated = $homeUrlParsed['host'];
                        }
                    }
                }
                // Fallback on server host
                if (!isset($this->usernameHydrated) && isset($_SERVER['HTTP_HOST'])) {
                    $this->usernameHydrated = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])); // not saved in database to allow detection of deployment from an env to another
                }
            } else if ($this->username !== '') {
                $this->usernameHydrated = $this->username;
            } else {
                throw new \Error('HTTP_HOST home url and username are both undefined');
            }
        }
        return $this->usernameHydrated;
    }

    public function getAccount($forceUpdate = false)
    {
        // update account if needed
        if (!isset($this->account) || $forceUpdate) {
            if ($forceUpdate) {
                $userAccount = $this->updateUserAccount(true);
            } else {
                $userAccount = $this->updateUserAccount();
            }
            if (isset($userAccount)) {
                $this->account = $userAccount;
            } else {
                return []; // in case of error
            }
        }
        return $this->account;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    public function getRegistrationUrl()
    {
        $redirectConnectUrl = menu_page_url($this->getPluginName(), false);
        if (isset($redirectConnectUrl) && $redirectConnectUrl !== false && $redirectConnectUrl !== '') {
            $redirectConnectUrl = add_query_arg([self::WPBLAST_REGISTERED_PLUGIN => '1'], $redirectConnectUrl);
        }
        return $this->getWebsite() . '/?method=registerPlugin&domain=' . urlencode($this->getUsername()) . '&plugin_token=' . $this->getPassword() . '&redirect_url=' . urlencode($redirectConnectUrl);
    }

    /**
     * @return bool
     */
    public function isEnableCrawler()
    {
        return $this->enableCrawler;
    }

    /**
     * @return bool
     */
    public function isEnableAutoGenCache()
    {
        return $this->enableAutoGenCache;
    }

    /**
     * @return string
     */
    public function getCrawlerRegexp()
    {
        return $this->crawlerRegexp;
    }

    /**
     * @return string
     */
    public function getCrawlerListAuto($forceUpdate = false)
    {
        // update crawler list if needed
        if (!isset($this->crawlerListAuto) || $forceUpdate) {
            if ($forceUpdate) {
                $crawlerList = $this->updateCrawlerList(true);
            } else {
                $crawlerList = $this->updateCrawlerList();
            }
            if (isset($crawlerList) && is_array($crawlerList)) {
                $this->crawlerListAuto = $crawlerList;
            } else {
                return ''; // in case of error
            }
        }
        return $this->crawlerListAuto;
    }

    /**
     * @return string
     */
    public function getCrawlerAutoRegexp($forceUpdate = false)
    {
        // update crawler list if needed
        if (!isset($this->crawlerAutoRegexp) || $forceUpdate) {
            if ($forceUpdate) {
                $crawlerList = $this->updateCrawlerList(true);
            } else {
                $crawlerList = $this->updateCrawlerList();
            }
            if (isset($crawlerList) && is_array($crawlerList)) {
                // Create a regexp from the list of crawlers regexp
                $arrayRegexpCrawlerList = array_map(function ($value) {
                    return isset($value['pattern']) && $value['pattern'] !== '' ? $value['pattern'] : '';
                }, $crawlerList);
                $arrayRegexpCrawlerList = array_filter($arrayRegexpCrawlerList, function ($value) {
                    return $value !== '';
                });
                $this->crawlerAutoRegexp = implode('|', $arrayRegexpCrawlerList);
            } else {
                return ''; // in case of error
            }
        }
        return $this->crawlerAutoRegexp;
    }

    public function getPluginData($forceUpdate = false)
    {
        // update crawler list if needed
        if (!isset($this->pluginData) || $forceUpdate) {
            if ($forceUpdate) {
                $pluginData = $this->updatePluginData(true);
            } else {
                $pluginData = $this->updatePluginData();
            }
            if (isset($pluginData)) {
                $this->pluginData = $pluginData;
            } else {
                return ''; // in case of error
            }
        }
        return $this->pluginData;
    }

    public function getCrawlerCacheGen()
    {
        return $this->crawlerCacheGen;
    }

    public function getCrawlerUserRegex()
    {
        return $this->crawlerUserRegexp;
    }

    /**
     * @return string
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return float|int
     */
    public function getCacheExpirationCrawlers()
    {
        return $this->cacheExpirationCrawlers;
    }

    public function getCacheGrace()
    {
        return $this->cacheGrace;
    }

    public function getMaxCacheItemsCrawlers()
    {
        return $this->maxCacheItemsCrawlers;
    }

    public function getMaxSitemapItems()
    {
        return $this->maxSitemapItems;
    }

    public function isWithScroll()
    {
        return $this->withScroll;
    }

    public function shouldInjectCss()
    {
        return $this->injectCss;
    }

    public function isWithImages()
    {
        return $this->withImages;
    }

    public function getCacheItemGarbageExpiration()
    {
        return $this->cacheItemGarbageExpiration;
    }
}
