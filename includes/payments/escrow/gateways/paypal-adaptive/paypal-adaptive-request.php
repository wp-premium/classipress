<?php

class APP_PayPal_Adaptive_Request {

	/**
	 * Sandbox generic APP ID.
	 */
	const SANDBOX_APPID = 'APP-80W284485P519543T';

	/**
	 * Used for chained payments. Delays payments to the secondary receivers.
	 * Only the payment to the primary receiver is processed.
	 * https://developer.paypal.com/webapps/developer/docs/classic/api/adaptive-payments/Pay_API_Operation/
	 */
	const ACTION_TYPE = 'PAY_PRIMARY';

	/**
	 * Secondary receivers email meta key.
	 */
	const EMAIL_META_KEY = 'pp_adaptive_paypal_email';

	/**
	 * Array contanining all the required options.
	 * @var array
	 */
	protected $options;

	/**
	 * Sandbox and live endpoints.
	 * @var array
	 */
	protected static $endpoint_url = array(
		'sandbox' => 'https://svcs.sandbox.paypal.com/AdaptivePayments/',
		'live' => 'https://svcs.paypal.com/AdaptivePayments/',
	);

	public function __construct( $options ) {
		$this->set_options( $options );
	}

	/**
	 * Transfers the funds form the sender to the primary receiver.
	 *
	 * @param type $order
	 * @param type $return_url
	 * @param type $cancel_url
	 * @return type
	 */
	public function pay( $order, $return_url, $cancel_url ) {

		$item = $order->get_item();

		$memo = '';
		if ( ! empty( $item['post'] ) ) {
			$memo = sprintf( __( '[%1$s] Funds for "%2$s" (Order ID #%3$s)', APP_TD ), get_bloginfo( 'name' ), $item['post']->post_title, $order->get_id() );
		}

		$create_packet = array(
			'actionType'         => self::ACTION_TYPE,
			'clientDetails'      => array( 'applicationId' => $this->options['appID'], 'ipAddress' => $_SERVER['SERVER_ADDR'] ),
			'feesPayer'          => $this->options['fees_payer'],
			'currencyCode'       => $order->get_currency(),
			'receiverList'       => array( 'receiver' => $this->get_receivers( $order ) ),
			'returnUrl'          => html_entity_decode( $return_url ),
			'cancelUrl'          => html_entity_decode( $cancel_url ),
			//'ipnNotificationUrl' => APP_PayPal_IPN_Listener::get_listener_url(), // @todo maybe add IPN listener
			'requestEnvelope'    => $this->get_envelope(),
			'memo'	             => $memo,
			'trackingId'	     => $order->get_id() . '+' . current_time('timestamp'),
		);

		return $this->request( $order, $create_packet, 'Pay' );
	}

	/**
	 * Transfers part or the whole funds on the primary receiver account to the secondary receivers.
	 *
	 * @param APP_Escrow_Order $order
	 * @param type $pay_key
	 * @return type
	 */
	public function refund( APP_Escrow_Order $order, $pay_key ) {
		$data = array(
		  'requestEnvelope' => $this->get_envelope(),
		  'payKey' => $pay_key
		);
		return $this->request( $order, $data, 'Refund' );
	}

	/**
	 * Transfers the funds back to the sender.
	 *
	 * @param APP_Escrow_Order $order
	 * @param type $pay_key
	 * @return type
	 */
	public function execute_payment( APP_Escrow_Order $order, $pay_key ) {
		$data = array(
		  'requestEnvelope' => $this->get_envelope(),
		  'payKey' => $pay_key
		);
		return $this->request( $order, $data, 'ExecutePayment' );
	}

	/**
	 * Retrieve payment details.
	 *
	 * @param APP_Escrow_Order $order
	 * @param type $pay_key
	 * @return type
	 */
	public function get_payment_details( APP_Escrow_Order $order, $pay_key ) {
		$data = array(
		  'requestEnvelope' => $this->get_envelope(),
		  'payKey' => $pay_key
		);
		return $this->request( $order, $data, 'PaymentDetails' );
	}

