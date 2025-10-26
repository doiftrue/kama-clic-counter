<?php

namespace KamaClickCounter;

use PHPUnit\Framework\TestCase;

class Helpers__Test extends TestCase {

	/**
	 * @covers Helpers::calc_clicks_per_day()
	 */
	public function test__calc_clicks_per_day() {
		\WP_Mock::userFunction( 'get_option' )->andReturn( 5 );

		$base_link = [
			'link_id'           => '123',
			'attach_id'         => '456',
			'in_post'           => true,
			'link_clicks'       => 42,
			'clicks_in_month'   => 30,
			'clicks_prev_month' => 25,
			'clicks_history'    => '',
			'link_name'         => 'Sample Link',
			'link_title'        => 'Sample Title',
			'link_description'  => 'A description for the sample link.',
			'link_date'         => '2024-06-01',
			'last_click_date'   => '2024-06-10',
			'link_url'          => 'https://example.com',
			'file_size'         => 2048,
			'downloads'         => 5,
		];

		$now = 1760954461; // 2025-10-20 10:01:01
		$per_day_cb = static fn( $data ) => Helpers::calc_clicks_per_day( new Link_Item( (object) ($data + $base_link) ), $now );

		$this->assertEquals( 5,   $per_day_cb( [ 'clicks_in_month' => 100, 'link_date' => '2024-06-01', ] ) );
		$this->assertEquals( 5,   $per_day_cb( [ 'clicks_in_month' => 100, 'link_date' => '2025-09-25', ] ) );
		$this->assertEquals( 50,  $per_day_cb( [ 'clicks_in_month' => 100, 'link_date' => '2025-10-18', ] ) );
		$this->assertEquals( 100, $per_day_cb( [ 'clicks_in_month' => 100, 'link_date' => '2025-10-20', ] ) );
		$this->assertEquals( 100, $per_day_cb( [ 'clicks_in_month' => 100, 'link_date' => '2025-10-19', ] ) );
	}

}
