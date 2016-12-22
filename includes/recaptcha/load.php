<?php
/**
 * Google reCaptcha Load.
 *
 * @package Components\ReCaptcha
 */

add_action( 'after_setup_theme', '_appthemes_load_recaptcha', 999 );


/**
 * Adds support for Google reCaptcha.
 */
 function _appthemes_load_recaptcha() {

	if ( ! current_theme_supports( 'app-recaptcha' ) ) {
		return;
	}

    // Skip if theme still uses old reCaptcha library.

	// @TODO: remove after themes migrate to ReCaptcha 2.0.
	if ( ! empty( $options['file'] ) && file_exists( $options['file']  ) ) {
        return;
    }

	list( $args ) = get_theme_support( 'app-recaptcha' );

	$site_key    = $args['public_key'];
	$private_key = $args['private_key'];

	unset( $args['private_key'] );
	unset( $args['public_key'] );

	require dirname( __FILE__ ) . '/class-recaptcha.php';
	require dirname( __FILE__ ) . '/functions.php';

	// Init new ReCaptcha.
	APP_Recaptcha::init( $site_key, $private_key, $args );

}
