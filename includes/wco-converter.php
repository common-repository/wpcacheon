<?php
namespace WP_Cache_On;

/**
 *
 * This is to convert to different file format
 *
 * @since      2.0.0
 * @package    WP_Cache_On
 * @subpackage WP_Cache_On/includes
 * @author     Jeffrey Cooper <1987coopjeff@gmail.com>
 */
class WP_Cache_On_Converter
{
    /**
     * Convert to webp
     */
    public static function convert_webp($asset)
    {
        if ('src' == $asset[1]) {
            return self::_convert_webp_src($asset[0]);
        } elseif ('ref' == $asset[1]) {
            return self::_convert_webp_src($asset[0]);
        } elseif ('set' == $asset[1]) {
            return self::_convert_webp_srcset($asset[0]);
        }

        return $asset[0];
    }

    /**
     * Convert src to webp source
     */
    private static function _convert_webp_src($src)
    {
        $upload_dir  = wp_upload_dir();
        $src_url     = parse_url($upload_dir['baseurl']);
        $upload_path = $src_url['path'];

        if (strpos($src, $upload_path) !== false) {

            $src_webp = str_replace('.jpg', '.webp', $src);
            $src_webp = str_replace('.jpeg', '.webp', $src_webp);
            $src_webp = str_replace('.png', '.webp', $src_webp);

            $parts         = explode($upload_path, $src_webp);
            $relative_path = $parts[1];

            //Check if relative path is not empty and file exists
            if (!empty($relative_path) && file_exists($upload_dir['basedir'] . $relative_path)) {
                return $src_webp;
            } else {
                //Try appended webp extension
                $src_webp_appended      = $src . '.webp';
                $parts_appended         = explode($upload_path, $src_webp_appended);
                $relative_path_appended = $parts_appended[1];

                //Check if relative path is not empty and file exists
                if (!empty($relative_path_appended) && file_exists($upload_dir['basedir'] . $relative_path_appended)) {
                    return $src_webp_appended;
                }
            }

        }

        return $src;
    }

    /**
     * Convert srcset to webp source
     */
    private static function _convert_webp_srcset($srcset)
    {
        $sizes       = explode(', ', $srcset);
        $upload_dir  = wp_upload_dir();
        $src_url     = parse_url($upload_dir['baseurl']);
        $upload_path = $src_url['path'];

        for ($i = 0; $i < count($sizes); $i++) {
            if (strpos($sizes[$i], $upload_path) !== false) {
                $src_webp = str_replace('.jpg', '.webp', $sizes[$i]);
                $src_webp = str_replace('.jpeg', '.webp', $src_webp);
                $src_webp = str_replace('.png', '.webp', $src_webp);

                $size_parts    = explode(' ', $src_webp);
                $parts         = explode($upload_path, $size_parts[0]);
                $relative_path = $parts[1];

                // check if relative path is not empty and file exists
                if (!empty($relative_path) && file_exists($upload_dir['basedir'] . $relative_path)) {
                    $sizes[$i] = $src_webp;
                } else {
                    // try appended webp extension
                    $size_parts_appended    = explode(' ', $sizes[$i]);
                    $src_webp_appended      = $size_parts_appended[0] . '.webp';
                    $parts_appended         = explode($upload_path, $src_webp_appended);
                    $relative_path_appended = $parts_appended[1];
                    $src_webp_appended      = $src_webp_appended . ' ' . $size_parts_appended[1];

                    // check if relative path is not empty and file exists
                    if (!empty($relative_path_appended) && file_exists($upload_dir['basedir'] . $relative_path_appended)) {
                        $sizes[$i] = $src_webp_appended;
                    }
                }
            }
        }

        $srcset = implode(', ', $sizes);

        return $srcset;
    }
}
