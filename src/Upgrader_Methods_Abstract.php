<?php

namespace KamaClickCounter;

abstract class Upgrader_Methods_Abstract {

	/** @var object[] */
	protected array $db_fields = [];

	public function __construct() {
		$this->set_db_fields();
	}

	private function set_db_fields(): void {
		global $wpdb;
		$this->db_fields = $wpdb->get_results( "SHOW COLUMNS FROM $wpdb->kcc_clicks" );

		// field name to index
		foreach( $this->db_fields as $k => $data ){
			$this->db_fields[ $data->Field ] = $data;
			unset( $this->db_fields[ $k ] );
		}
	}

}
