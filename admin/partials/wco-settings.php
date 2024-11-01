<?php
namespace WP_Cache_On\Admin;

use WP_Cache_On\WP_Cache_On;
use WP_Cache_On\WP_Cache_On_Disk;

global $wpdb, $wp_version;

$options      = WP_Cache_On::get_options();
$size         = WP_Cache_On_Disk::get_cache_size();
$minifySelect = WP_Cache_On::minify_select();

// Initialize variables
$page_count = 0;
$post_count = 0;

$cached_page_count = 0;
$cached_post_count = 0;

$page_slugs   = [];
$page_slugs[] = 'wco-homepage';

// The front page is set to display "A static page"
$homepage_mode = get_option('show_on_front');

$post_slugs = [];

// Determine the actual domain for constructing the cache path
$domain = parse_url(get_home_url(), PHP_URL_HOST);

// Construct the cache directory path dynamically
$cache_directory = WCO_CACHE_DIR . $domain . '/';

// DB size
$db_size = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(ROUND(((data_length + index_length) / 1024), 2)) FROM information_schema.tables WHERE table_schema = %s",
    DB_NAME
));
// Check if the 'compression' option exists, if not set a default value (like 0 or 1 depending on your needs)
$compression = isset($options['compression']) ? $options['compression'] : 0; // Default to 0 (unchecked) if not set

// Fetch number of pages, posts, and comments
$page_count = wp_count_posts('page')->publish;
$post_count = wp_count_posts('post')->publish;

// Fetch all slugs for pages, posts, and comments
$page_objects = get_posts(array('post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids'));
$post_objects = get_posts(array('post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids'));

// Extract slugs directly from objects
foreach ($page_objects as $page_id) {
    $page_slugs[] = get_post_field('post_name', $page_id);
}

foreach ($post_objects as $post_id) {
    $post_slugs[] = get_post_field('post_name', $post_id);
}

/**
 * Count cached pages and posts
 */
if (is_dir($cache_directory)) {
    $directory_iterator = new \RecursiveDirectoryIterator($cache_directory, \RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator           = new \RecursiveIteratorIterator($directory_iterator, \RecursiveIteratorIterator::SELF_FIRST);

    foreach ($iterator as $file) {
        if ($file->isDir()) {
            $slug = basename($file);

            // Check if this directory corresponds to a page slug
            if (in_array($slug, $page_slugs)) {
                $subdir_files = glob($file->getPathname() . '/wco*');
                if (!empty($subdir_files)) {
                    $cached_page_count++;
                }
            }

            // Check if this directory corresponds to a post slug
            if (in_array($slug, $post_slugs)) {
                $subdir_files = glob($file->getPathname() . '/wco*');
                if (!empty($subdir_files)) {
                    $cached_post_count++;
                }
            }
        }

    }
}

if ($db_size >= 1024) {
    $db_size = round(($db_size / 1024), 2) . " MB";
} else {
    $db_size = round($db_size, 2) . " KB";
}
?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#trigger_precache_button').on('click', function(e) {
        e.preventDefault();

        // Disable the button to prevent multiple clicks
        $(this).prop('disabled', true);

        // Show a loading indicator or message
        $('#precaching_status').text('Precaching in progress...');

        // Make the AJAX request
        $.ajax({
            url: ajaxurl, // WordPress global variable for AJAX URL
            type: 'POST',
            data: {
                action: 'trigger_precache',
                security: '<?php echo wp_create_nonce("wp_cache_on_precache_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#precaching_status').text('PreCache completed successfully!');
                } else {
                    $('#precaching_status').text('PreCache failed. Please try again.');
                }
                // Re-enable the button
                $('#trigger_precache_button').prop('disabled', false);
            },
            error: function() {
                $('#precaching_status').text('An error occurred. Please try again.');
                // Re-enable the button
                $('#trigger_precache_button').prop('disabled', false);
            }
        });
    });
});
</script>

