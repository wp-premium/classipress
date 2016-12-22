<?php
/**
 * Escrow Payments
 *
 * @package Components\Payments\Escrow
 */

/**
 * Order Statuses
 */
define( 'APPTHEMES_ORDER_PAID', 'tr_paid' );
define( 'APPTHEMES_ORDER_REFUNDED', 'tr_refunded' );


add_action( 'init', '_appthemes_register_escrow_statuses' );

// Escrow Gateways
require_once( dirname( __FILE__ ) . '/gateways/paypal-adaptive/paypal-adaptive-request.php' );
require_once( dirname( __FILE__ ) . '/gateways/paypal-adaptive/paypal-adaptive.php' );

// include escrow related files
require_once( dirname( __FILE__ ) . '/escrow-settings-form-class.php' );
require_once( dirname( __FILE__ ) . '/order-escrow-functions.php' );
require_once( dirname( __FILE__ ) . '/order-escrow-factory.php' );
require_once( dirname( __FILE__ ) . '/order-escrow-class.php' );
require_once( dirname( __FILE__ ) . '/admin/settings.php' );

/**
 * Register escrow related payments statuses.
 *
 * @return void
 */
function _appthemes_register_escrow_statuses() {
	if ( ! appthemes_is_escrow_enabled() ) {
		return;
	}

	register_post_status( APPTHEMES_ORDER_PAID, array(
		'public' => true,
		'show_in_admin_all_list' => true,
		'show_in_admin_status_list' => true,
		'label_count' => _n_noop( 'Paid <span class="count">(%s)</span>', 'Paid <span class="count">(%s)</span>', APP_TD ),
	));

	register_post_status( APPTHEMES_ORDER_REFUNDED, array(
		'public' => true,
		'show_in_admin_all_list' => true,
		'show_in_admin_status_list' => true,
		'label_count' => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', APP_TD ),
	));

}
