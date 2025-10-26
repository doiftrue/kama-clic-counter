<?php

namespace KamaClickCounter;

if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ){
	exit;
}

require_once __DIR__ . '/autoload.php';

if( is_multisite() ){
	$site_ids = get_sites( [ 'fields' => 'ids' ] );
	foreach( $site_ids as $site_id ){
		switch_to_blog( (int) $site_id );
		try{
			do_the_uninstall();
		}
		finally{
			restore_current_blog();
		}
	}
}
else{
	do_the_uninstall();
}

function do_the_uninstall(): void {
	global $wpdb;

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kcc_clicks" );
	delete_option( 'widget_kcc_widget' );
	delete_option( Options::OPTION_NAME );
	delete_option( Upgrader::OPTION_NAME );
	delete_option( Month_Clicks_Updater::OPTION_NAME );
}
