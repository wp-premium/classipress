<?php
/**
 * Paypal IPN listener
 *
 * @package Components\Payments\Gateways\PayPal
 */
class APP_PayPal_IPN_Listener{

	const QUERY_VAR = 'paypal_ipn';

	private $callback;

	public function __construct( $callback ){
		$this->callback = $callback;
		$this->listen();
	}

	public function listen(){

		if( !isset( $_GET[ self::QUERY_VAR ] ) )
			return;

		$passphrase = get_option( 'paypal_listener_passphrase', false );
		if( ! $passphrase ){
	    		return;
		}

		if( $_GET[ self::QUERY_VAR ] != $passphrase ){
	        	return;
		}

		if( ! self::validate_request() )
			return;

		call_user_func( $this->callback, $_POST );

		die;

	}

	public function validate_request(){

		$paypal_url = APP_PayPal::get_request_url();

		$received_values = array( 'cmd' => '_notify-validate' );
		$received_values += stripslashes_deep( $_POST );

		$params = array(
			'body' => $received_values,
			'sslverify' => false,
			'timeout' => 60,
			'httpversion' => '1.1',
			'user-agent' => 'AppThemes/' . get_bloginfo( 'version' ),
		);

		$response = wp_remote_post( $paypal_url, $params );

		if ( is_wp_error( $response ) )
			return false;

		if ( $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			if ( preg_match( "/VERIFIED/s", $response['body'] ) )
				return true;
		}

		return false;
	}

	public static function get_listener_url(){

		$passphrase = get_option( 'paypal_listener_passphrase', false );
		if ( ! $passphrase ) {
			$passphrase = md5( site_url() . time() );
			update_option( 'paypal_listener_passphrase', $passphrase );
		}

		return add_query_arg( self::QUERY_VAR, $passphrase, site_url( '/' ) );
	}

}
