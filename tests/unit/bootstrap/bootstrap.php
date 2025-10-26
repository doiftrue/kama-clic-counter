<?php

define( 'WP_ROOT_DIR', dirname( __DIR__, 6 ) );
define( 'WP_ROOT_URL', 'http://kama-click-counter.loc' );

// WP constants
//const ABSPATH         = WP_ROOT_DIR . '/core/';
//const WP_CONTENT_DIR  = WP_ROOT_DIR . '/wp-content';
//const WP_CONTENT_URL  = WP_ROOT_URL . '/wp-content';
//const WP_PLUGIN_DIR   = WP_CONTENT_DIR . '/mu-plugins';
//const WPMU_PLUGIN_DIR = WP_CONTENT_DIR . '/plugins';

define( 'THIS_PLUG_DIR', dirname( __DIR__, 3 ) );
define( 'THIS_PLUG_URL', str_replace( WP_ROOT_DIR, WP_ROOT_URL, THIS_PLUG_DIR ) );

// load

require_once dirname( WP_ROOT_DIR ) . '/vendor/doiftrue/unitest-wp-copy/zero.php';
require_once THIS_PLUG_DIR . '/autoload.php';

// init bootstrap

WP_Mock::bootstrap();
require_once __DIR__ . '/KCCTestCase.php';



