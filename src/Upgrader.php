<?php
namespace KamaClickCounter;

class Upgrader {

	public const OPTION_NAME = 'kcc_version';

	private string $db_ver;
	private string $curr_ver;

	public function __construct( string $start_from_ver = '' ) {
		$this->db_ver = $start_from_ver ?: get_option( self::OPTION_NAME, '1.0' );
		$this->curr_ver = plugin()->ver;
	}

	public function is_run_upgrade(): bool {
		return $this->db_ver !== $this->curr_ver;
	}

	public function run_upgrade(): void {
		$result = $this->run_methods( new Upgrader_Methods() );

		/** @noinspection ForgottenDebugOutputInspection */
		error_log( 'Kama-Click-Counter upgrade result log: ' . print_r( $result, true ) ); // TODO: better logging

		update_option( self::OPTION_NAME, $this->curr_ver );
	}

	/**
	 * @see Upgrader__Test::test__run_methods()
	 */
	private function run_methods( Upgrader_Methods_Abstract $methods_container ): array {
		$result = [];

		$to_run = [];
		$method_names = get_class_methods( $methods_container );
		foreach( $method_names as $method_name ) {
			if( preg_match( '~^v\d+~', $method_name ) ){
				$to_run[ $method_name ] = strtr( $method_name, [ 'v' => '', '_' => '.' ] ); // v3_6_2 -> 3.6.2
			}
		}
		uksort( $to_run, static fn( $a, $b ) => version_compare( $a, $b ) ); // ASC

		foreach( $to_run as $method => $version ){
			// process only versions greater than current db version
			if( ! version_compare( $version, $this->db_ver, '>' ) ){
				continue;
			}

			/**
			 * @see Upgrader_Methods::v3_6_2()
			 * @see Upgrader_Methods::v4_1_0()
			 */
			$methods_container->$method( $result );
		}

		return $result;
	}

}
