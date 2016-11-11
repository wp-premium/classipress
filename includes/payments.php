<?php
/**
 * Payment functions.
 *
 * @package ClassiPress\Payments
 * @author  AppThemes
 * @since   ClassiPress 3.3
 */


add_action( 'pending_to_publish', 'cp_payments_handle_moderated_transaction' );

add_action( 'appthemes_transaction_completed', 'cp_handle_payment_transaction' );
add_action( 'appthemes_transaction_activated', 'cp_handle_payment_transaction' );

add_action( 'appthemes_after_order_summary', 'cp_payments_display_order_summary_continue_button' );

/**
 * Triggers on payment transaction event and calls appropriate procedure.
 *
 * @param APP_Order $order An order object.
 */
function cp_handle_payment_transaction( $order ) {

	$event = current_action();
	$ad_id = _cp_get_order_ad_id( $order );

	if ( $ad_id ) {

		if ( 'appthemes_transaction_completed' === $event ) {
			cp_payments_handle_ad_listing_completed( $order, $ad_id );
		} elseif ( 'appthemes_transaction_activated' === $event ) {
			cp_payments_handle_ad_listing_activated( $order, $ad_id );
		}

	} elseif ( $package = cp_get_membership_package_from_order( $order ) ) {

		if ( 'appthemes_transaction_completed' === $event ) {
			cp_payments_handle_membership_completed( $order, $package );
		} elseif ( 'appthemes_transaction_activated' === $event ) {
			cp_payments_handle_membership_activated( $order, $package );
		}
	}

}

/**
 * Activates ad listing order and redirects user to ad if moderation is disabled,
 * otherwise redirects user to order summary.
 *
 * @param APP_Order $order An order object.
 * @param int $ad_id       An ad ID.
 *
 * @return void
 */
function cp_payments_handle_ad_listing_completed( $order, $ad_id = 0 ) {
	global $cp_options;

	if ( ! $ad_id ) {
		return;
	}

	// allow overriding order activations
	if ( ! apply_filters( 'cp_activate_order', true ) ) {
	   return;
	}

	$order_url = $order->get_return_url();

	if ( ! $cp_options->moderate_ads ) {

		$order->activate();

		if ( ! is_admin() ) {
			if ( did_action( 'wp_head' ) ) {
				cp_js_redirect( $order_url );
			} else {
				wp_redirect( $order_url );
			}
		}
		return;

	} else {

		wp_update_post( array( 'ID' => $ad_id, 'post_status' => 'pending' ) );
		if ( ! is_admin() ) {
			if ( did_action( 'wp_head' ) ) {
				cp_js_redirect( $order_url );
			} else {
				wp_redirect( $order_url );
			}
		}
		return;

	}
}


/**
 * Activates membership order if has been completed.
 *
 * @param APP_Order $order   An order object.
 * @param WP_Post   $package A package object.
 *
 * @return void
 */
function cp_payments_handle_membership_completed( $order, $package = false ) {

	if ( $package ) {
		$order->activate();
		if ( ! is_admin() ) {
			$order_url = $order->get_return_url();
			if ( did_action( 'wp_head' ) ) {
				cp_js_redirect( $order_url );
			} else {
				wp_redirect( $order_url );
			}
		}
	}

}


/**
 * Handles moderated transaction.
 *
 * @param object $post
 *
 * @return void
 */
function cp_payments_handle_moderated_transaction( $post ) {

	if ( $post->post_type != APP_POST_TYPE ) {
		return;
	}

	$order = appthemes_get_order_connected_to( $post->ID );
	if ( ! $order || $order->get_status() !== APPTHEMES_ORDER_COMPLETED ) {
		return;
	}

	add_action( 'save_post', 'cp_payments_activate_moderated_transaction', 11 );
}


/**
 * Activates moderated transaction.
 *
 * @param int $post_id
 *
 * @return void
 */
