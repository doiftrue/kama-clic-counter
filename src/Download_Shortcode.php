<?php

namespace KamaClickCounter;

class Download_Shortcode {

	public function __construct(){
	}

	public function init(){
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

		// write data to the database

		$link = plugin()->counter->get_link( $kcc_url );

		if( ! $link ){
			plugin()->counter->do_count( $kcc_url, $count = false ); // don't count this operation
			$link = plugin()->counter->get_link( $kcc_url );
		}

		$tpl = plugin()->opt->download_tpl;
		$tpl = str_replace( '[link_url]', esc_url( $kcc_url ), $tpl );

		$atts['title'] && ( $tpl = str_replace( '[link_title]', $atts['title'], $tpl ) );
		$atts['desc'] && ( $tpl = str_replace( '[link_description]', $atts['desc'], $tpl ) );

		return $this->tpl_replace_shortcodes( $tpl, $link );
	}

	/**
	 * Replaces the shotcodes in the template with real data.
	 *
	 * @param string $tpl   A template to replace the data in it.
	 * @param object $link  Reference data from the database.
	 *
	 * @return string The HTML code of the block is the replaced template.
	 */
	public function tpl_replace_shortcodes( string $tpl, $link ): string {

		$tpl = strtr( $tpl, [
			'[icon_url]'  => Helpers::get_icon_url( $link->link_url ),
			'[edit_link]' => $this->edit_link_url( $link->link_id ),
		] );

		if( preg_match( '@\[link_date:([^\]]+)\]@', $tpl, $date ) ){
			$tpl = str_replace( $date[0], apply_filters( 'get_the_date', mysql2date( $date[1], $link->link_date ) ), $tpl );
		}

		// меняем все остальные шоткоды
		preg_match_all( '@\[([^\]]+)\]@', $tpl, $match );
		foreach( $match[1] as $data ){
			$tpl = str_replace( "[$data]", $link->$data, $tpl );
		}

		return $tpl;
	}

	/**
	 * Returns the URL on the edit links in the admin
	 */
	public function edit_link_url( int $link_id, string $edit_text = '' ): string {

		if( ! plugin()->manage_access ){
			return '';
		}

		return sprintf( '<a class="kcc-edit-link" href="%s">%s</a>',
			admin_url( 'admin.php?page=' . plugin()->slug . "&edit_link=$link_id" ),
			( $edit_text ?: '✎' )
		);
	}

}
