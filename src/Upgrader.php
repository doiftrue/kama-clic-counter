<?php
/**
 * To forse upgrade add '?kcc_force_upgrade' parameter to URL
 */

namespace KamaClickCounter;

class Upgrader {

	use Upgrader_Run_Methods;

	public const OPTION_NAME = 'kcc_version';

	private string $prev_ver;
	private string $curr_ver;

	private bool $is_force_upgrade;

	/** @var object[] */
	private array $db_fields = [];

	public function __construct() {
		$this->is_force_upgrade = isset( $_GET['kcc_force_upgrade'] );
		$this->prev_ver = $this->is_force_upgrade ? '1.0' : get_option( self::OPTION_NAME, '1.0' );
		$this->curr_ver = plugin()->ver;
	}

	public function init(): void {
		if( $this->prev_ver === $this->curr_ver ){
			return;
		}

		update_option( self::OPTION_NAME, $this->curr_ver );

		$this->set_db_fields();
		if( ! $this->db_fields ){
			return;
		}

		$res = $this->run_upgrade();
		/** @noinspection ForgottenDebugOutputInspection */
		error_log( 'Kama-Click-Counter upgrade result log: ' . print_r( $res, true ) ); // TODO: add better logging

		if( $this->is_force_upgrade ){
			wp_redirect( remove_query_arg( 'kcc_force_upgrade' ) );
			exit;
		}
	}

	private function run_upgrade(): array {
		$res = [];
		$this->v3_6_2( $res );
		$this->v4_1_0( $res );
		return $res;
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
