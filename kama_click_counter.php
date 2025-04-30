<?php
/**
 * Plugin Name: Kama Click Counter
 * Description: Counts clicks on any link across the entire site. Creates a beautiful file download block in post content using the shortcode `[download url="any file URL"]`. Includes a widget for top clicks/downloads.
 *
 * Text Domain: kama-clic-counter
 * Domain Path: /languages
 *
 * Author: Kama
 * Author URI: https://wp-kama.com
 * Plugin URI: https://wp-kama.com/77
 *
 * Requires PHP: 7.1
 * Requires at least: 5.7
 *
 * Version: 4.0.3
 */

namespace KamaClickCounter;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/admin/admin-functions.php';

register_activation_hook( __FILE__, [ plugin(), 'activation' ] );

/**
 * NOTE: Init the plugin later on the 'after_setup_theme' hook to
 * run current_user_can() later to avoid possible conflicts.
 */
add_action( 'after_setup_theme', [ plugin(), 'init' ] );

function plugin(): Plugin {
	static $instance;
	$instance || $instance = new Plugin( __FILE__ );

	return $instance;
}


