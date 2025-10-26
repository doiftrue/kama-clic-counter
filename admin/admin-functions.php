<?php

namespace KamaClickCounter;

/**
 * Available shotcodes in link templates.
 */
function tpl_available_tags(): string {
	$array = [
		__( 'Shortcodes that can be used in template:', 'kama-clic-counter' ),
		__( '[icon_url] - URL to file icon', 'kama-clic-counter' ),
		__( '[link_url] - URL to download', 'kama-clic-counter' ),
		__( '[link_name]', 'kama-clic-counter' ),
		__( '[link_title]', 'kama-clic-counter' ),
		__( '[link_clicks] - number of clicks', 'kama-clic-counter' ),
		__( '[file_size]', 'kama-clic-counter' ),
		__( '[link_date:d.M.Y] - date in "d.M.Y"  format', 'kama-clic-counter' ),
		__( '[link_description]', 'kama-clic-counter' ),
		__( '[edit_link] - URL to edit link in admin', 'kama-clic-counter' ),
	];

	$out = '
	<div style="font-size:90%;">
		<div>' . implode( '</div><div>', $array ) . '</div>
	</div>
	';

	return str_replace( [ '[', ']' ], [ '<code>[', ']</code>' ], $out );
}

function calc_clicks_per_day( Link_Item $link ): float {
	static $curr_time, $curr_ymonth, $curr_day;
	$curr_time   || ( $curr_time = time() + ( get_option( 'gmt_offset' ) * 3600 ) );
	$curr_ymonth || ( $curr_ymonth = (int) date( 'Y-m', $curr_time ) );
	$curr_day    || ( $curr_day = (int) date( 'j', $curr_time ) );

	$month_clicks = $link->clicks_in_month;
	$days_passed = $curr_day - 1; // days passed in current month

	// link was added this month
	if( str_starts_with( $link->link_date, $curr_ymonth ) ){
		$days_passed = $curr_day - date( 'j', strtotime( $link->link_date ) );
		if( $days_passed < 0 ){
			trigger_error( 'Something wrong: unexpected behavior in calc_clicks_per_day(): days_passed < 0', E_USER_WARNING );
			$days_passed = 0;
		}
	}

	return round( $month_clicks / ( $days_passed ?: 1 ), 1 );
}
