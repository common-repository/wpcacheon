<?php
namespace WP_Cache_On;

// Include the functions file
include_once plugin_dir_path(__FILE__) . 'wco-functions.php';

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      2.0.0
 * @package    WP_Cache_On
 * @subpackage WP_Cache_On/includes
 * @author     Jeffrey Cooper <1987coopjeff@gmail.com>
 */
class WP_Cache_On_Deactivator
{
    public static function deactivate()
    {
        wco_error_log('Starting deactivation process.');

        WP_Cache_On_General::htaccess_action(false);
        self::remove_wp_cache_define();
        self::remove_advanced_cache_file();
        self::remove_cache_folder();

        wco_error_log('Deactivation process completed.');
    }

    /**
     * Remove define line from wp-config.php
     */
    private static function remove_wp_cache_define()
    {
        $wp_config_file = ABSPATH . 'wp-config.php';

        if (file_exists($wp_config_file)) {
            $wp_config_content = file_get_contents($wp_config_file);
            $define_line       = "define('WP_CACHE', true); // Added by WPCacheOn";

            // Remove the define line
            $updated_content = str_replace($define_line . PHP_EOL, '', $wp_config_content);

            if ($updated_content !== $wp_config_content) {
                if (file_put_contents($wp_config_file, $updated_content)) {
                    wco_error_log('Successfully removed WP_CACHE define from wp-config.php.');
                } else {
                    wco_error_log('Failed to update wp-config.php.');
                }
            } else {
                wco_error_log('WP_CACHE define not found in wp-config.php.');
            }
        } else {
            wco_error_log('wp-config.php file not found.');
        }
    }

    /**
     * Remove wp-content/advanced-cache.php
     */
    private static function remove_advanced_cache_file()
    {
        $advanced_cache_file = WP_CONTENT_DIR . '/advanced-cache.php';

        if (file_exists($advanced_cache_file)) {
            if (unlink($advanced_cache_file)) {
                wco_error_log('Successfully removed advanced-cache.php.');
            } else {
                wco_error_log('Failed to remove advanced-cache.php.');
            }
        } else {
            wco_error_log('advanced-cache.php file not found.');
        }
    }

    /**
     * Function to recursively remove wp-content/cache/wp-cache-on/ folder
     */
    private static function remove_cache_folder()
    {
        if (is_dir(WCO_CACHE_DIR)) {
            self::remove_directory(WCO_CACHE_DIR);

            if (!is_dir(WCO_CACHE_DIR)) {
                wco_error_log('Successfully removed wp-cache-on folder.');
            } else {
                wco_error_log('Failed to remove wp-cache-on folder.');
            }
        } else {
            wco_error_log('wp-cache-on folder not found.');
        }
    }

    /**
     * Helper function to recursively delete a directory and its contents
     */
    private static function remove_directory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);

        foreach ($files as $file) {
            if ('.' !== $file && '..' !== $file) {
                $file_path = $dir . '/' . $file;
                if (is_dir($file_path)) {
                    self::remove_directory($file_path);
                } else {
                    unlink($file_path);
                }
            }
        }
        rmdir($dir); // Finally remove the directory itself
    }

    private static function clear_cache_in_chunks()
    {
        wco_error_log('Starting clear_cache_in_chunks.');

        global $wpdb;
        $limit          = 1000;
        $offset         = 0;
        $iterations     = 0;
        $max_iterations = 50;

        do {
            $cache_keys = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s LIMIT %d, %d", '%_transient_%', $limit, $offset));
            if (!empty($cache_keys)) {
                foreach ($cache_keys as $cache_key) {
                    wp_cache_delete($cache_key);
                }
            }
            $offset += $limit;
            $iterations++;
            if ($iterations >= $max_iterations) {
                wco_error_log('Maximum iterations reached, stopping loop to prevent infinite execution.');
                break;
            }
        } while (count($cache_keys) > 0);

        self::clear_total_cache();
    }

    public static function clear_total_cache()
    {
        wco_error_log('Starting clear_total_cache.');

        WP_Cache_On_Disk::_clear_all_cache();
        delete_transient('cache_size');

        wp_cache_flush();
        gc_collect_cycles();
    }

    private static function cleanup_memory()
    {
        global $wpdb;
        wco_error_log('Starting memory cleanup.');

        // Close any open database connections and cleanup memory
        if (isset($wpdb)) {
            $wpdb->flush();
        }

        // Unset global objects and force garbage collection
        unset($wpdb);
        gc_collect_cycles(); // Forces garbage collection to clear any remaining memory
    }

    /**
     * Uninstall per multisite blog
     */
    public static function on_uninstall()
    {
        global $wpdb;
        wco_error_log('Starting on_uninstall.');

        if (is_multisite() && !empty($_GET['networkwide'])) {
            $old = $wpdb->blogid; // legacy blog
            $ids = WP_Cache_On_General::_get_blog_ids(); // blog id

            // Uninstall per blog
            foreach (array_chunk($ids, 10) as $id_chunk) {
                foreach ($id_chunk as $id) {
                    switch_to_blog($id);
                    self::_uninstall_backend();
                }
                switch_to_blog($old); // Restore
                wp_cache_flush(); // Free up memory after processing each chunk
            }
        } else {
            self::_uninstall_backend();
        }
        // Additional cleanup after uninstall
        self::cleanup_memory();
    }

    /**
     * Uninstall
     */
    private static function _uninstall_backend()
    {
        wco_error_log('Starting _uninstall_backend.');

        // Clear options and total cache
        delete_option('wp-cache-on');
        self::clear_cache_in_chunks();
    }
}
