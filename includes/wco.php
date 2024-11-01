<?php
namespace WP_Cache_On; // Ensure the namespace is correctly declared

// Include the functions file
include_once plugin_dir_path(__FILE__) . 'wco-functions.php';

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      2.0.0
 * @package    WP_Cache_On
 * @subpackage WP_Cache_On/includes
 * @author     Jeffrey Cooper <1987coopjeff@gmail.com>
 */
class WP_Cache_On
{
    /**
     * Options values for this pluggin
     */
    public $options;

    /**
     * Class that make all DISK releated opperations
     */
    private $disk;

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WP_Cache_On_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    private $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $wp_cache_on    The string used to uniquely identify this plugin.
     */
    private $wp_cache_on;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    private $version;

    /**
     * Minify default settings
     *
     * @since  2.0.0
     */

    const MINIFY_OFF     = 0;
    const MINIFY_HTML    = 1;
    const MINIFY_HTML_JS = 2;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    2.0.0
     */
    public function __construct($version)
    {
        $this->version     = $version;
        $this->wp_cache_on = 'wp-cache-on';
        $this->options     = $this->get_options();

        $this->loader = new WP_Cache_On_Loader();

        if (WP_Cache_On_Disk::is_permalink()) {
            $this->disk = new WP_Cache_On_Disk();
        }

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - WP_Cache_On_Loader. Orchestrates the hooks of the plugin.
     * - WP_Cache_On_i18n. Defines internationalization functionality.
     * - WP_Cache_On_Admin. Defines all hooks for the admin area.
     * - WP_Cache_On_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        $this->loader = new WP_Cache_On_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the WP_Cache_On_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new WP_Cache_On_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Admin\WP_Cache_On_Admin($this->get_wp_cache_on(), $this->get_version());
        $plugin_admin->register_hooks();

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        $this->loader->add_action('upgrader_process_complete', $plugin_admin, 'wp_upgrade_done', 10, 2);

        //Admin
        if (is_admin()) {
            $this->loader->add_action('wpmu_new_blog', $plugin_admin, 'install_later');
            $this->loader->add_action('delete_blog', $plugin_admin, 'uninstall_later');

            $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
            $this->loader->add_action('admin_menu', $plugin_admin, 'add_settings_page');

            // Notice to rate
            $this->loader->add_action('admin_notices', $plugin_admin, 'rate_notice');
            $this->loader->add_action('admin_init', $plugin_admin, 'wco_skip_rating');

            $this->loader->add_action('transition_comment_status', $plugin_admin, 'change_comment', 10, 3);
            $this->loader->add_action('comment_post', $plugin_admin, 'comment_post', 99, 2);
            $this->loader->add_action('edit_comment', $plugin_admin, 'edit_comment');

            $this->loader->add_filter('dashboard_glance_items', $plugin_admin, 'add_dashboard_count');
            $this->loader->add_action('post_submitbox_misc_actions', $plugin_admin, 'add_clear_dropdown');
            $this->loader->add_filter('plugin_row_meta', $plugin_admin, 'row_meta', 10, 2);
            $this->loader->add_filter('plugin_action_links_' . WCO_BASE, $plugin_admin, 'action_links');

            // warnings and notices
            $this->loader->add_action('admin_notices', $plugin_admin, 'warning_is_permalink');
            $this->loader->add_action('admin_notices', $plugin_admin, 'requirements_check');
            //$this->loader->add_action('admin_notices', $plugin_admin, 'marketing_notification');

            $hook = "in_plugin_update_message-{" . basename(dirname(__FILE__)) . "}/{" . basename(__FILE__) . "}";
            $this->loader->add_action($hook, $plugin_admin, 'prefix_plugin_update_message', 10, 2);
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {
        $plugin_public  = new WP_Cache_On_Public($this->get_wp_cache_on(), $this->get_version());
        $plugin_disk    = new WP_Cache_On_Disk();
        $plugin_general = new WP_Cache_On_General($this->get_wp_cache_on(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        $this->loader->add_filter('script_loader_src', $plugin_public, 'remove_query_strings_from_resources');
        $this->loader->add_filter('style_loader_src', $plugin_public, 'remove_query_strings_from_resources');

        $this->loader->add_action('init', $plugin_public, 'register_publish_hooks', 99);
        $this->loader->add_action('init', $plugin_public, 'process_clear_request');
        $this->loader->add_action('init', $plugin_public, 'homepage_cache');

        $this->loader->add_action('con_clear_post_cache', $plugin_disk, 'clear_page_cache_by_post_id');
        $this->loader->add_action('con_clear_cache', $plugin_disk, 'clear_total_cache');
        $this->loader->add_action('_core_updated_successfully', $plugin_disk, 'clear_total_cache');
        $this->loader->add_action('switch_theme', $plugin_disk, 'clear_total_cache');
        $this->loader->add_action('wp_trash_post', $plugin_disk, 'clear_total_cache');
        $this->loader->add_action('autoptimize_action_cachepurged', $plugin_disk, 'clear_total_cache');
        $this->loader->add_action('admin_bar_menu', $plugin_general, 'add_admin_links', 90);
        // Register AJAX action for logged-in users
        $this->loader->add_action('wp_ajax_trigger_precache', $plugin_disk, 'trigger_precache');
        // Register AJAX action for logged-in users to clear cache
        $this->loader->add_action('wp_ajax_clear_cache', $plugin_disk, 'clear_cache');

        if (!is_admin()) {
            $this->loader->add_action('pre_comment_approved', $plugin_public, 'new_comment', 99, 2);
            $this->loader->add_action('template_redirect', $plugin_public, 'handle_cache', 0);
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_wp_cache_on()
    {
        return $this->wp_cache_on;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    WP_Cache_On_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Get options
     */
    public static function get_options()
    {
        return wp_parse_args(
            get_option('wp-cache-on'),
            [
                'expires'        => 0,
                'compress'       => 1, // Ensure all options are initialized with default values
                'compression' => 0, // Initialize compression with a default value
                'excl_ids' => '',
                'minify'         => self::MINIFY_HTML_JS,
                'activated_on'   => date("Y/m/d"),
                'skip_rating'    => '',
                'homepage_cache' => 0, // Initialize homepage cache on install
            ]
        );
    }

    /**
     * Minify dropdown
     *
     * @since   2.0.0
     */
    public static function minify_select()
    {
        return array(
            self::MINIFY_OFF     => esc_html__('Disabled', 'wp-cache-on'),
            self::MINIFY_HTML    => esc_html__('HTML', 'wp-cache-on'),
            self::MINIFY_HTML_JS => esc_html__('HTML & Inline JS', 'wp-cache-on'), // Correctly placed line
        );
    }

    public static function minify_optiosns()
    {
        return array(
            "MINIFY_OFF"     => self::MINIFY_OFF,
            "MINIFY_HTML"    => self::MINIFY_HTML,
            "MINIFY_HTML_JS" => self::MINIFY_HTML_JS,
        );
    }
}
