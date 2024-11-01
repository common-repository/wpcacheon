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

class WP_Cache_On_General
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $wp_cache_on    The ID of this plugin.
     */
    private $wp_cache_on;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $wp_cache_on       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($wp_cache_on, $version)
    {

        $this->wp_cache_on = $wp_cache_on;
        $this->version     = $version;

    }

    /**
     * Get blog ids
     *
     * @return  array  blog ids array
     * @since    1.0.0
     */
    public static function _get_blog_ids()
    {
        global $wpdb;

        return $wpdb->get_col("SELECT blog_id FROM `$wpdb->blogs`");
    }

    public static function htaccess_action($activate = true)
    {
        wco_error_log('Adjust .htaccess file');

        $wp_config_file = ABSPATH . 'wp-config.php';

        if (is_writable($wp_config_file)) {
            $wp_config = file($wp_config_file); // get wp config as array

            if ($activate) {
                $wp_cache_ce_line = "define('WP_CACHE', true); // Added by WPCacheOn" . PHP_EOL;
            } else {
                $wp_cache_ce_line = "define('WP_CACHE', false);" . PHP_EOL;
            }

            $found_wp_cache_on = false;

            foreach ($wp_config as &$line) {
                if (preg_match('/^\s*define\s*\(\s*[\'\"]WP_CACHE[\'\"]\s*,\s*(.*)\s*\)/', $line)) {
                    $line              = $wp_cache_ce_line;
                    $found_wp_cache_on = true;
                    break;
                }
            }

            // add wp cache ce line if not found yet
            if (!$found_wp_cache_on) {
                //Remove first line of config that is <?php
                array_shift($wp_config);
                //Add it and
                array_unshift($wp_config, "<?php" . PHP_EOL, $wp_cache_ce_line);
            }

            // write wp-config.php file
            $fh = @fopen($wp_config_file, 'w');
            foreach ($wp_config as $ln) {
                @fwrite($fh, $ln);
            }

            @fclose($fh);
        }

        $wp_htaccess_file = ABSPATH . '.htaccess';

        if (file_exists($wp_htaccess_file) && is_writable($wp_htaccess_file)) {
            $wp_htaccess     = file_get_contents($wp_htaccess_file);
            $backup_htaccess = ABSPATH . ".htaccess.wco";

            if ($activate) {
                $backup = fopen($backup_htaccess, "w");
                fwrite($backup, $wp_htaccess);
                fclose($backup);

                $wco_htaccess = file_get_contents(WCO_DIR . "includes/.wpcacheon");
                $newHtaccess  = $wp_htaccess . PHP_EOL . $wco_htaccess;
            } else {
                $pattern = '/(##############################################([^\n]*\n+)## +BEGIN WPCacheOn \.htaccess optimizations +##([^\n]*\n+)##############################################)((.*?(\n))+.*?)(############################################([^\n]*\n+)## +END WPCacheOn \.htaccess optimizations +##([^\n]*\n+)############################################)/';

                // Replace the matched block with an empty string
                $newHtaccess = trim(preg_replace($pattern, '', $wp_htaccess));

                if (!@unlink($backup_htaccess)) {
                    $error = error_get_last();
                    wco_error_log('Error deleating "htaccess.wco": ' . $error['message']);
                } else {
                    wco_error_log('File "htaccess.wco" successfully deleted.');
                }
            }

            if ($newHtaccess !== $wp_htaccess) {
                // Trim the updated content to remove any extra newlines or whitespace
                if (file_put_contents($wp_htaccess_file, $newHtaccess) !== false) {
                    wco_error_log('Successfully update WPCacheOn rules from .htaccess.');
                } else {
                    wco_error_log('Failed to update .htaccess.');
                }
            } else {
                wco_error_log('No update needed on .htaccess');
            }
        } else {
            wco_error_log('.htaccess is not writeable');
        }
    }

    /**
     * Notification after clear cache
     */
    public static function clear_notice()
    {
        if (!is_admin_bar_showing() || !apply_filters('user_can_clear_cache', current_user_can('manage_options'))) {
            return false;
        }

        if (get_transient('wp_cache_on_clear_notice')) {
            echo sprintf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html__('The WPCacheOn cache is successfully cleared!', 'wpcacheon')
            );

            // Clear the transient after displaying the notice
            delete_transient('wp_cache_on_clear_notice');
        }
    }

    // Function to set the transient when cache is cleared
    public static function set_clear_notice()
    {
        set_transient('wp_cache_on_clear_notice', true, 30); // Display for 30 seconds
    }
    /**
     * Add admin links
     */
    public static function add_admin_links($wp_admin_bar)
    {
        if (!is_admin_bar_showing() or !apply_filters('user_can_clear_cache', current_user_can('manage_options'))) {
            return;
        }

        // add admin purge link
        $wp_admin_bar->add_menu(
            array(
                'id'     => 'clear-on',
                'href'   => wp_nonce_url(add_query_arg('_cache', 'clear'), '_cache__clear_nonce'),
                'parent' => 'top-secondary',
                'title'  => '<span class="ab-item">' . esc_html__('Flush cache - WPCacheOn', 'wpcacheon') . '</span>',
                'meta'   => array('title' => esc_html__('Flush cache - WPCacheOn', 'wpcacheon')),
            )
        );

        //Add admin purge link
        if (!is_admin()) {
            $wp_admin_bar->add_menu(
                array(
                    'id'     => 'clear-url-cache',
                    'href'   => wp_nonce_url(add_query_arg('_cache', 'clearurl'), '_cache__clear_nonce'),
                    'parent' => 'top-secondary',
                    'title'  => '<span class="ab-item">' . esc_html__('Clear URL Cache', 'wpcacheon') . '</span>',
                    'meta'   => array('title' => esc_html__('Clear URL Cache', 'wpcacheon')),
                )
            );
        }
    }

    /**
     * Check to bypass the cache
     */
    public static function bypass_cache()
    {
        //Bypass cache hook
        if (apply_filters('bypass_cache', false)) {
            wco_error_log('Handle request from the cache SKIP: bypass_cache');
            return true;
        }

        //Conditional tags
        if (self::_is_index() or is_search() or is_404() or is_feed() or is_trackback() or is_robots() or is_preview() or post_password_required()) {
            wco_error_log('Handle request from the cache SKIP: tag');
            return true;
        }

        //DONOTCACHEPAGE check e.g. woocommerce
        if (defined('DONOTCACHEPAGE') && DONOTCACHEPAGE) {
            wco_error_log('Handle request from the cache SKIP: DONOTCACHEPAGE');
            return true;
        }

        //EasyDigitalDownloads escape
        $urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (strpos($urlPath, '/edd-sl/') !== false || strpos($urlPath, 'x=y') !== false) {
            wco_error_log('Handle request from the cache SKIP: EasyDigitalDownloads');
            return true;
        }

        //Request method GET
        if (!isset($_SERVER['REQUEST_METHOD']) || 'GET' != $_SERVER['REQUEST_METHOD']) {
            wco_error_log('Handle request from the cache SKIP: !GET');
            return true;
        }

        $options = WP_Cache_On::get_options();

        //Request with query strings
        if (!empty($_GET) && !isset($_GET['utm_source'], $_GET['utm_medium'], $_GET['utm_campaign']) && get_option('permalink_structure')) {
            wco_error_log('Handle request from the cache SKIP: with querry strings');
            return true;
        }

        if (self::_is_logged_in()) {
            wco_error_log('Handle request from the cache SKIP: logged in');
            return true;
        }

        if (self::_is_mobile()) {
            wco_error_log('Handle request from the cache SKIP: mobile');
            return true;
        }

        //If post id excluded
        if ($options['excl_ids'] && is_singular()) {
            if (in_array($GLOBALS['wp_query']->get_queried_object_id(), self::_preg_split($options['excl_ids']))) {
                wco_error_log('Handle request from the cache SKIP: ids');
                return true;
            }
        }

        return false;
    }

    /**
     * Check if index.php
     */
    private static function _is_index()
    {
        return basename($_SERVER['SCRIPT_NAME']) != 'index.php';
    }

    /**
     * Check if logged in
     */
    private static function _is_logged_in()
    {
        if (is_user_logged_in()) {
            return true;
        }

        if (empty($_COOKIE)) {
            return false;
        }

        foreach ($_COOKIE as $k => $v) {
            if (preg_match('/^(wp-postpass|wordpress_logged_in|comment_author)_/', $k)) {
                return true;
            }
        }
    }

    /**
     * Check if mobile
     */
    private static function _is_mobile()
    {
        return (strpos(TEMPLATEPATH, 'wptouch') or strpos(TEMPLATEPATH, 'carrington') or strpos(TEMPLATEPATH, 'jetpack') or strpos(TEMPLATEPATH, 'handheld'));
    }

    /**
     * Explode on comma
     */
    private static function _preg_split($input)
    {
        return (array) preg_split('/,/', $input, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Copies the advanced-cache.php file from the plugin directory to wp-content
     * if the file does not exist or has changed.
     *
     * If the file does exist and has not changed, it will print a message to the
     * error log indicating that the files are the same.
     *
     * If the file does exist but has changed, it will try to copy the file. If
     * the copy fails, it will print a message to the error log with the error
     * message. If the copy is successful, it will print a message to the error
     * log indicating that the file was copied successfully.
     *
     * If either of the files do not exist, it will print a message to the error
     * log indicating which file does not exist.
     */
    public static function set_advance_cache_file()
    {
        $source_file      = WCO_DIR . 'includes/advanced-cache.php';
        $destination_file = WP_CONTENT_DIR . '/advanced-cache.php';

        // Check if both files exist before comparing
        if (file_exists($source_file)) {
            if (file_exists($destination_file)) {
                // Read the contents of both files
                $source_content      = hash_file('sha256', $source_file);
                $destination_content = hash_file('sha256', $destination_file);

                // Compare the contents
                if ($source_content === $destination_content) {
                    wco_error_log('The contents of the source and destination files are the same.');
                    return;
                }
            }

            try {
                if (!@copy($source_file, $destination_file)) {
                    // If copy fails, get the last error
                    $error = error_get_last();
                    throw new Exception("Error copying file: " . $error['message']);
                }
                wco_error_log('File copied successfully.');
            } catch (Exception $e) {
                wco_error_log('An error occurred: ' . $e->getMessage());
            }
        } else {
            wco_error_log('One or both files do not exist: (' . $source_file . ' | ' . $destination_file . ')');
        }
    }
}
