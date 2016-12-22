<?php
/**
 * Packages functions.
 *
 * @package ClassiPress\Packages
 * @author  AppThemes
 * @since   ClassiPress 3.4
 */


add_action( 'init', 'cp_packages_setup' );
add_action( 'admin_menu', 'cp_packages_add_menu', 11 );
add_filter( 'parent_file', 'cp_packages_set_menu_parent_page' );

add_action( 'wp_trash_post', '_cp_delete_order_membership_meta' );
add_action( 'deleted_post', '_cp_delete_order_membership_meta' );
add_action( 'untrashed_post', '_cp_restore_order_membership_meta' );

/**
 * Setups packages.
 *
 * @return void
 */
function cp_packages_setup() {

	// Listing Packages
	$labels = array(
		'name'               => __( 'Ad Packs', APP_TD ),
		'singular_name'      => __( 'Ad Pack', APP_TD ),
		'add_new'            => __( 'Add New', APP_TD ),
		'add_new_item'       => __( 'Add New Pack', APP_TD ),
		'edit_item'          => __( 'Edit Pack', APP_TD ),
		'new_item'           => __( 'New Pack', APP_TD ),
		'view_item'          => __( 'View Pack', APP_TD ),
		'search_items'       => __( 'Search Packs', APP_TD ),
		'not_found'          => __( 'No Packs found', APP_TD ),
		'not_found_in_trash' => __( 'No Packs found in Trash', APP_TD ),
		'parent_item_colon'  => __( 'Parent Pack:', APP_TD ),
		'menu_name'          => __( 'Ad Packs', APP_TD ),
	);

	$args = array(
		'labels'          => $labels,
		'hierarchical'    => false,
		'supports'        => array( 'page-attributes' ),
		'public'          => false,
		'capability_type' => 'page',
		'show_ui'         => true,
		'show_in_menu'    => false,
	);

	register_post_type( CP_PACKAGE_LISTING_PTYPE, $args );

	$listing_packages = new WP_Query( array( 'post_type' => CP_PACKAGE_LISTING_PTYPE, 'nopaging' => 1 ) );
	foreach ( $listing_packages->posts as $listing_package ) {
		APP_Item_Registry::register( $listing_package->post_name, sprintf( __( 'Package: %s', APP_TD ), $listing_package->post_title ) );
	}


	// Membership Packages
	$labels = array(
		'name'               => __( 'Membership Packs', APP_TD ),
		'singular_name'      => __( 'Membership Pack', APP_TD ),
		'add_new'            => __( 'Add New', APP_TD ),
		'add_new_item'       => __( 'Add New Pack', APP_TD ),
		'edit_item'          => __( 'Edit Pack', APP_TD ),
		'new_item'           => __( 'New Pack', APP_TD ),
		'view_item'          => __( 'View Pack', APP_TD ),
		'search_items'       => __( 'Search Packs', APP_TD ),
		'not_found'          => __( 'No Packs found', APP_TD ),
		'not_found_in_trash' => __( 'No Packs found in Trash', APP_TD ),
		'parent_item_colon'  => __( 'Parent Pack:', APP_TD ),
		'menu_name'          => __( 'Membership Packs', APP_TD ),
	);

	$args = array(
		'labels'          => $labels,
		'hierarchical'    => false,
		'supports'        => array( 'page-attributes' ),
		'public'          => false,
		'capability_type' => 'page',
		'show_ui'         => true,
		'show_in_menu'    => false,
	);

	register_post_type( CP_PACKAGE_MEMBERSHIP_PTYPE, $args );

	$membership_packages = new WP_Query( array( 'post_type' => CP_PACKAGE_MEMBERSHIP_PTYPE, 'nopaging' => 1 ) );
	foreach ( $membership_packages->posts as $membership_package ) {
		APP_Item_Registry::register( $membership_package->post_name, sprintf( __( 'Membership: %s', APP_TD ), $membership_package->post_title ) );
	}

}


/**
 * Adds packages into Payments menu.
 *
 * @return void
 */
