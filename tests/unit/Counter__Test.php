<?php

namespace KamaClickCounter;

use Closure;
use ReflectionClass;

class Counter__Test extends KCCTestCase {

	/**
	 * @covers Counter::parse_kcc_url()
	 * @covers Counter::replace_url_placeholders()
	 * @covers Counter::del_http_protocol()
	 */
	public function test__parse_kcc_url(): void {
		$counter = ( new ReflectionClass( Counter::class ) )->newInstanceWithoutConstructor();

		// numeric_id
		$this->assertSame(
			$counter->parse_kcc_url( '/?kcccount=123' ),
			[ 'kcccount' => '123', 'kccpid' => 0, 'download' => false ]
		);

		// numeric_id with download
		$this->assertSame(
			$counter->parse_kcc_url( 'http://kcc.loc/foo/?download=&kccpid=4840&kcccount=https://ru.wix.com/' ),
			[ 'kcccount' => 'https://ru.wix.com/', 'kccpid' => 4840, 'download' => false ]
		);

		// placeholders
		$q = Counter::URL_PLACEHOLDERS['?'];
		$a = Counter::URL_PLACEHOLDERS['&'];
		$this->assertSame(
			$counter->parse_kcc_url( "/?kccpid=7&download=1&kcccount=https://ex.com{$q}a=1{$a}b=2" ),
			[ 'kcccount' => 'https://ex.com?a=1&b=2', 'kccpid' => 7, 'download' => true ]
		);

		// protocol_relative
		$this->assertSame(
			$counter->parse_kcc_url( '/?kcccount=//ext.site/path' ),
			[ 'kcccount' => '//ext.site/path', 'kccpid' => 0, 'download' => false ]
		);

		// same_host_strip_protocol
		$this->assertSame(
			$counter->parse_kcc_url( '/?kcccount=https://kcc.loc/files/doc.pdf' ),
			[ 'kcccount' => '//kcc.loc/files/doc.pdf', 'kccpid' => 0, 'download' => false ]
		);

		// kccpid inside kcccount with anchor (that will be deleted)
		$this->assertSame(
			$counter->parse_kcc_url( '/?kcccount=https://kcc.loc/foo/bar?x=1#anchor&kccpid=9' ),
			[ 'kcccount' => '//kcc.loc/foo/bar?x=1', 'kccpid' => 0, 'download' => false ]
		);

		// cut hashtag
		$this->assertSame(
			$counter->parse_kcc_url( '/?kcccount=https://other.tld/path?q=1#anchor_hash' ),
			[ 'kcccount' => 'https://other.tld/path?q=1', 'kccpid' => 0, 'download' => false ]
		);

		// relative_path_prepends_home
		$this->assertSame(
			$counter->parse_kcc_url( '/?kcccount=/foo/bar?x=1' ),
			[ 'kcccount' => '//kcc.loc/foo/bar?x=1', 'kccpid' => 0, 'download' => false ]
		);

		// download_flag_variants
		$this->assertSame(
			$counter->parse_kcc_url( '/?download=1&kcccount=https://ex.com' ),
			[ 'kcccount' => 'https://ex.com', 'kccpid'   => 0, 'download' => true ]
		);
		$this->assertSame(
			$counter->parse_kcc_url( '/?download=&kcccount=https://ex.com' ),
			[ 'kcccount' => 'https://ex.com', 'kccpid' => 0, 'download' => false ]
		);

		// invalid_returns_empty_array
		$this->assertSame( $counter->parse_kcc_url( '/?kcccount=' ), [] );
		$this->assertSame( $counter->parse_kcc_url( '/?kcccount=asdasd' ), [] );
		$this->assertSame( $counter->parse_kcc_url( '/?kcccount=&foo=1' ), [] );
	}

	/**
	 * @covers Counter::is_file()
	 */
	public function test__is_file(): void {
		$counter = ( new ReflectionClass( Counter::class ) )->newInstanceWithoutConstructor();

		$is_file = Closure::bind( fn( $url ) => $this->is_file( $url ), $counter, Counter::class );

		/// true - valid file extensions (case-insensitive), with query, protocol-relative
		$this->assertTrue( $is_file( 'https://ex.com/file.pdf' ) );
		$this->assertTrue( $is_file( '/wp-content/uploads/file.JPG' ) );
		$this->assertTrue( $is_file( 'https://ex.com/archive.tar.gz' ) );
		$this->assertTrue( $is_file( 'https://ex.com/file.zip?download=1' ) );
		$this->assertTrue( $is_file( '//cdn.ex.com/img.webp' ) );

		/// false - html/php pages, no ext, long ext, schemes without file
		//$this->assertFalse( $is_file( 'mailto:test@example.com' ) ); // TODO fix this case
		$this->assertFalse( $is_file( 'https://ex.com/page.html' ) );
		$this->assertFalse( $is_file( 'https://ex.com/index.php' ) );
		$this->assertFalse( $is_file( 'https://ex.com/path/' ) );
		$this->assertFalse( $is_file( 'https://ex.com/file.longextension' ) );
		$this->assertFalse( $is_file( 'https://ex.com/file' ) );

		/// filter override - force true
		add_filter( 'kcc_is_file', static fn() => true );
		$this->assertTrue( $is_file( 'https://ex.com/page.html' ) );
		remove_all_filters( 'kcc_is_file' ); // clean

		/// filter override - force false
		add_filter( 'kcc_is_file', static fn() => false );
		$this->assertFalse( $is_file( 'https://ex.com/file.pdf' ) );
		remove_all_filters( 'kcc_is_file' );
	}

