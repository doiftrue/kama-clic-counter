<?php
/**
 * TinyMCE extend.
 * This file includs in Admin Class.
 */

namespace KamaClickCounter;

class TinyMCE {

	public static function init() {

		if( ! get_user_option( 'rich_editing' ) ){
			return;
		}

		add_filter( 'mce_buttons_2', [ __CLASS__, 'register_buttons' ] );
		add_filter( 'mce_external_plugins', [ __CLASS__, 'mce_js' ] );
		add_filter( 'wp_mce_translation', [ __CLASS__, 'l10n' ] );
	}

	public static function register_buttons( $buttons ) {
		$last      = array_pop( $buttons );
		$buttons[] = 'kcc';
		$buttons[] = $last;

		return $buttons;
	}

	public static function mce_js( $plugin_array ) {
		$plugin_array['KCC'] = plugin()->url . '/assets/tinymce.js';

		return $plugin_array;
	}

	public static function l10n( $mce_l10n ): array {

		$l10n = array_map( 'esc_js', [
			'kcc mcebutton name'       => __( 'Click Counter Shortcode', 'kama-clic-counter' ),
			'kcc frame button title'   => __( 'Select file', 'kama-clic-counter' ),
			'kcc find url frame title' => __( 'Find file for download shortcode', 'kama-clic-counter' ),
			'kcc select from media'    => __( 'Select from media', 'kama-clic-counter' ),
			'kcc modal title'          => __( 'Click counter shortcode insertion', 'kama-clic-counter' ),
			'kcc button text'          => __( 'Insert shortcode', 'kama-clic-counter' ),
			'kcc input title'          => __( 'Link title (not required)', 'kama-clic-counter' ),
			'kcc input link'           => __( 'Download file URL', 'kama-clic-counter' ),
		] );

		return $mce_l10n + $l10n;
	}

}