function cp_payments_activate_moderated_transaction( $post_id ) {

	if ( get_post_type( $post_id ) != APP_POST_TYPE ) {
		return;
	}

	$order = appthemes_get_order_connected_to( $post_id );
	if ( ! $order || $order->get_status() !== APPTHEMES_ORDER_COMPLETED ) {
		return;
	}

	$order->activate();

}


/**
 * Processes ad listing activation on order activation.
 *
 * @param APP_Order $order An order object.
 * @param int $ad_id       An ad ID.
 *
 * @return void
 */
function cp_payments_handle_ad_listing_activated( $order, $ad_id = 0 ) {
	global $cp_options;

	if ( ! $ad_id ) {
		return;
	}

	// update listing status
	$listing_args = array(
		'ID'            => $ad_id,
		'post_status'   => 'publish',
		'post_date'     => current_time( 'mysql' ),
		'post_date_gmt' => current_time( 'mysql', 1 ),
	);

	$listing_id = wp_update_post( $listing_args );

	$ad_length = get_post_meta( $listing_id, 'cp_sys_ad_duration', true );
	if ( empty( $ad_length ) ) {
		$ad_length = $cp_options->prun_period;
	}

	$ad_expire_date = appthemes_mysql_date( current_time( 'mysql' ), $ad_length );
	update_post_meta( $listing_id, 'cp_sys_expire_date', $ad_expire_date );

}


/**
 * Processes membership activation on order activation.
 *
 * @param APP_Order $order   An order object.
 * @param WP_Post   $package A package object.
 *
 * @return void
 */
function cp_payments_handle_membership_activated( $order, $package = false ) {

	$user = get_user_by( 'id', $order->get_author() );

	if ( $package && $user ) {
		$processed = cp_update_user_membership( $user->ID, $package );
		if ( $processed ) {
			cp_owner_activated_membership_email( $user, $order );
		}
	}

}


/**
 * Returns associated listing ID for given order, false if not found.
 *
 * @param object $order
 *
 * @return int|bool
 */
function _cp_get_order_ad_id( $order ) {

	foreach ( $order->get_items() as $item ) {
		if ( APP_POST_TYPE == $item['post']->post_type ) {
			return $item['post_id'];
		}
	}

	return false;
}


/**
 * Checks if payments are enabled on site.
 *
 * @param string $type
 *
 * @return bool
 */
function cp_payments_is_enabled( $type = 'listing' ) {
	global $cp_options;

	if ( ! current_theme_supports( 'app-payments' ) || ! current_theme_supports( 'app-price-format' ) ) {
		return false;
	}

	// check listing settings
	if ( $type == 'listing' ) {
		if ( ! $cp_options->charge_ads ) {
			return false;
		}

		if ( $cp_options->price_scheme == 'featured' && ! is_numeric( $cp_options->sys_feat_price ) ) {
			return false;
		}
	}

	// check membership settings
	if ( $type == 'membership' ) {
		if ( ! $cp_options->enable_membership_packs ) {
			return false;
		}
	}

	return true;
}


/**
 * Checks if post have some pending payment orders.
 *
 * @param int $post_id
 *
 * @return bool
 */
function cp_have_pending_payment( $post_id ) {

	if ( ! cp_payments_is_enabled() ) {
		return false;
	}

	$order = appthemes_get_order_connected_to( $post_id );
	if ( ! $order || ! in_array( $order->get_status(), array( APPTHEMES_ORDER_PENDING, APPTHEMES_ORDER_FAILED ) ) ) {
		return false;
	}

	return true;
}


/**
 * Returns url of order connected to given Post ID.
 *
 * @param int $post_id
 *
 * @return string
 */
function cp_get_order_permalink( $post_id ) {

	if ( ! cp_payments_is_enabled() ) {
		return;
	}

	$order = appthemes_get_order_connected_to( $post_id );
	if ( ! $order ) {
		return;
	}

	return appthemes_get_order_url( $order->get_id() );
}


/**
 * Displays Continue button on order summary page.
 *
 * @return void
 */
