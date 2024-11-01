<?php
/**
 * This file is used by WPCacheOn.
 *
 * With this file you will ensure maximum performance for
 * your website with WPCacheOn.
 *
 * To benefit from WPCacheOn ensure that this file exist.
 * If this file don't exist reactivate WPCacheOn.
 */
if (
    (!isset($_SERVER['REQUEST_METHOD']) || 'GET' != $_SERVER['REQUEST_METHOD'])
    || (!empty($_GET) && !isset($_GET['utm_source'], $_GET['utm_medium'], $_GET['utm_campaign']))
) {
    return false;
} elseif (!empty($_COOKIE)) {
    foreach ($_COOKIE as $key => $dymmy) {
        if (preg_match('/^(wp-postpass|wordpress_logged_in|comment_author)_/', $key)) {
            return false;
        }
    }
}

//Base Path
$path = sprintf(
    '%s%s%s%s',
    WP_CONTENT_DIR . '/cache/wpcacheon',
    DIRECTORY_SEPARATOR,
    parse_url(
        'http://' . strtolower($_SERVER['HTTP_HOST']),
        PHP_URL_HOST
    ),
    parse_url(
        $_SERVER['REQUEST_URI'],
        PHP_URL_PATH
    )
);

$path = rtrim($path, '/\\') . '/'; //Add trailing slash

//Path to cached variants
$path_html      = $path . 'wco.html';
$path_gzip      = $path . 'wco.html.gz';
$path_webp_html = $path . 'wco-webp.html';
$path_webp_gzip = $path . 'wco-webp.html.gz';

if (is_readable($path_html)) {
    //Set cache handler header
    header('x-cache-handler: Cached by WPCacheOn');

    //Get if-modified request headers
    if (function_exists('apache_request_headers')) {
        $headers                = apache_request_headers();
        $http_if_modified_since = (isset($headers['If-Modified-Since'])) ? $headers['If-Modified-Since'] : '';
        $http_accept            = (isset($headers['Accept'])) ? $headers['Accept'] : '';
        $http_accept_encoding   = (isset($headers['Accept-Encoding'])) ? $headers['Accept-Encoding'] : '';
    } else {
        $http_if_modified_since = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : '';
        $http_accept            = (isset($_SERVER['HTTP_ACCEPT'])) ? $_SERVER['HTTP_ACCEPT'] : '';
        $http_accept_encoding   = (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
    }

    //Check modified since with cached file and return 304 if no difference
    if ($http_if_modified_since && (strtotime($http_if_modified_since) == filemtime($path_html))) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified', true, 304);
        exit;
    }

    header('Last-Modified: ' . gmdate("D, d M Y H:i:s", filemtime($path_html)) . ' GMT');

    // Determine supported compression method
    $gzip    = strstr($http_accept_encoding, 'gzip');
    $deflate = strstr($http_accept_encoding, 'deflate');

    // Determine used compression method
    $encoding = $gzip ? 'gzip' : ($deflate ? 'deflate' : 'none');

    // Check for buggy versions of Internet Explorer
    if (
        !strstr($_SERVER['HTTP_USER_AGENT'], 'Opera')
        && preg_match('/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i', $_SERVER['HTTP_USER_AGENT'], $matches)
    ) {
        $version = floatval($matches[1]);
        if ($version < 6 || 6 == $version && !strstr($_SERVER['HTTP_USER_AGENT'], 'EV1')) {
            $encoding = 'none';
        }
    }

    //Some servers compress the output of PHP
    if (ini_get('output_handler') == 'ob_gzhandler' || ini_get('zlib.output_compression') == 1) {
        $encoding = 'none';
    }

    if ('none' != $encoding) {
        //Check webp and deliver gzip webp file if support
        if ($http_accept && (strpos($http_accept, 'webp') !== false)) {
            if (is_readable($path_webp_gzip)) {
                header('Content-Encoding: gzip');
                readfile($path_webp_gzip);
                exit;
            } elseif (is_readable($path_webp_html)) {
                readfile($path_webp_html);
                exit;
            }
        }

        //Check encoding and deliver gzip file if support
        if ($http_accept_encoding && (strpos($http_accept_encoding, 'gzip') !== false) && is_readable($http_accept_encoding)) {
            header('Content-Encoding: gzip');
            readfile($http_accept_encoding);
            exit;
        }
    } else {
        //Deliver cached file (default)
        readfile($path_html);
        exit;
    }

} else {
    return false;
}
