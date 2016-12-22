<?php
/**
 * Paypal notifier
 *
 * @package Components\Payments\Gateways\PayPal
 */
class APP_PayPal_Notifier{

	private static $callbacks = array(
		'—' => array(),
		'adjustment' => array(),
		'cart' => array(),
		'express_checkout' => array(),
		'masspay' => array(),
		'mp_signup' => array(),
		'merch_pmt' => array(),
		'new_case' => array(),
		'recurring_payment' => array(),
		'recurring_payment_expired' => array(),
		'recurring_payment_profile_created' => array(),
		'recurring_payment_skipped' => array(),
		'send_money' => array(),
		'subcr_cancel' => array(),
		'subcr_eot' => array(),
		'subcr_failed' => array(),
		'subcr_modify' => array(),
		'subcr_payment' => array(),
		'subcr_signup' => array(),
		'web_accept' => array(),
	);

	public static function register( $type, $callback ){
		self::$callbacks[ $type ][] = $callback;
	}

	public static function handle_response( $response, $order_limiter = '' ){

		if( !isset( $response['txn_type'] ) ){
			return false;
		}

		$type = strtolower( $response['txn_type'] );

		do_action( 'appthemes_paypal_ipn_register_callbacks' );

		if( empty( self::$callbacks[ $type ] ) )
			return;

		foreach( self::$callbacks[ $type ] as $callback ){
			call_user_func( $callback, $response );
		}

	}

}
