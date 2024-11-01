<?php

namespace Smartfire\Wordpress\WPBlast;

class Utils
{

    public static function format_json_encode($data)
    {
        $flags = (JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // phpcs:ignore Yoast.Yoast.AlternativeFunctions.json_encode_wp_json_encodeWithAdditionalParams -- This is the definition of format_json_encode.
        return wp_json_encode($data, $flags);
    }

    public static function rmdir_recursive($dir)
    {
        foreach (scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            if (is_dir("$dir/$file")) {
                self::rmdir_recursive("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }
        rmdir($dir);
    }
}
