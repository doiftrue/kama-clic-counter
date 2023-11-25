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
	private $data;

	public function __construct() {
		$this->set_data();
	}

	public function __get( $name ) {
		return $this->data[ $name ] ?? null;
	}

	public function __set( $name, $val ) {
		return null;
	}

	public function __isset( $name ) {
		return isset( $this->data[ $name ] );
	}

	/**
	 * @return void
	 */
	public function set_data() {
		$this->data = get_option( self::OPT_NAME, [] );

		foreach( $this->get_def_options() as $name => $val ){
			if( ! isset( $this->data[ $name ] ) ){
				$this->data[ $name ] = $val;
			}
		}
	}

	public function reset_to_defaults() {
		update_option( self::OPT_NAME, $this->data->get_def_options() );

		$this->data = get_option( self::OPT_NAME );
	}

	public function get_def_options(): array {

		$array = [
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
				</style>',
			// css класс для простых ссылок (если не указано, то этот функционал отключен).
			'links_class' => 'count',
			// may be: '', 'in_title' or 'in_plain' (for simple links)
			'add_hits'             => '',
			'in_post'              => 1,
			// прятать ссылку или нет?
			'hide_url'             => false,
			// включить виджет для WordPress
			'widget'               => 1,
			// выводить ссылку на статистику в Админ баре?
			'toolbar_item'         => 1,
			// Название ролей, кроме администратора, которым доступно упраление плагином.
			'access_roles'         => [],
			// подстроки. Если ссылка имеет указанную подстроку, то не считать клик на нее...
			'url_exclude_patterns' => '',
		];

		$array['download_tpl'] = trim( preg_replace( '~^\t{4}~m', '', $array['download_tpl'] ) );

		return $array;
	}

	public function update_option( array $new_data ): bool {
		$up = update_option( self::OPT_NAME, $new_data );

		$up && $this->set_data();

		return (bool) $up;
	}

}
