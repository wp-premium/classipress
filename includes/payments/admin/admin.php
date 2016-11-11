<?php
/**
 * Register Payments admin menu.
 *
 * @package Components\Payments\Admin
 */

if ( is_admin() ) {
	add_action( 'admin_menu', 'appthemes_admin_menu_setup', 11 );
	add_action( 'init', 'appthemes_register_payments_settings', 12 );
	add_action( 'parse_request', 'appthemes_admin_quick_find_post' );
}

/**
 * Get the full URL for an image.
 *
 * @param string $name The basename of the image.
 * @return string
 */
function appthemes_payments_image( $name ) {
	return appthemes_payments_get_args( 'images_url' ) . $name;
}

/**
 * Registers the payment settings page.
 * @return void
 */
function appthemes_register_payments_settings() {
	new APP_Payments_Settings_Admin( APP_Gateway_Registry::get_options() );
}

/**
 * Adds the Orders Top Level Menu.
 * @return void
 */
function appthemes_admin_menu_setup() {
	$capability = apply_filters( 'appthemes_map_view_orders_capability', 'edit_others_posts');
	add_menu_page( __( 'Orders', APP_TD ), __( 'Payments', APP_TD ), $capability, 'app-payments', null, 'dashicons-at-payments', 4 );
}

add_filter( 'appthemes_map_view_orders_capability', 'appthemes_admin_view_orders_setting' );
function appthemes_admin_view_orders_setting( $capability ) {
	$options    = APP_Gateway_Registry::get_options();
	if ( $options->allow_view_orders ) {
		$capability = 'edit_posts';
	}

	return $capability;
}

/**
 * Quick find a post.
 * @param  object $wp_query A WP_Query object.
 * @return void
 */
function appthemes_admin_quick_find_post( $wp_query ) {
	global $pagenow;

	if ( 'edit.php' !== $pagenow ) {
		return;
	}

	if ( empty( $wp_query->query_vars['s'] ) ) {
		return;
	}

	$query = $wp_query->query_vars['s'];
	if ( '#' !== substr( $query, 0, 1 ) ) {
		return;
	}

	$id = absint( substr( $query, 1 ) );
	if ( ! $id ) {
		$wp_query->query_vars['s'] = 'Bad ID';
	}

	$post = get_post( $id );
	if ( $post ) {
		$wp_query->query_vars['s'] = get_edit_post_link( $id );
		wp_redirect( 'post.php?action=edit&post=' . $id );
		exit;
	} else {
		$wp_query->query_vars['s'] = 'Not Found';
	}

}
