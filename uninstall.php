<?php
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ){
	exit;
}

global $wpdb;

$wpdb->query( "DROP TABLE {$wpdb->prefix}kcc_clicks" );
delete_option( 'kcc_options' );
delete_option( 'kcc_version' );
delete_option( 'widget_kcc_widget' );
