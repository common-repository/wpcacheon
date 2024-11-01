<?php
namespace WP_Cache_On\Admin;

use WP_Cache_On\Admin\WP_Cache_On_Admin_Display;
use WP_Cache_On\WP_Cache_On;
use WP_Cache_On\WP_Cache_On_Disk;
use WP_Cache_On\WP_Cache_On_Public;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      2.0.0
 * @package    WP_Cache_On
 * @subpackage WP_Cache_On/admin
 * @author     Jeffrey Cooper <1987coopjeff@gmail.com>
 */
class WP_Cache_On_Admin
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
    public function register_hooks()
    {
        // Ensure these hooks are registered
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function display_plugin_admin_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
        WP_Cache_On_Admin_Display::settings_page(); // Call the method to display the settings
        echo '</div>';
    }

    public function register_settings()
    {
        if (!empty($_GET['_cache']) && ('clear' === $_GET['_cache'] || 'clearurl' === $_GET['_cache'])) {
            WP_Cache_On_Public::process_clear_request($_GET);
        }

        // Register settings and add settings section
        register_setting($this->wp_cache_on, $this->wp_cache_on, array(__CLASS__, 'validate_settings'));

        add_settings_section(
            'wp_cache_on_settings_section',
            __('WP Cache On Settings', 'wp-cache-on'),
            null,
            $this->wp_cache_on
        );

        // Register all settings fields from the original file
        add_settings_field(
            'cache_expiry',
            __('Cache Expiry Time', 'wp-cache-on'),
            [$this, 'cache_expiry_callback'],
            $this->wp_cache_on,
            'wp_cache_on_settings_section',
            [
                'label_for' => 'cache_expiry',
            ]
        );

        add_settings_field(
            'excl_ids',
            __('Exclude Pages/Posts IDs', 'wp-cache-on'),
            [$this, 'excl_ids_callback'],
            $this->wp_cache_on,
            'wp_cache_on_settings_section',
            ['label_for' => 'excl_ids']
        );

        add_settings_field(
            'compression',
            __('Enable Compression', 'wp-cache-on'),
            [$this, 'compression_callback'],
            $this->wp_cache_on,
            'wp_cache_on_settings_section',
            [
                'label_for' => 'compression',
            ]
        );

        add_settings_field(
            'minify_option',
            __('Minify HTML & Inline JS', 'wp-cache-on'),
            [$this, 'minify_callback'],
            $this->wp_cache_on,
            'wp_cache_on_settings_section',
            [
                'label_for' => 'minify_option',
            ]
        );
    }

    public function cache_expiry_callback($args)
    {
        $options = get_option($this->wp_cache_on);
        $value   = isset($options['cache_expiry']) ? esc_attr($options['cache_expiry']) : '';
        echo '<input type="text" id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($this->wp_cache_on) . '[cache_expiry]" value="' . $value . '" />';
    }

    public function compression_callback($args)
    {
        $options = get_option($this->wp_cache_on);
        $checked = isset($options['compression']) && $options['compression'] ? 'checked' : '';
        echo '<input type="checkbox" id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($this->wp_cache_on) . '[compression]" value="1" ' . $checked . ' />';
    }

    public function minify_callback($args)
    {
        $options      = get_option($this->wp_cache_on);
        $minify_value = isset($options['minify']) ? esc_attr($options['minify']) : '';
        echo '<select id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($this->wp_cache_on) . '[minify]">';
        echo '<option value="0"' . selected($minify_value, 0, false) . '>' . __('Disabled', 'wp-cache-on') . '</option>';
        echo '<option value="1"' . selected($minify_value, 1, false) . '>' . __('HTML', 'wp-cache-on') . '</option>';
        echo '<option value="2"' . selected($minify_value, 2, false) . '>' . __('HTML & Inline JS', 'wp-cache-on') . '</option>';
        echo '</select>';
    }

    public function excl_ids_callback($args)
    {
        $options = get_option($this->wp_cache_on);
        $value   = isset($options['excl_ids']) ? esc_attr($options['excl_ids']) : '';
        echo '<input type="text" id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($this->wp_cache_on) . '[excl_ids]" value="' . $value . '" />';
    }

    /**
     * Register the stylesheets for the admin area.
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

        wp_enqueue_style($this->wp_cache_on, plugin_dir_url(__FILE__) . 'css/wco-admin.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
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

        wp_enqueue_script($this->wp_cache_on, plugin_dir_url(__FILE__) . 'js/wco-admin.js', array('jquery'), $this->version, false);

    }

    /**
     * This function runs when WordPress completes its upgrade process
     * It iterates through each plugin updated to see if ours is included
     * @param $upgrader_object Array
     * @param $options Array
     */
    public function wp_upgrade_done($upgrader_object, $options)
    {
        // If an update has taken place and the updated type is plugins and the plugins element exists
        if ('update' == $options['action'] && 'plugin' == $options['type'] && isset($options['plugins'])) {

            // Iterate through the plugins and check if ours is there
            foreach ($options['plugins'] as $plugin) {
                if (WCO_BASE == $plugin) {
                    //WPCacheOn::onDeactivation(); This must be part of activation
                    // WPCacheOn::onActivation();
                }
            }
        }
    }

    /**
     * Install on multi site setup
     */
    public static function install_later($id)
    {
        //Check if multi site setup
        if (!is_plugin_active_for_network(WCO_BASE)) {
            return;
        }

        switch_to_blog($id);
        WP_Cache_On_Activator::_install_backend();
        restore_current_blog();
    }

    /**
     * Uninstall for multisite and network
     */
    public static function uninstall_later($id)
    {
        if (!is_plugin_active_for_network(WCO_BASE)) {
            return;
        }

        switch_to_blog($id);
        WP_Cache_On_Activator::_uninstall_backend();
        restore_current_blog();
    }

    /**
     * Validate settings
     */
    public static function validate_settings($data)
    {
        if (empty($data)) {
            return;
        }

        WP_Cache_On_Disk::clear_total_cache(true);
        $options = WP_Cache_On::get_options();

        return array_merge(
            $options,
            array(
                'expires'     => (int) $data['expires'],
                'excl_ids'    => (string) sanitize_text_field(@$data['excl_ids']),
                'minify'      => (int) $data['minify'],
                'compression' => !empty($data['compression']) ? 1 : 0,
            )
        );
    }

    /**
     * Add settings page
     */
    public static function add_settings_page()
    {
        add_options_page(
            'WPCacheOn',
            'WPCacheOn',
            'manage_options',
            'wp-cache-on',
            [new WP_Cache_On_Admin_Display(), 'settings_page']
        );
    }

    /**
     * Clear cache if comment changes
     */
    public static function change_comment($after_status, $before_status, $comment)
    {
        self::_clear_comment_cache($comment->comment_ID);
    }

    /**
     * Clear cache if post comment
     */
    public static function comment_post($id, $approved)
    {
        self::_clear_comment_cache($id);
    }

    /**
     * Clear cache if edit comment
     */
    public static function edit_comment($id)
    {
        self::_clear_comment_cache($id);
    }

    /**
     * Clear comment cache for case of: edit, post, change
     */
    private static function _clear_comment_cache($id)
    {
        $commentPostID = get_comment($id)->comment_post_ID;
        WP_Cache_On_Disk::clear_page_cache_by_post_id($commentPostID);
        WP_Cache_On_Disk::cache_url(get_permalink($commentPostID));
    }
    /**
     * Add dashboard cache size count
     */
    public static function add_dashboard_count($items = array())
    {
        if (!current_user_can('manage_options')) {
            return $items;
        }

        $size = WP_Cache_On_Disk::get_cache_size(); //Get cache size

        //Display items
        $items[] = sprintf(
            '<a href="%s" title="%s" class="dashicons-performance">%s %s</a>',
            add_query_arg(
                array(
                    'page' => 'wp-cache-on',
                ),
                admin_url('options-general.php')
            ),
            esc_html__('Disk Cache', 'wpcacheon'),
            esc_html__('WPCacheOn Size', 'wpcacheon'),
            (empty($size) ? esc_html__('Empty', 'wpcacheon') : size_format($size))
        );

        return $items;
    }

    /**
     * Add clear option dropdown on post publish widget
     */
    public static function add_clear_dropdown()
    {
        //On published post page only
        if (empty($GLOBALS['pagenow']) or 'post.php' !== $GLOBALS['pagenow'] or empty($GLOBALS['post']) or !is_object($GLOBALS['post']) or 'publish' !== $GLOBALS['post']->post_status) {
            return;
        }

        //Check user role
        if (!current_user_can('publish_posts')) {
            return;
        }

        //Validate nonce
        wp_nonce_field(WCO_BASE, '_cache__status_nonce_' . $GLOBALS['post']->ID);

        //Get current action
        $current_action = (int) get_user_meta(
            get_current_user_id(),
            '_clear_post_cache_on_update',
            true
        );

        //Init variables
        $dropdown_options  = '';
        $available_options = array(
            esc_html__('Completely', 'wpcacheon'),
            esc_html__('Page specific', 'wpcacheon'),
        );

        //Set dropdown options
        foreach ($available_options as $key => $value) {
            $dropdown_options .= sprintf(
                '<option value="%1$d" %3$s>%2$s</option>',
                $key,
                $value,
                selected($key, $current_action, false)
            );
        }

        //Output drowdown
        echo sprintf(
            '<div class="misc-pub-section" style="border-top:1px solid #eee">
                <label for="cache_action">
                    %1$s: <span id="output-cache-action">%2$s</span>
                </label>
                <a href="#" class="edit-cache-action hide-if-no-js">%3$s</a>

                <div class="hide-if-js">
                    <select name="_clear_post_cache_on_update" id="cache_action">
                        %4$s
                    </select>

                    <a href="#" class="save-cache-action hide-if-no-js button">%5$s</a>
                     <a href="#" class="cancel-cache-action hide-if-no-js button-cancel">%6$s</a>
                 </div>
            </div>',
            esc_html__('Clear cache', 'wpcacheon'),
            $available_options[$current_action],
            esc_html__('Edit', 'wpcacheon'),
            $dropdown_options,
            esc_html__('OK', 'wpcacheon'),
            esc_html__('Cancel', 'wpcacheon')
        );
    }

    /**
     * Cache On meta links
     */
    public static function row_meta($input, $page)
    {
        if (WCO_BASE != $page) //Check permissions
        {
            return $input;
        }

        array_pop($input);
        return array_merge(
            $input,
            array(
                sprintf('<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
                    esc_url(network_admin_url('plugin-install.php?tab=plugin-information&plugin=wpcacheon&TB_iframe=true&width=772&height=882')),
                    esc_attr(sprintf(__('More information about %s'), 'WPCacheOn')),
                    esc_attr('WPCacheOn'),
                    __('View details')
                ),
                '<a href="https://wpcacheon.io/" target="_blank">Visit plugin site</a>',
                '<a href="https://www.facebook.com/wpcacheon/" target="_blank">Like Us on Facebook</a>',
                '<a href="https://wordpress.org/support/plugin/wpcacheon/reviews/?rate=5#new-post" target="_blank">Rate Us</a>',
                '<a href="https://wpcacheon.io/contact/" target="_blank">Support & Report</a>',
            )
        );
    }

    /**
     * Add action links
     */
    public static function action_links($data)
    {
        if (!current_user_can('manage_options')) { //Check user role
            return $data;
        }

        return array_merge(
            array(
                sprintf(
                    '<a href="%s">%s</a>',
                    add_query_arg(
                        array(
                            'page' => 'wp-cache-on',
                        ),
                        admin_url('options-general.php')
                    ),
                    esc_html__('Settings', 'wpcacheon')
                ),
                sprintf(
                    '<a href="%s">%s</a>',
                    wp_nonce_url(add_query_arg('_cache', 'clear'), '_cache__clear_nonce'),
                    esc_html__('Flush Cache', 'wpcacheon')
                ),
            ),
            $data
        );
    }

    /**
     * Warning if no custom permalink
     */
    public static function warning_is_permalink()
    {
        if (!WP_Cache_On_Disk::is_permalink() and current_user_can('manage_options')) {
            return '
            <div class="error">
                <p>' . printf(
                __('The <b>%s</b> plugin requires a custom permalink structure to start caching properly. Please go to <a href="%s">Permalink</a> to enable it', 'wpcacheon'),
                'WPCacheOn',
                admin_url('options-permalink.php')
            ) . '</p>
            </div>';
        }
    }

    /**
     * Check plugin requirements
     */
    public static function requirements_check()
    {
        $options = WP_Cache_On::get_options();

        // WordPress version check
        if (version_compare($GLOBALS['wp_version'], WCO_MIN_WP . 'alpha', '<')) {
            show_message(
                sprintf(
                    '<div class="error"><p>%s</p></div>',
                    sprintf(
                        __('The <b>%s</b> is optimized for WordPress %s. Please disable the plugin or upgrade your WordPress installation (recommended).', 'wp-cache-on'),
                        'WPCacheOn',
                        WCO_MIN_WP
                    )
                )
            );
        }

        //Permission check
        if (file_exists(WCO_CACHE_DIR) && !is_writable(WCO_CACHE_DIR)) {
            show_message(
                sprintf(
                    '<div class="error"><p>%s</p></div>',
                    sprintf(
                        __('The <b>%s</b> requires at least %s permissions on %s folder to optimize your website. Please reactivate the plugin for complete optimization after permision fix!', 'wp-cache-on'),
                        'WPCacheOn',
                        '<code>644</code>',
                        '<code>wp-content/cache/wpcacheon</code>',
                        WCO_MIN_WP
                    )
                )
            );
        }

        //Check .htaccess permision
        if (file_exists(ABSPATH . '.htaccess') && !is_writable(ABSPATH . '.htaccess')) {
            show_message(
                sprintf(
                    '<div class="error"><p>%s</p></div>',
                    sprintf(
                        __('The <b>%s</b> requires at least %s permissions on %s file to optimize your website. Please reactivate the plugin for complete optimization after permision fix!', 'wp-cache-on'),
                        'WPCacheOn',
                        '<code>644</code>',
                        '<code>.htaccess</code>',
                        WCO_MIN_WP
                    )
                )
            );
        }

        // autoptimize minification check
        if (defined('AUTOPTIMIZE_PLUGIN_DIR') && $options['minify'] && get_option('autoptimize_html', '') != '') {
            show_message(
                sprintf(
                    '<div class="error"><p>%s</p></div>',
                    sprintf(
                        __('The <b>%s</b> plugin is active. Please disable minification in the <b>%s</b> settings.', 'wp-cache-on'),
                        'Autoptimize',
                        'WPCacheOn'
                    )
                )
            );
        }
    }

    /**
     * Notificaiton message that will stand on top
     */
    public static function marketing_notification()
    {
        // show_message();
    }

    //Custom update message under plugin row
    public function prefix_plugin_update_message($data, $response)
    {
        if (isset($data['upgrade_notice'])) {
            printf(
                '<div class="update-message">%s</div>',
                wpautop($data['upgrade_notice'])
            );
        }
    }

    // Notice to rate plugin
    public function rate_notice()
    {
        $options = WP_Cache_On::get_options();

        if (!empty($options['activated_on'])) {
            $ignore_rating = empty($options['skip_rating']) ? "" : $options['skip_rating'];

            if ("yes" != $ignore_rating) {
                $instaledDate = $options['activated_on'];
                $dateNow      = date("Y/m/d");
                $diff         = abs(strtotime($dateNow) - strtotime($instaledDate));
                $days         = floor($diff / (60 * 60 * 24));

                if ($days >= 7) {
                    $skipRatingURL = $_SERVER['REQUEST_URI'];
                    $skipRatingURL = add_query_arg('wco-skip-rating', '1', $skipRatingURL);

                    echo '<div class="updated"><p>';
                    printf(__('You are awesome! It seems you have been using <a href="options-general.php?page=wp-cache-on">WPCacheOn</a> for more than 7 days.  Would you mind taking a seconds to give it a 5-star rating on WordPress? We thank you in advance :) <a href="%2$s" target="_blank">Ok, you earn it</a> | <a href="%1$s">Already done</a> | <a href="%1$s">Sorry, not good enough</a>', 'wp-cache-on'), $skipRatingURL,
                        'https://wordpress.org/support/plugin/wpcacheon/reviews/?filter=5#new-post');
                    echo "</p></div>";
                }
            }
        }
    }

    public function wco_skip_rating()
    {
        if (isset($_GET['wco-skip-rating']) && "1" == $_GET['wco-skip-rating']) {
            $settings                = WP_Cache_On::get_options();
            $settings['skip_rating'] = "yes";

            //Remove sanitizing for adding
            remove_filter("sanitize_option_wp-cache-on", array(__CLASS__, 'validate_settings'));
            update_option('wp-cache-on', $settings);
        }
    }
}