function cp_payments_display_order_summary_continue_button() {

	$url = '';
	$text = '';

	$step = _appthemes_get_step_from_query();
	if ( ! is_singular( APPTHEMES_ORDER_PTYPE ) && ( ! empty( $step ) && 'order-summary' !== $step ) ) {
		return;
	}

	$order = get_order();

	if ( $membership = cp_get_membership_package_from_order( $order ) ) {
		$package = cp_get_user_membership_package( $order->get_author() );

		if ( $package ) {
			$url = CP_ADD_NEW_URL;
			$text = __( 'Post a new Ad', APP_TD );
		} else {
			$url = CP_DASHBOARD_URL;
			$text = __( 'Visit your dashboard', APP_TD );
		}

	} else if ( $listing_id = _cp_get_order_ad_id( $order ) ) {
		$url = get_permalink( $listing_id );
		$text = __( 'View ad listing', APP_TD );
	}

	echo html( 'p', html( 'em', __( 'Thank you for your purchase!', APP_TD ) ) );

	if ( $url && $text ) {
		if ( ! in_array( $order->get_status(), array( APPTHEMES_ORDER_PENDING, APPTHEMES_ORDER_FAILED ) ) ) {
			echo html( 'p', html( 'em', __( 'Your order has been completed!', APP_TD ) ) );
		}
		echo html( 'button', array( 'type' => 'submit', 'class' => 'btn_orange', 'onClick' => "location.href='" . $url . "';return false;" ), $text );
	}
}


/**
 * Displays the total cost per listing on the 1st step page.
 *
 * @return void
 */
function cp_cost_per_listing() {
	global $cp_options;

	// make sure we are charging for ads
	if ( ! cp_payments_is_enabled() ) {
		_e( 'Free', APP_TD );
		return;
	}

	// figure out which pricing scheme is set
	switch( $cp_options->price_scheme ) {

		case 'category':
			$cost_per_listing = __( 'Price depends on category', APP_TD );
			break;

		case 'single':
			$cost_per_listing = __( 'Price depends on ad package selected', APP_TD );
			break;

		case 'percentage':
			$cost_per_listing = sprintf( __( '%s of your ad listing price', APP_TD ), $cp_options->percent_per_ad . '%' );
			break;

		case 'featured':
			$cost_per_listing = __( 'Free listing unless featured.', APP_TD );
			break;

		default:
			// pricing structure must be free
			$cost_per_listing = __( 'Free', APP_TD );
			break;

	}

	echo $cost_per_listing;
}


/**
 * Calculates the ad listing fee.
 *
 * @param int $category_id
 * @param int $package_id
 * @param float $cp_price
 * @param string $price_curr
 *
 * @return float
 */
function cp_ad_listing_fee( $category_id, $package_id, $cp_price, $price_curr ) {
	global $cp_options;

	// make sure we are charging for ads
	if ( ! cp_payments_is_enabled() ) {
		return 0;
	}

	// now figure out which pricing scheme is set
	switch( $cp_options->price_scheme ) {

		case 'category':
			$prices = $cp_options->price_per_cat;
			$adlistingfee = ( isset( $prices[ $category_id ] ) ) ? (float) $prices[ $category_id ] : 0;
			break;

		case 'percentage':
			// grab the % and then put it into a workable number
			$ad_percentage = ( $cp_options->percent_per_ad * 0.01 );
			// calculate the ad cost. Ad listing price x percentage.
			$adlistingfee = ( appthemes_clean_price( $cp_price, 'float' ) * $ad_percentage );

			// can modify listing fee. example: apply currency conversion
			$adlistingfee = apply_filters( 'cp_percentage_listing_fee', $adlistingfee, $cp_price, $ad_percentage, $price_curr );
			break;

		case 'featured':
			// listing price is always free in this pricing schema
			$adlistingfee = 0;
			break;

		case 'single':
		default: // pricing model must be single ad packs

			$listing_package = cp_get_listing_package( $package_id );
			if ( $listing_package ) {
				$adlistingfee = $listing_package->price;
			} else {
				$adlistingfee = 0;
				//sprintf( __( 'ERROR: no ad packs found for ID %s.', APP_TD ), $package_id );
			}
			break;

	}

	// return the ad listing fee
	return $adlistingfee;
}