	/**
	 * Makes a PayPal request for a given callback.
	 *
	 * @param APP_Escrow_Order $order
	 * @param type $data
	 * @param type $call
	 * @return boolean
	 */
	protected function request( APP_Escrow_Order $order, $data, $call ) {

		// open connection
		$ch = curl_init();

		if ( ! $ch ) {
			$order->log( sprintf( 'The following error ocurred while trying to execute the payment: "%s"', 'Could not open a cURL session' ) );
			return false;
		}

		curl_setopt( $ch, CURLOPT_URL, $this->options['endpoint'] . $call );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt( $ch, CURLOPT_POST, TRUE );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->get_headers() );

		$response = json_decode( curl_exec( $ch ), true );

		curl_close($ch);

		return $response;
	}

	### Setters

	protected function set_options( $options  ) {

		$payment_options = APP_Gateway_Registry::get_options();

		$gateway_mode = ! empty( $options['sandbox_enabled'] ) ? 'sandbox' : 'live';
		$options_mode = sprintf( 'pp_adaptive_%s_', $gateway_mode );

		$all_options = array(
			'appID' =>  empty( $options['sandbox_enabled'] ) ? $options[$options_mode . 'appid'] : self::SANDBOX_APPID,
			'fees_payer'=> $options['pp_adaptive_fees_payer'],
			'paypal_email' =>  $options['email_address'],
			'username' => $options[ $options_mode . 'username' ],
			'password' => $options[ $options_mode . 'password' ],
			'signature'=> $options[ $options_mode . 'signature' ],
			'endpoint' => self::$endpoint_url[ $gateway_mode ],
		);

		$this->options = $all_options;
	}

	### Getters

	protected function get_headers() {
		return array(
		  'X-PAYPAL-SECURITY-USERID: ' . $this->options['username'],
		  'X-PAYPAL-SECURITY-PASSWORD: ' . $this->options['password'],
		  'X-PAYPAL-SECURITY-SIGNATURE: ' . $this->options['signature'],
		  'X-PAYPAL-REQUEST-DATA-FORMAT: JSON',
		  'X-PAYPAL-RESPONSE-DATA-FORMAT: JSON',
		  'X-PAYPAL-APPLICATION-ID: ' . $this->options['appID']
		);
	}

	protected function get_envelope() {
		return array(
		  'errorLanguage' => 'en_US',
		  'detailLevel'   => 'returnAll'
		);
	}

	protected function get_receivers( APP_Escrow_Order $order ) {

		$receiver_list = array();

		$receivers = $order->get_receivers();

		if ( empty( $receivers ) ) {
			$this->fail_order( __( 'PayPal was unable to verify the payment receivers. Please contact site owner.', APP_TD ) );
			$order->log( sprintf( 'The following error ocurred while trying to execute the payment: "%s"', 'Receivers list is empty' ) );
			return false;
		}

		// add the primary receiver - site owner
		$receivers[1] = $order->get_total();

		// sort the receivers - primary receiver should be the first in the list
		ksort( $receivers );

		$key = 0;

		foreach( $receivers as $user_id => $amount ) {

			// primary receiver is the site owner
			if (  0 == $key ) {
				$paypal_email = $this->options['paypal_email'];
			} else {
				// email field name from the user personal settings
				$email_field = self::EMAIL_META_KEY;

				$user = get_user_by( 'id', $user_id );
				$paypal_email = get_user_option( $email_field, $user_id);

				$order->log( sprintf( __( 'Added user \'%1$s\' with email \'%2$s\' as receiver', APP_TD ), $user->display_name, $paypal_email ) );
			}

			$receiver_list[ $key ] = array(
				'email' => $paypal_email,
				'amount' => (float) $amount,
				'primary' => ( $key == 0 ),
			);
			$key++;
		}

		return $receiver_list;
	}

}
