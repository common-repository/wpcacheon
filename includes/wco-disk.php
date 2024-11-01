<?php
namespace WP_Cache_On;

/**
 *
 * This class defines all general function
 *
 * @since      2.0.0
 * @package    WP_Cache_On
 * @subpackage WP_Cache_On/includes
 * @author     Jeffrey Cooper <1987coopjeff@gmail.com>
 */

// Ensure the main plugin file is loaded
if (!defined('WCO_CACHE_DIR')) {
    include_once plugin_dir_path(__DIR__) . '../wp-cache-on.php';
}
class WP_Cache_On_Disk
{

    const FILE_HTML      = 'wco.html';
    const FILE_GZIP      = 'wco.html.gz';
    const FILE_WEBP_HTML = 'wco-webp.html';
    const FILE_WEBP_GZIP = 'wco-webp.html.gz';

    /**
     * Permalink check
     */
    public static function is_permalink()
    {
        return get_option('permalink_structure');
    }

    /**
     * clear complete cache
     */
    public static function clear_total_cache()
    {
        $settings = WP_Cache_On::get_options();

        WP_Cache_On_Disk::_clear_all_cache(); //Clear disk cache
        delete_transient('cache_size'); //Delete transient

        // This will trigger homepage cache generating
        $settings['homepage_cache'] = 0;
        update_option('wp-cache-on', $settings);
    }

    /**
     * Clear all cache files
     */
    public static function _clear_all_cache()
    {
        self::_recursive_delete(WCO_CACHE_DIR); # Remove old folder if exist
    }

