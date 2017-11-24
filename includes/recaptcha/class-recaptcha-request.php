<?php
/**
 * Google reCaptcha.
 *
 * @reference https://developers.google.com/recaptcha/intro
 * @reference https://developers.google.com/recaptcha/docs/display
 *
 * @package Components\reCaptcha
 */


/**
 * Sends WP Remote request to the Google reCaptcha service.
 */
class APP_Recaptcha_Request implements gReCaptcha_RequestMethod {

	/**
	 * URL to which requests are sent.
	 * @const string
	 */
	const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';


	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {

	}


	/**
	 * Submit the cURL request with the specified parameters.
	 *
	 * @param gReCaptcha_RequestParameters $params Request parameters
	 *
	 * @return string Body of the reCAPTCHA response
	 */
	public function submit( gReCaptcha_RequestParameters $params ) {

		$args = array(
			'body' => $params->toArray(),
		);

		$response = wp_remote_post( self::SITE_VERIFY_URL, $args );
		if ( is_wp_error( $response ) ) {
			return $this->error_response( $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return $this->error_response( __( 'Invalid response code.', APP_TD ) );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return $this->error_response( __( 'Invalid response message.', APP_TD ) );
		}

		return $body;
	}


	/**
	 * Prepares error response for the reCaptcha library.
	 *
	 * @param $message Error message
	 *
	 * @return string Error response
	 */
	public function error_response( $message ) {
		$error = array(
			'error-codes' => array( $message ),
		);

		return wp_json_encode( $error );
	}


}
