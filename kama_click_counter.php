<?php
/**
 * Plugin Name: Kama Click Counter
 * Description: Count clicks on any link all over the site. Creates beautiful file download block in post content - use shortcode [download url="any file URL"]. Has widget of top clicks/downloads.
 *
 * Text Domain: kama-clic-counter
 * Domain Path: /languages
 *
 * Author: Kama
 * Author URI: https://wp-kama.com
 * Plugin URI: https://wp-kama.com/77
 *
 * Requires PHP: 7.0
 * Requires at least: 4.2
 *
 * Version: 4.0.0
 */

namespace KamaClickCounter;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/admin/admin-functions.php';

register_activation_hook( __FILE__, [ plugin(), 'activation' ] );

add_action( 'plugins_loaded', [ plugin(), 'init' ] );

function plugin(): Plugin {
	static $instance;
	$instance || $instance = new Plugin( __FILE__ );

	return $instance;
}