/**
 * Calculates the total ad cost.
 *
 * @param int $category_id
 * @param int $package_id
 * @param float $featuredprice
 * @param float $cp_price
 * @param string $cp_coupon (deprecated)
 * @param string $price_curr
 *
 * @return float
 */
function cp_calc_ad_cost( $category_id, $package_id, $featuredprice, $cp_price, $cp_coupon, $price_curr ) {

	if ( ! cp_payments_is_enabled() ) {
		return 0;
	}

	// check for deprecated argument
	if ( ! empty( $cp_coupon ) ) {
		_deprecated_argument( __FUNCTION__, '3.3' );
	}

	// calculate the listing fee price
	$adlistingfee = cp_ad_listing_fee( $category_id, $package_id, $cp_price, $price_curr );
	// calculate the total cost for the ad.
	$totalcost_out = $adlistingfee + $featuredprice;

	//set proper return format
	$totalcost_out = number_format( $totalcost_out, 2, '.', '' );

	//if total cost is less then zero, then make the cost zero (free)
	if ( $totalcost_out < 0 ) {
		$totalcost_out = 0;
	}

	return $totalcost_out;
}

/**
 * Retrieves an associative array of available payment addons.
 *
 * @since 3.5
 */
function cp_get_addons( $type = '' ) {

	$addons = array(
		'featured' => array(
			array(
				'type' => 'featured-listing',
				'title' => __( 'Featured', APP_TD ),
				'meta' => array(),
			),
		),
		'pricing' => array(
			array(
				'type' => 'single',
				'title' => __( 'Fixed Price per Ad', APP_TD ),
				'meta' => array(),
			),
			array(
				'type' => 'category',
				'title' => __( 'Price per Category', APP_TD ),
				'meta' => array(),
			),
			array(
				'type' => 'percentage',
				'title' => __( '% of Sellers Price', APP_TD ),
				'meta' => array(),
			),
			array(
				'type' => 'featured',
				'title' => __( 'Free unless Featured', APP_TD ),
				'meta' => array(),
			),
			array(
				'type' => CP_ITEM_LISTING,
				'title' => __( 'Listing', APP_TD ),
				'meta' => array(),
			),
			array(
				'type' => CP_ITEM_MEMBERSHIP,
				'title' => __( 'Membership', APP_TD ),
				'meta' => array(),
			),
		),
	);

	if ( $type && ! empty( $addons[ $type ] ) ) {
		return $addons[ $type ];
	} elseif( $type ) {
		return false;
	}

	return array_merge( $addons['featured'], $addons['pricing'] );
}

/**
 * Retrieves all the available plan types.
 *
 * @since 3.5
 */
function cp_get_plan_types() {
	$types = array( CP_PACKAGE_LISTING_PTYPE, CP_PACKAGE_MEMBERSHIP_PTYPE );
	return $types;
}

/**
 * Retrieves all available plan types.
 */
function cp_get_plans( $plan_types, $args = array() ) {

	if ( ! $plan_types ) {
		$plan_types = cp_get_plan_types();
	}

	$defaults = array(
		'post_type'   => $plan_types,
		'nopaging'    => true,
		'post_status' => 'publish',
		'orderby'     => 'menu_order'
	);
	$args = wp_parse_args( $args, $defaults );

	$plans = new WP_Query( $args );

	$plans_data = array();

	foreach( $plans->posts as $key => $post ) {
		$plans_data[ $key ] = cp_get_plan_data( $post->ID );
		$plans_data[ $key ]['post'] = $post;
	}
	return $plans_data;
}

/**
 * Retrieves the plan data for a given Order and/or plan post type.
 *
 * @since 3.5
 */
