<?php

namespace KamaClickCounter;

spl_autoload_register(
	static function( $class ) {
		if( 0 !== strpos( $class, __NAMESPACE__ ) ){
			return;
		}

		$rel_path = str_replace( [ __NAMESPACE__, '\\' ], [ 'src', '/' ], $class );

		require_once __DIR__ . "/$rel_path.php";
	}
);
