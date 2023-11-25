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

}
