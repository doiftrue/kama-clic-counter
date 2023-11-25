<?php
/**
 * To forse upgrade add '?kcc_force_upgrade' parameter to URL
 */

namespace KamaClickCounter;

class Upgrader {

	const OPTION_NAME = 'kcc_version';

	/** @var string */
	private $prev_ver;

	/** @var string */
	private $curr_ver;

	/** @var bool */
	private $is_force_upgrade;

	/** @var object[] */
	private $db_fields;

	public function __construct(){
	    $this->is_force_upgrade = isset( $_GET['kcc_force_upgrade'] );

	    $this->prev_ver = $this->is_force_upgrade ? '1.0' : get_option( self::OPTION_NAME, '1.0' );
	    $this->curr_ver = plugin()->info['version'];
	}

	public function init() {

		if( $this->prev_ver === $this->curr_ver ){
			return;
		}

		update_option( self::OPTION_NAME, $this->curr_ver );

		$this->set_db_fields();
		if( ! $this->db_fields ){
			return;
		}

		//$this->v3_0();
		//$this->v3_4_7();
		//$this->v3_6_2();

		if( $this->is_force_upgrade ){
			wp_redirect( remove_query_arg( 'kcc_force_upgrade' ) );
			exit;
		}

	}

	private function set_db_fields() {
		global $wpdb;

		$this->db_fields = $wpdb->get_results( "SHOW COLUMNS FROM $wpdb->kcc_clicks" );

		// field name to index
		foreach( $this->db_fields as $k => $data ){
			$this->db_fields[ $data->Field ] = $data;
			unset( $this->db_fields[ $k ] );
		}

		/*
		$this->db_fields = Array (
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
	}

	private function v3_0() {
		global $wpdb;

		if( ! isset( $this->db_fields['last_click_date'] ) ){
			// $wpdb->query("UPDATE $wpdb->posts SET post_content=REPLACE(post_content, '[download=', '[download url=')");
			// обновим таблицу

			// добавим поле: дата последнего клика
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks ADD `last_click_date` DATE NOT NULL default '0000-00-00' AFTER link_date" );
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks ADD `downloads` ENUM('','yes') NOT NULL default ''" );
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks ADD INDEX  `downloads` (`downloads`)" );

			// обновим существующие поля
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_date`  `link_date` DATE NOT NULL default  '0000-00-00'" );
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_id`    `link_id`   BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT" );
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks CHANGE  `attach_id`  `attach_id` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0'" );
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks CHANGE  `in_post`    `in_post`   BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0'" );
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_clicks`  `link_clicks` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0'" );
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks DROP  `permissions`" );
		}
	}

	private function v3_4_7() {
		global $wpdb;

		$charset_collate = 'CHARACTER SET ' . ( ( ! empty( $wpdb->charset ) ) ? $wpdb->charset : 'utf8' );
		$charset_collate .= ' COLLATE ' . ( ( ! empty( $wpdb->collate ) ) ? $wpdb->collate : 'utf8_general_ci' );

		if( 'text' !== $this->db_fields['link_url']->Type ){
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_name`        `link_name`        VARCHAR(191) $charset_collate NOT NULL default ''" );
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_title`       `link_title`       text         $charset_collate NOT NULL " );
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_url`         `link_url`         text         $charset_collate NOT NULL " );
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks CHANGE  `link_description` `link_description` text         $charset_collate NOT NULL " );
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks CHANGE  `file_size`        `file_size`        VARCHAR(100) $charset_collate NOT NULL default ''" );
		}

		if( $this->db_fields['link_url']->Key ){
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks DROP INDEX link_url, ADD INDEX link_url (link_url(191))" );
		}
		else{
			$wpdb->query( "ALTER TABLE $wpdb->kcc_clicks ADD INDEX link_url (link_url(191))" );
		}
	}

	private function v3_6_2() {
		global $wpdb;

		if( ! version_compare( $this->prev_ver, '3.6.8.2', '<' ) ){
			return;
		}

		// удалим протоколы у всех ссылок в БД
		$wpdb->query( "UPDATE $wpdb->kcc_clicks SET link_url = REPLACE(link_url, 'http://', '//')" );
		$wpdb->query( "UPDATE $wpdb->kcc_clicks SET link_url = REPLACE(link_url, 'https://', '//')" );
	}

}
