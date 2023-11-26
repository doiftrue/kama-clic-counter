<?php

namespace KamaClickCounter;

class Counter {

	const COUNT_KEY = 'kcccount';

	const PID_KEY = 'kccpid';

	const URL_PLACEHOLDERS = [
		'?' => '__QUESTION__',
		'&' => '__AMPERSAND__',
	];

	/** @var Options */
	public $opt;

	public function __construct( Options $options ){

		$this->opt = $options;
	}

	public function init() {

		add_action( 'wp_footer', [ $this, 'footer_js' ], 99 );

		add_filter( 'init', [ $this, 'redirect' ], 0 );
	}

	/**
	 * A script to count links all over the site.
	 *
	 * @return void
	 */
	public function footer_js() {
		$js = strtr(
			file_get_contents( plugin()->dir . '/assets/counter.js' ),
			[
				'{kcckey}'      => self::COUNT_KEY,
				'{pidkey}'      => self::PID_KEY,
				'{urlpatt}'     => $this->get_kcc_url( '{url}', '{in_post}', '{download}' ),
				'{aclass}'      => sanitize_html_class( $this->opt->links_class ),
				'{questSymbol}' => self::URL_PLACEHOLDERS['?'],
				'{ampSymbol}'   => self::URL_PLACEHOLDERS['&'],
			]
		);

		$js = preg_replace( '~[^:]//[^\n]+|[\t\n\r]~', '', $js ); // remove comments, \t\r\n
		$js = preg_replace( '~[ ]{2,}~', ' ', $js );
		?>
		<script id="kama-click-counter"><?= $js ?></script>
		<?php
	}

	/**
	 * Gets the link on which clicks will be counted.
	 *
	 * @param string     $url       String or Placeholder `{url}`
	 * @param int|string $in_post   1/0 or Placeholder `{in_post}`.
	 * @param int|string $download  1/0 or Placeholder `{download}`.
	 *
	 * @return mixed|null
	 */
	public function get_kcc_url( string $url = '', $in_post = '', $download = '' ) {

		// order matters...
		$vars = [
			'download'      => sanitize_text_field( $download ),
			self::PID_KEY   => sanitize_text_field( $in_post ),
			self::COUNT_KEY => self::add_url_placeholders( $url ),
		];

		if( ! $this->opt->in_post ){
			unset( $vars[ self::PID_KEY ] );
		}

		$kcc_url_params = [];
		foreach( $vars as $key => $val ){
			$val = trim( $val );
			if( $val ){
				$kcc_url_params[] = "$key=$val";
			}
		}

		$kcc_url = home_url() . '?' . implode( '&', $kcc_url_params );
		if( $this->opt->hide_url ){
			$kcc_url = $this->hide_link_url( $kcc_url );
		}

		return apply_filters( 'get_kcc_url', $kcc_url, $this->opt );
	}

	/**
	 * Hides the original link under the link ID. The link must exist in the database.
	 *
	 * @param string $kcc_url Plugin formated URL of the link counting.
	 *
	 * @return string URL with a hidden link.
	 */
	public function hide_link_url( $kcc_url ): string {

		$parsed = $this->parse_kcc_url( $kcc_url );

		// не прячем если это простая ссылка или урл уже спрятан
		if( empty( $parsed['download'] ) || ( isset( $parsed[ self::COUNT_KEY ] ) && is_numeric( $parsed[ self::COUNT_KEY ] ) ) ){
			return $kcc_url;
		}

		// do not hide if the link is not exist in the database
		if( ! $link = $this->get_link( $kcc_url ) ){
			return $kcc_url;
		}

		return preg_replace( '~' . self::COUNT_KEY . '=.*~', self::COUNT_KEY . "=$link->link_id", $kcc_url );
	}

