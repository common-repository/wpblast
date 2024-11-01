<?php

use Smartfire\Wordpress\WPBlast\Bootstrap;

defined('ABSPATH') || exit;

require __DIR__ . '/../../globals.php';

if (Bootstrap::should_blast()) {
    define('WPBLAST_SKIP_ADVANCED_CACHE', true);
}