function cp_packages_add_menu() {
	global $pagenow, $typenow;

	$package_types = array( CP_PACKAGE_LISTING_PTYPE, CP_PACKAGE_MEMBERSHIP_PTYPE );

	foreach ( $package_types as $ptype ) {
		$ptype_obj = get_post_type_object( $ptype );

		add_submenu_page( 'app-payments', $ptype_obj->labels->name, $ptype_obj->labels->all_items, $ptype_obj->cap->edit_posts, "edit.php?post_type=$ptype" );

		if ( $pagenow == 'post-new.php' && $typenow == $ptype ) {
			add_submenu_page( 'app-payments', $ptype_obj->labels->new_item, $ptype_obj->labels->new_item, $ptype_obj->cap->edit_posts, "post-new.php?post_type=$ptype" );
		}
	}

}


/**
 * Sets the Payments as parent page in menu.
 *
 * @param string $parent_file
 *
 * @return string
 */
function cp_packages_set_menu_parent_page( $parent_file ) {
	global $pagenow, $typenow;

	$package_types = array( CP_PACKAGE_LISTING_PTYPE, CP_PACKAGE_MEMBERSHIP_PTYPE );

	foreach ( $package_types as $ptype ) {
		if ( $parent_file == "edit.php?post_type=$ptype" && ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) && $typenow == $ptype ) {
			return 'app-payments';
		}
	}

	return $parent_file;
}


/**
 * Returns membership package benefit text.
 *
 * @param int $pack_id
 *
 * @return string
 */
function cp_get_membership_package_benefit_text( $pack_id ) {
	global $cp_options;

	if ( ! current_theme_supports( 'app-price-format' ) ) {
		return '';
	}

	$package = cp_get_membership_package( $pack_id );
	if ( ! $package ) {
		return '';
	}

	$benefit = '';

	if ( ! $cp_options->charge_ads ) {
		$benefit .= __( 'Free Posting', APP_TD );
	} else if ( $package->pack_type == 'percentage' ) {
		//$benefit .= sprintf( __( '%s%% of price', APP_TD ), preg_replace( '/.00$/', '', $package->price_modifier ) ); //remove decimal when decimal is .00
		$benefit .=  sprintf( __( '%s%% of price', APP_TD ), (float) $package->price_modifier );
	} else if ( $package->pack_type == 'discount' ) {
		$benefit .= sprintf( __( '%s\'s less per ad', APP_TD ), appthemes_get_price( $package->price_modifier ) );
	} else if ( $package->pack_type == 'static' ) {
		if ( $package->price_modifier == 0 ) {
			$benefit .= __( 'Free Posting', APP_TD );
		} else {
			$benefit .= sprintf( __( '%s per ad', APP_TD ), appthemes_get_price( $package->price_modifier ) );
		}
	}

	if ( ! empty( $benefit ) && $package->pack_satisfies_required ) {
		$benefit .= ' (' . __( 'required to post', APP_TD ) . ')';
	}

	return $benefit;
}


/**
 * Returns membership package benefit.
 *
 * @param int $pack_id
 * @param float $price
 *
 * @return float
 */
function cp_calculate_membership_package_benefit( $pack_id, $price ) {

	$package = cp_get_membership_package( $pack_id );
	if ( ! $package ) {
		return $price;
	}

	if ( $package->pack_type == 'percentage' ) {
		$multiplier = $package->price_modifier / 100;
		$price = $price * $multiplier;
	} else if ( $package->pack_type == 'discount' ) {
		$price = $price - $package->price_modifier;
	} else if ( $package->pack_type == 'static' ) {
		$price = $package->price_modifier;
	}

	return number_format( $price, 2, '.', '' );
}


/**
 * Returns membership packages.
 *
 * @param array $args (optional)
 *
 * @return array
 */
function cp_get_membership_packages( $args = array() ) {
	$defaults = array(
		'post_type'     => CP_PACKAGE_MEMBERSHIP_PTYPE,
		'post_status'   => 'publish',
		'nopaging'      => 1,
		'no_found_rows' => true,
		'orderby'       => 'menu_order',
		'order'         => 'asc'
	);
	$args = wp_parse_args( $args, $defaults );

	$packages = new WP_Query( $args );

	if ( empty( $packages->posts ) ) {
		return array();
	}

	$membership_packages = array();
	foreach ( $packages->posts as $package ) {
		$membership_packages[] = cp_get_membership_package( $package->ID );
	}
	return $membership_packages;
}


/**
 * Returns membership package.
 *
 * @param int $pack_id
 *
 * @return object
 */
