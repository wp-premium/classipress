<?php
/**
 * Admin Packages lists.
 *
 * @package ClassiPress\Admin\Packages
 * @author  AppThemes
 * @since   ClassiPress 3.4
 */


// Listing Package
add_filter( 'manage_' . CP_PACKAGE_LISTING_PTYPE . '_posts_columns', 'cp_package_listing_manage_columns', 11 );
add_action( 'manage_' . CP_PACKAGE_LISTING_PTYPE . '_posts_custom_column', 'cp_package_listing_add_column_data', 10, 2 );
add_filter( 'admin_notices', 'cp_package_listing_list_page_notice', 11 );

// Membership Package
add_filter( 'manage_' . CP_PACKAGE_MEMBERSHIP_PTYPE . '_posts_columns', 'cp_package_membership_manage_columns', 11 );
add_action( 'manage_' . CP_PACKAGE_MEMBERSHIP_PTYPE . '_posts_custom_column', 'cp_package_membership_add_column_data', 10, 2 );
add_filter( 'admin_notices', 'cp_package_membership_list_page_notice', 11 );


/**
 * Modifies columns on admin listing package page.
 *
 * @param array $columns
 *
 * @return array
 */
function cp_package_listing_manage_columns( $columns ) {

	$columns['title'] = __( 'Name', APP_TD );
	$columns['description'] = __( 'Description', APP_TD );
	$columns['price'] = __( 'Price Per Ad', APP_TD );
	$columns['duration'] = __( 'Duration', APP_TD );
	$columns['status'] = __( 'Status', APP_TD );

	unset( $columns['date'] );

	return $columns;
}


/**
 * Displays listing package custom columns data.
 *
 * @param string $column_index
 * @param int $post_id
 *
 * @return void
 */
function cp_package_listing_add_column_data( $column_index, $post_id ) {

	$package = cp_get_listing_package( $post_id );
	if ( ! $package ) {
		return;
	}

	switch ( $column_index ) {

		case 'description' :
			echo strip_tags( $package->description );
			break;

		case 'price' :
			appthemes_display_price( $package->price );
			break;

		case 'duration' :
			printf( _n( '%d day', '%d days', $package->duration, APP_TD ), $package->duration );
			break;

		case 'status' :
			if ( $package->post_status == 'publish' ) {
				_e( 'Active', APP_TD );
			} else {
				_e( 'Inactive', APP_TD );
			}
			break;

	}
}


/**
 * Displays notices on admin listing package page.
 *
 * @return void
 */
function cp_package_listing_list_page_notice() {
	global $pagenow, $typenow, $cp_options;

	if ( $pagenow != 'edit.php' || $typenow != CP_PACKAGE_LISTING_PTYPE ) {
		return;
	}

	if ( $cp_options->price_scheme != 'single' ) {
		echo scb_admin_notice( sprintf( __( 'Ad Packs are disabled. Change the <a href="%1$s">pricing model</a> to enable Ad Packs.', APP_TD ), 'admin.php?page=app-pricing&tab=general' ), 'error' );
	}

	echo scb_admin_notice(
		__( 'Ad Packs allow you to create bundled listing options for your customers to choose from.', APP_TD ) . '<br />' .
		__( 'For example, instead of only offering a set price for xx days (30 days for $5), you could also offer discounts for longer terms (60 days for $7).', APP_TD ) . '<br />' .
		__( 'These only work if you are selling ads and using the "Fixed Price Per Ad" price model.', APP_TD )
	);

}


/**
 * Modifies columns on admin membership package page.
 *
 * @param array $columns
 *
 * @return array
 */
function cp_package_membership_manage_columns( $columns ) {

	$columns['title'] = __( 'Name', APP_TD );
	$columns['description'] = __( 'Description', APP_TD );
	$columns['price'] = __( 'Price Modifier', APP_TD );
	$columns['terms'] = __( 'Terms', APP_TD );
	$columns['status'] = __( 'Status', APP_TD );

	unset( $columns['date'] );

	return $columns;
}


/**
 * Displays membership package custom columns data.
 *
 * @param string $column_index
 * @param int $post_id
 *
 * @return void
 */
function cp_package_membership_add_column_data( $column_index, $post_id ) {

	$package = cp_get_membership_package( $post_id );
	if ( ! $package ) {
		return;
	}

	switch ( $column_index ) {

		case 'description' :
			echo strip_tags( $package->description );
			break;

		case 'price' :
			echo cp_get_membership_package_benefit_text( $package->ID );
			break;

		case 'terms' :
			printf( __( '%1$s / %2$s days', APP_TD ), appthemes_get_price( $package->price ), $package->duration );
			break;

		case 'status' :
			if ( $package->post_status == 'publish' ) {
				_e( 'Active', APP_TD );
			} else {
				_e( 'Inactive', APP_TD );
			}
			break;

	}
}


/**
 * Displays notices on admin membership package page.
 *
 * @return void
 */
function cp_package_membership_list_page_notice() {
	global $pagenow, $typenow, $cp_options;

	if ( $pagenow != 'edit.php' || $typenow != CP_PACKAGE_MEMBERSHIP_PTYPE ) {
		return;
	}

	if ( ! $cp_options->enable_membership_packs ) {
		echo scb_admin_notice( sprintf( __( 'Membership Packs are disabled. Enable the <a href="%1$s">membership packs</a> option.', APP_TD ), 'admin.php?page=app-pricing&tab=membership' ), 'error' );
	}

	echo scb_admin_notice(
		__( 'Membership Packs allow you to setup subscription-based pricing packages.', APP_TD ) . '<br />' .
		__( 'This enables your customers to post unlimited ads for a set period of time or until the membership becomes inactive.', APP_TD ) . '<br />' .
		sprintf( __( 'These memberships affect pricing regardless of the ad packs or pricing model you have set as long as you have enabled the <a href="%1$s">membership packs</a> option.', APP_TD ), 'admin.php?page=app-pricing&tab=membership' )
	);

}

