<?php

// Backward compatibility with -3.5.0 version

add_action( 'plugins_loaded', 'kcc_backward_compatibility', 20 );

function kcc_backward_compatibility(){
	KCC::$inst = & KCCounter::$instance;
	KCClick::$inst = & KCCounter::$instance;
}

// version -3.5.0 - for class name KCC
if( ! class_exists( 'KCC' ) ){

	final class KCC {

		const OPT_NAME = KCCounter::OPT_NAME;
		const COUNT_KEY = KCCounter::COUNT_KEY;
		const PID_KEY = KCCounter::PID_KEY;

		static $inst;

		public function __get( $name ){
			if( property_exists( 'KCCounter', $name ) ){
				return KCCounter()->$name;
			}
		}

		public function __call( $name, $arguments ){
			if( method_exists( 'KCCounter', $name ) ){
				return KCCounter()->$name( $arguments );
			}
		}

		public static function __callStatic( $name, $arguments ){
			if( method_exists( 'KCCounter', $name ) ){
				return KCCounter::$name( $arguments );
			}
		}
	}
}

// for class name KCClick
if( ! class_exists( 'KCClick' ) ){

	final class KCClick {

		const OPT_NAME = KCCounter::OPT_NAME;
		const COUNT_KEY = KCCounter::COUNT_KEY;
		const PID_KEY = KCCounter::PID_KEY;

		static $inst;

		public function __get( $name ){
			if( property_exists( 'KCCounter', $name ) ){
				return KCCounter()->$name;
			}
		}

		public function __call( $name, $arguments ){
			if( method_exists( 'KCCounter', $name ) ){
				return KCCounter()->$name( $arguments );
			}
		}

		public static function __callStatic( $name, $arguments ){
			if( method_exists( 'KCCounter', $name ) ){
				return KCCounter::$name( $arguments );
			}
		}
	}
}