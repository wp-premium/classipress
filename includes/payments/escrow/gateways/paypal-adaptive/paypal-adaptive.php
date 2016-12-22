<?php
/**
 * PayPal gateway
 *
 * @package Components\Payments\Escrow\PayPal
 */

add_action( 'init', '_appthemes_init_paypal_adaptive', 15 );

/**
 * Registers PayPal Adaptive gateway.
 *
 * @return void
 */
function _appthemes_init_paypal_adaptive() {
	if ( appthemes_is_escrow_enabled() ) {
		appthemes_register_gateway( 'APP_PayPal_Adaptive' );
	}
}

/**
 * Extends PayPal gateway to handle adaptive payments
 */
class APP_PayPal_Adaptive extends APP_PayPal implements APP_Escrow_Payment_Processor {

	/**
	 * Checkout URL's for sandbox and live mode.
	 * @var array
	 */
	protected static $checkout_url = array(
		'sandbox' => 'https://www.sandbox.paypal.com/webscr?cmd=_ap-payment&paykey=',
		'live' => 'https://www.paypal.com/webscr?cmd=_ap-payment&paykey=',
	);

	public function __construct() {
		parent::__construct();

		add_action( 'admin_init' , array( $this, 'details_meta_box' ) );
	}

	public function details_meta_box() {
		new APP_PayPal_Adaptive_Meta_Box;
	}

	/**
	 * Processes an escrow order by transferring funds to a primary receiver account.
	 *
	 * @param APP_Escrow_Order $order Order to process
	 * @param array $options User inputted options
	 *
	 * @return void
	 */
	public function process_escrow( APP_Escrow_Order $order, array $options ) {

		// Otherwise, validate regularly
		if ( $this->is_returning() ) {
			$order->paid();
			return true;
		} else {
			return $this->_process_escrow( $order, $options );
		}

	}

	/**
	 * See process_escrow()
	 */
	protected function _process_escrow( APP_Escrow_Order $order, $options ) {

		$return_url = $this->get_return_url( $order );
		$cancel_url = $this->get_cancel_url( $order );

		$pp_adaptive_request = new APP_PayPal_Adaptive_Request( $options );
		$pay_response = $pp_adaptive_request->pay( $order, $return_url, $cancel_url );

		$responsecode = strtoupper( $pay_response['responseEnvelope']['ack'] );

		if ( ( 'SUCCESS' != $responsecode && 'SUCCESSWITHWARNING' != $responsecode ) ) {
			$this->fail_order( __( 'PayPal was not able to execute the payment request. Please contact site owner.', APP_TD ) );
			if ( $responsecode ) {
				$order->log( sprintf( __( 'The following error ocurred while trying to make the pay request: "%s"', APP_TD ), $pay_response['error'][0]['message'] ) );
			} else {
				$order->log( __( 'No response code from PayPal while trying to execute the payment.', APP_TD ) );
			}
			return false;
		}

		$order->add_data( 'pay_key', $pay_response['payKey'] );

		$form_atts = array(
			'name' => 'paypal_payform',
			'action' => APP_PayPal::get_request_url(),
		);

		$fields = array(
			'cmd' => '_ap-payment',
			'paykey' => $pay_response['payKey'],
		);

		$this->redirect( $form_atts, $fields, __( 'You are now being redirected to PayPal.', APP_TD ) );

		return true;
	}

