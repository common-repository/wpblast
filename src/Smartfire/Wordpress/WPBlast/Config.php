<?php

namespace Smartfire\Wordpress\WPBlast;

/**
 * Configuration class for WP Rocket cache
 *
 * @since 3.3
 * @author Remy Perona
 */
class Config
{

    /**
     * Path to the directory containing the config files.
     *
     * @var    string
     */
    private static $config_dir_path;

    /**
     * Values of $_SERVER to use for some tests.
     *
     * @var    array
     */
    private static $server;

    /**
     * Constructor
     *
     * @param array $args {
     *     An array of arguments.
     *
     *     @type string $config_dir_path WP Rocket config directory path.
     *     @type array  $server          Values of $_SERVER to use for the tests. Default is $_SERVER.
     * }
     */
    public function __construct($args = [])
    {
        if (isset(self::$config_dir_path)) {
            // Make sure to keep the same values all along.
            return;
        }

        if (!isset($args['server']) && !empty($_SERVER) && is_array($_SERVER)) {
            $args['server'] = [];
            if (isset($_SERVER['HTTP_HOST'])) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- We can't use wordpress sanitizing function here as WP is not loaded yet. This variable is only called in this function with strict check and is safe.
                $args['server']['HTTP_HOST'] = $_SERVER['HTTP_HOST'];
            }
            if (isset($_SERVER['REQUEST_URI'])) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- We can't use wordpress sanitizing function here as WP is not loaded yet. This variable is only called in this function with strict check and is safe.
                $args['server']['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
            }
        }

        self::$config_dir_path = rtrim($args['config_dir_path'], '/') . '/';
        self::$server          = !empty($args['server']) && is_array($args['server']) ? $args['server'] : [];
    }

    /**
     * Get a $_SERVER entry.
     *
     * @param  string $entry_name Name of the entry.
     * @param  mixed  $default    Value to return if the entry is not set.
     * @return mixed
     */
    public function get_server_input($entry_name, $default = null)
    {
        if (!isset(self::$server[$entry_name])) {
            return $default;
        }

        return self::$server[$entry_name];
    }

    /**
     * Get the `server` property.
     * 
     * @return array
     */
    public function get_server()
    {
        return self::$server;
    }

    /**
     * Get a specific config/option value.
     * 
     * @param  string $config_name Name of a specific config/option.
     * @return mixed
     */
    public function get_config($config_name)
    {
        $config = $this->get_configs();
        return isset($config[$config_name]) ? $config[$config_name] : null;
    }

    /**
     * Get the whole current configuration.
     * 
     * @return array|bool An array containing the configuration. False on failure.
     */
    public function get_configs()
    {

        $config_file_path = $this->get_config_file_path();

        if (!$config_file_path['success']) {
            return false;
        }

        // Add fail proof in case the file doesn't exist, this will fallback to default value
        // This could happened in case advanced cache is not cleaned and request are coming
        if (file_exists($config_file_path['path'])) {
            include $config_file_path['path'];
        }

        $config = [
            'crawler_enabled' => true,
            'crawler_ua_regex' => null,
            'crawler_ua_regex_auto' => null,
            'crawler_ua_self' => null,
        ];

        foreach ($config as $entry_name => $entry_value) {
            $var_name = 'wpblast_' . $entry_name;

            if (isset($$var_name)) {
                $config[$entry_name] = $$var_name;
            }
        }

        return $config;
    }

    /**
     * Get the host, to use for config and cache file path.
     *
     * @return string
     */
    public function get_host()
    {
        $host = $this->get_server_input('HTTP_HOST', (string) time());
        $host = preg_replace('/:\d+$/', '', $host);
        $host = trim(strtolower($host), '.');

        return rawurlencode($host);
    }

    /**
     * Get the path to an existing config file.
     *
     * @return string|bool The path to the file. False if no file is found.
     */
    public function get_config_file_path()
    {
        $config_dir_real_path = realpath(self::$config_dir_path) . DIRECTORY_SEPARATOR;

        $host = $this->get_host();

        if (realpath(self::$config_dir_path . $host . '.php') && 0 === stripos(realpath(self::$config_dir_path . $host . '.php'), $config_dir_real_path)) {
            $config_file_path = self::$config_dir_path . $host . '.php';
            return [
                'success' => true,
                'path'    => $config_file_path,
            ];
        }

        $path = str_replace('\\', '/', strtok($this->get_server_input('REQUEST_URI', ''), '?'));
        $path = preg_replace('|(?<=.)/+|', '/', $path);
        $path = explode('%2F', preg_replace('/^(?:%2F)*(.*?)(?:%2F)*$/', '$1', rawurlencode($path)));

        foreach ($path as $p) {
            static $dir;

            if (realpath(self::$config_dir_path . $host . '.' . $p . '.php') && 0 === stripos(realpath(self::$config_dir_path . $host . '.' . $p . '.php'), $config_dir_real_path)) {
                $config_file_path = self::$config_dir_path . $host . '.' . $p . '.php';
                return [
                    'success' => true,
                    'path'    => $config_file_path,
                ];
            }

            if (realpath(self::$config_dir_path . $host . '.' . $dir . $p . '.php') && 0 === stripos(realpath(self::$config_dir_path . $host . '.' . $dir . $p . '.php'), $config_dir_real_path)) {
                $config_file_path = self::$config_dir_path . $host . '.' . $dir . $p . '.php';
                return [
                    'success' => true,
                    'path'    => $config_file_path,
                ];
            }

            $dir .= $p . '.';
        }

        return [
            'success' => false,
            'path'    => self::$config_dir_path . $host . implode('/', $path) . '.php',
        ];
    }

    public function display_error($message)
    {
        $class = 'notice notice-error';
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    public function write_configuration($config)
    {
        if (!is_dir(self::$config_dir_path)) {
            if (!@mkdir(self::$config_dir_path, 0700, true)) {
                $this->display_error(__('Error while writing configuration directory. Please make your wp-content folder writable.', 'wpblast') . ' ' . self::$config_dir_path);
            }
        }
        $code = '<?php
// THIS FILE HAS BEEN AUTO-GENERATED
defined( \'ABSPATH\' ) || exit;
';
        foreach ($config as $name => $value) {
            $code .= "\$wpblast_$name = unserialize(base64_decode(\"" . base64_encode(serialize($value)) . "\"));\n";
        }

        if (@file_put_contents(self::$config_dir_path . $this->get_host() . '.php', $code) === false) {
            $this->display_error(__('Error while writing configuration file. Please make your wp-content folder writable.', 'wpblast') . ' ' . self::$config_dir_path . $this->get_host() . '.php');
        }
        if (@file_put_contents(self::$config_dir_path . 'index.html', '') === false) {
            $this->display_error(__('Error while writing configuration file. Please make your wp-content folder writable.', 'wpblast') . ' ' . self::$config_dir_path . 'index.html');
        }
    }

    public function get_config_dir_path()
    {
        return self::$config_dir_path;
    }
}
