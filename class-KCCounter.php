<?php

class KCCounter {

	const OPT_NAME  = 'kcc_options';

	const COUNT_KEY = 'kcccount';

	const PID_KEY   = 'kccpid';

	public $opt;

	public static $q_symbol_alts = array(
		'?' => '__QUESTION__',
		'&' => '__AMPERSAND__'
	);

	protected $admin_access;

	public static $instance;

	public static function instance(){

		if( ! self::$instance )
			self::$instance = is_admin() ? new KCCounter_Admin() : new self;

		return self::$instance;
	}

	public function __construct(){
		global $wpdb;

		$this->opt = get_option( self::OPT_NAME, [] );

		// дополним недостающие
		foreach( $this->get_def_options() as $name => $val ){
			if( ! isset( $this->opt[ $name ] ) ){
				$this->opt[ $name ] = $val;
			}
		}

		// access
		// set it here in order to use in front
		$this->admin_access = apply_filters( 'kcc_admin_access', null );

		if( $this->admin_access === null ){
			$this->admin_access = current_user_can( 'manage_options' );

			if( ! $this->admin_access && ! empty( $this->opt['access_roles'] ) ){

				foreach( wp_get_current_user()->roles as $role ){

					if( in_array( $role, (array) $this->opt['access_roles'], 1 ) ){
						$this->admin_access = true;
						break;
					}
				}
			}
		}

		// set table name
		$wpdb->tables[] = 'kcc_clicks';
		$wpdb->kcc_clicks = $wpdb->prefix . 'kcc_clicks';

		// локализация
		//if( ($locale = get_locale()) && (substr($locale,0,3) !== 'en_') ) $res = load_textdomain('kcc', dirname(__FILE__) . '/lang/'. $locale . '.mo' );

		// Рабочая часть
		if( $this->opt['links_class'] )
			add_filter( 'the_content', [ $this, 'modify_links' ] );

		// admin_bar
		if( $this->opt['toolbar_item'] && $this->admin_access )
			add_action( 'admin_bar_menu', [ $this, 'add_toolbar_menu' ], 90 );

		add_action( 'wp_footer', [ $this, 'footer_js' ], 99 );
		//add_action( 'wp_footer', [ $this, 'enqueue_jquery_if_need' ], 0 );

		// добавляем шоткод загрузок
		add_shortcode( 'download', [ $this, 'download_shortcode' ] );

		// событие редиректа
		add_filter( 'init', [ $this, 'redirect' ], 0 );

		// Добавляем Виджет
		if( $this->opt['widget'] )
			require_once KCC_PATH . 'widget.php';
	}

	//public function enqueue_jquery_if_need(){
	//	wp_script_is( 'jquery', 'enqueued' ) || wp_enqueue_script( 'jquery' );
	//}

	static function alts_to_q_symbol( $url ){
		return str_replace( [ self::$q_symbol_alts['?'], self::$q_symbol_alts['&'] ], [ '?', '&' ], $url );
	}

	static function q_symbol_to_alts( $url ){
		return str_replace( [ '?', '&' ], [ self::$q_symbol_alts['?'], self::$q_symbol_alts['&'] ], $url );
	}