	/**
	 * Completes an escrow order by trasferring the money to the secondary receivers, given the pay key
	 *
	 * @param object $order The Order object
	 * @param  array $options User inputted options
	 *
	 * @return bool True on success, False on failure
	 */
	public function complete_escrow( APP_Escrow_Order $order, array $options ) {

		$pay_key = $order->get_data( 'pay_key' );

		$pp_adaptive_request = new APP_PayPal_Adaptive_Request( $options );
		$pay_response = $pp_adaptive_request->execute_payment( $order, $pay_key );

		$responsecode = strtoupper( $pay_response['responseEnvelope']['ack'] );

		if ( ( 'SUCCESS' != $responsecode && 'SUCCESSWITHWARNING' != $responsecode ) ) {
			$this->fail_order( __( 'PayPal was not able to execute the payment. Please contact site owner.', APP_TD ) );
			if ( $responsecode ) {
				$order->log( sprintf( __( 'The following error ocurred while trying to execute the payment: "%s"', APP_TD ), $pay_response['error'][0]['message'] ) );
			} else {
				$order->log( __( 'No response code from PayPal while trying to execute the payment.', APP_TD ) );
			}
			return false;
		}

		$order->log( __( 'Funds transferred to receivers', APP_TD ) );

		return true;
	}

	/**
	 * Refunds an escrow transaction given the pay key
	 *
	 * @param object $order The Order object
	 * @param  array $options User inputted options
	 *
	 * @return bool True on success, False on failure
	 */
	public function fail_escrow( APP_Escrow_Order $order, array $options ) {

		$pay_key = $order->get_data( 'pay_key' );

		$pp_adaptive_request = new APP_PayPal_Adaptive_Request( $options );
		$pay_response = $pp_adaptive_request->refund( $order, $pay_key );

		$responsecode = strtoupper( $pay_response['responseEnvelope']['ack'] );

		if ( ( 'SUCCESS' != $responsecode && 'SUCCESSWITHWARNING' != $responsecode ) ) {
			$this->fail_order( __( 'PayPal was not able to execute the refund. Please contact site owner.', APP_TD ) );
			if ( $responsecode ) {
				$order->log( sprintf( __( 'The following error ocurred while trying to execute the refund: "%s"', APP_TD ), $pay_response['error'][0]['message'] ) );
			} else {
				$order->log( __( 'No response code from PayPal while trying to execute the refund.', APP_TD ) );
			}
			return false;
		}

		return true;
	}

	/**
	 * Retrieves the payment details for an active escrow.
	 *
	 * @param object $order The Order object
	 * @param  array $options User inputted options
	 *
	 * @return array An assoaciative array with the payment details
	 */
	public function get_details( APP_Escrow_Order $order, array $options ) {

		$pay_key = $order->get_data( 'pay_key' );

		$pp_adaptive_request = new APP_PayPal_Adaptive_Request( $options );

		return $pp_adaptive_request->get_payment_details( $order, $pay_key );
	}

	### Front End Settings Fields

