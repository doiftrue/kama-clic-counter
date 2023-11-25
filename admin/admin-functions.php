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

function get_clicks_per_day( $link ): float {
	static $cur_time;
	if( $cur_time === null ){
		$cur_time = time() + ( get_option( 'gmt_offset' ) * 3600 );
	}

	return round( ( (int) $link->link_clicks / ( ( $cur_time - strtotime( $link->link_date ) ) / ( 3600 * 24 ) ) ), 1 );
}