	# обавка для подсчета ссылок на всем сайте
	public function footer_js(){
		$kcc_url_patt = $this->get_kcc_url( '{url}', '{in_post}', '{download}' );

		ob_start();
		?>
		<!-- KCCounter -->
		<script>
		(function(){

			let kcckey  = '<?= self::COUNT_KEY ?>';
			let pidkey  = '<?= self::PID_KEY ?>';
			let urlpatt = '<?= $kcc_url_patt ?>';
			let aclass = '<?= $this->opt['links_class'] ?>';
			let q_symbol_alts__fn = function(url){
				return url
					.replace( /[?]/g, '<?= self::$q_symbol_alts['?'] ?>' )
					.replace( /[&]/g, '<?= self::$q_symbol_alts['&'] ?>' );
			};

			// hide ugly kcc url
			document.addEventListener( 'mouseover', function( ev ){
				let a = ev.target;

				// test `a[href*="'+ kcckey +'"]` selector
				if( 'A' !== a.tagName || a.href.indexOf( kcckey ) === -1 )
					return;

				let realurl  = a.href.match( new RegExp( kcckey +'=(.*)' ) )[1];

				if( realurl ){
					if( parseInt( realurl ) )
						realurl = '/#download'+ realurl;

					a.dataset.kccurl = a.href.replace( realurl, q_symbol_alts__fn(realurl) ); // before a.href !
					a.href = realurl;
				}

			});

			let clickEventHandler = function( ev ){
				let a = ev.target;

				if( 'A' !== ev.target.tagName )
					return;

				// already set before - `a[data-kccurl]`
				if( a.dataset.kccurl ){
					a.href = a.dataset.kccurl;
					return;
				}

				let href = a.href;

				// modified link
				if( href.indexOf( kcckey ) !== -1 ){
					let url = href.match( new RegExp( kcckey +'=(.*)' ) )[1];

					if( url ){
						if( !! parseInt(url) )
							url = '/#download'+ url;

						a.href = url;
						a.dataset.kccurl = href.replace( url, q_symbol_alts__fn(url) );

					}
				}
				// count class
				else if( a.classList.contains( aclass ) ){
					let pid  = a.dataset[ pidkey ] || '';

					let kccurl;
					kccurl = urlpatt.replace( '{in_post}', pid );
					kccurl = kccurl.replace( '{download}', ( a.dataset.kccdownload ? 1 : '' ) );
					kccurl = kccurl.replace( '{url}', q_symbol_alts__fn( href ) );

					a.dataset.kccurl = kccurl;
				}

				a.dataset.kccurl && ( a.href = a.dataset.kccurl );

			};

			document.addEventListener( 'click', clickEventHandler );
			document.addEventListener( 'mousedown', clickEventHandler );
			document.addEventListener( 'contextmenu ', clickEventHandler );

		})();
		</script>
		<?php

		$scr = ob_get_clean();
		$scr = preg_replace( '~[^:]//[^\n]+|[\t\n\r]~', '', $scr ); // remove: comments, \t\r\n
		$scr = preg_replace( '~[ ]{2,}~', ' ', $scr );

		echo "$scr\n";
	}

	public function get_def_options(){

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
			'links_class' => 'count',
			// проверять class в простых ссылках
			'add_hits'             => '',
			// may be: '', 'in_title' or 'in_plain' (for simple links)
			'in_post'              => 1,
			'hide_url'             => false,
			// прятать ссылку или нет?
			'widget'               => 1,
			// включить виджет для WordPress
			'toolbar_item'         => 1,
			// выводить ссылку на статистику в Админ баре?
			'access_roles'         => [],
			// Название ролей, кроме администратора, которым доступно упраление плагином.
			'url_exclude_patterns' => '',
			// подстроки. Если ссылка имеет указанную подстроку, то не считать клик на нее...
		];

		$array['download_tpl'] = trim( preg_replace( '~^\t{4}~m', '', $array['download_tpl'] ) );

