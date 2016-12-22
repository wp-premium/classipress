<?php
/**
 * Payments API
 *
 * @package Components\Payments
 */

define( 'APPTHEMES_ORDER_PTYPE', 'transaction' );
define( 'APPTHEMES_ORDER_CONNECTION', 'order-connection' );

add_action( 'init', 'appthemes_setup_orders', 10 );
add_action( 'appthemes_first_run', 'appthemes_upgrade_order_statuses' );

add_action( 'appthemes_create_order', 'appthemes_payments_add_tax', 99 );

add_action( 'appthemes_transaction_activated', 'appthemes_schedule_next_order_recurrance' );

/**
 * Order Statuses
 */
define( 'APPTHEMES_ORDER_PENDING', 'tr_pending' );
define( 'APPTHEMES_ORDER_FAILED', 'tr_failed' );
define( 'APPTHEMES_ORDER_COMPLETED', 'tr_completed' );
define( 'APPTHEMES_ORDER_ACTIVATED', 'tr_activated' );

/**
 * Sets up the order system
 * @return void
 */
function appthemes_setup_orders() {

	$options = APP_Gateway_Registry::get_options();
	$show_ui = false;

	if ( current_user_can( 'edit_others_posts' ) || ( current_user_can( 'edit_posts' ) && $options->allow_view_orders ) ) {
		$show_ui = true;
	}

	$args = array(
		'labels' => array(
			'name' => __( 'Orders', APP_TD ),
			'singular_name' => __( 'Order', APP_TD ),
			'add_new' => __( 'Add New', APP_TD ),
			'add_new_item' => __( 'Add New Order', APP_TD ),
			'edit_item' => __( 'Edit Order', APP_TD ),
			'new_item' => __( 'New Order', APP_TD ),
			'view_item' => __( 'View Order', APP_TD ),
			'search_items' => __( 'Search Orders', APP_TD ),
			'not_found' => __( 'No orders found', APP_TD ),
			'not_found_in_trash' => __( 'No orders found in Trash', APP_TD ),
			'parent_item_colon' => __( 'Parent Order:', APP_TD ),
			'menu_name' => __( 'Orders', APP_TD ),
		),
		'hierarchical' => false,
		'supports' => array( 'author', 'custom-fields' ),
		'public' => true,
		'show_ui' => $show_ui,
		'show_in_menu' => 'app-payments',
		'exclude_from_search'  => true,
		'has_archive'  => true,
		'rewrite' => array('slug' => 'order')
	);
	register_post_type( APPTHEMES_ORDER_PTYPE, apply_filters( 'appthemes_order_ptype_args', $args ) );

	$statuses = array(
		APPTHEMES_ORDER_PENDING => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', APP_TD ),
		APPTHEMES_ORDER_FAILED => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', APP_TD ),
		APPTHEMES_ORDER_COMPLETED => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', APP_TD ),
		APPTHEMES_ORDER_ACTIVATED => _n_noop( 'Activated <span class="count">(%s)</span>', 'Activated <span class="count">(%s)</span>', APP_TD ),
	);

	foreach( $statuses as $status => $translate_string ){
		register_post_status( $status, array(
			'public' => true,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label_count' => $translate_string
		));
	}

	$args = appthemes_payments_get_args();
	$initial_ptypes = $args['items_post_types'];

	if( !is_array( $initial_ptypes ) ){
		$initial_ptypes = array( $initial_ptypes );
	}

	$post_types = apply_filters( 'appthemes_order_item_posts_types', $initial_ptypes );
	$post_types[] = APPTHEMES_ORDER_PTYPE;
	p2p_register_connection_type( array(
		'name' => APPTHEMES_ORDER_CONNECTION,
		'from' => APPTHEMES_ORDER_PTYPE,
		'to' => $post_types,
		'cardinality' => 'many-to-many',
		'admin_box' => false,
		'prevent_duplicates' => false,
		'self_connections' => true,
	) );

	APP_Item_Registry::register( '_regional-tax', __( 'Regional Taxes', APP_TD ), array(), 99 );
}

/**
 * Creates a blank order object, or upgrades a draft order
 * @param  APP_Draft_order $draft_order A draft order to be upgraded
 * @return APP_Order                    An order object representing the new order
 */
function appthemes_new_order( $draft_order = null ) {

	if( ! empty( $draft_order )  ){
		return APP_Draft_Order::upgrade( $draft_order );
	}else{
		return APP_Order_Factory::create();
	}
}

/**
 * Returns an instance of APP_Order for the given Order ID
 * @param  int $order_id An order ID
 * @return APP_Order     An order object representing the order
 */
function appthemes_get_order( $order_id, $force_refresh = false ) {
	return APP_Order_Factory::retrieve( $order_id, $force_refresh );
}

/**
 * Returns an instance of APP_Order for the given Post ID connected to Order
 * @param int $post_id				An post ID
 * @param  array $args (optional)	Additional params to pass to the WP_Query
 * @return APP_Order				An order object representing the order last connected to the post
 */
function appthemes_get_order_connected_to( $post_id, $args = array() ) {
	$connected = _appthemes_orders_get_connected( $post_id, $args );
	if ( ! $connected->posts )
		return false;

	return appthemes_get_order( $connected->post->ID );
}

