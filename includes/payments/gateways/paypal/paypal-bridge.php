<?php
/**
 * Paypal bridge
 *
 * @package Components\Payments\Gateways\PayPal
 */
class APP_PayPal_Bridge{

	const SUBSCRIBE_START = 'subscr_signup';
	const SUBSCRIBE_PAYMENT = 'subscr_payment';
	const SUBSCRIBE_END = 'subscr_cancel';
	const SUBSCRIBE_FAILED = 'subscr_failed';
	const SUBSCRIBE_EOT = 'subscr_eot';

	public function __construct(){
		add_action( 'appthemes_paypal_ipn_register_callbacks', array( $this, 'register_hooks' ) );
		add_action( 'appthemes_create_order_duplicate', array( $this, 'copy_subscription_meta' ), 10, 2 );
	}

	public function register_hooks(){

		APP_PayPal_Notifier::register( 'web_accept', array( $this, 'process_single' ) );

		APP_PayPal_Notifier::register( self::SUBSCRIBE_START, array( $this, 'process_subscription' ));
		APP_PayPal_Notifier::register( self::SUBSCRIBE_PAYMENT, array( $this, 'process_subscription' ) );
		APP_PayPal_Notifier::register( self::SUBSCRIBE_FAILED, array( $this, 'process_subscription' ) );
		APP_PayPal_Notifier::register( self::SUBSCRIBE_END, array( $this, 'process_subscription' ) );
		APP_PayPal_Notifier::register( self::SUBSCRIBE_EOT, array( $this, 'process_subscription' ));
	}

	public function log_to_order( $order, $errors ){

		if( ! $errors->get_error_codes() )
			return $errors;

		$order->log( 'Order attempted to be modified, but gave the following errors' );

		foreach( $errors->get_error_messages() as $message ){
			$order->log( $message );
		}

		return $errors;
	}

	public function process_single( $response ){
		$errors = new WP_Error();

		$item_number = false;
		if( isset( $response['item_number1'] ) )
			$item_number = $response['item_number1'];

		if( isset( $response['item_number'] ) )
			$item_number = $response['item_number'];

		if( !$item_number ){
			$errors->add( 'no_order', 'No order id was given.' );
			return $errors;
		}

		$order = appthemes_get_order( $item_number );
		if( !$order ){
			$error_data = array(
				'actual' => $item_number
			);
			$errors->add( 'bad_order', 'Given order id could not be located', $error_data );
			return $errors;
		}

		$errors = $this->validate_order( $order, $response, $errors );
		if( $errors->get_error_codes() ){
			return $this->log_to_order( $order, $errors );
		}

		if( !isset( $response['mc_gross'] ) || $order->get_total() != $response['mc_gross'] ){
			$mc_gross = isset( $response['mc_gross'] ) ? $response['mc_gross'] : 'no amount given';
			$error_data = array(
				'expected' => $order->get_total(),
				'actual' => $mc_gross,
			);
			$errors->add( 'bad_amount', 'Given amount did not match order.', $error_data );
			return $this->log_to_order( $order, $errors );
		}

		$order->complete();

		return $errors;
	}

	public function process_subscription( $response ){
		$errors = new WP_Error;

		if( !isset( $response['invoice'] ) ){
			$errors->add( 'no_invoice', 'No invoice number was specified' );
			return $errors;
		}


		$invoice_id = intval( $response['invoice'] );
		$order = $this->find_next_processable_subscription_order( $invoice_id );
		if( ! $order ){
			$errors->add( 'bad_invoice', 'There are no processable orders by the given invoice number' );
			return $errors;
		}

		$errors = $this->validate_order( $order, $response );
		if( $errors->get_error_codes() ){
			return $this->log_to_error( $errors );
		}

		switch( $response['txn_type'] ){

			case self::SUBSCRIBE_START:
				$this->process_subscription_start( $order, $response );
				break;
			case self::SUBSCRIBE_PAYMENT:
				$this->process_subscription_payment_completed( $order, $response, $errors );
				break;
			case self::SUBSCRIBE_FAILED:
				$this->process_subscription_payment_failed( $order, $response );
				break;
			case self::SUBSCRIBE_END:
				$this->process_subscription_end( $order, $response );
				break;
			case self::SUBSCRIBE_EOT;
				$this->process_subscription_end( $order, $response );
				break;

		}

		return $errors;
	}

	public function copy_subscription_meta( $duplicate, $original ){

		$subscription_id = get_post_meta( $original->get_id(), 'paypal_subscription_id', true );
		update_post_meta( $duplicate->get_id(), 'paypal_subscription_id', $subscription_id );

	}

	private function process_subscription_start(){
		return false; //Do Nothing
	}

	private function process_subscription_payment_completed( $order, $response, $errors ){

		if( !isset( $response['mc_gross'] ) || $order->get_total() != $response['mc_gross'] ){
			$errors->add( 'bad_amount', 'Response amount did not match order.');
			return $errors;
		}

		$order->complete();

	}

	private function process_subscription_payment_failed( $order ){
		$order->failed();
	}

	private function process_subscription_end( $order ){
		$order->failed();
	}

	private function find_next_processable_subscription_order( $subscription_id ){

		$posts = new WP_Query( array(
			'post_type' => APPTHEMES_ORDER_PTYPE,
			'post_status' => array( APPTHEMES_ORDER_PENDING, APPTHEMES_ORDER_FAILED ),
			'meta_query' => array(
				array(
					'key' => 'paypal_subscription_id',
					'value' => $subscription_id
				)
			),
			'order' => 'ASC',
			'orderby' => 'date',
		) );

		if( count( $posts->posts ) == 0 ){
			return false;
		}

		return appthemes_get_order( $posts->post->ID );
	}

	/**
	 * Completes the given order. Simple, but useful for callbacks
	 * @param  APP_Order $order The order to be completed
	 * @return void
	 */
	public function complete( $order ){
		$order->complete();
	}

	/**
	 * Checks that the order being given by PayPal matches the one on file
	 * @return WP_Error Object containing errors, if any were found
	 */
	private function validate_order( $order, $response, $errors = null ){

		$options = APP_Gateway_Registry::get_gateway_options( 'paypal' );

		if( ! $errors )
			$errors = new WP_Error;

		if( ! isset( $response['business'] ) || strtolower( $options['email_address'] ) != strtolower( $response['business'] ) ) {
			$business = isset( $response['business'] ) ? $response['business'] : 'no email given';
			$errors->add( 'bad_email', 'Given email address did not match settings.' . $business . '/' . $options['email_address'] );
		}

		if( !isset( $response['mc_currency'] )  || $order->get_currency() != strtoupper( $response['mc_currency'] ) )
			$errors->add( 'bad_currency', 'Given currency code did not match order.');

		if( $order->get_gateway() != 'paypal' )
			$errors->add( 'bad_gateway', 'Order was not using PayPal as a gateway.');

		return $errors;

	}
}