function cp_get_order_plan_data( $order, $post_types = '' ) {

	// retrieve plan data from all plan types if the type was not specified
	if ( ! $post_types ) {
		$post_types = cp_get_plan_types();
	}

	$plans = cp_get_plans( $post_types );

	foreach( $plans as $key => $plan ) {

		if ( empty( $plan['post']->post_name ) ) {
			continue;
		}

		$plan_slug = $plan['post']->post_name;

		$items = $order->get_items( $plan_slug );

		if ( $items ) {
			return array(
				'type' => $plan_slug,
				'data' => $plan,
			);
		}
	}
	return false;
}

/**
 * Retrieves all data for a given plan ID.
 *
 * @since 3.5
 */
function cp_get_plan_data( $plan_id ) {

	$data = get_post_custom( $plan_id );

	$collapsed_data = array();

	foreach( $data as $key => $array ) {
		$collapsed_data[$key] = $array[0];
	}
	$collapsed_data['ID'] = $plan_id;

	// make sure we have valid price and relist price

	if ( empty( $collapsed_data['price'] ) ) {
		$collapsed_data['price'] = 0;
	}

	return $collapsed_data;
}

/**
 * Retrieve orders verbiages.
 *
 * @since 3.5
 *
 * @param  string $status Optional. The status to get the verbiage.
 *
 * @return mixed The verbiage for a given status or a list of verbiages.
 */
function cp_get_order_statuses_verbiages( $status = '' ) {

	$statuses = array(
		APPTHEMES_ORDER_PENDING		=> __( 'Pending', APP_TD ),
		APPTHEMES_ORDER_FAILED		=> __( 'Failed', APP_TD ),
		APPTHEMES_ORDER_COMPLETED	=> __( 'Completed', APP_TD ),
		APPTHEMES_ORDER_ACTIVATED	=> __( 'Activated', APP_TD ),
	);

	if ( $status && ! empty( $statuses ) ) {
		return $statuses[ $status ];
	} elseif( ! $status ) {
		return $statuses;
	}
	return;
}


/**
 * Retrieves the summary for a given Order.
 *
 * @since 3.5
 */
function cp_get_the_order_summary( $order, $output = 'plain' ) {

	$order_items = '';

	$items = $order->get_items();

	foreach ( $items as $item ) {
		if ( ! APP_Item_Registry::is_registered( $item['type'] ) ) {
			$item_title = __( 'Unknown', APP_TD );
		} else {
			$item_title = APP_Item_Registry::get_title( $item['type'] );
		}
		$item_html = ( 'html' == $output ? html( 'div', $item_title ) : ( $order_items ? ' / ' . $item_title : $item_title ) );
		$order_items .= $item_html;
	}

	if ( !$order_items )
		$order_items = '-';

	return $order_items;
}


### Template tags

/**
 * Retrieves the permalink for an Order ad.
 *
 * @since 3.5
 *
 * @param  $object $order The Order object.
 *
 * @return null|string The ad link or null if the Order is empty.
 */
function the_order_ad_link( $order ) {

	$ad_id = _cp_get_order_ad_id( $order );
	if ( ! $ad_id ) {
		return;
	}

	$title = get_the_title( $ad_id );

	$html = html( 'a', array( 'href' => esc_url( get_permalink( $ad_id ) ) ), $title );
	echo $html;
}

/**
 * Retrieves the payment information for a given order.
 *
 * @since 3.5
 *
 * @param  $object $order The Order object.
 *
 * @return string The Order information.
 */
function the_orders_history_payment( $order ) {
	$gateway_id = $order->get_gateway();

	if ( !empty( $gateway_id ) ) {
		$gateway = APP_Gateway_Registry::get_gateway( $gateway_id );
		if ( $gateway ) {
			$gateway = $gateway->display_name( 'admin' );
		} else {
			$gateway = __( 'Unknown', APP_TD );
		}
	} else {
		$gateway = __( 'Undecided', APP_TD );
	}

	$gateway = html( 'div', array( 'class' => 'order-history-gateway' ), $gateway );
	$status = html( 'div', array( 'class' => 'order-history-status' ), $order->get_display_status() );

	echo $gateway . $status;
}
