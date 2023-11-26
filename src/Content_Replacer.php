<?php

namespace KamaClickCounter;

class Content_Replacer {

	public function __construct(){
	}

	public function init(){

		if( plugin()->opt->links_class ){
			add_filter( 'the_content', [ $this, 'modify_links' ] );
		}
	}

	/**
	 * Change links that have special class in given content.
	 */
	public function modify_links( string $content ): string {

		$links_class = plugin()->opt->links_class;

		if( false === strpos( $content, $links_class ) ){
			return $content;
		}

		return preg_replace_callback( "@<a ([^>]*class=['\"][^>]*{$links_class}(?=[\s'\"])[^>]*)>(.+?)</a>@",
			[ $this, '_make_html_link_cb', ],
			$content
		);
	}

	/**
	 * Parse string to detect and process pairs of tag="value".
	 */
	public function _make_html_link_cb( array $match ): string {
		global $post;

		$link_attrs  = $match[1];
		$link_anchor = $match[2];

		preg_match_all( '~[^=]+=([\'"])[^\1]+?\1~', $link_attrs, $args );

		foreach( $args[0] as $pair ){
			list( $tag, $value ) = explode( '=', $pair, 2 );
			$value = trim( trim($value, '"\'') );
			$args[ trim($tag) ] = $value;
		}
		unset( $args[0], $args[1] );

		$after = '';
		$args[ 'data-'. Counter::PID_KEY ] = $post->ID;
		if( plugin()->opt->add_hits ){
			$link = plugin()->counter->get_link( $args['href'] );

			if( $link && $link->link_clicks ){
				if( plugin()->opt->add_hits === 'in_title' ){
					$args['title'] = "(" . __( 'clicks:', 'kama-clic-counter' ) . " {$link->link_clicks})" . $args['title'];
				}
				else{
					$after = ( plugin()->opt->add_hits === 'in_plain' )
						? ' <span class="hitcounter">(' . __( 'clicks:', 'kama-clic-counter' ) . ' ' . $link->link_clicks . ')</span>'
						: '';
				}
			}
		}

		$link_attrs = '';
		foreach( $args as $key => $value ){
			$link_attrs .= sprintf( '%s="%s" ', $key, esc_attr( $value ) );
		}

		$link_attrs = trim( $link_attrs );

		return "<a $link_attrs>$link_anchor</a>$after";
	}

}
