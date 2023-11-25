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
 * Requires at least: 3.6
 *
 * Version: 3.6.10
 */

namespace KamaClickCounter;

defined( 'ABSPATH' ) || exit;

define( 'KCC_FILE', __FILE__ );
define( 'KCC_VER', get_file_data( __FILE__, [ 'ver' => 'Version' ] )['ver'] );
define( 'KCC_PATH', plugin_dir_path( __FILE__ ) );
define( 'KCC_URL', plugin_dir_url( __FILE__ ) );
define( 'KCC_NAME', basename( KCC_PATH ) );

require_once KCC_PATH . 'autoload.php';
require_once KCC_PATH . 'src/legacy/backcompat.php';


register_activation_hook( __FILE__, [ KCCounter(), 'activation' ] );

add_action( 'plugins_loaded', [ KCCounter(), 'init' ] );

function KCCounter(): KCC_Counter {
	return KCC_Counter::instance();
}


