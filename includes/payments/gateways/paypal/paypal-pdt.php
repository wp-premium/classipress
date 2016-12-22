<?php
/**
 * Helps process PayPal PDT Payments
 *
 * @package Components\Payments\Gateways\PayPal
 */
class APP_Paypal_PDT{

	/**
	 * Checks whether the current response is a valid transaction key
	 * @param  APP_Order  $order   Order being processed
	 * @param  array  $options     User inputted options
	 * @return boolean              True if transaction key is valid, false if invalid
	 */
	static public function get_transaction( $transaction_key, $identity_token, $sandbox = false ){

		$data = array(
			'cmd' => '_notify-synch',
			'tx' => $transaction_key,
			'at' => $identity_token,
		);

		$url = APP_PayPal::get_request_url();
		$options = array(
			'method' => 'POST',
			'body' => $data,
			'sslverify' => false,
			'httpversion' => '1.1',
		);

		$response =  self::get_url( $url, $options );
		if( strpos( $response, 'SUCCESS' ) !== 0 ){
			return false;
		}

		$values = array();
		$lines = explode( "\n", $response );

		foreach($lines as $string){

			$key_value_string = explode( '=', $string );

			if( array_key_exists(1, $key_value_string ) )
				$value = $key_value_string[1];
			else
				$value = '';

			$values[ $key_value_string[0] ] = urldecode( $value );

		}

		return $values;

		wp_update_post( array(
			"ID" => $order->get_id(),
			"post_content" => $transaction_id
		));

	}


	/**
	 * Checks if this request is in a format PDT can handle
	 * @return boolean True if handlable, false otherwise
	 */
	static public function can_be_handled(){
		return isset( $_GET['tx'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'paypal');
	}

	/**
	 * Returns the body of a requested URL
	 * @param  string $url     The URL to grab
	 * @param  array  $options The data to send to the site
	 * @return string|bool     The response to the request, boolean False if request failed
	 */
	static private function get_url( $url, $options ){
		$response = wp_remote_post( $url, $options );

		if ( is_wp_error( $response ) )
			return false;

		return $response['body'];
	}

}
