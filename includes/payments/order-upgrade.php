<?php
/**
 * Payments upgrade
 *
 * @package Components\Payments
 */

/**
 * Upgrades orders not using custom Order statuses
 * @return void
 */
function appthemes_upgrade_order_statuses(){

	$posts = new WP_Query( array(
		'post_type' => APPTHEMES_ORDER_PTYPE,
		'post_status' => array( 'publish', 'pending', 'draft' ),
		'nopaging' => true
	) );

	foreach( $posts->posts as $post ){

		$new_status = APPTHEMES_ORDER_PENDING;
		switch( $post->post_status ){
			case 'draft':
				$new_status = APPTHEMES_ORDER_PENDING;
				break;
			case 'pending':
				$new_status = APPTHEMES_ORDER_FAILED;
				break;
			case 'publish':
				$new_status = APPTHEMES_ORDER_COMPLETED;
				break;
		}

		wp_update_post( array(
			'ID' => $post->ID,
			'post_stauts' => $new_status
		) );

	}

}

/**
 * Upgrades orders using the old item structure
 * @return void
 */
function appthemes_upgrade_item_addons(){

	// Get All Orders
	$posts = new WP_Query( array(
		'post_type' => APPTHEMES_ORDER_PTYPE,
		'nopaging' => true
	) );

	foreach( $posts->posts as $order ){

		$connected = new WP_Query( array(
			'connected_type' => APPTHEMES_ORDER_CONNECTION,
			'connected_from' => $order->ID,
		) );

		// Get all items
		foreach ( $connected->posts as $post ) {
			$post_id = $post->ID;

			// Get all addons
			$meta = p2p_get_meta( $post->p2p_id );

			// Don't upgrade new items
			if( isset( $meta['type'] ) ){
				continue;
			}

			if( isset( $meta['addon'] ) ){
				foreach ( $meta['addon'] as $addon ) {

					// Add an item for each addon
					$p2p_id = p2p_type( APPTHEMES_ORDER_CONNECTION )->connect( $order->ID, $post_id );

					// Add meta data
					p2p_add_meta( $p2p_id, 'type', $addon);
					p2p_add_meta( $p2p_id, 'price', $meta[$addon][0]);

				}
			}

			// Add an item for the regular item
			$p2p_id = p2p_type( APPTHEMES_ORDER_CONNECTION )->connect( $order->ID, $post_id );
			p2p_add_meta( $p2p_id, 'type', 'regular'); // value of VA_ITEM_REGULAR, since upgrade is only Vantage-applicable
			p2p_add_meta( $p2p_id, 'price', $meta['price'][0]);

			// Delete the old item
			p2p_delete_connection( $post->p2p_id );

		}

	}


}

?>