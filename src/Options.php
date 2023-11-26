<?php

namespace KamaClickCounter;

/**
 * @property-read string $download_tpl
 * @property-read string $links_class
 * @property-read string $add_hits
 * @property-read int    $in_post
 * @property-read bool   $hide_url
 * @property-read bool   $widget
 * @property-read bool   $toolbar_item
 * @property-read array  $access_roles
 * @property-read string $url_exclude_patterns
 */
class Options {

	const OPT_NAME = 'kcc_options';

	/** @var array */
	private $options;

	private $default_options = [
		// download block template
		'download_tpl' => '
			<div class="kcc_block" title="Скачать" onclick="document.location.href=\'[link_url]\'">
				<img class="alignleft" src="[icon_url]" alt="" />

				<div class="kcc_info_wrap">
					<a class="kcc_link" href="[link_url]" title="[link_name]">Скачать: [link_title]</a>
					<div class="kcc_desc">[link_description]</div>
					<div class="kcc_info">Скачано: [link_clicks], размер: [file_size], дата: [link_date:d M. Y]</div>
				</div>
				[edit_link]
			</div>

			<style>
				.kcc_block{ position:relative; padding:1em 0 2em; transition:background-color 0.4s; cursor:pointer; }
				.kcc_block img{ float:left; width:2.1em; height:auto; margin:0; border:0px !important; box-shadow:none !important; }
				.kcc_block .kcc_info_wrap{ padding-left:1em; margin-left:2.1em; }
				.kcc_block a{ border-bottom:0; }
				.kcc_block a.kcc_link{ text-decoration:none; display:block; font-size:150%; line-height:1.2; }
				.kcc_block .kcc_desc{ color:#666; }
				.kcc_block .kcc_info{ font-size:80%; color:#aaa; }
				.kcc_block:hover a{ text-decoration:none !important; }
				.kcc_block .kcc-edit-link{ position:absolute; top:0; right:.2em; }
				.kcc_block:after{ content:""; display:table; clear:both; }
			</style>
		',
		// css class for links in content (if not specified, this functionality is disabled).
		'links_class' => 'count',
		// may be: '', 'in_title' or 'in_plain' (for simple links)
		'add_hits'             => '',
		'in_post'              => 1,
		// should we hide the link or not?
		'hide_url'             => false,
		// enable a widget for WordPress?
		'widget'               => 1,
		// Show a link to the stats in the admin bar?
		'toolbar_item'         => 1,
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
		return null;
	}

	public function __isset( $name ) {
		return isset( $this->options[ $name ] );
	}

	/**
	 * @return void
	 */
	public function set_options() {
		$this->options = get_option( self::OPT_NAME, [] );

		foreach( $this->get_def_options() as $name => $val ){
			if( ! isset( $this->options[ $name ] ) ){
				$this->options[ $name ] = $val;
			}
		}
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

	public function update_option( array $new_data ): bool {
		$up = update_option( self::OPT_NAME, $new_data );

		$up && $this->set_options();

		return (bool) $up;
	}

}
