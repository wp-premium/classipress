<?php
/**
 * Order Factory
 *
 * @package Components\Payments\Escrow
 */
class APP_Escrow_Order_Factory extends APP_Order_Factory {

	static public function create( $description = '' ) {
		$order = self::make( $description );

		do_action( 'appthemes_create_escrow_order_original', $order );
		return $order;
	}

	/**
	 * Prepares and returns a new escrow Order
	 * @return APP_Escrow_Order New Order object
	 */
	static protected function make( $description = '' ) {

		if ( empty( $description ) ) {
			$description = __( 'Transaction', APP_TD );
		}

		$id = wp_insert_post( array(
			'post_title' => $description,
			'post_content' => __( 'Transaction Data', APP_TD ),
			'post_type' => APPTHEMES_ORDER_PTYPE,
			'post_status' => APPTHEMES_ORDER_PENDING,
		) );

		add_post_meta( $id, 'currency', appthemes_price_format_get_args( 'currency_default' ), true );
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			add_post_meta( $id, 'ip_address', $_SERVER['REMOTE_ADDR'], true );
		}

		// flag the order as escrow
		add_post_meta( $id, 'is_escrow', 1, true );

		wp_update_post( array(
			'ID' => $id,
			'post_name' => $id
		) );

		$order = self::retrieve( $id );
		$order->log( 'Escrow Order Created', 'major' );

		return $order;
	}

	/**
	 * Retrieves an existing order by ID
	 *
	 * @param int $order_id Order ID
	 *
	 * @return APP_Escrow_Order Object representing the escrow order
	 */
	static public function retrieve( $order_id ) {

		if ( ! is_numeric( $order_id ) ) {
			trigger_error( 'Invalid order id given. Must be an integer', E_USER_WARNING );
		}

		$order_data = get_post( $order_id );
		if ( ! $order_data || $order_data->post_type != APPTHEMES_ORDER_PTYPE ) {
			return false;
		}

		$order = new APP_Escrow_Order( $order_data, self::get_order_items( $order_id ) );
		return $order;
	}

}
