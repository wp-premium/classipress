<?php
/**
 * Payments API
 *
 * @package Components\Payments\Escrow
 */

add_action( 'init', '_appthemes_handle_escrow_settings_form', 20 );

add_action( 'appthemes_transaction_completed', 'appthemes_escrow_complete' );
add_action( 'tr_paid_to_tr_refunded', 'appthemes_escrow_refund' );

/**
 * Creates a blank escrow order object.
 *
 * @return APP_Escrow_Order An order object representing the new escrow order
 */
function appthemes_new_escrow_order() {
	return APP_Escrow_Order_Factory::create();
}

/**
 * Checks if a given order is an escrow order or a normal order without loading the order
 *
 * @param mixed $order_id The Order ID or Order object to check
 *
 * @return bool True if is an escrow Order, False if is a normal Order
 */
function appthemes_is_escrow_order( $order_id ) {

	if ( intval( $order_id ) ) {
		return (bool) get_post_meta( $order_id, 'is_escrow', true );
	} else if ( is_object( $order_id ) && ( $order_id instanceof APP_Escrow_Order ) ) {
		return true;
	}

	return false;
}

/**
 * Processes an escrow payment for a gateway. Moves the funds from the sender to the the primary receiver account.
 *
 * @param string $gateway_id The gateway ID to process
 * @param APP_Escrow_Order $order The escrow Order object
 *
 * @return bool True on success, False on failure
 */
function appthemes_escrow_process( $gateway_id, APP_Escrow_Order $order ) {

	$gateway = APP_Gateway_Registry::get_gateway( $gateway_id );
	if ( $gateway && ( APP_Gateway_Registry::is_gateway_enabled( $gateway_id, 'escrow' ) || current_user_can( 'manage_options' ) ) ) {

		// @todo create APP_Gateway_Registry::get_escrow_gateway() to replace this check
		if ( ! ( $gateway instanceof APP_Escrow_Payment_Processor ) ) {
			return false;
		}

		$options = APP_Gateway_Registry::get_gateway_options( $gateway_id );
		return $gateway->process_escrow( $order, $options );

	} else {
		return false;
	}

}

/**
 * Completes the escrow order by moving the funds held in escrow to the secondary receiver(s).
 *
 * @uses do_action() Calls 'appthemes_escrow_completed'
 * @uses do_action() Calls 'appthemes_escrow_complete_failed'
 *
 * @param APP_Order $order The original Order object
 *
 * @return bool True on success, False on failure
 */
function appthemes_escrow_complete( $order ) {

	if ( ! $order->is_escrow() ) {
		return false;
	}

	$gateway_id = $order->get_gateway();
	$gateway = APP_Gateway_Registry::get_gateway( $gateway_id );

	if ( $gateway && ( APP_Gateway_Registry::is_gateway_enabled( $gateway_id, 'escrow' ) || current_user_can( 'manage_options' ) ) ) {

		if ( ! ( $gateway instanceof APP_Escrow_Payment_Processor ) ) {
			return  false;
		}

		$options = APP_Gateway_Registry::get_gateway_options( $gateway_id );
		$result = $gateway->complete_escrow( $order, $options );

		if ( ! $result ) {
			do_action( 'appthemes_escrow_complete_failed', $order );
		} else {
			do_action( 'appthemes_escrow_completed', $order );
		}

		return $result;

	} else {
		return false;
	}

}

/**
 * Returns funds held in escrow to the original sender.
 * Triggered only when changing an Order from 'Paid' to 'Failed'.
 *
 * @uses do_action() Calls 'appthemes_escrow_refunded'
 * @uses do_action() Calls 'appthemes_escrow_refund_failed'
 *
 * @param Post object $post The post being updated
 *
 * @return bool True on success, False on failure
 */
function appthemes_escrow_refund( $post ) {

	$order = appthemes_get_order( $post->ID );

	if ( ! $order->is_escrow() ) {
		return false;
	}

	$gateway_id = $order->get_gateway();
	$gateway = APP_Gateway_Registry::get_gateway( $gateway_id );

	if ( $gateway && ( APP_Gateway_Registry::is_gateway_enabled( $gateway_id, 'escrow' ) || current_user_can( 'manage_options' ) ) ) {

		if ( ! ( $gateway instanceof APP_Escrow_Payment_Processor ) ) {
			return  false;
		}

		$options = APP_Gateway_Registry::get_gateway_options( $gateway_id );
		$result = $gateway->fail_escrow( $order, $options );

		if ( ! $result ) {
			do_action( 'appthemes_escrow_refund_failed', $order );
		} else {
			do_action( 'appthemes_escrow_refunded', $order );
		}

		return $result;

	} else {
		return false;
	}

}

/**
 * Retrieves details for an escrow order.
 *
 * @param APP_Escrow_Order $order The order object
 *
 * @return bool|mixed The details for the order or False if no details found
 */
function appthemes_get_escrow_details( APP_Escrow_Order $order ) {

	$gateway_id = $order->get_gateway();
	$gateway = APP_Gateway_Registry::get_gateway( $gateway_id );

	if ( $gateway && ( APP_Gateway_Registry::is_gateway_enabled( $gateway_id ) || current_user_can( 'manage_options' ) ) ) {

		if ( ! ( $gateway instanceof APP_Escrow_Payment_Processor ) ) {
			return  false;
		}

		$options = APP_Gateway_Registry::get_gateway_options( $gateway_id );
		return $gateway->get_details( $order, $options );

	} else {
		return false;
	}

}

/**
 * Handles the manage escrow settings form posted data.
 */
function _appthemes_handle_escrow_settings_form() {
	APP_Escrow_Settings_Form::handle_form();
}

/**
 * Outputs the escrow settings form for active escrow gateways with all the fields required by each gateways.
 */
function appthemes_display_escrow_form() {

	foreach( APP_Gateway_Registry::get_active_gateways( 'escrow' ) as $gateway ) {
		$fields[ $gateway->identifier() ] = $gateway->user_form();
	}

	if ( empty( $fields ) ) {
		return;
	}

	new APP_Escrow_Settings_Form( $fields );
}

/**
 * The amount to be sent to the secondary receiver on an escrow Order.
 *
 * @param float $amount_no_fees The amount base to calculate fees.
 * @return float The final amount after fees
 */
function appthemes_escrow_receiver_amount( $amount_no_fees ) {

	$payment_options = APP_Gateway_Registry::get_options();

	switch( $payment_options->escrow['retain_type'] ){

		case 'percent':
			$multiplier = ( (int) $payment_options->escrow['retain_amount'] ) / 100;
			$sec_amount = $amount_no_fees * $multiplier;
			break;

		default:
			$sec_amount = (float) $payment_options->escrow['retain_amount'];
			break;
	}

	return number_format( $amount_no_fees - $sec_amount, 2, '.', '' );
}
