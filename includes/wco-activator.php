<?php
namespace WP_Cache_On;

// Include the functions file
include_once plugin_dir_path(__FILE__) . 'wco-functions.php';

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.0.0
 * @package    WP_Cache_On
 * @subpackage WP_Cache_On/includes
 * @author     Jeffrey Cooper <1987coopjeff@gmail.com>
 */

class WP_Cache_On_Activator
{
    public static function activate()
    {
        wco_error_log('Activating plugin');

        // Properly clear old version of pluggin settings
        delete_option('wp-cache-on');

        if (is_multisite() && !empty($_GET['networkwide'])) {
            $ids = WP_Cache_On_General::_get_blog_ids(); //Blog ids

            //Switch to blog
            foreach ($ids as $id) {
                switch_to_blog($id);
                self::_install_backend();
            }

            restore_current_blog();
        } else {
            self::_install_backend();
        }

        WP_Cache_On_General::htaccess_action(true);
        WP_Cache_On_General::set_advance_cache_file();
    }

    /**
     * Installation options
     */
    private static function _install_backend()
    {
        add_option('wp-cache-on', array());

        WP_Cache_On_Disk::clear_total_cache(true);

        //Set right permision of files and folders if possible.
        // If can`t set it will show errors based on missing permision
        try {
            if (!file_exists(WCO_CACHE_DIR)) {
                mkdir(WCO_CACHE_DIR, 0775, true);
            }

            // Logic related to .htaccess
            if (!file_exists(WCO_CACHE_DIR . '/index.php')) {
                $createIndex = fopen(WCO_CACHE_DIR . "/index.php", "w");
                fwrite($createIndex, "<?php " . PHP_EOL . " echo 'Secure cache by WPCacheOn';");
                fclose($createIndex);
            }

            chmod(ABSPATH . '.htaccess', 0644);
        } catch (Exception $e) {
            wco_error_log('Caught exception: ' . $e->getMessage() . PHP_EOL);
        }
    }
}