	public function user_form() {

		$fields = array(
			'title' => __( 'PayPal Escrow Information', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'PayPal Email', APP_TD ),
					'type' => 'text',
					'name' => APP_PayPal_Adaptive_Request::EMAIL_META_KEY,
					'extra' => array(
						'class' => 'text regular-text',
					),
					'desc' => __( 'Money transfers will be made to this PayPal email address.', APP_TD ),
				),
			),
		);

		return $fields;
	}

	### Admin Settings Fields

	/**
	 * Returns an array for the administrative settings
	 *
	 * See APP_Gateway::form()
	 *
	 * @uses apply_filters() Calls 'appthemes_paypal_adaptive_settings_form'
	 *
	 * @return array scbForms style inputs
	 */
	public function form() {

		$fields = parent::form();

		$sandbox_escrow_fields = array(
			'title' => __( 'Adaptive Payments SANDBOX API Credentials (Escrow)', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Username', APP_TD ),
					'tip' => sprintf( __( 'Your sandbox account API credentials username. You can find more info on creating/managing API credentials <a href="%s">here</a>.', APP_TD ), 'https://developer.paypal.com/docs/classic/api/gs_PayPalAPIs/#credentials' ),
					'type' => 'text',
					'name' => 'pp_adaptive_sandbox_username',
				),
				array(
					'title' => __( 'Password', APP_TD ),
					'tip' => sprintf( __( 'Your sandbox account API credentials password. You can find more info on creating/managing API credentials <a href="%s">here</a>.', APP_TD ), 'https://developer.paypal.com/docs/classic/api/gs_PayPalAPIs/#credentials' ),
					'type' => 'text',
					'name' => 'pp_adaptive_sandbox_password',
				),
				array(
					'title' => __( 'Signature', APP_TD ),
					'tip' => sprintf( __( 'Your sandbox account API credentials signature. You can find more info on creating/managing API credentials <a href="%s">here</a>.', APP_TD ), 'https://developer.paypal.com/docs/classic/api/gs_PayPalAPIs/#credentials' ),
					'type' => 'text',
					'name' => 'pp_adaptive_sandbox_signature',
				),
				array(
					'title' => '',
					'type' => 'custom',
					'name' => '_blank',
					'render' => array( $this, 'sandbox_notes' ),
				),
			)
		);

		$live_escrow_fields = array(
			'title' => __( 'Adaptive Payments LIVE API Credentials (Escrow)', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Username', APP_TD ),
					'tip' => sprintf( __( 'Your LIVE API credentials username. You can find more info on creating/managing API credentials <a href="%s">here</a>.', APP_TD ), 'https://developer.paypal.com/docs/classic/lifecycle/goingLive/' ),
					'type' => 'text',
					'name' => 'pp_adaptive_live_username',
				),
				array(
					'title' => __( 'Password', APP_TD ),
					'tip' => sprintf( __( 'Your LIVE API credentials password. You can find more info on creating/managing API credentials <a href="%s">here</a>.', APP_TD ), 'https://developer.paypal.com/docs/classic/lifecycle/goingLive/' ),
					'type' => 'text',
					'name' => 'pp_adaptive_live_password',
				),
				array(
					'title' => __( 'Signature', APP_TD ),
					'tip' => sprintf( __( 'Your LIVE API credentials signature. You can find more info on creating/managing API credentials <a href="%s">here</a>.', APP_TD ), 'https://developer.paypal.com/docs/classic/lifecycle/goingLive/' ),
					'type' => 'text',
					'name' => 'pp_adaptive_live_signature',
				),
				array(
					'title' => __( 'AppID', APP_TD ),
					'tip' => sprintf( __( 'Your Application ID. This should be your LIVE AppID <u>NOT</u> the generic AppID <code>APP-80W284485P519543</code> used for testing. You can find more info on creating/managing PayPal applications <a href="%s">here</a>.', APP_TD ), 'https://developer.paypal.com/docs/classic/lifecycle/goingLive/#register' ) .
							'<br/><br/>' . __( '<strong>Important:</strong> You can only start accepting \'escrow\' transactions after your AppID is fully approved by PayPal. Applications with status \'Approved Conditionally\' are not considered approved for \'escrow\' transactions. ', APP_TD ),
					'type' => 'text',
					'name' => 'pp_adaptive_live_appid',
				),
				array(
					'title' => '',
					'type' => 'custom',
					'name' => '_blank',
					'render' => array( $this, 'live_notes' ),
				),
			)
		);

		$fees = array(
			'title' => __( 'Adaptive Payments Fees', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Payer', APP_TD ),
					'tip' => __( 'Who should pay any applicable fees?', APP_TD ),
					'type' => 'select',
					'name' => 'pp_adaptive_fees_payer',
					'choices' => array(
						'PRIMARYRECEIVER' => __( 'Primary Receiver (You)', APP_TD ),
						'SECONDARYONLY' => __( 'Secondary Receiver', APP_TD ),
					),
				),
			),
		);

		$currency_notes = array(
			'title' => '',
			'fields' => array(
				array(
					'title' => '',
					'type' => 'custom',
					'name' => '_blank',
					'render' => array( $this, 'currency_notes' ),
				),
			),
		);

		$fields['escrow_sandbox'] = $sandbox_escrow_fields;
		$fields['escrow_live'] = $live_escrow_fields;
		$fields['paypal_fees'] = $fees;

		return apply_filters( 'appthemes_paypal_adaptive_settings_form', $fields );
	}

	public function sandbox_notes() {
		$notes = sprintf( __( '<a href="%s">Obtain Sandbox API Credentials</a>', APP_TD ), 'https://developer.paypal.com/docs/classic/api/gs_PayPalAPIs/#credentials' );
		return $notes;
	}

	public function live_notes() {
		$notes = __( '<strong>Important:</strong> To obtain live PayPal credentials, you must have a verified Premier or verified Business PayPal account. Also, make sure your PayPal account is able to receive money on every currency accepted on your site. Otherwise, users will not be able to transfer money to you.', APP_TD );
		$notes.= '<br/><br/>' . sprintf( __( '<a href="%s">Obtain Live API Credentials</a>', APP_TD ), 'https://developer.paypal.com/docs/classic/api/gs_PayPalAPIs/#credentials' );
		return $notes;
	}

	public static function get_checkout_url( $options ) {
		$gateway_mode = ! empty( $options['sandbox_enabled'] ) ? 'sandbox' : 'live';
		return self::$checkout_url[ $gateway_mode ];
	}

	/**
	 * Checks if the current gateway supports a specific service
	 * @return bool
	 */
	public function supports( $service = 'instant' ){
		switch ( $service ) {
			case 'escrow':
				return true;
				break;
			default:
				return parent::supports( $service );
				break;
		}
	}

}