<div class="content-max-width">
	<div class="overview-box-top">
		<div class="overview-box-head"><?php _e('Overview', 'wp-cache-on');?></div>
		<ul class="overview-box-line fixed">
			<li>
				<div class="overview-text-left"><?php _e('WP Version', 'wp-cache-on');?>:&nbsp;</div>
				<div class="overview-text-right"><?php echo $wp_version; ?></div>
			</li>
			<li>
				<div class="overview-text-left"><?php _e('Database size', 'wp-cache-on');?>:&nbsp;</div>
				<div class="overview-text-right"><?php echo $db_size; ?></div>
			</li>
			<li>
				<div class="overview-text-left"><?php _e('PHP version', 'wp-cache-on');?>:&nbsp;</div>
				<div class="overview-text-right"><?php echo phpversion(); ?> <a href="https://wpcacheon.io/why-you-should-use-the-latest-php-version-for-your-wordpress-website/" title="See why you always should use the latest PHP version" target="_blank">(Always use latest PHP)</a></div>
			</li>
			<li>
				<div class="overview-text-left"><?php _e("Current cache size", "wp-cache-on");?>:&nbsp;</div>
				<div class="overview-text-right"><?php echo (empty($size) ? _e("Empty", "wp-cache-on") : size_format($size)); ?></div>
			</li>

            <!-- New Fields: Pages, Posts, Comments Count -->
            <li style="clear: both;">
                <div class="overview-text-left"><?php _e('Cached Pages', 'wp-cache-on');?>:&nbsp;</div>
                <div class="overview-text-right"><?php echo $cached_page_count . '/' . $page_count; ?></div>
            </li>
            <li style="clear: both;">
                <div class="overview-text-left"><?php _e('Cached Posts', 'wp-cache-on');?>:&nbsp;</div>
                <div class="overview-text-right"><?php echo $cached_post_count . '/' . $post_count; ?></div>
            </li>
			<li>
				<snap class="overview-text">
					<?php _e("By default the plugin is configured to it's cutting edge, so it could improve your website speed without the need of additional configuration. If you would like to perform changes over the plugin cache settings, you can perform them on this page.", 'wp-cache-on');?>
				</snap>
			</li>
		</ul>
	</div>

	<div class="overview-box-bottom">
		<div class="overview-box-head"><?php _e('Settings', 'wp-cache-on');?></div>

		<form action="options.php" method="POST">
			<?php settings_fields('wp-cache-on')?>
			<ul class="settings-box-line ">

				<li>
					<?php _e("Cache Expiration:", "wp-cache-on");?>
					<input type="text" class="custom-medium" name="wp-cache-on[expires]" value="<?php echo esc_attr($options['expires']); ?>" style="float:right"/>

					<div class="overview-setting-desc">
						<?php _e("Set for how many hours the cache will be used before removed and new cache generated. If you would not like the cache to expire set the value to '0'.", "wp-cache-on");?>
					</div>
				</li>

				<li>
					<?php _e("Turn off caching for:", "wp-cache-on")?>
					<input type="text" class="custom-medium" name="wp-cache-on[excl_ids]" value="<?php echo esc_attr($options['excl_ids']) ?>" style="float:right"/>

					<div class="overview-setting-desc">
						<?php _e("List the ID of a page or post separated by a <code>,</code> that you would like to be not cached.", "wp-cache-on");?>
					</div>
				</li>

				<li>
					<?php _e("Cache Minification", "wp-cache-on")?>

					<select name="wp-cache-on[minify]"  class="sel-minification" >
						<?php foreach ($minifySelect as $k => $v) {?>
							<option value="<?php echo esc_attr($k) ?>" <?php selected($options['minify'], $k);?>>
								<?php echo esc_html($v) ?>
							</option>
						<?php }?>
					</select>

					<div class="overview-setting-desc">
						<?php _e("The minification options are: Disabled, to disable minification. HTML to minify only the HTML content. HTML & Inline JS will minify everything.", "wp-cache-on");?>
					</div>
				</li>

				<!-- Added Enable Compression option -->
                <li>
                    <?php _e("Enable Compression", "wp-cache-on")?>
                    <input type="checkbox" class="custom-medium" name="wp-cache-on[compression]" value="1" <?php checked($options['compression'], 1);?> style="float:right"/>
                    <div class="overview-setting-desc">
                        <?php _e("Check this box to enable GZIP compression for the cached files, reducing their size and speeding up loading times.", "wp-cache-on");?>
                    </div>
                </li>

		        <!-- New Field: Activate PreCache -->
		        <li>
                    <?php _e("PreCache", "wp-cache-on")?>
                    <button type="button" id="trigger_precache_button" class="button button-primary" style="float:right"><?php _e("Trigger", "wp-cache-on");?></button>
		              <p id="precaching_status"></p>
			          <div class="overview-setting-desc">
                      <strong><?php _e("Notice:", "wp-cache-on");?></strong> <?php _e("Activating PreCache may be time-consuming and consume more than usual computing resources, depending on the number of pages on your website.", "wp-cache-on");?>
                    </div>
                </li>
		    </ul>
			<?php submit_button()?>
		</form>
	</div>
	<div class="clear-both"></div>
</div>