function cp_get_membership_package( $pack_id ) {
	$package = get_post( $pack_id );
	if ( ! $package || $package->post_type != CP_PACKAGE_MEMBERSHIP_PTYPE ) {
		return false;
	}

	$package_meta = get_post_custom( $pack_id );

	$package->pack_name = ! empty( $package_meta['pack_name'][0] ) ? $package_meta['pack_name'][0] : '';
	$package->pack_type = ! empty( $package_meta['pack_type'][0] ) ? $package_meta['pack_type'][0] : 'static';
	$package->pack_satisfies_required = ! empty( $package_meta['pack_satisfies_required'][0] );

	$package->price = ! empty( $package_meta['price'][0] ) ? (float) $package_meta['price'][0] : 0;
	$package->price_modifier = ! empty( $package_meta['price_modifier'][0] ) ? (float) $package_meta['price_modifier'][0] : 0;

	$package->duration = ! empty( $package_meta['duration'][0] ) ? (int) $package_meta['duration'][0] : 30;
	$package->description = ! empty( $package_meta['description'][0] ) ? $package_meta['description'][0] : '';

	return $package;
}


/**
 * Returns membership package.
 *
 * @param object $order
 *
 * @return object
 */
function cp_get_membership_package_from_order( $order ) {
	$packages = cp_get_membership_packages();

	foreach ( $packages as $package ) {
		if ( $order->get_items( $package->post_name ) ) {
			return $package;
		}
	}

	return false;
}


/**
 * Returns user membership package.
 *
 * @param int $user_id
 *
 * @return object
 */
function cp_get_user_membership_package( $user_id ) {
	$user = get_user_by( 'id', $user_id );
	if ( ! $user ) {
		return false;
	}

	if ( empty( $user->active_membership_pack ) || empty( $user->membership_expires ) ) {
		return false;
	}

	$current_membership = cp_get_membership_package( $user->active_membership_pack );
	if ( $current_membership && appthemes_days_between_dates( $user->membership_expires ) > 0 ) {
		return $current_membership;
	}

	return false;
}


/**
 * Updates user membership.
 *
 * @param int $user_id
 * @param object $package
 *
 * @return bool
 */
function cp_update_user_membership( $user_id, $package ) {
	$user = get_user_by( 'id', $user_id );
	if ( ! $user || ! $package ) {
		return false;
	}

	$current_membership = cp_get_user_membership_package( $user_id );

	if ( $current_membership && $current_membership->ID == $package->ID ) {
		// user have active that same membership, so extend date
		$base_date = $user->membership_expires;
	} else {
		$base_date = current_time( 'mysql' );
	}

	$new_expiration_date = appthemes_mysql_date( $base_date, $package->duration );

	// update user membership package id and expiration date
	update_user_meta( $user_id, 'active_membership_pack', $package->ID );
	update_user_meta( $user_id, 'membership_expires', $new_expiration_date );

	return true;
}


/**
 * Deletes/trashes membership related meta on trashed/deleted membership orders.
 *
 * User meta related with the active membership is kept until
 * the order is permanently deleted. It can be restored while in trash.
 *
 * @since 3.5
 */
function _cp_delete_order_membership_meta( $post_id ) {
	$action = current_action();

	$post = get_post( $post_id );

	if ( APPTHEMES_ORDER_PTYPE != $post->post_type ) {
		return;
	}

	$user_id = $post->post_author;

	$delete_active_umeta = false;

	// trashed order
	if ( 'wp_trash_post' == $action ) {
		$order = appthemes_get_order( $post_id );

		// check if the order is for a membership purchase
		$pack = cp_get_order_plan_data( $order, CP_PACKAGE_MEMBERSHIP_PTYPE );
		if ( ! $pack ) {
			return;
		}

		$active_pack_id = get_user_meta( $user_id, 'active_membership_pack', true );
		$active_expire_date = get_user_meta( $user_id, 'membership_expires', true );

		// only keep trashed membership meta if the trashed order pack ID is for an active membership
		if  ( ! $active_pack_id || $active_pack_id != $pack['data']['ID'] ) {
			return;
		}

		// temporarily store trashed membership meta if the order is trashed
		update_user_meta( $user_id, '_trashed-active_membership_pack', $active_pack_id );
		update_user_meta( $user_id, '_trashed-membership_expires', $active_expire_date );

		// set a new user meta flag to better identify the trashed order if it's later permanently deleted
		update_user_meta( $user_id, '_trashed-membership_order_id', $post_id );

		$delete_active_umeta = true;

	// permanently deleted order
	} else {

		$trashed_order_id = get_user_meta( $user_id, '_trashed-membership_order_id', true );

	}

	// delete any existing trashed membership meta if the order is permanently deleted
	if ( ! empty( $trashed_order_id ) && $post_id == $trashed_order_id ) {
		delete_user_meta( $user_id, '_trashed-membership_order_id' );
		delete_user_meta( $user_id, '_trashed-active_membership_pack' );
		delete_user_meta( $user_id, '_trashed-membership_expires' );

		$delete_active_umeta = true;
	}

	// delete user meta only if this is an order with an active membership
	if ( $delete_active_umeta ) {
		delete_user_meta( $user_id, 'active_membership_pack' );
		delete_user_meta( $user_id, 'membership_expires' );
	}

}


