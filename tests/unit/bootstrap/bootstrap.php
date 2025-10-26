<?php

define( 'WP_ROOT_DIR', dirname( __DIR__, 6 ) );
define( 'WP_ROOT_URL', 'http://kcc.loc' );

define( 'THIS_PLUG_DIR', dirname( __DIR__, 3 ) );
define( 'THIS_PLUG_URL', str_replace( WP_ROOT_DIR, WP_ROOT_URL, THIS_PLUG_DIR ) );

// load

//const ABSPATH         = WP_ROOT_DIR . '/core/';
//const WP_CONTENT_DIR  = WP_ROOT_DIR . '/wp-content';
//const WP_CONTENT_URL  = WP_ROOT_URL . '/wp-content';
$GLOBALS['stub_wp_options'] = [
	'home' => WP_ROOT_URL,
];
require_once THIS_PLUG_DIR . '/vendor/doiftrue/unitest-wp-copy/zero.php';
require_once THIS_PLUG_DIR . '/autoload.php';

// init bootstrap

WP_Mock::bootstrap();
require_once __DIR__ . '/KCCTestCase.php';



