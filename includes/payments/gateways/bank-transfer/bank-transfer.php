<?php
/**
 * Bank Transfer gateway
 *
 * @package Components\Payments\Gateways\Bank-Transfer
 */

require 'bt-emails.php';

if( is_admin() ){
	require 'bt-admin.php';
	new APP_Bank_Transfer_Queue;
}

/**
 * Payment Gateway for processing payments via Bank Transfer
 * or other manual method
 */
class APP_Bank_Transfer_Gateway extends APP_Gateway{

	/**
	 * Sets up the gateway
	 */
	public function __construct() {
		parent::__construct( 'bank-transfer', array(
			'dropdown' => __( 'Bank Transfer', APP_TD ),
			'admin' => __( 'Bank Transfer', APP_TD ),
		) );

	}

	/**
	 * Builds the administration settings form
	 * @return array scbForms style form
	 */
	public function form() {

		$form_values = array(

			array(
				'title' => __( 'Message', APP_TD ),
				'type' => 'textarea',
				'name' => 'message',
				'desc' => __( 'This content will be displayed once checkout has been completed. It will be up to the purchaser to follow the instructions.', APP_TD ),
				'extra' => array(
					'rows' => 10,
					'cols' => 50,
					'class' => 'large-text code',
				),
			),

		);

		$return_array = array(
			"title" => __( 'General', APP_TD ),
			"fields" => $form_values
		);

		return $return_array;

	}

	/**
	 * Processes a Bank Transfer Order to display
	 * instructions to the user
	 * @param  APP_Order $order   Order to display information for
	 * @param  array $options     User entered options
	 * @return void
	 */
	public function process( $order, $options ) {

		$sent = get_post_meta( $order->get_ID(), 'bt-sentemail', true );
		if ( empty( $sent ) ){
			appthemes_bank_transfer_pending_email( get_post( $order->get_ID() ) );
			update_post_meta( $order->get_ID(), 'bt-sentemail', true );
		}

		$templates = appthemes_payments_get_args('templates');

		$template_name = $templates['bank-transfer'];
		$located = appthemes_locate_template( $template_name );

		if ( $located ) {

			// load theme template

			$order = appthemes_get_order( $order->get_ID() );
			appthemes_load_template( $template_name, array(
				'order'	=> $order,
			) );

		} else {

			// load bundled template

			require_once dirname( __FILE__ )  . '/template/' . $template_name;

		}
	}
}

appthemes_register_gateway( 'APP_Bank_Transfer_Gateway' );
