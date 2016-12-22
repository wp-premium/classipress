<?php
/**
 * Payments template API
 *
 * @package Components\Payments
 */

/**
 * Processes order pages and locates templates for the page being displayed
 *
 * @package Components\Payments\Views
 */
class APP_Order_Summary extends APP_View {

	function condition() {

		if ( apply_filters( 'appthemes_disable_order_summary_template', false ) ) {
			return false;
		}

		return is_singular( APPTHEMES_ORDER_PTYPE );
	}

	function template_include( $template ) {

		appthemes_auth_redirect_login();
		$order = get_order();

		if ( ! current_user_can( 'edit_post', $order->get_id() ) ) {
			return appthemes_locate_template( '404.php' );
		}

		// actions available only to pending & failed orders
		if ( in_array( $order->get_status(), array( APPTHEMES_ORDER_PENDING, APPTHEMES_ORDER_FAILED ) ) ) {

			// cancel gateway selection
			if ( isset( $_GET['cancel'] ) ) {
				$order->clear_gateway();
				$this->send_to_cancel_url();
				// locate order summary if redirect failed
				return $this->get_template();
			}

			// auto complete zero orders
			if ( $order->get_total() == 0 ) {
				$order->complete();
				$this->send_to_complete_url();
				// locate order summary if redirect failed
				return $this->get_template();
			}

			// ask to select gateway
			if ( ! $order->get_gateway() ) {
				$this->send_to_cancel_url();
				// locate order summary if redirect failed
				return $this->get_template();
			}

			// none of above, so process order
			return $this->get_template( 'checkout' );
		}

		// redirect to 'complete_url' if it's not the same page or template
		if ( $this->get_complete_url() != $order->get_return_url() && strpos( $this->get_complete_url(), 'step=order-summary' ) === false ) {
			$this->send_to_complete_url();
		}

		// order processed, so display summary
		return $this->get_template();
	}

	/**
	 * Retrieves the template vars for Order templates.
	 *
	 * @return array
	 */
	function template_vars() {

		$template_vars = array(
			'app_order' => get_order(),
		);

		return $template_vars;
	}

	/**
	 * Redirects user to order cancel url.
	 *
	 * @return void|bool Boolean False on failure
	 */
	function send_to_cancel_url() {
		if ( $url = $this->get_cancel_url() ) {
			wp_redirect( $url );
			appthemes_exit( 'send_to_cancel_url' );
		}
		return false;
	}

	/**
	 * Redirects user to order complete url.
	 *
	 * @return void|bool Boolean False on failure
	 */
	function send_to_complete_url() {
		if ( $url = $this->get_complete_url() ) {
			wp_redirect( $url );
			appthemes_exit( 'send_to_complete_url' );
		}
		return false;
	}

	/**
	 * Retrieves the Order template path and adds specific content variable
	 *
	 * @param string $content
	 *
	 * @return string located template path
	 */
	function get_template( $content = 'summary' ) {

		appthemes_add_template_var( 'app_order_content', $content );

		return appthemes_locate_template(
			array(
				'single-' . APPTHEMES_ORDER_PTYPE . '.php',
				// to ensure compatibility with old order templates
				"order-{$content}.php",
			)
		);
	}

	/**
	 * Returns an order cancel url.
	 *
	 * @return string
	 */
	function get_cancel_url() {
		$order = get_order();
		return ( $order ) ? get_post_meta( $order->get_id(), 'cancel_url', true ) : '';
	}

	/**
	 * Returns an order complete url.
	 *
	 * @return string
	 */
	function get_complete_url() {
		$order = get_order();
		return ( $order ) ? get_post_meta( $order->get_id(), 'complete_url', true ) : '';
	}
}

/**
 * Prevents to query Orders archive.
 */
class APP_Order_Archive extends APP_View {

	public function condition() {
		return is_post_type_archive( APPTHEMES_ORDER_PTYPE );
	}

	function pre_get_posts( $wp_query ) {
		$wp_query->set_404();
	}
}

/**
 * Returns an order object.
 *
 * @param int $order_id (optional) If given, returns the specified order, otherwise returns the order currently being queried.
 *
 * @return object APP_Order
 */
function get_order( $order_id = null ) {

	if ( ! $order_id && $order = get_query_var( 'app_order' ) ) {
		$order_id = $order->get_id();
	} else if ( empty( $order_id ) ) {
		$post = get_queried_object();
		$order_id = $post->ID;
	}

	return appthemes_get_order( $order_id );
}

/**
 * Returns an order object.
 *
 * @deprecated Use get_order()
 *
 * @param int $order_id (optional) If given, returns the specified order, otherwise returns the order currently being queried.
 *
 * @return object APP_Order
 */
function get_the_order( $order_id = null ) {
	_deprecated_function( __FUNCTION__, '12/10/12', 'get_order()' );
	return get_order( $order_id );
}

/**
 * Displays the current order ID.
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return void
 */
function the_order_id( $order_id = null ) {
	echo get_the_order_id( $order_id );
}

/**
 * Returns the current order ID.
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return int
 */
function get_the_order_id( $order_id = null ) {
	return get_order( $order_id )->get_id();
}

