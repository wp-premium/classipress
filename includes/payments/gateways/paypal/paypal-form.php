<?php
/**
 * Paypal form
 *
 * @package Components\Payments\Gateways\PayPal
 */
class APP_PayPal_Form{

	const TYPE = 'cmd';

	const BUY_NOW = '_xclick';
	const AMOUNT = 'amount';

	const SUBSCRIBE = '_xclick-subscriptions';
	const RECURRING_AMOUNT = 'a3';
	const RECURRING_PERIOD = 'p3';
	const RECURRING_PERIOD_TYPE = 't3';
	const RECURR_BILLING = 'src';

	const RECUR_BY_DAYS = 'D';
	const RECUR_BY_WEEKS = 'W';
	const RECUR_BY_MONTHS = 'M';
	const RECUR_BY_YEARS = 'Y';

	const SELLER_EMAIL = 'business';
	const ITEM_NAME = 'item_name';
	const ITEM_NUMBER = 'item_number';
	const CURRENCY_CODE = 'currency_code';

	const RETURN_METHOD = 'rm';
	const RETURN_BY_GET = 0;
	const RETURN_BY_GET_NO_QUERY = 1;
	const RETURN_BY_POST = 2;

	const RETURN_TEXT = 'cbt';
	const RETURN_URL = 'return';
	const CANCEL_URL = 'cancel_return';
	const NOTIFY_URL = 'notify_url';

	const NO_SHIPPING = 'no_shipping';
	const NO_NOTE = 'no_note';
	const CHARSET = 'charset';
	const INVOICE = 'invoice';

	/**
	 * Displays the form for user redirection
	 * @param  APP_Order $order   Order to process
	 * @param  array $options     User inputted options
	 * @return void
	 */
	public static function create_form( $order, $options, $return_url, $cancel_url ) {

		$options = wp_parse_args( $options, array(
	       		'email_address' => ''
		) );

		$fields = array(
			self::SELLER_EMAIL => $options['email_address'],

			self::ITEM_NAME => $order->get_description(),
			self::ITEM_NUMBER => $order->get_id(),
			self::CURRENCY_CODE => $order->get_currency(),

			self::RETURN_TEXT => sprintf( __( 'Continue to %s', APP_TD ), get_bloginfo( 'name' ) ),
			self::RETURN_URL => $return_url,
			self::CANCEL_URL => $cancel_url,
			self::NO_SHIPPING => 1,
			self::NO_NOTE => 1,

			self::RETURN_METHOD => self::RETURN_BY_GET,
			self::CHARSET => 'utf-8',
		);

		if( $order->is_recurring() ){

			if( get_post_meta( $order->get_id(), 'paypal_subscription_id', true ) ){
				self::print_processing_script( $order );
				return array();
			}

			$fields[ self::TYPE ] = self::SUBSCRIBE;
			$fields[ self::RECURR_BILLING ] = 1;

			$subscription_id = $order->get_id() . mt_rand(0, 1000);
			$fields[ self::INVOICE ] = $subscription_id;
			update_post_meta( $order->get_id(), 'paypal_subscription_id', $subscription_id );

			$fields[ self::RECURRING_AMOUNT ] = $order->get_total();

			$recurring_period_info = self::get_recurring_period_info( $order->get_recurring_period(), $order->get_recurring_period_type() );
			$fields[ self::RECURRING_PERIOD ] = $recurring_period_info['recurring_period'];
			$fields[ self::RECURRING_PERIOD_TYPE ] = $recurring_period_info['recurring_period_type'];

		}else{
			$fields[ self::TYPE ] = self::BUY_NOW;
			$fields[ self::AMOUNT ] = $order->get_total();
		}

		if ( !empty( $options['ipn_enabled'] ) ) {
			$fields[ self::NOTIFY_URL ] = APP_PayPal_IPN_Listener::get_listener_url();
		}
		
		$form = array(
			'action' => APP_PayPal::get_request_url(),
			'name' => 'paypal_payform',
			'id' => 'create_listing',
		);

		return array( $form, $fields );

	}

	public static function get_recurring_period_info( $recurring_period, $recurring_period_type = '' ) {

		if ( empty( $recurring_period_type ) || $recurring_period_type == self::RECUR_BY_DAYS ) {
			if ( $recurring_period > 90 ) {
				$recurring_period = ceil( $recurring_period / 30 );
				$recurring_period_type = self::RECUR_BY_MONTHS;
			}
		}

		if ( $recurring_period_type == self::RECUR_BY_MONTHS ) {
			$recurring_period = min( 24, $recurring_period );
		} else if ( $recurring_period_type == self::RECUR_BY_WEEKS ) {
			$recurring_period = min( 52, $recurring_period );
		} else if ( $recurring_period_type == self::RECUR_BY_YEARS ) {
			$recurring_period = min( 5, $recurring_period );
		}

		return compact( 'recurring_period', 'recurring_period_type' );
	}

	public static function print_processing_script( $order ){
		echo html( 'p', __( 'Your Order is still being processed. Please wait a few seconds...', APP_TD ) );
		echo html( 'p', sprintf( __( 'If your Order does not complete soon, please contact us and refer to your Order ID - #%d.', APP_TD ), $order->get_id() ) );

		$page = $_SERVER['REQUEST_URI'];
		echo html( 'script', 'setTimeout( function(){ location.href="' . $page . '"; }, 5000 );' );
	}

}
