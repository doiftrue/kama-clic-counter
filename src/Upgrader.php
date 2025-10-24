<?php
namespace KamaClickCounter;

class Upgrader {

	use Upgrader_Run_Methods;

	public const OPTION_NAME = 'kcc_version';

	private string $prev_ver;
	private string $curr_ver;

	/** @var object[] */
	private array $db_fields = [];

	public function __construct( string $start_from_ver = '' ) {
		$this->prev_ver = $start_from_ver ?: get_option( self::OPTION_NAME, '1.0' );
		$this->curr_ver = plugin()->ver;
	}

	public function is_run_upgrade(): bool {
		return $this->prev_ver !== $this->curr_ver;
	}

	public function run_upgrade(): void {
		$this->set_db_fields();

		$res = $this->run_methods();
		/** @noinspection ForgottenDebugOutputInspection */
		error_log( 'Kama-Click-Counter upgrade result log: ' . print_r( $res, true ) ); // TODO: add better logging

		update_option( self::OPTION_NAME, $this->curr_ver ); // update to current version
	}

	private function run_methods(): array {
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
