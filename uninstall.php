<?php
namespace WP_Cache_On;

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @link       https://wpcacheon.io
 * @since      2.0.0
 *
 * @package    WP_Cache_On
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

define('WCO_CACHE_DIR', WP_CONTENT_DIR . '/cache/wpcacheon');

require_once sprintf(dirname(__FILE__) . '/includes/wco-deactivator.php');
require_once sprintf(dirname(__FILE__) . '/includes/wco-disk.php');
require_once sprintf(dirname(__FILE__) . '/includes/wco-general.php');

WP_Cache_On_Deactivator::on_uninstall();
