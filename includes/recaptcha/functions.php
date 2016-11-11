<?php
/**
 * Google reCaptcha.
 *
 * Helper Functions.
 *
 * @package Components\ReCaptcha
 */

/**
 * Displays the Google ReCaptcha.
 */
function appthemes_display_recaptcha() {

	if ( ! current_theme_supports( 'app-recaptcha' ) ) {
		return;
	}

	APP_Recaptcha::display();
}

/**
 * Enqueue script dependencies.
 */
function appthemes_enqueue_recaptcha_scripts() {
	if ( ! current_theme_supports( 'app-recaptcha' ) ) {
		return;
	}

	add_action( 'wp_enqueue_scripts', array( 'APP_Recaptcha', 'enqueue_scripts' ) );
}

/**
 * Verifies the user response token.
 *
 * @param  string $response  The user response token.
 * @param  string $remote_ip The users IP address.
 * @return boolean|WP_Error  True on success, WP_Error object on failure.
 */
function appthemes_recaptcha_verify( $response = '', $remote_ip = '' ) {

	if ( ! $response ) {

		// Skip early if the response var does not exist.
		if ( ! isset( $_POST['g-recaptcha-response'] ) ) {
			return true;
		}
		$response = $_POST['g-recaptcha-response'];
	}

	if ( ! $remote_ip ) {
		$remote_ip = $_SERVER['REMOTE_ADDR'];
	}

	return APP_Recaptcha::verify( $response, $remote_ip );
}
