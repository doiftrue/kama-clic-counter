<?php

namespace KamaClickCounter;

class Download_Shortcode {

	public function __construct() {
	}

	public function init(): void {
		add_shortcode( 'download', [ $this, 'download_shortcode' ] );
	}

	public function download_shortcode( $atts = [] ): string {
		global $post;

		$atts = shortcode_atts( [
			'url'   => '',
			'title' => '',
			'desc'  => '',
		], $atts );

		if( ! $atts['url'] ){
			return '[download]';
		}

		$kcc_url = plugin()->counter->get_kcc_url( $atts['url'], $post->ID, 1 );

		$link = plugin()->counter->get_link( $kcc_url );
		if( ! $link ){
			plugin()->counter->do_count( $kcc_url, $count = false ); // don't count this operation
			$link = plugin()->counter->get_link( $kcc_url );
		}
		if( ! $link ){
			return 'Link not found in DB for [download] shortcode.';
		}

		/**
		 * Allow to override the output of the [download] shortcode.
		 *
		 * If the filter returns a non-empty value, it will be used as the output.
		 *
		 * @param string    $out   The output of the shortcode. Default is empty.
		 * @param Link_Item $link  Reference data from the database.
		 * @param array     $atts  Shortcode attributes.
		 */
		$out = apply_filters( 'kcc_pre_download_shortcode', '', $link, $atts );
		if( $out ){
			return $out;
		}

		$tpl = plugin()->opt->download_tpl;
		$tpl = str_replace( '[link_url]', esc_url( $kcc_url ), $tpl );

		$atts['title'] && ( $tpl = str_replace( '[link_title]',       esc_html( $atts['title'] ), $tpl ) );
		$atts['desc']  && ( $tpl = str_replace( '[link_description]', esc_html( $atts['desc'] ), $tpl ) );

		return $this->tpl_replace_shortcodes( $tpl, $link );
	}

	/**
	 * Replaces the shotcodes in the template with real data.
	 *
	 * @param string    $tpl   A template to replace the data in it.
	 * @param Link_Item $link  Reference data from the database.
	 *
	 * @return string The HTML code of the block is the replaced template.
	 */
	public function tpl_replace_shortcodes( string $tpl, Link_Item $link ): string {
		$tpl = strtr( $tpl, [
			'[icon_url]'  => esc_url( Helpers::get_icon_url( $link->link_url ) ),
			'[edit_link]' => $this->edit_link_button( $link->link_id ),
		] );

		if( preg_match( '~\[link_date:([^\]]+)\]~', $tpl, $mm ) ){
			$link_date = apply_filters( 'get_the_date', mysql2date( $mm[1], $link->link_date ) );
			$tpl = str_replace( $mm[0], $link_date, $tpl );
		}

		// change all other shortcodes
		$map = [
			'[link_clicks]'      => (int) $link->link_clicks,                // 48
			'[link_name]'        => esc_html( $link->link_name ),            // "Some name"
			'[link_title]'       => esc_html( $link->link_title ),           // "Some name"
			'[link_description]' => wp_kses_post( $link->link_description ), // "Some description"
			'[link_url]'         => esc_attr( $link->link_url ),             // "//github.com/wp_limit_login/releases/tag/v4.0"
			'[file_size]'        => esc_html( $link->file_size ),            // "0"
			//'[link_id]'          => (int) $link->link_id,                    // 4382
			//'[attach_id]'        => (int) $link->attach_id,                  // 0
			//'[in_post]'          => (int) $link->in_post,                    // 2943
			//'[last_click_date]'  => esc_html( $link->last_click_date ),      // "2025-07-05"
		];

		foreach( $map as $placeholder => $val ){
			$tpl = str_replace( $placeholder, $val, $tpl );
		}

		return $tpl;
	}

	/**
	 * Returns the URL on the edit links in the admin
	 */
	public function edit_link_button( int $link_id, string $edit_text = '' ): string {
		if( ! plugin()->manage_access ){
			return '';
		}

		return sprintf( '<a class="kcc-edit-link" href="%s">%s</a>',
			admin_url( 'admin.php?page=' . plugin()->slug . "&edit_link=$link_id" ),
			( $edit_text ?: '✎' )
		);
	}

}