		return $array;
	}

	# получает ссылку по которой будут считаться клики
	public function get_kcc_url( $url = '', $in_post = 0, $download = 0 ){

		// порядок имеет значение...
		$vars = [
			'download'      => $download,
			self::PID_KEY   => $in_post,
			self::COUNT_KEY => self::q_symbol_to_alts( $url ),
		];

		if( ! $this->opt['in_post'] )
			unset( $vars[ self::PID_KEY ] );

		$kcc_url_data = [];
		foreach( $vars as $key => $val ){
			if( $val )
				$kcc_url_data[] = $key .'='. trim($val);
		}

		$kcc_url = home_url() .'?'.  implode('&', $kcc_url_data );
		if( $this->opt['hide_url'] )
			$kcc_url = $this->hide_link_url( $kcc_url );

		return apply_filters( 'get_kcc_url', $kcc_url );
	}

	/**
	 * Прячет оригинальную ссылку под ID ссылки. Ссылка должна существовать в БД.
	 *
	 * @param string $kcc_url  URL плагина для подсчета ссылки.
	 *
	 * @return string URL со спрятанной ссылкой.
	 */
	public function hide_link_url( $kcc_url ){

		$parsed = $this->parce_kcc_url( $kcc_url );

		// не прячем если это простая ссылка или урл уже спрятан
		if( empty( $parsed['download'] ) || ( isset( $parsed[ self::COUNT_KEY ] ) && is_numeric( $parsed[ self::COUNT_KEY ] ) ) )
			return $kcc_url;

		// не прячем если ссылки нет в БД
		if( ! $link = $this->get_link( $kcc_url ) )
			return $kcc_url;

		return preg_replace( '~'. self::COUNT_KEY .'=.*~', self::COUNT_KEY ."=$link->link_id", $kcc_url );
	}

	# link in toolbar
	public function add_toolbar_menu( $toolbar ){

		$toolbar->add_menu( [
			'id'    => 'kcc',
			'title' => 'Click Counter',
			'href' => admin_url( 'admin.php?page=' . KCC_NAME ),
		] );
	}

	// COUNTING PART --------

	# add clicks by given url
	public function do_count( $kcc_url, $count = true ){
		global $wpdb;

		$parsed = is_array( $kcc_url ) ? $kcc_url : $this->parce_kcc_url( $kcc_url );

		$args = [
			'link_url'  => $parsed[ self::COUNT_KEY ], // заметка: без http протокола
			'in_post'   => (int) $parsed[ self::PID_KEY ],
			'downloads' => empty($parsed['download']) ? '' : 'yes',
			'kcc_url'   => $kcc_url,
			'count'     => $count,
		];

		$link_url = & $args['link_url'];
		$link_url = urldecode( $link_url ); // Mark Carson
		$link_url = self::del_http_protocol( $link_url );

		// do not count when the link of the current page is specified so as not to catch looping
		//if( false !== strpos( $link_url, $_SERVER['REQUEST_URI']) )
		//	return;

		// checks
		// can't be empty - must be url or attach ID
		if( empty( $link_url ) )
			return false;

		// can't contain self parameters - like: link&kcccount=
		$_pattern = '~[?&](?:download|'. self::COUNT_KEY .'|'. self::PID_KEY .')~';
		if( preg_match( $_pattern, $link_url ) ){
			return print '<h3>kcc error: download shortcode bad url: cant contain self parameters like: "link&kcccount="</h3>';
		}

		// exclude filter
		if( ! empty( $this->opt['url_exclude_patterns'] ) ){

			$excl_patts = array_map( 'trim', preg_split('/[,\n]/', $this->opt['url_exclude_patterns']) );

			foreach( $excl_patts as $patt ){
				// maybe regular expression
				if( $patt[0] === '/' && substr($patt, -1) === '/' ){
					if( preg_match( $patt, $link_url) )
						return false; // stop
				}
				// simple substring check
				else {
					if( false !== strpos($link_url, $patt) )
						return false; // stop
				}
			}
		}

		$WHERE = [];
		if( is_numeric( $link_url ) ){
			$WHERE[] = $wpdb->prepare( 'link_id = %d ', $link_url );
		}
		else {
			$WHERE[] = $wpdb->prepare( 'link_url = %s ', $link_url );

			if( $this->opt['in_post'] )
				$WHERE[] = $wpdb->prepare( 'in_post = %d', $args['in_post'] );
			if( $args['downloads'] )
				$WHERE[] = $wpdb->prepare( 'downloads = %s', $args['downloads'] );
		}

		$WHERE = implode( ' AND ', $WHERE );

		$curr_time = current_time('mysql');

		$sql = "UPDATE $wpdb->kcc_clicks SET link_clicks = (link_clicks + 1), last_click_date = '". $curr_time ."' WHERE $WHERE LIMIT 1";
		// $wpdb->prepare() can't be used, because of false will be returned if the link with encoded symbols is passed, for example, Cyrillic will have % symbol: /%d0%bf%d1%80%d0%b8%d0%b2%d0%b5%d1%82...

		// Kludge: update doubles...
		$more_links = $wpdb->get_results( "SELECT * FROM $wpdb->kcc_clicks WHERE $WHERE LIMIT 1,999" );
		if( $more_links ){

			$up_link_id = $wpdb->get_var( "SELECT link_id FROM $wpdb->kcc_clicks WHERE $WHERE LIMIT 1" );

			foreach( $more_links as $link ){
				$wpdb->query( "UPDATE $wpdb->kcc_clicks SET link_clicks = (link_clicks + 1) WHERE link_id = $up_link_id;" );
				$wpdb->query( "DELETE FROM $wpdb->kcc_clicks WHERE link_id = $link->link_id;" );
			}
		}

		// data of adding link
		$data = [];

		do_action_ref_array( 'kcc_count_before', [ $args, & $sql, & $data ] );

		// try to update
		$updated = $wpdb->query( $sql );

		// updated
		if( $updated ){
			$return = true;
		}
		// insert
		else {

			// create data to add to DB
			$data = array_merge( [
				'attach_id'        => 0,
				'in_post'          => $args['in_post'],
				// Для загрузок, когда запись добавляется просто при просмотре,
				// все равно добавляется 1 первый просмотр, чтобы добавить запись в бД
				'link_clicks'      => $args['count'] ? 1 : 0,
				'link_name' => untrailingslashit( $this->is_file( $link_url )
					? basename( $link_url )
					: preg_replace( '~^(https?:)?//|\?.*$~', '', $link_url ) ),
				'link_title'       => '', // устанавливается отдлеьно ниже
				'link_description' => '',
				'link_date'        => $curr_time,
				'last_click_date'  => $curr_time,
				'link_url'         => $link_url,
				'file_size'        => self::file_size( $link_url ),
				'downloads'        => $args['downloads'],
			], $data );

			// cyrillic domain
			if( false !== stripos( $data['link_name'], 'xn--') ){
				$host = parse_url( $data['link_url'], PHP_URL_HOST );

				require_once KCC_PATH .'php-punycode/idna_convert.class.php';
				$ind = new idna_convert();

				$data['link_name'] = str_replace( $host, $ind->decode($host), $data['link_name'] );
			}

			$title = & $data['link_title']; // easy life

			// is_attach?
			$_link_url_like = '%' . $wpdb->esc_like( $link_url ) . '%';
			$attach = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND guid LIKE %s", $_link_url_like
			) );
			if( $attach ){
				$title                    = $attach->post_title;
				$data['attach_id']        = $attach->ID;
				$data['link_description'] = $attach->post_content;
			}

			// get link_title from url
			if( ! $title ){
				if( $this->is_file($link_url) ){
					$title = preg_replace('~[.][^.]+$~', '', $data['link_name'] ); // delete ext
					$title = preg_replace('~[_-]~', ' ', $title);
					$title = ucwords( $title );
				}
				else {
					$title = $this->get_html_title( $link_url );
				}
			}

			// if title could not be determined
			if( ! $title )
				$title = $data['link_name'];

			$data = apply_filters( 'kcc_insert_link_data', $data, $args );

			$return = $wpdb->insert( $wpdb->kcc_clicks, $data ) ? $wpdb->insert_id : false;
		}

		do_action( 'kcc_count_after', $args, $updated, $data );

		$this->clear_link_cache( $kcc_url );

		return $return;
	}

	# redirect to link url
	public function redirect(){

		// to override a function
		if( apply_filters( 'kcc_redefine_redirect', false, $this ) )
			return;

		if( empty( $_GET[ self::COUNT_KEY ] ) || ! $url = $_GET[ self::COUNT_KEY ] )
			return;

		$parsed = $this->parce_kcc_url( $_SERVER['REQUEST_URI'] );
		if( ! $url = $parsed[ self::COUNT_KEY ] )
			return;

		// count
		if( apply_filters( 'kcc_do_count', true, $this ) )
			$this->do_count( $parsed );

		if( is_numeric($url) ){
			if( $link = $this->get_link( $url ) )
				$url = $link->link_url;
			else
				return trigger_error( sprintf('Error: kcc link with id %s not found.', $url) );
		}

		// redirect
		if( headers_sent() ){
			print "<script>location.replace('". esc_url($url) ."');</script>";
		}
		else {

			// not to remove spaces in such URL: '?Subject=This has spaces' // thanks to: Mark Carson
			$esc_url = esc_url( $url, null, 'not_display' );

			wp_redirect( $esc_url, 303 );
		}

		exit;
	}

	/**
	 * Разибирает KСС УРЛ.
	 *
	 * Конвертирует относительный путь "/blog/dir/file" в абсолютный (от корня сайта) и чистит УРЛ
	 * Расчитан на прием грязных/неочищенных URL.
	 *
	 * @param  string $kcc_url Kcc УРЛ.
	 * @return array параметры переданой строки
	 */
	public function parce_kcc_url( $kcc_url ){

		preg_match( '/\?(.+)$/', $kcc_url, $m ); // get kcc url query args
		$kcc_query = $m[1]; // parse_url( $kcc_url, PHP_URL_QUERY );

		// cut URL from $query, because - there could be query args (&) that is why cut it
		$split = preg_split( '/[&?]?'. self::COUNT_KEY .'=/', $kcc_query );
		$query = $split[0];
		$url   = self::alts_to_q_symbol( $split[1] ); // can be base64 encoded

		if( ! $url )
			return array();

		// parse other query part
		parse_str( $query, $query_args );

		$url = preg_replace( '/#.*$/', '', $url ); // delete #anchor

		// if begin with single '/' add home_url()
		if( $url[0] === '/' && $url[1] !== '/' )
			$url = rtrim( home_url(), '/' ) . $url;

		// remove http, https protocol if it's current site url
		if( strpos( $url, $_SERVER['HTTP_HOST'] ) )
			$url = self::del_http_protocol( $url );

		// if begin with no '/' - it's not any type off url
		// disable url like '&foo=' or 'asdsad'
		if(
			! is_numeric( $url )
			&& $url[0] !== '/'
			&& ! preg_match( '~^(?:' . implode( '|', wp_allowed_protocols() ) . '):~', $url )
		)
			return array();

		$return = [
			// no esc_url()
			self::COUNT_KEY => $url,
			self::PID_KEY   => (int) @ $query_args[ self::PID_KEY ],
			//array_key_exists('download', $query_args ), // isset null не берет
			'download'      => ! ! @ $query_args['download'],
		];

		return apply_filters( 'parce_kcc_url', $return );
	}

	static function del_http_protocol( $url ){
		return preg_replace( '/https?:/', '', $url );
	}

	public function is_file( $url ){

		// Allow to repalce `KCCounter::is_file()` method
		$return = apply_filters( 'kcc_is_file', null );
		if( null !== $return )
			return $return;

		if( ! preg_match( '~\.([a-zA-Z0-9]{1,8})(?=$|\?.*)~', $url, $m ) )
			return false;

		$f_ext = $m[1];

		$not_supported_ext = [ 'html', 'htm', 'xhtml', 'xht', 'php' ];

		if( in_array( $f_ext, $not_supported_ext ) )
			return false;

		return true; // any other ext - is true
	}

	/**
	 * Retrieve title of a (local or remote) webpage.
	 *
	 * @param  string $url URL title we get to
	 *
	 * @return string
	 */
	public function get_html_title( $url ){

		// without protocol - //site.ru/foo
		if( substr( $url, 0, 2 ) === '//' )
			$url = "http:$url";

		if( ! $html = wp_remote_retrieve_body( wp_remote_get( $url ) ) )
			$html = @ file_get_contents( $url, false, null, 0, 10000 );

		if( $html && preg_match( '@<title[^>]*>(.*?)</title>@is', $html, $mm ) )
			return substr( trim($mm[1]), 0, 300 ); // ограничим на всякий

		return '';
	}

	# Получает размер файла по сылке
	static function file_size( $url ){

		//$url = urlencode( $url );
		$size = null;

		// direct. considers WP subfolder install
		$_home_url = self::del_http_protocol( home_url() );
		if( ! $size && ( false !== strpos( $url, $_home_url ) ) ){

			$path_part = str_replace( $_home_url, '', self::del_http_protocol( $url ) );
			$file = wp_normalize_path( ABSPATH . $path_part );
			// maybe WP in subfolder
			if( ! file_exists( $file ) )
				$file = wp_normalize_path( dirname( ABSPATH ) . $path_part );

			$size = @ filesize( $file );
		}
		// curl enabled
		if( ! $size && function_exists('curl_version') ){
			$size = self::curl_get_file_size( $url );
		}
		// get_headers
		if( ! $size && function_exists('get_headers') ){
			$headers = @ get_headers( $url, 1 );
			$size = @ $headers['Content-Length'];
		}

		$size = (int) $size;

		if( ! $size )
			return 0;

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
	 * @param $url - The location of the remote file to download. Cannot
	 * be null or empty.
	 *
	 * @return int|false The size of the file referenced by $url, or false if the size
	 * could not be determined.
	 */
	static function curl_get_file_size( $url ){

		// $url не может быть без протокола http
		if( preg_match( '~^//~', $url ) )
			$url = "http:$url";

		$curl = curl_init( $url );
		// Issue a HEAD request and follow any redirects.
		curl_setopt( $curl, CURLOPT_NOBODY, true );
		curl_setopt( $curl, CURLOPT_HEADER, true );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );

		$data = curl_exec( $curl );
		curl_close( $curl );

		if( ! $data )
			return false;

		// http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
		// 200 - это нужный статус код
		// не забываем что ответ может содержать 301 редирект, потому ищем именно часть ответа со статусом 200
		if( preg_match("/HTTP\/1\.[01] (200).*Content-Length: (\d+)/s", $data, $match ) ){
			return (int) $match[2]; // Content-Length
		}

	}

	# TEXT REPLACEMENT PART -------------

	# change links that have special class in given content
	public function modify_links( $content ){

		if( false === strpos( $content, $this->opt['links_class'] ) )
			return $content;

		return preg_replace_callback( "@<a ([^>]*class=['\"][^>]*{$this->opt['links_class']}(?=[\s'\"])[^>]*)>(.+?)</a>@", [
			$this,
			'_make_html_link_cb',
		], $content );
	}

	# parse string to detect and process pairs of tag="value"
	public function _make_html_link_cb( $match ){
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
		$args[ 'data-'. self::PID_KEY ] = $post->ID;
		if( $this->opt['add_hits'] ){
			$link = $this->get_link( $args['href'] );

			if( $link && $link->link_clicks ){
				if( $this->opt['add_hits'] === 'in_title' ){
					$args['title'] = "(" . __( 'clicks:', 'kama-clic-counter' ) . " {$link->link_clicks})" . $args['title'];
				}
				else{
					$after = ( $this->opt['add_hits'] === 'in_plain' ) ? ' <span class="hitcounter">(' . __( 'clicks:', 'kama-clic-counter' ) . ' ' . $link->link_clicks . ')</span>' : '';
				}
			}
		}

		$link_attrs = '';
		foreach( $args as $key => $value ){
			$link_attrs .= sprintf( '%s="%s"', $key, esc_attr( $value ) );
		}

		$link_attrs = trim( $link_attrs );

		return "<a $link_attrs>$link_anchor</a>$after";
	}

	# gets a link to the icon image by the extension in the passed URL
	public function get_url_icon( $url ){

		$url_path = parse_url( $url, PHP_URL_PATH );

		if( preg_match( '~\.([a-zA-Z0-9]{1,8})(?=$|\?.*)~', $url_path, $m ) )
			$icon_name = $m[1] .'.png';
		else
			$icon_name = 'default.png';

		$icon_name  = file_exists( KCC_PATH . "icons/$icon_name") ? $icon_name : 'default.png';

		$icon_url = KCC_URL . "icons/$icon_name";

		return apply_filters( 'get_url_icon', $icon_url, $icon_name );
	}

	public function download_shortcode( $atts = [] ){
		global $post;

		// белый список параметров и значения по умолчанию
		$atts = shortcode_atts( [
			'url'   => '',
			'title' => '',
			'desc'  => '',
		], $atts );

		if( ! $atts['url'] )
			return '[download]';

		$kcc_url = $this->get_kcc_url( $atts['url'], $post->ID, 1 );

		// записываем данные в БД
		$link = $this->get_link( $kcc_url );

		if( ! $link ){
			$this->do_count( $kcc_url, $count = false ); // для проверки, чтобы не считать эту операцию
			$link = $this->get_link( $kcc_url );
		}

		$tpl = $this->opt['download_tpl'];
		$tpl = str_replace('[link_url]', esc_url($kcc_url), $tpl );

		if( $atts['title'] ) $tpl = str_replace('[link_title]',       $atts['title'], $tpl );
		if( $atts['desc'] )  $tpl = str_replace('[link_description]', $atts['desc'],  $tpl );

		return $this->tpl_replace_shortcodes( $tpl, $link );
	}

	/**
	 * Заменяет шоткоды в шаблоне на реальные данные
	 * @param  string $tpl  Шаблон для замены в нем данных
	 * @param  object $link данные ссылки из БД
	 * @return string HTML код блока - замененный шаблон
	 */
	public function tpl_replace_shortcodes( $tpl, $link ){

		$tpl = str_replace( [ '[icon_url]', '[edit_link]' ], [
			$this->get_url_icon( $link->link_url ),
			$this->edit_link_url( $link->link_id )
		], $tpl );

		if( preg_match( '@\[link_date:([^\]]+)\]@', $tpl, $date ) )
			$tpl = str_replace( $date[0], apply_filters('get_the_date', mysql2date($date[1], $link->link_date) ), $tpl );

		// меняем все остальные шоткоды
		preg_match_all( '@\[([^\]]+)\]@', $tpl, $match );
		foreach( $match[1] as $data ){
			$tpl = str_replace( "[$data]", $link->$data, $tpl );
		}

		return $tpl;
	}

	/**
	 * Получает данные уже существующие ссылки из БД.
	 * Кэширует в static переменную, если не удалось получить ссылку кэш не устанавливается.
	 *
	 * @param  string/int  $kcc_url      URL или ID ссылки, или kcc_URL
	 * @param  boolean     $clear_cache  Когда нужно очистить кэш ссылки.
	 * @return object/null               null при очистке кэша или если не удалось получить данные.
	 */
	public function get_link( $kcc_url, $clear_cache = false ){
		global $wpdb;

		static $cache;

		if( $clear_cache ){
			unset( $cache[$kcc_url] );
			return;
		}

		if( isset($cache[ $kcc_url ]) )
			return $cache[ $kcc_url ];

		// тут кэш юзать можно только со сбросом в нужном месте...

		// if it is a direct link and not 'kcc_url'
		if( is_numeric($kcc_url) || false === strpos( $kcc_url, self::COUNT_KEY ) ){
			$link_url = $kcc_url;
		}
		// it is 'kcc_url'
		else {
			$parsed   = $this->parce_kcc_url( $kcc_url );

			$link_url = $parsed[ self::COUNT_KEY ];
			$pid      = $parsed[ self::PID_KEY ];
		}

		// the link ID is passed, not the URL
		if( is_numeric($link_url) ){
			$WHERE = $wpdb->prepare( 'link_id = %d', $link_url );
		}
		else {
			$in_post = @ $pid ? $wpdb->prepare(' AND in_post = %d', $pid ) : '';
			$WHERE = $wpdb->prepare( 'link_url = %s ', self::del_http_protocol( $link_url ) ) . $in_post;
		}

		$link_data = $wpdb->get_row( "SELECT * FROM $wpdb->kcc_clicks WHERE $WHERE" );

		if( $link_data )
			$cache[ $kcc_url ] = $link_data;

		return $link_data;
	}

	public function clear_link_cache( $kcc_url ){
		$this->get_link( $kcc_url, 'clear_cache' );
	}

	# returns the URL on the edit links in the admin
	public function edit_link_url( $link_id, $edit_text = '' ){

		if( ! $this->admin_access )
			return '';

		return sprintf( '<a class="kcc-edit-link" href="%s">%s</a>',
			admin_url( 'admin.php?page=' . KCC_NAME . '&edit_link=' . $link_id ), ( $edit_text ?: '✎' )
		);
	}


}
