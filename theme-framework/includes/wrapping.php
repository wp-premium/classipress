<?php
/**
 * Automatically pass all templates through a wrapper.php template.
 *
 * @package ThemeFramework\Wrapping
 */

/**
 * Retrieves Path to the template file
 *
 * @return string Path to the template file
 */
function app_template_path() {
	return APP_Wrapping::get_main_template();
}

function app_template_base() {
	return APP_Wrapping::get_base();
}


class APP_Wrapping {

	private static $main_template;
	private static $base;

	static function wrap( $template ) {
		self::$main_template = $template;

		self::$base = substr( basename( self::$main_template ), 0, -4 );

		if ( 'index' == self::$base ) {
			self::$base = false;
		}

		$templates = array( 'wrapper.php' );

		if ( self::$base ) {
			array_unshift( $templates, sprintf( 'wrapper-%s.php', self::$base ) );
		}

		return locate_template( $templates );
	}

	static function get_main_template() {
		return self::$main_template;
	}

	static function get_base() {
		return self::$base;
	}
}

add_filter( 'template_include', array( 'APP_Wrapping', 'wrap' ), 99 );

