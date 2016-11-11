<?php
/**
 * Translates javascripts
 *
 * @package ThemeFramework\Localize-JS
 */
function _appthemes_localize_theme_scripts() {

	// jQuery Colorbox
	wp_register_script( 'colorbox-lang', APP_THEME_FRAMEWORK_URI . '/js/colorbox/jquery.colorbox-lang.js', array( 'colorbox' ) );
	wp_localize_script( 'colorbox-lang', 'colorboxL10n', array(
		'current' =>        __( 'image {current} of {total}', APP_TD ),
		'previous' =>       __( 'previous', APP_TD ),
		'next' =>           __( 'next', APP_TD ),
		'close' =>          __( 'close', APP_TD ),
		'xhrError' =>       __( 'This content failed to load.', APP_TD ),
		'imgError' =>       __( 'This image failed to load.', APP_TD ),
		'slideshowStart' => __( 'start slideshow', APP_TD ),
		'slideshowStop' =>  __( 'stop slideshow', APP_TD ),
	) );

}