	/**
	 * Adds clicks (count click) by given url.
	 *
	 * @param array|string $kcc_url
	 * @param bool         $count
	 *
	 * @return bool|int
	 */
	public function do_count( $kcc_url, $count = true ) {

		$parsed = is_array( $kcc_url ) ? $kcc_url : $this->parse_kcc_url( $kcc_url );

		$args = [
			'link_url'  => $parsed[ self::COUNT_KEY ], // заметка: без http протокола
			'in_post'   => (int) $parsed[ self::PID_KEY ],
			'downloads' => empty( $parsed['download'] ) ? '' : 'yes',
			'kcc_url'   => $kcc_url,
			'count'     => $count,
		];

		$link_url = &$args['link_url'];
		$link_url = urldecode( $link_url ); // Mark Carson
		$link_url = self::del_http_protocol( $link_url );

		// do not count when the link of the current page is specified so as not to catch looping
		//if( false !== strpos( $link_url, $_SERVER['REQUEST_URI']) )
		//	return;

		// checks

		// can't be empty - must be url or attach ID
		if( ! $link_url ){
			return false;
		}

		// can't contain self parameters - like: link&kcccount=
		$_pattern = '~[?&](?:download|' . self::COUNT_KEY . '|' . self::PID_KEY . ')~';
		if( preg_match( $_pattern, $link_url ) ){
			echo '<h3>kcc error: download shortcode bad url: cant contain self parameters like: "link&kcccount="</h3>';

			return false;
		}

		if( $this->is_url_in_exclude_list( $link_url ) ){
			return false;
		}

		$updated = $this->update_existing_link( $args );
		if( $updated ){
			$return = true;
		}
		else{
			list( $insert_id, $insert_data ) = $this->insert_new_link( $args );
			$return = $insert_id;
		}

		/**
		 * Allows to do something after count.
		 */
		do_action( 'kcc_count_after', $args, $updated, ( $insert_data ?? [] ) );

		$this->clear_link_cache( $kcc_url );

		return $return;
	}

	private function update_existing_link( array $args ): bool {
		global $wpdb;

		$link_url = $args['link_url'];

		$WHERE = [];
		if( is_numeric( $link_url ) ){
			$WHERE[] = $wpdb->prepare( 'link_id = %d ', $link_url );
		}
		else{
			$WHERE[] = $wpdb->prepare( 'link_url = %s ', $link_url );

			if( $this->opt->in_post ){
				$WHERE[] = $wpdb->prepare( 'in_post = %d', $args['in_post'] );
			}
			if( $args['downloads'] ){
				$WHERE[] = $wpdb->prepare( 'downloads = %s', $args['downloads'] );
			}
		}

		$WHERE = implode( ' AND ', $WHERE );

		// NOTE: $wpdb->prepare() can't be used, because of false will be returned if the link
		// with encoded symbols is passed, for example, Cyrillic will have % symbol: /%d0%bf%d1%80%d0%b8%d0%b2%d0%b5%d1%82...
		$update_sql = "UPDATE $wpdb->kcc_clicks SET link_clicks = (link_clicks + 1), last_click_date = '" . current_time( 'mysql' ) . "' WHERE $WHERE LIMIT 1";

		$this->check_and_delete_multiple_same_links( $WHERE );

		do_action_ref_array( 'kcc_count_before', [ $args, & $update_sql ] );

		return (bool) $wpdb->query( $update_sql );
	}

	/**
	 * For some reason (possibly due to race condition) additional identical links sometimes appear in the database.
	 * This method tries to find such links and removes them.
	 */
	private function check_and_delete_multiple_same_links( $WHERE ) {
		global $wpdb;

		$all_links = $wpdb->get_results( "SELECT * FROM $wpdb->kcc_clicks WHERE $WHERE ORDER BY link_clicks DESC LIMIT 99" );

		if( count( $all_links ) > 1 ){
			$first_link = array_shift( $all_links );

			foreach( $all_links as $link ){
				$add_clicks = (int) $link->link_clicks;
				$wpdb->query( "UPDATE $wpdb->kcc_clicks SET link_clicks = (link_clicks + $add_clicks) WHERE link_id = $first_link->link_id;" );
				$wpdb->query( "DELETE FROM $wpdb->kcc_clicks WHERE link_id = $link->link_id;" );
			}
		}
	}

