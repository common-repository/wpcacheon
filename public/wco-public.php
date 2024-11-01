<?php
namespace WP_Cache_On;

use WP_Cache_On\WP_Cache_On;
use WP_Cache_On\WP_Cache_On_Disk;
use WP_Cache_On\WP_Cache_On_General;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    WP_Cache_On
 * @subpackage WP_Cache_On/public
 * @author     Jeffrey Cooper <1987coopjeff@gmail.com>
 */

class WP_Cache_On_Public
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

    private $source_file;

    private $destination_file;
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $wp_cache_on       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($wp_cache_on, $version)
    {
        $this->wp_cache_on = $wp_cache_on;
        $this->version     = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in WP_Cache_On_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The WP_Cache_On_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->wp_cache_on, plugin_dir_url(__FILE__) . 'css/wco-public.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in WP_Cache_On_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The WP_Cache_On_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->wp_cache_on, plugin_dir_url(__FILE__) . 'js/wco-public.js', array('jquery'), $this->version, false);

    }

    public function remove_query_strings_from_resources($src)
    {
        if (false !== ($url = @parse_url($src))) {

            // Check result array
            if (!empty($url) && is_array($url) && !empty($url['query'])) {

                // Extract arguments
                @parse_str($url['query'], $args);
                if (!empty($args) && is_array($args)) {
                    // Remove arguments without value
                    foreach ($args as $arg => $value) {
                        if ('' === trim('' . $value)) {
                            $src = remove_query_arg($arg, $src);
                        }
                    }

                    $unwanted_args = apply_filters('rmqrst_unwanted_args', ['ver', 'version', 'v'], $src);

                    if (empty($unwanted_args) || !is_array($unwanted_args)) {
                        return $src;
                    }

                    foreach ($args as $arg => $value) {
                        if (in_array($arg, $unwanted_args)) {
                            // Remove avoiding aggressive arg removing
                            $src = remove_query_arg($arg, $src);
                        }
                    }
                }
            }
        }

        return $src;
    }

    /**
     * Register publish hooks for custom post types
     */
    public static function register_publish_hooks()
    {
        //Get post types
        $post_types = get_post_types(
            array('public' => true)
        );

        //Check if empty
        if (empty($post_types)) {
            return;
        }

        //Post type actions
        foreach ($post_types as $post_type) {
            add_action('publish_' . $post_type, array(__CLASS__, '_publish_post_types'), 10, 2);
            add_action('publish_future_' . $post_type, array(new WP_Cache_On_Disk, 'clear_total_cache'));
        }
    }

    /**
     * Delete post type cache on post updates
     */
    public static function _publish_post_types($post_ID, $post)
    {
        //Check if post id or post is empty
        if (empty($post_ID) || empty($post)) {
            return;
        }

        //Check post status
        if (!in_array($post->post_status, array('publish', 'future'))) {
            return;
        }

        //Validate user role
        if (!current_user_can('publish_posts')) {
            return;
        }

        //Purge complete cache or specific post
        if (!empty($post_ID)) {
            // Delete cached version
            WP_Cache_On_Disk::clear_page_cache_by_post_id($post_ID);
            // Crete cached version
            WP_Cache_On_Disk::cache_url(get_permalink($post_ID));
        } else {
            // Delete cached version
            WP_Cache_On_Disk::clear_total_cache();
            // Crete cached version
            WP_Cache_On_Disk::precache_pages();
        }
    }

    /**
     * Process clear request
     */
    public static function process_clear_request($data)
    {
        //Check if clear request
        if (empty($_GET['_cache']) or ('clear' !== $_GET['_cache'] && 'clearurl' !== $_GET['_cache'])) {
            return;
        }

        //Validate nonce
        if (empty($_GET['_wpnonce']) or !wp_verify_nonce($_GET['_wpnonce'], '_cache__clear_nonce')) {
            return;
        }

        //Check user role
        if (!is_admin_bar_showing() or !apply_filters('user_can_clear_cache', current_user_can('manage_options'))) {
            return;
        }

        //Load if network
        if (!function_exists('is_plugin_active_for_network')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        //Set clear url w/o query string
        $clear_url = preg_replace('/\?.*/', '', home_url(add_query_arg(null, null)));

        //Multisite and network setup
        if (is_multisite() && is_plugin_active_for_network(WCO_BASE)) {

            if (is_network_admin()) {

                //Legacy blog
                $legacy = $GLOBALS['wpdb']->blogid;

                //Blog ids
                $ids = self::_get_blog_ids();

                //Switch blogs
                foreach ($ids as $id) {
                    switch_to_blog($id);
                    WP_Cache_On_Disk::clear_page_cache_by_url(home_url());
                }

                //Restore
                switch_to_blog($legacy);

                //Clear notice
                if (is_admin()) {
                    add_action('network_admin_notices', array('WP_Cache_On\WP_Cache_On_General', 'clear_notice'));
                }
            } else {
                if ('clearurl' == $_GET['_cache']) {
                    // clear specific multisite url cache
                    WP_Cache_On_Disk::clear_page_cache_by_url($clear_url);

                    // Log the deleted URL
                    error_log(sprintf('[WPCacheOn] User deleted cached URL: %s', esc_url($clear_url)));

                } else {
                    // clear specific multisite cache
                    WP_Cache_On_Disk::clear_page_cache_by_url(home_url());

                    // clear notice
                    if (is_admin() && current_user_can('manage_options')) {
                        add_action('admin_notices', array('WP_Cache_On\WP_Cache_On_General', 'clear_notice'));
                    }
                }
            }
        } else {
            if ('clearurl' == $_GET['_cache']) {
                // clear url cache
                WP_Cache_On_Disk::clear_page_cache_by_url($clear_url);
                wco_error_log('User deleted cached URL: ' . $clear_url);
            } else {
                // clear cache
                WP_Cache_On_Disk::clear_total_cache();
                WP_Cache_On_General::set_clear_notice();

                // clear notice
                if (is_admin()) {
                    add_action('admin_notices', array('WP_Cache_On\WP_Cache_On_General', 'clear_notice'));
                }
            }
        }

        if (!is_admin()) {
            wp_safe_redirect(
                remove_query_arg(
                    '_cache',
                    wp_get_referer()
                )
            );

            exit();
        }
    }

    /**
     * Make initial request to make a cache for home page
     */
    public static function homepage_cache()
    {
        $settings = WP_Cache_On::get_options();

        if (0 === $settings['homepage_cache']) {
            $settings['homepage_cache'] = 1;

            //Remove sanitizing for adding
            // remove_filter("sanitize_option_wp-cache-on", array(__CLASS__, 'validate_settings'));
            update_option('wp-cache-on', $settings);

            WP_Cache_On_Disk::cache_url(get_site_url() . '/');

            // We need this because on cache flush everything is deleted from WCO_CACHE_DIR
            $createIndex = fopen(WCO_CACHE_DIR . "/index.php", "w");
            fwrite($createIndex, "<?php " . PHP_EOL . " echo 'Secure cache by WPCacheOn';");
            fclose($createIndex);
        } else {
            // wco_error_log('Homepage cache skipped. ' . $settings['homepage_cache']);
        }

        // Clear any cached information about files
        clearstatcache();
    }

    /**
     * Clear cache if new comment
     */
    public static function new_comment($approved, $comment)
    {
        // check if comment is approved
        if (1 === $approved) {
            WP_Cache_On_Disk::clear_page_cache_by_post_id($comment['comment_post_ID']);
        }

        return $approved;
    }

    /**
     * Handle cache
     */
    public static function handle_cache()
    {
        // Bypass cache
        if (\WP_Cache_On\WP_Cache_On_General::bypass_cache()) {
            return;
        }

        $cached  = WP_Cache_On_Disk::check_asset();
        $expired = WP_Cache_On_Disk::check_expiry();

        // Check if cache empty OR expired
        if (!$cached || $expired) {
            ob_start(['WP_Cache_On\WP_Cache_On_Disk', 'set_cache']);
            return;
        }

        //Return cached asset
        WP_Cache_On_Disk::get_asset();
    }
}
