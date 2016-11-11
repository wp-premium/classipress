<?php
/**
 * Gateways API
 *
 * @package Components\Payments\Gateways
 */

/**
 * Registers a gateway with the APP_Gateway_Registry
 * @param  string $class_name Name of the class to be used as a Gateway
 * @return void
 */
function appthemes_register_gateway( $class_name ) {

	APP_Gateway_Registry::register_gateway( $class_name );

}

/**
 * Runs the process() method on a currently active gateway
 * @param  string $gateway_id Identifier of currently active gateway
 * @param  APP_Order $order   Order to be processed
 * @return boolean            False on error
 */
function appthemes_process_gateway( $gateway_id, $order ) {

	$receipt_order = APP_Order_Receipt::retrieve( $order->get_id() );
	$options = APP_Gateway_Registry::get_gateway_options( $gateway_id );
	$gateway = APP_Gateway_Registry::get_gateway( $gateway_id );

	if ( $gateway && ( APP_Gateway_Registry::is_gateway_enabled( $gateway_id ) || current_user_can( 'manage_options' ) ) ) {
		$gateway->process( $receipt_order, $options );
		return true;
	} else {
		return false;
	}

}

/**
 * Runs the process_recurring() method on a currently active gateway
 * @param  string $gateway_id Identifier of currently active gateway
 * @param  APP_Order $order   Order to be processed
 * @return boolean            False on error
 */
function appthemes_process_recurring_gateway( $gateway_id, $order ) {

	$receipt_order = APP_Order_Receipt::retrieve( $order->get_id() );
	$options = APP_Gateway_Registry::get_gateway_options( $gateway_id );
	$gateway = APP_Gateway_Registry::get_gateway( $gateway_id );
	
	if ( ! appthemes_is_recurring_available() ) {
		return false;
	}

	if( APP_Gateway_Registry::is_gateway_enabled( $gateway_id ) || current_user_can( 'manage_options') ){

		if( ! $gateway->is_recurring() )
			return false;

		$gateway->process_recurring( $receipt_order, $options );
		return true;
	}
	else{
		return false;
	}

}

function appthemes_recurring_available( $gateway_id = '' ){

	if( ! empty( $gateway_id ) && APP_Gateway_Registry::is_gateway_enabled( $gateway_id ) ){
		$gateways = array( APP_Gateway_Registry::get_gateway( $gateway_id ) );
	}else{
		$gateways = APP_Gateway_Registry::get_active_gateways();
	}

	foreach( $gateways as $gateway ){
		if( $gateway->is_recurring() )
			return true;
	}

	return false;

}

/**
 * Displays a dropdown form with currently active gateways
 * @param  string $input_name Name of the input field
 * @return void
 */
function appthemes_list_gateway_dropdown( $input_name = 'payment_gateway', $recurring = false, $args = array() ) {

	if( is_array( $input_name ) ){
		$args = $input_name;
		$input_name = 'payment_gateway';
	}

	$args = wp_parse_args( $args, array(
		'input_name' => $input_name,
		'recurring' => $recurring,
		'service' => 'instant',
		'empty_text' => __( 'No payment gateways are available. Please contact your site administrator', APP_TD ),
	) );

	$gateways = array();
	foreach ( APP_Gateway_Registry::get_gateways( $args['service'] ) as $gateway ) {

		if ( $args['recurring'] && ! $gateway->is_recurring() ) {
			continue;
		}

		$text = $gateway->display_name( 'dropdown' );

		if ( ! APP_Gateway_registry::is_gateway_enabled( $gateway->identifier(), $args['service'] ) ) {
			continue;
		}
		$gateways[ $gateway->identifier() ] = $text;
	}

	if( empty( $gateways ) ){
		$gateways[''] = $args['empty_text'];
	}

	echo scbForms::input( array(
		'type' => 'select',
		'name' => $input_name,
		'values' => $gateways,
		'extra' => array( 'class' => 'required' )
	) );

}
