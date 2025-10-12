<?php

namespace KamaClickCounter;

class Content_Replacer {

	public function __construct() {
	}

	public function init(): void {
		if( plugin()->opt->links_class ){
			add_filter( 'the_content', [ $this, 'modify_links' ] );
		}
	}

	/**
	 * Change links that have special class in given content.
	 */
	public function modify_links( string $content ): string {
		$the_class = plugin()->opt->links_class;
		if( false === strpos( $content, $the_class ) ){
			return $content;
		}

		return preg_replace_callback( "@<a ([^>]*class=['\"][^>]*{$the_class}(?=[\s'\"])[^>]*)>(.+?)</a>@",
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

		$link_attrs .= sprintf( 'data-%s="%s"', Counter::PID_KEY, $post->ID );

		// add hits info after link or in title
		$after = '';
		if( plugin()->opt->add_hits ){
			preg_match_all( '~[^=]+=([\'"])[^\1]+?\1~', $link_attrs, $args );

			foreach( $args[0] as $pair ){
				[ $name, $value ] = explode( '=', $pair, 2 );
				$value = trim( trim( $value, '"\'' ) );
				$args[ trim( $name ) ] = $value;
			}
			unset( $args[0], $args[1] );

			$link = plugin()->counter->get_link( $args['href'] );
			if( $link && $link->link_clicks ){
				switch( plugin()->opt->add_hits ){
					case 'in_title':
						$args['title'] = esc_attr( sprintf( "(%s $link->link_clicks)%s", __( 'clicks:', 'kama-clic-counter' ), ($args['title'] ?? '') ) );
						break;
					case 'in_plain':
						$after = sprintf( ' <span class="hitcounter">(%s %s)</span>', __( 'clicks:', 'kama-clic-counter' ), $link->link_clicks );
						break;
				}
			}

			// re-set link attributes
			$link_attrs = '';
			foreach( $args as $key => $value ){
				$link_attrs .= sprintf( '%s="%s" ', $key, $value );
			}
			$link_attrs = trim( $link_attrs );
		}

		return "<a $link_attrs>$link_anchor</a>$after";
	}

}
