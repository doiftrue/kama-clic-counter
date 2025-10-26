<?php

namespace KamaClickCounter;

use ReflectionClass;

class Upgrader__Test extends KCCTestCase {

	/**
	 * @covers Upgrader::run_methods()
	 */
	public function test__run_methods(): void {
		$upgrader = ( new ReflectionClass( Upgrader::class ) )->newInstanceWithoutConstructor();

		// get access to private method
		$result = ( function(){
			/** @var Upgrader $this */
			$this->db_ver = '4.0.0';
			$this->curr_ver = '4.7.0';
			return $this->run_methods( new Upgrader_Methods_Mock() );
		} )->call( $upgrader );

		$this->assertSame(
			[
				'v4_0_1' => 'v4_0_1 result',
				'v4_1_0' => 'v4_1_0 result',
			],
			$result
		);
	}

}

class Upgrader_Methods_Mock extends Upgrader_Methods_Abstract {
	public function __construct() {
	    // skip constructor
	}
	public function v3_0_0( array & $res ): void { $res['v3_0_0'] = 'v3_0_0 result'; }
	public function v3_2_2( array & $res ): void { $res['v3_2_2'] = 'v3_2_2 result'; }
	public function v4_0_1( array & $res ): void { $res['v4_0_1'] = 'v4_0_1 result'; }
	public function v4_1_0( array & $res ): void { $res['v4_1_0'] = 'v4_1_0 result'; }
	public function v4_2_0( array & $res ): void { $res['v4_1_0'] = 'v4_1_0 result'; }
}