/**
 * Restores existing membership meta on previously trashed orders.
 *
 * @since 3.5
 */
function _cp_restore_order_membership_meta( $post_id ) {
	$post = get_post( $post_id );

	if ( APPTHEMES_ORDER_PTYPE != $post->post_type ) {
		return;
	}

	$order = appthemes_get_order( $post_id );

	// check if the order is for a membership purchase
	$pack = cp_get_order_plan_data( $order, CP_PACKAGE_MEMBERSHIP_PTYPE );
	if ( ! $pack ) {
		return;
	}

	$user_id = $post->post_author;

	$trashed_order_id = get_user_meta( $user_id, '_trashed-membership_order_id', true );

	// check if this is an order with a previously active membership previously trashed
	if ( $post_id == $trashed_order_id ) {

		$active_pack_id = get_user_meta( $user_id, 'active_membership_pack', true );

		// only restore if there's not a new membership already activated - otherwise skip and clear previous trash meta
		if ( ! $active_pack_id ) {
			$pack_id = get_user_meta( $user_id, '_trashed-active_membership_pack', true );
			$expire_date = get_user_meta( $user_id, '_trashed-membership_expires', true );

			// restore membership meta if the order is trashed
			update_user_meta( $user_id, 'active_membership_pack', $pack_id );
			update_user_meta( $user_id, 'membership_expires', $expire_date );
		}

		// delete the temp trashed membership meta
		delete_user_meta( $user_id, '_trashed-membership_order_id' );
		delete_user_meta( $user_id, '_trashed-active_membership_pack' );
		delete_user_meta( $user_id, '_trashed-membership_expires' );
	}

}


/**
 * Returns listing packages.
 *
 * @param array $args (optional)
 *
 * @return array
 */
function cp_get_listing_packages( $args = array() ) {
	$defaults = array(
		'post_type'     => CP_PACKAGE_LISTING_PTYPE,
		'post_status'   => 'publish',
		'nopaging'      => 1,
		'no_found_rows' => true,
		'orderby'       => 'menu_order',
		'order'         => 'asc'
	);
	$args = wp_parse_args( $args, $defaults );

	$packages = new WP_Query( $args );

	if ( empty( $packages->posts ) ) {
		return array();
	}

	$listing_packages = array();
	foreach ( $packages->posts as $package ) {
		$listing_packages[] = cp_get_listing_package( $package->ID );
	}
	return $listing_packages;
}


/**
 * Returns listing package.
 *
 * @param int $pack_id
 *
 * @return object
 */
function cp_get_listing_package( $pack_id ) {
	$package = get_post( $pack_id );
	if ( ! $package || $package->post_type != CP_PACKAGE_LISTING_PTYPE ) {
		return false;
	}

	$package_meta = get_post_custom( $pack_id );

	$package->pack_name = ! empty( $package_meta['pack_name'][0] ) ? $package_meta['pack_name'][0] : '';
	$package->price = ! empty( $package_meta['price'][0] ) ? (float) $package_meta['price'][0] : 0;
	$package->duration = ! empty( $package_meta['duration'][0] ) ? (int) $package_meta['duration'][0] : 30;
	$package->description = ! empty( $package_meta['description'][0] ) ? $package_meta['description'][0] : '';

	return $package;
}


/**
 * Returns duration of listing package.
 *
 * @param int $package_id
 *
 * @return int
 */
function cp_get_ad_pack_length( $package_id ) {

	if ( ! $package_id ) {
		return 0;
	}

	$listing_package = cp_get_listing_package( $package_id );
	if ( ! $listing_package ) {
		return 0;
	}

	return $listing_package->duration;
}
