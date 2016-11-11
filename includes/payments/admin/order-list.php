<?php
/**
 * Order list API
 *
 * @package Components\Payments\Admin
 */

add_filter( 'manage_' . APPTHEMES_ORDER_PTYPE . '_posts_columns', 'appthemes_order_manage_columns' );
add_filter( 'manage_edit-' . APPTHEMES_ORDER_PTYPE . '_sortable_columns', 'appthemes_order_manage_sortable_columns' );
add_action( 'manage_' . APPTHEMES_ORDER_PTYPE . '_posts_custom_column', 'appthemes_order_add_column_data', 10, 2 );
add_action( 'admin_print_styles', 'appthemes_order_table_css' );

/**
 * Sets the columns for the orders page
 * @param  array $columns Currently available columns
 * @return array          New column order
 */
function appthemes_order_manage_columns( $columns ) {

	unset( $columns['cb'] );
	unset( $columns['author'] );
	unset( $columns['date'] );
	unset( $columns['title'] );

	$columns['order'] = __( 'Order', APP_TD );
	$columns['title'] = __( 'Description', APP_TD );
	$columns['order_author'] = __( 'Author', APP_TD );
	$columns['item'] = __( 'Item', APP_TD );
	$columns['price'] = __( 'Price', APP_TD );
	$columns['order_date'] = __( 'Date', APP_TD );
	$columns['payment'] = __( 'Payment', APP_TD );

	return $columns;

}

/**
 * Sets the columns for the orders page
 * @param  array $columns Currently available columns
 * @return array          New column order
 */
function appthemes_order_manage_sortable_columns( $columns ) {
	$columns['order'] = 'ID';
	$columns['order_date'] = 'post_date';
	$columns['order_author'] = 'author';
	$columns['price'] = 'price';
	$columns['payment'] = 'gateway';
	return $columns;

}


/**
 * Outputs column data for orders
 * @param  string $column_index Name of the column being processed
 * @param  int $post_id         ID of order being dispalyed
 * @return void
 */
function appthemes_order_add_column_data( $column_index, $post_id ) {

	$order = appthemes_get_order( $post_id );

	switch( $column_index ){

		case 'order' :
			if ( current_user_can( 'edit_post', $order->get_ID() ) ) {
				echo '<a href="' . get_edit_post_link( $post_id ) . '">' . $order->get_ID() . '</a>';
			} else {
				echo $order->get_ID();
			}
			break;

		case 'order_author':
			$user = get_userdata( $order->get_author() );
			echo $user->display_name;
			echo '<br>';
			echo $order->get_ip_address();
			break;

		case 'item' :

			$count = count( $order->get_items() );
			$string = _n( 'Purchased %s item', 'Purchased %s items', $count, APP_TD );

			printf( $string, $count );
			break;

		case 'price':
			$currency = $order->get_currency();
			if( !empty( $currency ) ){
				echo appthemes_get_price( $order->get_total(), $order->get_currency() );
			}else{
				echo appthemes_get_price( $order->get_total() );
			}
			break;

		case 'payment':

			$gateway_id = $order->get_gateway();

			if ( !empty( $gateway_id ) ) {
				$gateway = APP_Gateway_Registry::get_gateway( $gateway_id );
				if( $gateway ){
					echo $gateway->display_name( 'admin' );
				}else{
					_e( 'Unknown', APP_TD );
				}
			}else{
				_e( 'Undecided', APP_TD );
			}

			echo '</br>';

			$status = $order->get_display_status();
			if( $order->get_status() == APPTHEMES_ORDER_PENDING ){
				echo '<strong>' . ucfirst( $status ) . '</strong>';
			}else{
				echo ucfirst( $status );
			}

			break;

		case 'status':
			echo ucfirst( $order->get_status() );
			break;

		case 'order_date':
			$order_post = get_post( $order->get_ID() );
			if ( '0000-00-00 00:00:00' == $order_post->post_date ) {
				$t_time = $h_time = __( 'Unpublished', APP_TD );
				$time_diff = 0;
			} else {
				$t_time = get_the_time( _x( 'Y/m/d g:i:s A', 'Order Date Format', APP_TD ) );
				$m_time = $order_post->post_date;
				$time = get_post_time( 'G', true, $order_post );

				$time_diff = time() - $time;

				if ( $time_diff > 0 && $time_diff < 24*60*60 )
					$h_time = sprintf( __( '%s ago', APP_TD ), human_time_diff( $time ) );
				else
					$h_time = mysql2date( _x( 'Y/m/d', 'Order Date Format', APP_TD ), $m_time );
			}
			echo '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';

			break;
	}

}

/**
 * Inline CSS for the payment listings.
 * @return void
 */
function appthemes_order_table_css() {
?>
	<style type="text/css">
		.post-type-transaction .top .actions:first-child,
		.post-type-transaction .bottom .actions:first-child,
		.post-type-transaction .wrap .add-new-h2 {
			display: none;
		}
		@media screen and (max-width: 782px) {
			.fixed .column-order,
			.fixed .column-plan_type,
			.fixed .column-order_author,
			.fixed .column-item,
			.fixed .column-order_date,
			.fixed .column-payment {
				display: none;
			}
		}
	</style>
<?php
}
