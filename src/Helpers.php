<?php

namespace KamaClickCounter;

class Helpers {

	/**
	 * @param string $message  HTML.
	 * @param string $type     One of: success|error|warning|info.
	 */
	public static function notice_message( string $message, string $type = 'warning' ): void {
		add_action( 'admin_notices', static function() use ( $message, $type ) {
			?>
			<div id="message" class="notice <?= esc_attr( "notice-$type" ) ?>">
				<p><?= wp_kses_post( $message ) ?></p>
			</div>
			<?php
		} );
	}

	/**
	 * Gets a link to the icon image by the extension in the passed URL.
	 *
	 * @return mixed|null
	 */
	public static function get_icon_url( $link_url ) {
		$url_path = parse_url( $link_url, PHP_URL_PATH ) ?: '';

		if( preg_match( '~\.([a-zA-Z0-9]{1,8})(?=$|\?.*)~', $url_path, $m ) ){
			$icon_name = $m[1] . '.png';
		}
		else {
			$icon_name = 'default.png';
		}

		$icon_name = file_exists( plugin()->dir . "/assets/icons/$icon_name" ) ? $icon_name : 'default.png';

		$icon_url = plugin()->url . "/assets/icons/$icon_name";

		return apply_filters( 'click_counter__get_icon_url', $icon_url, $icon_name );
	}

	/**
	 * @see Helpers__Test::test__calc_clicks_per_day()
	 */
	public static function calc_clicks_per_day( Link_Item $link, int $now = 0 ): float {
		static $curr_time, $curr_ymonth, $curr_day;
		$curr_time   || ( $curr_time = ( $now ?: time() ) + ( get_option( 'gmt_offset' ) * 3600 ) );
		$curr_ymonth || ( $curr_ymonth = date( 'Y-m', $curr_time ) );
		$curr_day    || ( $curr_day = (int) date( 'j', $curr_time ) );

		$month_clicks = $link->clicks_in_month;
		$days_passed = $curr_day; // days passed in current month

		// link was added this month
		if( str_starts_with( $link->link_date, $curr_ymonth ) ){
			$days_passed = $curr_day - date( 'j', strtotime( $link->link_date ) );
			if( $days_passed < 0 ){
				trigger_error( 'Something wrong: unexpected behavior in Helpers::calc_clicks_per_day(): days_passed < 0', E_USER_WARNING );
				$days_passed = 0;
			}
		}

		return round( $month_clicks / ( $days_passed ?: 1 ), 1 );
	}

}