	private function new_counter( array $options ) {
		static $get_option;
		$get_option || $get_option = \WP_Mock::userFunction( 'get_option' );
		$new_counter = static function ( $args ) use ( $get_option ) {
			$get_option->andReturn( $args );
			return new Counter( new Options() );
		};

		return $new_counter( [ 'in_post' => true, 'hide_url' => false, ] );
	}

	private function new_counter_cb(): Closure {
		$get_option = \WP_Mock::userFunction( 'get_option' );
		return static function ( $args ) use ( $get_option ) {
			$get_option->andReturn( $args );
			return new Counter( new Options() );
		};
	}

	/**
	 * @covers Counter::get_kcc_url()
	 * @covers Counter::add_url_placeholders()
	 */
	public function test__get_kcc_url(): void {
		/** @var Counter $counter */
		$new_counter = $this->new_counter_cb();

		// basic - with pid and download, order: download, kccpid, kcccount
		$counter = $new_counter( [ 'in_post' => true, 'hide_url' => false, ] );
		$this->assertSame(
			'http://kcc.loc?download=1&kccpid=77&kcccount=https://ex.com__QUESTION__a=1__AMPERSAND__b=2',
			$counter->get_kcc_url( 'https://ex.com?a=1&b=2', '77', '1' )
		);

		// empty params trimmed - only kcccount remains
		$this->assertSame(
			'http://kcc.loc?kcccount=https://ex.com',
			$counter->get_kcc_url( 'https://ex.com', '', '' )
		);

		// in_post disabled - kccpid omitted
		$counter = $new_counter( [ 'in_post' => false, 'hide_url' => false, ] );
		$this->assertSame(
			'http://kcc.loc?download=1&kcccount=https://ex.com__QUESTION__a=1',
			$counter->get_kcc_url( 'https://ex.com?a=1', '999', '1' )
		);

		// hide_url=true but no download - no hiding occurs, original returned
		$counter = $new_counter( [ 'in_post' => true, 'hide_url' => true, ] );
		$this->assertSame(
			'http://kcc.loc?kccpid=5&kcccount=https://cdn.tld/file.zip',
			$counter->get_kcc_url( 'https://cdn.tld/file.zip', '5', '' )
		);

		// filter applied
		add_filter( 'get_kcc_url', static fn( $url ) => "$url&x=1", 10, 2 );
		$this->assertSame(
			'http://kcc.loc?kccpid=1&kcccount=https://ex.com&x=1',
			$counter->get_kcc_url( 'https://ex.com', '1', '' )
		);
		remove_all_filters( 'get_kcc_url' );
	}

	/**
	 * @covers Counter::is_url_in_exclude_list()
	 */
	public function test__is_url_in_exclude_list(): void {
		/** @var Counter $counter */
		$new_counter = $this->new_counter_cb();

		// empty patterns -> false
		$counter = $new_counter( [ 'url_exclude_patterns' => '' ] );
		$call = ( fn( $url ) => $this->is_url_in_exclude_list( $url ) )->bindTo( $counter, Counter::class );
		$this->assertFalse( $call( 'https://example.com/file.pdf' ) );

		// simple substring match
		$counter = $new_counter( [ 'url_exclude_patterns' => 'example.com' ] );
		$call = ( fn( $url ) => $this->is_url_in_exclude_list( $url ) )->bindTo( $counter, Counter::class );
		$this->assertTrue( $call( 'https://example.com/path' ) );
		$this->assertFalse( $call( 'https://another.com/path' ) );

		// regex match
		$counter = $new_counter( [ 'url_exclude_patterns' => '/skip-this-path/' ] );
		$call = ( fn( $url ) => $this->is_url_in_exclude_list( $url ) )->bindTo( $counter, Counter::class );
		$this->assertTrue( $call( 'https://site.tld/a/skip-this-path/b' ) );
		$this->assertFalse( $call( 'https://site.tld/a/keep-this-path/b' ) );

		// mixed comma + newline, trimming
		$counter = $new_counter( [ 'url_exclude_patterns' => "  example.net  ,\n   /\\.(?:zip|pdf)$/ ,   foo " ] );
		$call = ( fn( $url ) => $this->is_url_in_exclude_list( $url ) )->bindTo( $counter, Counter::class );
		$this->assertTrue( $call( 'https://example.net/page' ) );              // substring
		$this->assertTrue( $call( 'https://cdn.tld/file.pdf' ) );              // regex .pdf
		$this->assertTrue( $call( 'https://bar.tld/path/to/foo/resource' ) );  // substring foo
		$this->assertFalse( $call( 'https://cdn.tld/file.PDF' ) );             // regex case-insensitive? - no i - should be false
		$this->assertFalse( $call( 'https://bar.tld/path/to/bar/resource' ) );

		// multiple mixed sample from code comment
		$counter = $new_counter( [ 'url_exclude_patterns' => 'example.com, /skip-this-path/, .pdf ' ] );
		$call = ( fn( $url ) => $this->is_url_in_exclude_list( $url ) )->bindTo( $counter, Counter::class );
		$this->assertTrue( $call( 'https://example.com/x' ) );
		$this->assertTrue( $call( 'https://s.tld/skip-this-path/file' ) );
		$this->assertTrue( $call( 'https://s.tld/docs/file.pdf' ) );
		$this->assertFalse( $call( 'https://s.tld/docs/file.doc' ) );
	}

}