/**
 * Returns the URL for an order
 * @param int $order_id An order ID
 * @return string The URL for the order
 */
function appthemes_get_order_url( $order_id ){
	return APP_Order::get_url( $order_id );
}

/**
 * Returns the orders revenue for given date period, total revenue if date range not specified
 * @param string $since_date Optional. A since date in Y-m-d date format
 * @param string $until_date Optional. A until date in Y-m-d date format
 * @return int The orders revenue for given date period
 */
function appthemes_get_orders_revenue( $since_date = false, $until_date = false ) {
	global $wpdb;

	$sql = "SELECT sum( m.meta_value ) FROM $wpdb->postmeta m INNER JOIN $wpdb->posts p ON m.post_id = p.ID WHERE m.meta_key = 'total_price' AND p.post_status IN ( '" . APPTHEMES_ORDER_COMPLETED . "', '" . APPTHEMES_ORDER_ACTIVATED . "' )";

	if ( $since_date && is_string( $since_date ) )
		$sql .= $wpdb->prepare( " AND p.post_date >= %s", $since_date );

	if ( $until_date && is_string( $until_date ) )
		$sql .= $wpdb->prepare( " AND p.post_date <= %s", $until_date );

	return $wpdb->get_var( $sql );
}

/**
 * Returns items connected via APPTHEMES_ORDER_CONNECTION
 * @param  int $id					ID of post connected
 * @param  array $args (optional)	Additional params to pass to the WP_Query
 * @return array					See WP_Query
 */
function _appthemes_orders_get_connected( $id, $args = array() ){

	if( !is_numeric( $id ) )
		trigger_error( 'Invalid order id given. Must be an integer', E_USER_WARNING );

	$defaults = array(
		'connected_type' => APPTHEMES_ORDER_CONNECTION,
		'connected_query' => array( 'post_status' => 'any' ),
		'post_status' => 'any',
		'nopaging' => true
	);
	$args = wp_parse_args( $args, $defaults );

	$type = get_post_type( $id );
	if( $type == APPTHEMES_ORDER_PTYPE ){
		$args['connected_from'] = $id;
	}else{
		$args['connected_to'] = $id;
	}

	return new WP_Query( $args );
}

/**
 * Adds tax to an order based on settings
 */
function appthemes_payments_add_tax( $order ){

	$order->remove_item( '_regional-tax' );

	$options = APP_Gateway_Registry::get_options();
	$tax_rate = $options->tax_charge;

	$total = $order->get_total();
	$charged_tax = $total * ( $tax_rate / 100 );

	if( $charged_tax == 0 )
		return;

	$order->add_item( '_regional-tax', number_format( $charged_tax, 2, '.', '' ), $order->get_id() );
}

/**
 * Schedules the next reccurance of an activated recurring order
 * Hooked to appthemes_transaction_activated
 */
function appthemes_schedule_next_order_recurrance( $order ) {
	if ( $order->is_recurring() ) {

		$next_order = APP_Order_Factory::duplicate( $order );
		$type = $order->get_recurring_period_type();

		switch( $type ) {
			case 'Y' :
				$period_type = 'years';
				break;
			case 'W' :
				$period_type = 'weeks';
				break;
			case 'M' :
				$period_type = 'months';
				break;
			case 'D' :
			default:
				$period_type = 'days';
				break;
		}

		$date = date( 'Y-m-d H:i:s', strtotime( '+' . intval( $order->get_recurring_period() ) . ' ' . $period_type ) );

		wp_update_post( array(
			'ID'			=> $next_order->get_id(),
			'post_parent'	=> $order->get_id(),
			'post_status'	=> APPTHEMES_ORDER_PENDING,
			'post_date'		=> $date,
		) );

	}
}

/**
 * Gets a display version of a recurring period type
 */
function appthemes_get_recurring_period_type_display( $period_type, $period = 1 ) {
	$period_type = strtolower( $period_type );
	switch( $period_type ) {
		case 'y' :
		case 'year' :
			$period_type = _n( 'year', 'years', $period, APP_TD );
			break;
		case 'w' :
		case 'week' :
			$period_type = _n( 'week', 'weeks', $period, APP_TD );
			break;
		case 'm' :
		case 'month' :
			$period_type = _n( 'month', 'months', $period, APP_TD );
			break;
		case 'd' :
		case 'day' :
		default:
			$period_type = _n( 'day', 'days', $period, APP_TD );
			break;
	}

	return $period_type;
}

/**
 * Checks if escrow sub component is enabled.
 *
 * @return bool
 */
function appthemes_is_escrow_enabled() {
	return (bool) appthemes_payments_get_args( 'escrow' );
}

/**
 * Checks if recurring payments is available.
 *
 * @return bool
 */
function appthemes_is_recurring_available() {
	return class_exists( 'APP_Recurring_Order' );
}

/**
 * Checks if a given order is an escrow order or a normal order without loading the order
 *
 * @param mixed $order_id The Order ID or Order object to check
 *
 * @return bool True if is an escrow Order, False if is a normal Order
 */
function appthemes_is_recurring_order( $order_id ) {

	if ( ! appthemes_is_recurring_available() ) {
		return false;
	} else {
		return _appthemes_is_recurring_order( $order_id );
	}

}
