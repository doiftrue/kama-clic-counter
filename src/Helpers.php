<?php

namespace KamaClickCounter;

class Helpers {

	/**
	 * @param string $message Message HTML.
	 * @param string $type    Allowed: success | error | warning | info.
	 */
	public static function notice_message( string $message, string $type = 'warning' ) {

		add_action(
			'admin_notices',
			function () use ( $message, $type ) {
				?>
				<div id="message" class="notice <?= esc_attr( "notice-$type" ) ?>">
					<p><?= wp_kses_post( $message ) ?></p>
				</div>
				<?php
			}
		);
	}

	/**
	 * Gets a link to the icon image by the extension in the passed URL.
	 *
	 * @param $link_url
	 *
	 * @return mixed|null
	 */
	public static function get_icon_url( $link_url ){

		$url_path = parse_url( $link_url, PHP_URL_PATH );

		if( preg_match( '~\.([a-zA-Z0-9]{1,8})(?=$|\?.*)~', $url_path, $m ) ){
			$icon_name = $m[1] . '.png';
		}
		else{
			$icon_name = 'default.png';
		}

		$icon_name = file_exists( plugin()->dir . "/assets/icons/$icon_name" ) ? $icon_name : 'default.png';

		$icon_url = plugin()->url . "/assets/icons/$icon_name";

		return apply_filters( 'click_counter__get_icon_url', $icon_url, $icon_name );
	}

}