	private function insert_new_link( array $args ): array {
		global $wpdb;

		$link_url = $args['link_url'];

		// data to add to DB
		$insert_data = [
			'attach_id'        => 0,
			'in_post'          => $args['in_post'],
			// Для загрузок, когда запись добавляется просто при просмотре,
			// все равно добавляется 1 первый просмотр, чтобы добавить запись в бД
			'link_clicks'      => $args['count'] ? 1 : 0,
			'link_name'        => untrailingslashit( $this->is_file( $link_url )
				? basename( $link_url )
				: preg_replace( '~^(https?:)?//|\?.*$~', '', $link_url ) ),
			'link_title'       => '', // устанавливается отдлеьно ниже
			'link_description' => '',
			'link_date'        => current_time( 'mysql' ),
			'last_click_date'  => current_time( 'mysql' ),
			'link_url'         => $link_url,
			'file_size'        => self::file_size( $link_url ),
			'downloads'        => $args['downloads'],
		];

		// cyrillic domain
		if( false !== stripos( $insert_data['link_name'], 'xn--' ) ){
			$host = parse_url( $insert_data['link_url'], PHP_URL_HOST );

			$ind = new \KamaClickCounter\libs\idna_convert();

			$insert_data['link_name'] = str_replace( $host, $ind->decode( $host ), $insert_data['link_name'] );
		}

		$title = &$insert_data['link_title'];

		// is_attach?
		$_link_url_like = '%' . $wpdb->esc_like( $link_url ) . '%';
		$attach = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND guid LIKE %s", $_link_url_like
		) );
		if( $attach ){
			$title = $attach->post_title;
			$insert_data['attach_id'] = $attach->ID;
			$insert_data['link_description'] = $attach->post_content;
		}

		// get link_title from url
		if( ! $title ){
			if( $this->is_file( $link_url ) ){
				$title = preg_replace( '~[.][^.]+$~', '', $insert_data['link_name'] ); // delete ext
				$title = preg_replace( '~[_-]~', ' ', $title );
				$title = ucwords( $title );
			}
			else{
				$title = $this->get_html_title( $link_url );
			}
		}

		// if title could not be determined
		if( ! $title ){
			$title = $insert_data['link_name'];
		}

		$insert_data = apply_filters( 'kcc_insert_link_data', $insert_data, $args );

		// sanitize data before save
		$insert_data['link_name'] = sanitize_text_field( $insert_data['link_name'] );

		$insert_id = $wpdb->insert( $wpdb->kcc_clicks, $insert_data ) ? $wpdb->insert_id : 0;

		return [ $insert_id, $insert_data ];
	}

	private function is_url_in_exclude_list( $url ): bool {

		if( ! $this->opt->url_exclude_patterns ){
			return false;
		}

		$excl_patts = array_map( 'trim', preg_split( '/[,\n]/', $this->opt->url_exclude_patterns ) );

		foreach( $excl_patts as $patt ){
			// maybe regular expression
			if( $patt[0] === '/' && substr( $patt, -1 ) === '/' ){
				if( preg_match( $patt, $url ) ){
					return true; // stop
				}
			}
			// simple substring check
			elseif( false !== strpos( $url, $patt ) ){
				return true; // stop
			}
		}

		return false;
	}

	/**
	 * Redirect to link url.
	 *
	 * @return void
	 */
	public function redirect() {

		/**
		 * Allows to override counting function.
		 */
		if( apply_filters( 'kcc_redefine_redirect', false, $this ) ){
			return;
		}

		$url = $_GET[ self::COUNT_KEY ] ?? '';
		if( ! $url ){
			return;
		}

		$parsed = $this->parse_kcc_url( $_SERVER['REQUEST_URI'] );
		$url = $parsed[ self::COUNT_KEY ];
		if( ! $url ){
			return;
		}

		// count
		if( apply_filters( 'kcc_do_count', true, $this ) ){
			$this->do_count( $parsed );
		}

		if( is_numeric( $url ) ){
			if( $link = $this->get_link( $url ) ){
				$url = $link->link_url;
			}
			else{
				trigger_error( sprintf( 'Error: kcc link with id %s not found.', $url ) );
				return;
			}
		}

		// redirect
		if( headers_sent() ){
			print "<script>location.replace('" . esc_url( $url ) . "');</script>";
		}
		else{

			// not to remove spaces in such URL: '?Subject=This has spaces' // thanks to: Mark Carson
			$esc_url = esc_url( $url, null, 'not_display' );

			wp_redirect( $esc_url, 303 );
		}

		exit;
	}

	/**
	 * Parses the KCC URL.
	 *
	 * Конвертирует относительный путь "/blog/dir/file" в абсолютный
	 * (от корня сайта) и чистит УРЛ. Расчитан на прием грязных (неочищенных) URL.
	 */
	public function parse_kcc_url( string $kcc_url ): array {

		preg_match( '/\?(.+)$/', $kcc_url, $m ); // get kcc url query args
		$kcc_query = $m[1]; // parse_url( $kcc_url, PHP_URL_QUERY );

		// cut URL from $query, because - there could be query args (&) that is why cut it
		$split = preg_split( '/[&?]?'. self::COUNT_KEY .'=/', $kcc_query );
		$query = $split[0];
		$url   = self::replace_url_placeholders( $split[1] ); // can be base64 encoded

		if( ! $url ){
			return [];
		}

		// parse other query part
		parse_str( $query, $query_args );

		$url = preg_replace( '/#.*$/', '', $url ); // delete #anchor

		// if begin with single '/' add home_url()
		if( $url[0] === '/' && $url[1] !== '/' ){
			$url = rtrim( home_url(), '/' ) . $url;
		}

		// remove http, https protocol if it's current site url
		if( strpos( $url, $_SERVER['HTTP_HOST'] ) ){
			$url = self::del_http_protocol( $url );
		}

		// if begin with no '/' - it's not any type off url
		// disable url like '&foo=' or 'asdsad'
		if(
			! is_numeric( $url )
			&& $url[0] !== '/'
			&& ! preg_match( '~^(?:' . implode( '|', wp_allowed_protocols() ) . '):~', $url )
		) {
			return [];
		}

		$return = [
			self::COUNT_KEY => $url, // no esc_url()
			self::PID_KEY   => (int) ( $query_args[ self::PID_KEY ] ?? 0 ),
			// array_key_exists( 'download', $query_args ), // isset null не берет
			'download'      => (bool) ( $query_args['download'] ?? false ),
		];

		return apply_filters( 'click_counter__parse_kcc_url', $return );
	}

	public static function del_http_protocol( $url ){
		return preg_replace( '/https?:/', '', $url );
	}

	private function is_file( $url ){
		/**
		 * Allows to repalce {@see Counter::is_file()} method.
		 *
		 * @param bool $is_file
		 */
		$return = apply_filters( 'kcc_is_file', null );
		if( null !== $return ){
			return $return;
		}

		if( ! preg_match( '~\.([a-zA-Z0-9]{1,8})(?=$|\?.*)~', $url, $m ) ){
			return false;
		}

		$f_ext = $m[1];

		$not_supported_ext = [ 'html', 'htm', 'xhtml', 'xht', 'php' ];

		if( in_array( $f_ext, $not_supported_ext, true ) ){
			return false;
		}

		return true; // any other ext - is true
	}

	/**
	 * Retrieve title of a (local or remote) webpage.
	 */
	private function get_html_title( string $url ): string {

		// without protocol - //site.ru/foo
		if( '//' === substr( $url, 0, 2 ) ){
			$url = "http:$url";
		}

		$html = wp_remote_retrieve_body( wp_remote_get( $url ) );
		if( ! $html ){
			$html = @ file_get_contents( $url, false, null, 0, 10000 );
		}

		if( $html && preg_match( '@<title[^>]*>(.*?)</title>@is', $html, $mm ) ){
			return substr( trim( $mm[1] ), 0, 300 ); // ограничим на всякий
		}

		return '';
	}

	/**
	 * Gets the file size from the link.
	 *
	 * @return string Eg: `136.6 KB` or empty string if no size determined.
	 */
	private static function file_size( string $url ): string {

		//$url = urlencode( $url );
		$size = null;

		// direct. considers WP subfolder install
		$_home_url = self::del_http_protocol( home_url() );
		if( ! $size && ( false !== strpos( $url, $_home_url ) ) ){

			$path_part = str_replace( $_home_url, '', self::del_http_protocol( $url ) );
			$file = wp_normalize_path( ABSPATH . $path_part );
			// maybe WP in subfolder
			if( ! file_exists( $file ) ){
				$file = wp_normalize_path( dirname( ABSPATH ) . $path_part );
			}

			$size = @ filesize( $file );
		}

		// curl enabled
		if( ! $size && function_exists( 'curl_version' ) ){
			$size = self::curl_get_file_size( $url );
		}

		// get_headers
		if( ! $size && function_exists( 'get_headers' ) ){
			$headers = @ get_headers( $url, 1 );
			$size = @ $headers['Content-Length'];
		}

		$size = (int) $size;

		if( ! $size ){
			return '';
		}

		$i = 0;
		$type = [ "B", "KB", "MB", "GB" ];
		while( ( $size/1024 ) > 1 ){
			$size = $size/1024;
			$i++;
		}

		return substr( $size, 0, strpos( $size, '.' ) + 2 ) . ' ' . $type[ $i ];
	}

	/**
	 * Returns the size of a file without downloading it.
	 *
	 * @param string $url The location of the remote file to download. Cannot be null or empty.
	 *
	 * @return int The size of the file referenced by $url, or 0 if the size could not be determined.
	 */
	private static function curl_get_file_size( string $url ): int {

		// $url не может быть без протокола http
		if( preg_match( '~^//~', $url ) ){
			$url = "http:$url";
		}

		$curl = curl_init( $url );

		// Issue a HEAD request and follow any redirects.
		curl_setopt( $curl, CURLOPT_NOBODY, true );
		curl_setopt( $curl, CURLOPT_HEADER, true );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );

		$data = curl_exec( $curl );
		curl_close( $curl );

		if( ! $data ){
			return 0;
		}

		// http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
		// 200 - this is the right status code.
		// Don't forget that a reply may contain 301 redirects, so we are looking for the part of the reply with status 200.
		if( preg_match( "/HTTP\/1\.[01] (200).*Content-Length: (\d+)/s", $data, $match ) ){
			return (int) $match[2]; // Content-Length
		}

		return 0;
	}

	/**
	 * Gets data of already existing link from the database.
	 * Caches to a static variable, if it fails to get the link the cache is not set.
	 *
	 * @param string|int $kcc_url      URL or link ID, or kcc_URL.
	 * @param bool       $clear_cache  When you need to clear the link cache.
	 *
	 * @return object|void             NULL when the cache is cleared or if the data could not be retrieved.
	 */
	public function get_link( $kcc_url, $clear_cache = false ) {
		global $wpdb;

		static $cache;

		if( $clear_cache ){
			unset( $cache[ $kcc_url ] );

			return;
		}

		if( isset( $cache[ $kcc_url ] ) ){
			return $cache[ $kcc_url ];
		}

		// you can only use the cache with a reset in the right place.

		// if it is a direct link and not 'kcc_url'
		if( is_numeric( $kcc_url ) || false === strpos( $kcc_url, self::COUNT_KEY ) ){
			$link_url = $kcc_url;
		}
		// it is 'kcc_url'
		else{
			$parsed = $this->parse_kcc_url( $kcc_url );

			$link_url = $parsed[ self::COUNT_KEY ];
			$pid = $parsed[ self::PID_KEY ];
		}

		// the link ID is passed, not the URL
		if( is_numeric( $link_url ) ){
			$WHERE = $wpdb->prepare( 'link_id = %d', $link_url );
		}
		else{
			$in_post = ! empty( $pid ) ? $wpdb->prepare( ' AND in_post = %d', $pid ) : '';
			$WHERE = $wpdb->prepare( 'link_url = %s ', self::del_http_protocol( $link_url ) ) . $in_post;
		}

		$link_data = $wpdb->get_row( "SELECT * FROM $wpdb->kcc_clicks WHERE $WHERE" );

		if( $link_data ){
			$cache[ $kcc_url ] = $link_data;
		}

		return $link_data;
	}

	public function clear_link_cache( $kcc_url ) {
		$this->get_link( $kcc_url, $clear_cache = true );
	}

	private static function replace_url_placeholders( $url ) {
		return str_replace( [ self::URL_PLACEHOLDERS['?'], self::URL_PLACEHOLDERS['&'] ], [ '?', '&' ], $url );
	}

	private static function add_url_placeholders( $url ) {
		return str_replace( [ '?', '&' ], [ self::URL_PLACEHOLDERS['?'], self::URL_PLACEHOLDERS['&'] ], $url );
	}

}
