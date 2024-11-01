<?php
namespace WP_Cache_On\Admin;

use WP_Cache_On\WP_Cache_On;
use WP_Cache_On\WP_Cache_On_Disk;

if (!defined('WCO_ADMIN_DIR')) {
    define('WCO_ADMIN_DIR', plugin_dir_path(__FILE__) . 'admin/');
}
class WP_Cache_On_Admin_Display
{
    public static function settings_page()
    {
        $wp_config_path            = ABSPATH . 'wp-config.php';
        $wp_cache_constant_defined = false;

        // Check if wp-config.php exists and is readable
        if (!file_exists($wp_config_path) || !is_readable($wp_config_path)) {
            echo '<div class="notice notice-error"><p>' . __('wp-config.php does not exist or is not readable. Please check your server configuration.', 'wp-cache-on') . '</p></div>';
            return;
        }

        // Check if wp-config.php is writable
        if (!is_writable($wp_config_path)) {
            echo '<div class="notice notice-error"><p>' . __('wp-config.php is not writable. Please update permissions.', 'wp-cache-on') . '</p></div>';
            return;
        }

        // Read the wp-config.php content
        $config_content = file_get_contents($wp_config_path);

        if (false === $config_content) {
            echo '<div class="notice notice-error"><p>' . __('Failed to read wp-config.php content.', 'wp-cache-on') . '</p></div>';
            return;
        }

        // Check if 'WP_CACHE' is defined in wp-config.php
        if (strpos($config_content, "define('WP_CACHE'") !== false) {
            // Check if it's set to true
            if (strpos($config_content, "define('WP_CACHE', true);") !== false) {
                $wp_cache_constant_defined = true;
            }
        }

        // If WP_CACHE is not defined or is not set to true
        if (!$wp_cache_constant_defined) {
            // Define the line to add
            $wp_cache_line = "define('WP_CACHE', true);";

            // Check for existing 'define('WP_CACHE', false)' and replace it
            if (strpos($config_content, "define('WP_CACHE', false);") !== false) {
                $config_content = str_replace("define('WP_CACHE', false);", $wp_cache_line, $config_content);
            } else {
                // Append WP_CACHE line just before 'That's all, stop editing! Happy publishing.'
                if (strpos($config_content, "/* That's all, stop editing!") !== false) {
                    $config_content = preg_replace("/(\/\*\s+That's all, stop editing!\s+Happy publishing.\s+\*\/)/", "$wp_cache_line\n\n$1", $config_content);
                } else {
                    // If the marker is not found, append at the end as a fallback
                    $config_content .= "\n\n$wp_cache_line\n";
                }
            }

            // Attempt to write the changes back to wp-config.php
            $result = file_put_contents($wp_config_path, $config_content);

            if (false === $result) {
                echo '<div class="notice notice-error"><p>' . __('Failed to write to wp-config.php. Please check your server settings.', 'wp-cache-on') . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>' . __('WP_CACHE has been enabled in wp-config.php.', 'wp-cache-on') . '</p></div>';
            }
        }

        $options     = WP_Cache_On::get_options();
        $size        = WP_Cache_On_Disk::get_cache_size();
        $lastVersion = self::getPluginVersionFromRepository('wpcacheon');

        // Start the main wrapper div
        echo '<div class="wrap">'; // Add this line

        ?>

        <div>
            <table width="100%" cellspacing="0">
                <tr style="background:#fff;border:0px solid #eee;">
                    <td style="padding:10px 10px 10px 20px">
                        <img src="<?php echo plugins_url('images/logo.png', WCO_ADMIN_DIR); ?>" alt="Plugin Logo"/>
                    </td>

                    <td width="100%">
                        <div style="background:#fff;padding:10px;margin-bottom:10px;">
                            <div style="font-size: 20px;font-weight: 400;margin-bottom:10px">
                                <?php _e("WPCacheOn", "wp-cache-on") . " " . WCO_VERSION?>
                                <?php if (WCO_VERSION != $lastVersion) {?>
                                    <a href="<?php echo admin_url('update-core.php'); ?>"><span style="font-size:small;">(Update available)</span></a>
                                <?php }?>
                            </div>
                            <div style="border-top:1px dashed #eee;padding-top:4px">
                                <span class="row-text"><?php _e('By', 'wp-cache-on');?></span>
                                <a class="sidebar-link" href="https://profiles.wordpress.org/jeffreycooper/" target="_blank">Jeffrey Cooper</a>
                                &nbsp;|&nbsp;
                                <span class="row-text"><?php _e('Need help?', 'wp-cache-on');?></span>
                                <a class="sidebar-link" href="https://wpcacheon.io/contact/" target="_blank"><?php _e('Contact us!', 'wp-cache-on');?></a>
                            </div>
                        </div>
                    </td>

                    <td style="text-align:center">
                        <div style="background:#fff;padding:10px;margin-bottom:10px;">
                            <a class="sidebar-link" href="https://wpcacheon.io/contact/" target="_blank">
                                <img style="width:50px" src="<?php echo plugins_url('images/help.svg', WCO_ADMIN_DIR); ?>" alt="Help" />
                                <br/>
                                <span><?php _e('Support', 'wp-cache-on');?></span>
                            </a>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <h1 style="font-size:10px"></h1>

        <div class="margin-r-300">
            <div class="tab-box">
                <?php
$tabs = array(
            'settings' => array("name" => __('Settings', 'wp-cache-on'), "css" => 'dashicons-admin-settings dashicons'),
        );

        $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
        echo '<div class="tab-box-div">';

        switch ($current_tab) {
            case 'settings':
                $settings_file_path = WCO_DIR . 'admin/partials/wco-settings.php';

                if (file_exists($settings_file_path)) {
                    include_once $settings_file_path;
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Settings file not found.', 'wp-cache-on') . '</p></div>';
                }
                break;
        }
        echo '</div>';
        ?>
            </div>

            <div class="sidebar">
                <div style="margin-top:-41px">
                    <div id="upgrade">
                        <img src="<?php echo plugins_url('images/logo.png', WCO_ADMIN_DIR); ?>" alt="Plugin Logo"/>
                        <p>
                            <?php echo sprintf(
            __('If you love this plugin and would like support us, you can like our %s page, follow us on %s or become a %s. You are awasome.', 'wp-cache-on'),
            '<a target="_blank" href="https://www.facebook.com/wpcacheon">Facebook</a>',
            '<a target="_blank" href="https://twitter.com/wpcacheon">Twitter</a>',
            '<a target="_blank" href="https://www.patreon.com/wpcacheon">Patreon</a>'
        ); ?>
                            <br/>
                            <span style="float:right">
                                <?php _e('Thank you!', 'wp-cache-on');?>
                            </span>
                            <br/>
                        </p>

                        <center style="padding-bottom:20px">
                            <a href="https://www.patreon.com/bePatron?u=8652264" data-patreon-widget-type="become-patron-button">Become a member!</a><script async src="https://c6.patreon.com/becomePatronButton.bundle.js"></script>
                        </center>

                        <div style="text-align:center;border-top:1px dashed #ccc;color:#999"></div>
                        <p>
                            <a href="https://wordpress.org/support/plugin/wpcacheon/reviews/?filter=5#new-post" target="_blank" class="rate-us"><?php _e('Please rate us', 'wp-cache-on');?>  <img style="float:right" src="<?php echo plugins_url('images/rate.png', WCO_ADMIN_DIR); ?>" alt="Rate Us"/> <!--- <img style="float:right" src="<?php echo WCO_ADMIN_DIR; ?>images/rate.png"/> ---> </a>
                        </p>
                    </div>
                </div>
            </div>

        </div>
        </div> <!-- Close the wrap div here --> <!-- Add this line -->

        <?php
}

    public static function getPluginVersionFromRepository($slug)
    {
        $url      = "https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slugs][]={$slug}";
        $response = wp_remote_get($url);
        $plugins  = json_decode($response['body']);

        foreach ($plugins as $key => $plugin) {
            $version = $plugin->version;
        }
        return $version;
    }
}