/**
 * Displays the current order description.
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return void
 */
function the_order_description( $order_id = null ) {
	echo get_the_order_description( $order_id );
}

/**
 * Returns the current order description.
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return string
 */
function get_the_order_description( $order_id = null ) {

	return get_order( $order_id )->get_description();
}

/**
 * Displays the current order human readable status.
 * Uses APP_Order::get_display_status()
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return void
 */
function the_order_status( $order_id = null ) {
	echo get_the_order_status( $order_id );
}

/**
 * Returns the current order human readable status.
 * Uses APP_Order::get_display_status()
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return string
 */
function get_the_order_status( $order_id = null ) {

	return get_order( $order_id )->get_display_status();
}

/**
 * Displays the current order total in a human readable format.
 * Uses appthemes_get_price() for formatting
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return void
 */
function the_order_total( $order_id = null ) {
	echo get_the_order_total( $order_id );
}

/**
 * Returns the current order total in a human readable format.
 * Uses appthemes_get_price() for formatting
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return string
 */
function get_the_order_total( $order_id = null ) {

	$order = get_order( $order_id );
	return appthemes_get_price( $order->get_total(), $order->get_currency() );
}

/**
 * Displays the name of the current order's currency.
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return void
 */
function the_order_currency( $order_id = null ) {
	echo get_the_order_currency_name( $order_id );
}

/**
 * Returns the name of the current order's currency.
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return string
 */
function get_the_order_currency_name( $order_id = null ) {

	$order = get_order( $order_id );
	return APP_Currencies::get_name( $order->get_currency() );
}

/**
 * Returns the three-letter currency code for the current order's currency.
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return string
 */
function get_the_order_currency_code( $order_id = null ) {

	return get_order( $order_id )->get_currency();
}

/**
 * Displays the current order's return url.
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return void
 */
function the_order_return_url( $order_id = null ) {
	echo get_the_order_return_url( $order_id );
}

/**
 * Returns the current order's return url.
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return string
 */
function get_the_order_return_url( $order_id = null ) {

	return get_order( $order_id )->get_return_url();
}

/**
 * Display the current order's cancel url.
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return void
 */
function the_order_cancel_url( $order_id = null ) {
	echo get_the_order_cancel_url( $order_id );
}

/**
 * Return the current order's cancel url.
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return string
 */
function get_the_order_cancel_url( $order_id = null ) {

	return get_order( $order_id )->get_cancel_url();
}

/**
 * Displays the order summary table for the current order.
 * @uses APP_Order_Summary_Table
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return void
 */
function the_order_summary( $order_id = null ) {
	$order = get_order( $order_id );

	do_action( 'appthemes_before_order_summary', $order );
	$table = new APP_Order_Summary_Table( $order );
	$table->show();
	do_action( 'appthemes_after_order_summary', $order );
}

/**
 * Allows the gateway for the current order to process the order.
 * The gateway may output content during this call
 *
 * @param int $order_id (optional) If given, uses the specified order id, otherwise uses the order currently being queried.
 *
 * @return void
 */
function process_the_order( $order_id = null ) {
	$order = get_order( $order_id );

	if ( $order->is_escrow() ) {
		$result = appthemes_escrow_process( $order->get_gateway(), $order );
	} else {
		$result = appthemes_process_gateway( $order->get_gateway(), $order );
	}

	return $result;
}

/**
 * Used to construct and display an order summary table for an order
 */
class APP_Order_Summary_Table extends APP_Table {

	protected $order, $currency;

	public function __construct( $order, $args = array() ) {

		$this->order = $order;
		$this->currency = $order->get_currency();

		$this->args = wp_parse_args( $args, array(
			'wrapper_html' => 'table',
			'header_wrapper' => 'thead',
			'body_wrapper' => 'tbody',
			'footer_wrapper' => 'tfoot',
			'row_html' => 'tr',
			'cell_html' => 'td',
		) );

	}

	public function show( $attributes = array() ) {

		$items = $this->order->get_items();

		$sorted = array();
		foreach ( $items as $item ) {
			$priority = APP_Item_Registry::get_priority( $item['type'] );
			if ( ! isset( $sorted[ $priority ] ) ) {
				$sorted[ $priority ] = array( $item );
			} else {
				$sorted[ $priority ][] = $item;
			}
		}

		ksort( $sorted );
		$final = array();
		foreach ( $sorted as $sorted_items ) {
			$final = array_merge( $final, $sorted_items );
		}

		echo $this->table( $final, $attributes, $this->args );
	}

	protected function footer( $items ) {

		$cells = array(
			__( 'Total', APP_TD ),
			appthemes_get_price( $this->order->get_total(), $this->currency )
		);

		return html( $this->args['row_html'], array(), $this->cells( $cells, $this->args['cell_html'] ) );
	}

	protected function row( $item ) {

		if ( ! APP_Item_Registry::is_registered( $item['type'] ) ) {
			return '';
		}

		$cells = array(
			APP_Item_Registry::get_title( $item['type'] ),
			appthemes_get_price( $item['price'], $this->currency )
		);

		return html( $this->args['row_html'], array(), $this->cells( $cells ) );
	}

}
