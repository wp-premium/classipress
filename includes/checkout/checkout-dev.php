<?php
/**
 * Dynamic Checkout developers info
 *
 * @package Components\Checkouts
 */
add_action( 'admin_bar_menu', 'appthemes_checkout_dev_info', 99 );

function appthemes_checkout_dev_info( $wp_admin_bar ){

	$checkout =  APP_Current_Checkout::get_checkout();

	if( ! $checkout )
		return;

	if( isset( $_GET['step'] ) )
		$current_step = $_GET['step'];
	else
		$current_step = $checkout->get_next_step();

	$wp_admin_bar->add_node( array(
		'id' => 'checkout',
		'parent' => false,
		'title' => sprintf( 'Current Step: %s', $current_step ),
		'href' => '#',
		'meta' => array( 'class' => 'opposite' )
	) );

	$checkout_type = $checkout->get_checkout_type();
	$wp_admin_bar->add_node( array(
		'id' => 'checkout-type',
		'parent' => 'checkout',
		'title' => sprintf( 'Checkout Type: %s', $checkout_type ),
		'meta' => array( 'class' => 'opposite' )
	) );

	$num = 1;
	$all_steps = $checkout->get_steps();
	$first_step = false;
	foreach( $all_steps as $step => $callbacks ){

		if( $first_step == false )
			$first_step = $step;

		$current = '';
		if( $step == $current_step )
			$current = ' (current) ';

		$wp_admin_bar->add_node( array(
			'id' => 'checkout-' . $step,
			'parent' => 'checkout',
			'title' => sprintf( 'Step %s: %s', $num, $step ) . $current,
			'href' => esc_url( appthemes_get_step_url( $step ) ),
		) );
		$num++;
	}
	$wp_admin_bar->add_node( array(
		'id' => 'checkout-reset',
		'parent' => 'checkout',
		'title' => 'Start Over',
		'href' => esc_url( add_query_arg( 'hash', '', appthemes_get_step_url( $first_step ) ) )
	) );

	$wp_admin_bar->add_node( array(
		'id' => 'steps-reset-main',
		'parent' => false,
		'title' => 'Start Over',
		'href' => esc_url( add_query_arg( 'hash', '', appthemes_get_step_url( $first_step ) ) )
	) );

}
