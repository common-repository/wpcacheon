<?php
namespace WP_Cache_On;

/**
 * Plugin Name
 *
 * @package           WP_Cache_On
 * @author            Jeffrey Cooper
 * @copyright         2020 Jeffrey Cooper
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       WPCacheOn
 * Plugin URI:        https://wordpress.org/plugins/wpcacheon/
 * Description:       Simple yet powerful cache plugin for WordPress. Install and activate, that simple, your website is already loading faster!
 * Version:           2.1.0
 * Requires at least: 4.6
 * Requires PHP:      5.4
 * Author:            Jeffrey Cooper
 * Author URI:        https://wpcacheon.io
 * Text Domain:       wpcacheon
 * Domain Path:       /languages/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Define pluggin base name
if (!defined('WCO_BASE')) {
    define('WCO_BASE', plugin_basename(__FILE__));
}

// Define current plugin version
if (!defined('WCO_VERSION')) {
    define('WCO_VERSION', '2.1.0');
}

// Define the minimum supported WordPress version
if (!defined('WCO_MIN_WP')) {
    define('WCO_MIN_WP', '5.0');
}

// Correctly define pluggin cache directory
if (!defined('WCO_CACHE_DIR')) {
    define('WCO_CACHE_DIR', WP_CONTENT_DIR . '/cache/wpcacheon/');
}

// Define the pluggin directory
if (!defined('WCO_DIR')) {
    define('WCO_DIR', plugin_dir_path(__FILE__));
}

// [Security] If this file is called directly or direct access, abort.
if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

// Correct file inclusions with absolute paths
include_once WCO_DIR . 'includes/wco-functions.php';
require_once WCO_DIR . 'includes/wco-general.php';
require_once WCO_DIR . 'admin/wco-admin.php';
require_once WCO_DIR . 'admin/wco-admin-display.php';

require_once WCO_DIR . 'includes/wco-activator.php';
require_once WCO_DIR . 'includes/wco-converter.php';
require_once WCO_DIR . 'includes/wco-deactivator.php';
require_once WCO_DIR . 'includes/wco-disk.php';
require_once WCO_DIR . 'includes/wco-i18n.php';
require_once WCO_DIR . 'includes/wco-loader.php';
require_once WCO_DIR . 'includes/wco.php';

require_once WCO_DIR . 'public/wco-public.php';

register_activation_hook(__FILE__, array('WP_Cache_On\WP_Cache_On_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('WP_Cache_On\WP_Cache_On_Deactivator', 'deactivate'));

// Main plugin class initialization
function run_wp_cache_on()
{
    $plugin = new WP_Cache_On(WCO_VERSION);
    $plugin->run();
}

run_wp_cache_on();
