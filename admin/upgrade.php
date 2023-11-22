<?php

/**
 * to forse upgrade add '?kcc_force_upgrade' parameter to URL
 */

function kccount_upgrade_init(){

	$prev_ver = get_option('kcc_version');

	if( isset($_GET['kcc_force_upgrade']) )
		$prev_ver = '1.0';

	if( $prev_ver === KCC_VER ) return;

	update_option( 'kcc_version', KCC_VER );

	global $wpdb;

	// обнволение структуры таблиц
	//require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	//$doe = dbDelta( dem_get_db_schema() );
	//wp_die(print_r($doe));

	$fields = $wpdb->get_results( "SHOW COLUMNS FROM $wpdb->kcc_clicks" );

	if( ! $fields ) return;

	// название поля в индекс
	foreach( $fields as $k => $data ){
		$fields[ $data->Field ] = $data;
		unset($fields[ $k ]);
	}
	/*
	$fields = Array (
		[link_id] => stdClass Object (
			[Field] => link_id
			[Type] => bigint(20) unsigned
			[Null] => NO
			[Key] => PRI
			[Default] =>
			[Extra] => auto_increment
		)

		[link_url] => stdClass Object (
			[Field] => link_url
			[Type] => text
			[Null] => NO
			[Key] => MUL
			[Default] =>
			[Extra] =>
		)
		...
	*/

	//die( print_r($fields) );

	$charset_collate  = 'CHARACTER SET ' . ( (! empty( $wpdb->charset )) ? $wpdb->charset : 'utf8' );
	$charset_collate .= ' COLLATE ' . ( (! empty( $wpdb->collate )) ? $wpdb->collate : 'utf8_general_ci' );

	// 3.0
	if( ! isset($fields['last_click_date']) ){
		// $wpdb->query("UPDATE $wpdb->posts SET post_content=REPLACE(post_content, '[download=', '[download url=')");
		// обновим таблицу

		// добавим поле: дата последнего клика
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks ADD `last_click_date` DATE NOT NULL default '0000-00-00' AFTER link_date");
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks ADD `downloads` ENUM('','yes') NOT NULL default ''");
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks ADD INDEX  `downloads` (`downloads`)");

		// обновим существующие поля
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_date`  `link_date` DATE NOT NULL default  '0000-00-00'");
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_id`    `link_id`   BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT");
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `attach_id`  `attach_id` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0'");
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `in_post`    `in_post`   BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0'");
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_clicks`  `link_clicks` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0'");
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks DROP  `permissions`");
	}

	// 3.4.7
	if( $fields['link_url']->Type != 'text' ){
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_name`        `link_name`        VARCHAR(191) $charset_collate NOT NULL default ''");
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_title`       `link_title`       text         $charset_collate NOT NULL ");
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_url`         `link_url`         text         $charset_collate NOT NULL ");
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_description` `link_description` text         $charset_collate NOT NULL ");
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks CHANGE  `file_size`        `file_size`        VARCHAR(100) $charset_collate NOT NULL default ''");
	}

	if( $fields['link_url']->Key )
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks DROP INDEX link_url, ADD INDEX link_url (link_url(191))");
	else
		$wpdb->query("ALTER TABLE $wpdb->kcc_clicks ADD INDEX link_url (link_url(191))");

	// if less then 3.6.2
	if( version_compare( $prev_ver, '3.6.8.2', '<' ) ){
		// удалим протоколы у всех ссылок в БД
		$wpdb->query( "UPDATE $wpdb->kcc_clicks SET link_url = REPLACE(link_url, 'http://', '//')" );
		$wpdb->query( "UPDATE $wpdb->kcc_clicks SET link_url = REPLACE(link_url, 'https://', '//')" );
	}

	if( isset($_GET['kcc_force_upgrade']) ){
		wp_redirect( remove_query_arg('kcc_force_upgrade') );
		exit;
	}

}