    private static function _recursive_delete($dirPath, $deleteParent = true)
    {
        if (file_exists($dirPath)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
                if ($path->isFile()) {
                    @unlink($path->getPathname());
                } else {
                    @rmdir($path->getPathname());
                }
            }

            if ($deleteParent) {
                @rmdir($dirPath);
            }
        }
    }

    /**
     * Clear page cache by post id
     */
    public static function clear_page_cache_by_post_id($post_ID)
    {
        if ((int) $post_ID == !$post_ID) {
            return;
        }

        self::clear_page_cache_by_url(
            get_permalink($post_ID)
        );
    }

    /**
     * clear page cache by url
     */
    public static function clear_page_cache_by_url($url)
    {
        if ((string) $url == !$url) {
            return;
        }

        self::delete_asset($url);
    }

    /**
     * Delete asset
     */
    public static function delete_asset($url)
    {
        if (empty($url)) {
            wp_die('URL is empty.');
        }

        self::_recursive_delete(self::file_path($url));
    }

    /**
     * Cache path
     */
    public static function file_path($path = null)
    {
        // Add homepage cache files to separate folder
        if ("/" === $_SERVER['REQUEST_URI']) {
            $path = "/wco-homepage";
        }

        $final_path = sprintf(
            '%s%s%s%s',
            WCO_CACHE_DIR,
            DIRECTORY_SEPARATOR,
            parse_url(
                'http://' . strtolower($_SERVER['HTTP_HOST']),
                PHP_URL_HOST
            ),
            parse_url(
                ($path ? $path : $_SERVER['REQUEST_URI']),
                PHP_URL_PATH
            )
        );

        if (is_file($final_path) > 0) {
            wp_die('Path is not valid.');
        }

        return trailingslashit($final_path);
    }

    /**
     * Check asset
     */
    public static function check_asset()
    {
        return is_readable(self::_file_html());
    }

    /**
     * Get file path
     */
    private static function _file_html()
    {
        return self::file_path() . self::FILE_HTML;
    }

    /**
     * Minify html
     */
    private static function _minify_cache($data)
    {
        $options       = WP_Cache_On::get_options();
        $minifyOptions = WP_Cache_On::minify_optiosns();

        // check if disabled
        if (!$options['minify']) {
            return $data;
        }

        //Strlen limit
        if (strlen($data) > 700000) {
            return $data;
        }

        // Ignore this tags
        $ignore_tags = (array) apply_filters(
            'cache_minify_ignore_tags',
            array(
                'textarea',
                'pre',
            )
        );

        // Ignore JS if selected
        if ($minifyOptions['MINIFY_HTML'] === $options['minify']) {
            $ignore_tags[] = 'script';
        }

        //Return of no ignore tags
        if (!$ignore_tags) {
            return $data;
        }

        //Stringify
        $ignore_regex = implode('|', $ignore_tags);

        //Regex minification
        $cleaned = preg_replace(
            array(
                '/<!--[^\[><](.*?)-->/s',
                '#(?ix)(?>[^\S ]\s*|\s{2,})(?=(?:(?:[^<]++|<(?!/?(?:' . $ignore_regex . ')\b))*+)(?:<(?>' . $ignore_regex . ')\b|\z))#',
            ),
            array(
                '',
                ' ',
            ),
            $data
        );

        $search = array(
            '/\>[^\S ]+/s', // strip whitespaces after tags, except space
            '/[^\S ]+\</s', // strip whitespaces before tags, except space
            '/(\s)+/s', // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/', // Remove HTML comments
        );

        $replace = array(
            '>',
            '<',
            '\\1',
            '',
        );

        $cleaned = preg_replace($search, $replace, $cleaned);

        //something went wrong
        if (strlen($cleaned) <= 1) {
            return $data;
        }

        return $cleaned;
    }

    /**
     * Set cache
     */
    public static function set_cache($data)
    {
        if (empty($data)) {
            return '';
        }

        $minifiedHtml = self::_minify_cache($data);

        self::store_asset($minifiedHtml);
        wco_error_log('Successfully generate cache for the page: ' . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        return $data;
    }

    /**
     * Store asset
     */
    public static function store_asset($data)
    {
        if (empty($data)) {
            wp_die('Asset is empty');
        }

        // save asset
        self::_create_files($data);
    }

    /**
     * Create files
     */
    private static function _create_files($data)
    {
        if (!wp_mkdir_p(self::file_path())) {
            wp_die('Unable to create directory.');
        }

        //Get base signature
        $cache_signature = self::_cache_signatur();

        $options = WP_Cache_On::get_options();

        //Create files
        self::_create_file(self::_file_html(), $data . $cache_signature . " (html) -->");

        //Create pre-compressed file
        if ($options['compress']) {
            self::_create_file(self::_file_gzip(), gzencode($data . $cache_signature . " (html gzip) -->", 9));
        }

        /**
         * Create webp supported files
         */
        //Magic regex rule
        $regex_rule = '#(?<=(?:(ref|src|set)=[\"\']))(?:http[s]?[^\"\']+)(\.png|\.jp[e]?g)(?:[^\"\']+)?(?=[\"\')])#';

        //Call the webp converter callback
        $converted_data = preg_replace_callback($regex_rule, ['WP_Cache_On\WP_Cache_On_Converter', 'convert_webp'], $data);

        self::_create_file(self::_file_webp_html(), $converted_data . $cache_signature . " (webp) -->");

        //Create pre-compressed file
        if ($options['compress']) {
            self::_create_file(self::_file_webp_gzip(), gzencode($converted_data . $cache_signature . " (webp gzip) -->", 9));
        }
    }

    /**
     * Create signature
     */
    private static function _cache_signatur()
    {
        return sprintf(
            "\n\n<!-- %s @ %s",
            'WPCacheOn by ',
            date_i18n(
                'd.m.Y H:i:s',
                current_time('timestamp')
            )
        );
    }

    /**
     * Create file
     */
    private static function _create_file($file, $data)
    {
        //Open file handler
        if (!$handle = @fopen($file, 'wb')) {
            wp_die('Can not write to file.');
        }

        //Write
        @fwrite($handle, $data);
        fclose($handle);
        clearstatcache();

        //Set permissions
        $stat  = @stat(dirname($file));
        $perms = $stat['mode'] & 0007777;
        $perms = $perms & 0000666;
        @chmod($file, $perms);
        clearstatcache();
    }

    /**
     * Get gzip file path
     */
    private static function _file_gzip()
    {
        return self::file_path() . self::FILE_GZIP;
    }

    /**
     * Get webp file path
     */
    private static function _file_webp_html()
    {
        return self::file_path() . self::FILE_WEBP_HTML;
    }

    /**
     * Get gzip webp file path
     */
    private static function _file_webp_gzip()
    {
        return self::file_path() . self::FILE_WEBP_GZIP;
    }

    /**
     * Get cache size
     */
    public static function get_cache_size()
    {
        $size = get_transient('cache_size');

        if (!$size) {
            $size = (int) self::cache_size(WCO_CACHE_DIR);

            // Cache size in KB cached for 10 seconds
            set_transient('cache_size', $size, 10);
        }

        return $size;
    }

    /**
     * Get cache size
     */
    public static function cache_size($dir = '.')
    {
        if (!is_dir($dir)) {
            return;
        }

        $objects = array_diff(
            scandir($dir),
            array('..', '.')
        );

        if (empty($objects)) {
            return;
        }

        $size = 0;

        foreach ($objects as $object) {
            $object = $dir . DIRECTORY_SEPARATOR . $object;

            if (is_dir($object)) {
                $size += self::cache_size($object);
            } else {
                $size += filesize($object);
            }
        }

        return $size;
    }

    /**
     * Get asset
     */
    public static function get_asset()
    {
        wco_error_log('Return cached page');

        //Set cache handler header
        header('x-cache-handler: Cached by WPCacheOn');

        // get if-modified request headers
        if (function_exists('apache_request_headers')) {
            $headers                = apache_request_headers();
            $http_if_modified_since = (isset($headers['If-Modified-Since'])) ? $headers['If-Modified-Since'] : '';
            $http_accept            = (isset($headers['Accept'])) ? $headers['Accept'] : '';
            $http_accept_encoding   = (isset($headers['Accept-Encoding'])) ? $headers['Accept-Encoding'] : '';
        } else {
            $http_if_modified_since = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : '';
            $http_accept            = (isset($_SERVER['HTTP_ACCEPT'])) ? $_SERVER['HTTP_ACCEPT'] : '';
            $http_accept_encoding   = (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
        }

        // Determine supported compression method
        $gzip    = strstr($http_accept_encoding, 'gzip');
        $deflate = strstr($http_accept_encoding, 'deflate');

        // Determine used compression method
        $encoding = $gzip ? 'gzip' : ($deflate ? 'deflate' : 'none');

        // Check for buggy versions of Internet Explorer
        if (
            !strstr($_SERVER['HTTP_USER_AGENT'], 'Opera')
            && preg_match('/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i', $_SERVER['HTTP_USER_AGENT'], $matches)
        ) {
            $version = floatval($matches[1]);
            if ($version < 6 || 6 == $version && !strstr($_SERVER['HTTP_USER_AGENT'], 'EV1')) {
                $encoding = 'none';
            }
        }

        //Some servers compress the output of PHP
        if (ini_get('output_handler') == 'ob_gzhandler' || ini_get('zlib.output_compression') == 1) {
            $encoding = 'none';
        }

        if ('none' != $encoding) {
            //Check modified since with cached file and return 304 if no difference
            if ($http_if_modified_since && (strtotime($http_if_modified_since) == filemtime(self::_file_html()))) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified', true, 304);
                exit;
            }

            //Check webp and deliver gzip webp file if support
            if ($http_accept && (strpos($http_accept, 'webp') !== false)) {
                if (is_readable(self::_file_webp_gzip())) {
                    header('Content-Encoding: gzip');
                    readfile(self::_file_webp_gzip());
                    exit;
                } elseif (is_readable(self::_file_webp_html())) {
                    readfile(self::_file_webp_html());
                    exit;
                }
            }

            //Check encoding and deliver gzip file if support
            if ($http_accept_encoding && (strpos($http_accept_encoding, 'gzip') !== false) && is_readable(self::_file_gzip())) {
                header('Content-Encoding: gzip');
                readfile(self::_file_gzip());
                exit;
            }
        } else {
            //Deliver cached file (default)
            readfile(self::_file_html());
            exit;
        }
    }

    public static function check_expiry()
    {
        $options = WP_Cache_On::get_options();

        // check if expires is active
        if (0 == $options['expires']) {
            return false;
        }

        $now             = time();
        $expires_seconds = 3600 * $options['expires'];

        // check if asset has expired
        if ((filemtime(self::_file_html()) + $expires_seconds) <= $now) {
            return true;
        }

        return false;
    }

    public function trigger_precache()
    {
        // Verify nonce for security
        check_ajax_referer('wp_cache_on_precache_nonce', 'security');

        // Include the function that handles the pre-cache process
        self::precache_pages();

        // Send a response back
        wp_send_json_success('PreCache process initiated.');
    }

    public static function precache_pages()
    {
        // Home page URL
        $home_url = home_url();

        // Fetch all slugs for pages and posts
        $page_objects = get_posts(array('post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1));
        $post_objects = get_posts(array('post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1));

        // URLs to cache
        $urls_to_cache = array();

        // Add home page to cache list
        $urls_to_cache[] = $home_url;

        // Add all pages and posts to cache list
        foreach ($page_objects as $page) {
            $urls_to_cache[] = get_permalink($page->ID);
        }

        foreach ($post_objects as $post) {
            $urls_to_cache[] = get_permalink($post->ID);
        }

        // Logic to cache the URLs
        foreach ($urls_to_cache as $url) {
            // Here you would add the logic to cache the URL, for example:
            self::cache_url($url);
        }
    }

    /*
     * Function to cache a specific URL
     */
    public static function cache_url($url)
    {
        // Use wp_remote_get or similar function to trigger caching
        $response = wp_remote_get($url);

        // Check if the response is successful
        if (is_wp_error($response)) {
            wco_error_log('Error caching URL: ' . $url . ' - ' . $response->get_error_message());
        }
    }

    /**
     * AJAX handler to clear cache.
     */
    public function clear_cache()
    {
        // Verify nonce for security
        check_ajax_referer('clear_cache_nonce', 'security');

        // Call the function to clear the cache
        WP_Cache_On_Disk::clear_total_cache();

        // Send a response back
        wp_send_json_success('Cache cleared successfully.');
    }
}
