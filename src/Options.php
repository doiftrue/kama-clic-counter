<?php

namespace KamaClickCounter;

/**
 * @property-read string $download_tpl
 * @property-read string $download_tpl_styles
 * @property-read string $links_class
 * @property-read string $add_hits
 * @property-read bool   $in_post
 * @property-read bool   $hide_url
 * @property-read bool   $widget
 * @property-read bool   $toolbar_item
 * @property-read array  $access_roles
 * @property-read string $url_exclude_patterns
 */
class Options {

	public const OPT_NAME = 'kcc_options';

	private array $options;

	private array $default_options = [
		// download block template
		'download_tpl' => <<<'HTML'
			<div class="kcc_block">
				<img class="alignleft" src="[icon_url]" alt="" />

				<div class="kcc_info_wrap">
					<a class="kcc_link" href="[link_url]" title="[link_name]">[link_title] <small>(download)</small></a>
					<div class="kcc_desc">[link_description]</div>
					<div class="kcc_info">Downloaded: [link_clicks]. Size: [file_size]. Date: [link_date:d M. Y]</div>
				</div>
				[edit_link]
			</div>
			HTML,
		'download_tpl_styles' => <<<'CSS'
			.kcc_block{ position:relative; display:flex; align-items:center; gap:1em; padding:1em 0 2em; }
			.kcc_block img{ display:block; width:3em; height:auto; align-self:start; object-fit:contain;
				margin:0; border:0 !important; box-shadow:none !important;
			}
			.kcc_info_wrap{ display:flex; flex-direction:column; gap:.4em; }
			.kcc_block a.kcc_link{ display:block; font-size:150%; line-height:1.2; }
			.kcc_block .kcc_desc{ opacity:.7; line-height:1.3; }
			.kcc_block .kcc_desc:empty{ display:none; }
			.kcc_block .kcc_info{ font-size:80%; opacity:.5; }
			.kcc_block .kcc-edit-link{ position:absolute; top:0; right:.2em; }
			CSS,
		// css class for links in content (if not specified, this functionality is disabled).
		'links_class'          => 'count',
		// may be: '', 'in_title' or 'in_plain' (for simple links)
		'add_hits'             => '',
		'in_post'              => true,
		// should we hide the link or not?
		'hide_url'             => false,
		// enable a widget for WordPress?
		'widget'               => true,
		// Show a link to the stats in the admin bar?
		'toolbar_item'         => true,
		// The name of roles, other than administrator, to which control of the plugin is available.
		'access_roles'         => [],
		// Substrings. If the link has the specified substring, then don't count clicking on it.
		'url_exclude_patterns' => '',
	];

	public function __construct() {
		$this->set_options();
	}

	public function __get( $name ) {
		return $this->options[ $name ] ?? null;
	}

	public function __set( $name, $val ) {
		throw new \RuntimeException( 'Set values not allowed for this class. Use set_options() method.' );
	}

	public function __isset( $name ) {
		return isset( $this->options[ $name ] );
	}

	public function set_options(): void {
		$this->options = (array) get_option( self::OPT_NAME, [] );

		foreach( $this->options as $key => $val ){
			$this->options[ $key ] = $this->cast_type( $key, $val );
		}

		foreach( $this->get_def_options() as $key => $def_val ){
			/**
			 * @see self::$download_tpl
			 * @see self::$download_tpl_styles
			 * @see self::$links_class
			 * @see self::$add_hits
			 * @see self::$in_post
			 * @see self::$hide_url
			 * @see self::$widget
			 * @see self::$toolbar_item
			 * @see self::$access_roles
			 * @see self::$url_exclude_patterns
			 */
			$this->options[ $key ] ??= $def_val;
		}
	}

	private function cast_type( string $key, $val ) {
		settype( $val, gettype( $this->default_options[ $key ] ) );

		return $val;
	}

	public function get_raw_options(): array {
		return (array) get_option( self::OPT_NAME, [] );
	}

	public function reset_to_defaults(): bool {
		$this->options = $this->get_def_options();

		return (bool) update_option( self::OPT_NAME, $this->options );
	}

	public function get_def_options(): array {
		$options = $this->default_options;
		$options['download_tpl'] = trim( preg_replace( '~^\t{4}~m', '', $options['download_tpl'] ) );

		return $options;
	}

	public function update_option( array $new_options ): bool {
		$new_options = $this->sanitize( $new_options );
		$up = update_option( self::OPT_NAME, $new_options );
		$up && $this->set_options();

		return (bool) $up;
	}

	private function sanitize( array $options ): array {
		foreach( $options as $key => & $val ){
			is_string( $val ) && $val = trim( $val );

			if( $key === 'download_tpl' ){
				$val = wp_kses_post( $val );
			}
			elseif( $key === 'download_tpl_styles' ){
				$val = wp_kses( $val, 'strip' );
			}
			elseif( $key === 'url_exclude_patterns' ){
				// no sanitize... wp_kses($val, 'post');
			}
			elseif( $key === 'access_roles' ){
				$val = array_map( 'sanitize_key', $val );
				$not_allowed_roles =  [ 'contributor', 'subscriber' ];
				$val = array_filter( $val, static fn( $role ) => ! in_array( $role, $not_allowed_roles, true ) );
			}
			else{
				$val = is_array( $val )
					? array_map( 'sanitize_key', $val )
					: sanitize_key( $val );
			}

			$val = $this->cast_type( $key, $val );
		}
		unset( $val );

		return $options;
	}

}