/**
 * Provides an additional meta box with escrow details to escrow orders.
 *
 * @package Components\Payments\Escrow\PayPal
 */
class APP_PayPal_Adaptive_Meta_Box extends APP_Meta_Box {

	/**
	 * Sets up the meta box with WordPress
	 */
	function __construct(){

		if ( ! isset( $_GET['post'] ) ) {
			return;
		}

		$order = appthemes_get_order( (int) $_GET['post'] );

		if ( ! $order || ! $order->is_escrow() || $order->get_gateway() != 'paypal' ) {
			return;
		}

		parent::__construct( 'order-pp-adaptive-details', __( 'Details', APP_TD ), APPTHEMES_ORDER_PTYPE, 'side' );
	}

	/**
	 * Displays specific details for PayPal Adaptive escrow orders
	 *
	 * @param object $post WordPress Post object
	 */
	function display( $post ) {

		$order = appthemes_get_order( $post->ID );

		$details = appthemes_get_escrow_details( $order );
?>
		<style type="text/css">
			#admin-escrow-order-details td.paypal-email {
				font-size: 11px;
			}
		</style>
		<?php if ( ! empty( $details['paymentInfoList']['paymentInfo'] ) ) : ?>
			<table id="admin-escrow-order-details">

				<?php $retained = 0;	?>
				<tbody>
						<?php foreach( $details['paymentInfoList']['paymentInfo'] as $key => $payment_info ) { ?>
							<?php if ( 0 == $key ) continue; ?>
							<?php $retained += $payment_info['receiver']['amount']; ?>
							<tr>
								<th><?php _e( 'Funds', APP_TD ); ?>: </th>
								<td><?php echo appthemes_display_price( $order->get_total(), $order->get_currency() ); ?></td>
							</tr>
							<tr>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<th><?php _e( 'Receiver', APP_TD ); ?>: </th>
								<td class="paypal-email"><?php echo $payment_info['receiver']['email']; ?></td>
							</tr>
							<tr>
								<th><?php _e( 'Amount', APP_TD ); ?>: </th>
								<td><?php echo appthemes_display_price( $payment_info['receiver']['amount'], $order->get_currency() ); ?></td>
							</tr>
						<?php } ?>
						<tr>
							<th><?php _e( 'Retained', APP_TD ); ?>: </th>
							<td><?php echo appthemes_display_price( $order->get_total() - $retained, $order->get_currency() ); ?> <?php echo __( '(when completed)', APP_TD ); ?> </td>
						</tr>
					</tr>
				</tbody>
			</table>

		<?php else: ?>

			<?php echo __(  'N/A', APP_TD );  ?>

		<?php endif; ?>

		<div class="clear"></div>
<?php
	}

}
