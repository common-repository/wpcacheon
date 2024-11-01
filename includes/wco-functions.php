<?php
/**
 *
 * This function defines all code necessary to run during
 *
 * @since      2.0.0
 * @package    WP_Cache_On
 * @subpackage WP_Cache_On/includes
 * @author     Jeffrey Cooper <1987coopjeff@gmail.com>
 */

// Define pluggin debug log file
if (!defined('WP_CACHE_ON_LOG_FILE')) {
    define('WP_CACHE_ON_LOG_FILE', WP_CONTENT_DIR . '/plugins/wpcacheon/debug.log');
}

/**
 * Check if the WP_CACHE_ON_LOG_FILE constant is defined
 */
if (defined('WP_CACHE_ON_LOG_FILE')) {
    // Check if the file exists
    if (!file_exists(WP_CACHE_ON_LOG_FILE)) {
        // Create the file with the proper permissions
        if (file_put_contents(WP_CACHE_ON_LOG_FILE, '') !== false) {
            // Set the file permissions to be writable (optional, but recommended)
            chmod(WP_CACHE_ON_LOG_FILE, 0664); // Read and write permissions for owner and group, read-only for others
        } else {
            error_log('[WCO] Failed to create log file: ' . WP_CACHE_ON_LOG_FILE);
        }
    } else {
        // Check if the file is writable
        if (!is_writable(WP_CACHE_ON_LOG_FILE)) {
            error_log('[WCO] Log file exists but is not writable: ' . WP_CACHE_ON_LOG_FILE);
        }
    }
} else {
    error_log('[WCO] Log file path is not defined.');
}

/**
 * Custom error log function for the plugin purposes
 */
if (!function_exists('wco_error_log')) {
    function wco_error_log($message)
    {
        // Define the maximum number of lines in the log file
        $max_lines = 1000;

        // Define the backup file name
        $backup_file = WP_CACHE_ON_LOG_FILE . '.bak';

        // Get the current UTC timestamp
        $timestamp = gmdate('Y-m-d H:i:s');

        // Format the message with the UTC timestamp
        $formatted_message = "[$timestamp] " . $message . PHP_EOL;

        // Check if the log file is defined and writable
        if (defined('WP_CACHE_ON_LOG_FILE') && is_writable(WP_CACHE_ON_LOG_FILE)) {
            // Check if the log file exists
            if (file_exists(WP_CACHE_ON_LOG_FILE)) {
                // Get the current lines of the file
                $lines = file(WP_CACHE_ON_LOG_FILE, FILE_IGNORE_NEW_LINES);

                // Check if the number of lines exceeds the maximum allowed
                if (count($lines) >= $max_lines) {
                    // If a backup file exists, delete it
                    if (file_exists($backup_file)) {
                        unlink($backup_file);
                    }

                    // Create a backup of the current log file
                    rename(WP_CACHE_ON_LOG_FILE, $backup_file);

                    // Create a new log file with the new message
                    file_put_contents(WP_CACHE_ON_LOG_FILE, $formatted_message);
                } else {
                    // Log the message using error_log() if the number of lines is less than or equal to 1000
                    error_log($formatted_message, 3, WP_CACHE_ON_LOG_FILE);
                }
            } else {
                // If the file doesn't exist, create it with the initial message using file_put_contents
                file_put_contents(WP_CACHE_ON_LOG_FILE, $formatted_message);
            }
        } else {
            // Fallback to the default PHP error log
            error_log('[WCO] ' . $formatted_message);
        }
    }
}
