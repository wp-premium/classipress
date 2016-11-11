<?php
/**
 * Single order metaboxes
 *
 * @package Components\Payments\Admin
 */

add_action( 'admin_menu', 'appthemes_remove_orders_meta_boxes' );
add_action( 'edit_form_advanced', 'appthemes_display_order_summary_table' );

/**
 * Removes Wordpress default metaboxes from the page
 * @return void
 */
function appthemes_remove_orders_meta_boxes() {

	remove_meta_box( 'submitdiv', APPTHEMES_ORDER_PTYPE, 'side' );
	remove_meta_box( 'postcustom', APPTHEMES_ORDER_PTYPE, 'normal' );
	remove_meta_box( 'slugdiv', APPTHEMES_ORDER_PTYPE, 'normal' );
	remove_meta_box( 'authordiv', APPTHEMES_ORDER_PTYPE, 'normal');

}

/**
 * Displays the order summary table.
 */
function appthemes_display_order_summary_table() {
	global $post;

	if ( APPTHEMES_ORDER_PTYPE != $post->post_type )
		return;

	$order = appthemes_get_order( $_GET['post'] );

	?>
	<style type="text/css">
		#admin-order-summary tbody td{
			padding-top: 10px;
			padding-bottom: 10px;
		}
		#admin-order-summary{
			margin-bottom: 20px;
		}
		#normal-sortables, #post-body-content{
			display: none;
		}
	</style>
	<?php

	$table = new APP_Admin_Order_Summary_Table( $order );
	$table->show( array(
		'class' => 'widefat',
		'id' => 'admin-order-summary'
	) );
}


class APP_Admin_Order_Summary_Table extends APP_Order_Summary_Table{

	protected function header( $data ){

		$cells = array(
			__( 'Order Summary', APP_TD ),
			__( 'Price', APP_TD ),
			__( 'Affects', APP_TD ),
		);

		return html( 'tr', array(), $this->cells( $cells, 'th' ) );

	}

	protected function footer( $data ){

		$cells = array(
			__( 'Total', APP_TD ),
			appthemes_get_price( $this->order->get_total(), $this->currency ),
			''
		);

		return html( 'tr', array(), $this->cells( $cells, 'th' ) );

	}

	protected function row( $item ){

		if( ! APP_Item_Registry::is_registered( $item['type'] ) ){
			return html( 'tr', array(), html( 'td', array(
				'colspan' => '3',
				'style' => 'font-style: italic;'
			), __('This item could not be recognized. It might be from another theme or an uninstalled plugin.', APP_TD ) ) );
		}

		$ptype_obj = get_post_type_object( $item['post']->post_type );
		$item_link = ( $ptype_obj->public ) ? html_link( get_edit_post_link( $item['post_id'] ), $item['post']->post_title ) : '';

		$cells = array(
			APP_Item_Registry::get_title( $item['type'] ),
			appthemes_get_price( $item['price'], $this->currency ),
			$item_link
		);

		return html( 'tr', array(), $this->cells( $cells ) );

	}

}

/**
 * Controls the Order Status Meta Box
 *
 * @package Components\Payments\Admin\Metaboxes
 */
class APP_Order_Status extends APP_Meta_Box {

	/**
	 * Sets up the meta box with Wordpress
	 */
	function __construct(){
		parent::__construct( 'order-status', __( 'Order Status', APP_TD ), APPTHEMES_ORDER_PTYPE, 'side', 'high' );
	}

	/**
	 * Displays the order status summary
	 * @param  object $post Wordpress Post object
	 * @return void
	 */
	function display( $post ){

		$order = appthemes_get_order( $post->ID );
		?>
		<style type="text/css">
			#admin-order-status th{
				padding-right: 10px;
				text-align: right;
				width: 40%;
			}
		</style>
		<table id="admin-order-status">
			<tbody>
				<tr>
					<th><?php _e( 'ID', APP_TD ); ?>: </th>
					<td><?php echo $order->get_ID(); ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Status', APP_TD ); ?>: </th>
					<td><?php echo $order->get_display_status(); ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Gateway', APP_TD ); ?>: </th>
					<td>
					<?php
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
					?>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Currency', APP_TD ); ?>: </th>
					<td><?php echo APP_Currencies::get_currency_string( $order->get_currency() ); ?></td>
				</tr>
				<?php if( $order->is_recurring() ){
					$period = $order->get_recurring_period();
					$period_type = appthemes_get_recurring_period_type_display( $order->get_recurring_period_type(), $period );
				?>
				<tr>
					<th><?php _e( 'Recurs:', APP_TD ); ?></th>
					<td><?php printf( __( 'Every %d %s', APP_TD ), $period, $period_type ); ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Payment Date:', APP_TD ); ?></th>
					<td><?php echo appthemes_display_date( get_post( $order->get_id() )->post_date, 'date' ); ?></td>
				</tr>
				<?php } ?>
				<?php if( $order->get_parent() != 0 ){ ?>
				<tr>
					<th><?php _e( 'Previously', APP_TD ); ?></th>
					<td><a href="<?php echo  get_edit_post_link( $order->get_parent() ); ?>">#<?php echo $order->get_parent(); ?></a></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php

	}
}


/**
 * Controls the Order Author Meta box
 *
 * @package Components\Payments\Admin\Metaboxes
 */
class APP_Order_Author extends APP_Meta_Box {

	/**
	 * Sets up the meta box with Wordpress
	 */
	function __construct(){
		parent::__construct( 'order-author', __( 'Order Author', APP_TD ), APPTHEMES_ORDER_PTYPE, 'side', 'low' );
	}

	/**
	 * Displays the order author box
	 * @param  object $post Wordpress Post object
	 * @return void
	 */
	function display( $post ){

		$order = appthemes_get_order( $post->ID );
		?>
		<style type="text/css">
			#admin-order-author{
				padding-left: 10px;
				text-align: left;
			}
			.avatar{
				float: left;
			}
		</style>
		<?php echo get_avatar( $order->get_author(), 72 ); ?>
		<table id="admin-order-author">
			<?php $user = get_userdata( $order->get_author() ); ?>
			<tbody>
				<tr>
					<td><?php

					$username = $user->user_login;
					$display_name = $user->display_name;

					if( $username == $display_name )
						echo $username;
					else
						echo $display_name . ' (' . $username . ') ';

					?></td>
				</tr>
				<tr>
					<td><?php echo $user->user_email; ?></td>
				</tr>
				<tr>
					<td><?php echo $order->get_ip_address(); ?></td>
				</tr>
			</tbody>
		</table>
		<div class="clear"></div>
		<?php

	}

}

/**
 * Order actions
 *
 * @package Components\Payments\Admin\Metaboxes
 */
class APP_Order_Actions extends APP_Meta_Box{

	public function __construct(){

		parent::__construct( 'app-order-actions', __( 'Order Actions', APP_TD ), APPTHEMES_ORDER_PTYPE, 'side', 'high' );
		add_action( 'wp_ajax_reset-order', array( $this, 'reset_order' ) );
		add_action( 'wp_ajax_activate-order', array( $this, 'activate_order' ) );
		add_action( 'wp_ajax_pay-order', array( $this, 'pay_order' ) );
		add_action( 'wp_ajax_complete-order', array( $this, 'complete_order' ) );
		add_action( 'wp_ajax_fail-order', array( $this, 'fail_order' ) );
		add_action( 'wp_ajax_refund-order', array( $this, 'refund_order' ) );
		add_action( 'wp_ajax_reset-gateway', array( $this, 'reset_gateway' ) );
	}

	public function display( $post ) {
		$this->admin_script( $post );

		$is_escrow = appthemes_is_escrow_order( $post->ID );

		if ( ! in_array( $post->post_status, array( APPTHEMES_ORDER_COMPLETED, APPTHEMES_ORDER_FAILED, APPTHEMES_ORDER_REFUNDED, APPTHEMES_ORDER_ACTIVATED ) ) ) {

			if ( $is_escrow ) {

				if ( APPTHEMES_ORDER_PAID == $post->post_status ) {
					$this->display_button( 'complete-order', __( 'Mark as Completed', APP_TD ) );
				}

				if ( APPTHEMES_ORDER_PENDING == $post->post_status ) {
					$this->display_button( 'fail-order', __( 'Mark as Failed', APP_TD ) );
				} else {
					$this->display_button( 'fail-order-refund', __( 'Mark as Failed (Refund)', APP_TD ) );
				}

			} else {
				$this->display_button( 'complete-order', __( 'Mark as Completed', APP_TD ) );
				$this->display_button( 'fail-order', __( 'Mark as Failed', APP_TD ) );
				$this->display_button( 'reset-gateway', __( 'Reset Gateway', APP_TD ) );
			}

		} else {

			if ( $post->post_status == APPTHEMES_ORDER_FAILED && ! $is_escrow ){
				$this->display_button( 'reset-order', __( 'Reset Order', APP_TD ) );
			}
			else if( $post->post_status == APPTHEMES_ORDER_COMPLETED && ! $is_escrow ) {
				$this->display_button( 'activate-order', __( 'Activate Order', APP_TD ) );
			}
			else{
				printf( '<em>%s</em>', __( 'No actions available', APP_TD ) );
			}
		}

	}

	protected function display_button( $id, $title ){
		echo html( 'a', array( 'id' => $id, 'class' => 'button order-action' ), $title );
	}

	public function admin_script( $post ){
?>
		<style type="text/css">
			.wp-core-ui .button.order-action{
				margin-right: 5px;
				margin-bottom: 5px;
			}
		</style>
		<script type="text/javascript">
		jQuery(document).ready(function($){

			$( ".order-action" ).click( function(){
				var data = {
					'action' : $(this).attr('id'),
					'post' : "<?php echo $post->ID; ?>",
				};

				$.get( ajaxurl, data, function( response ){
					window.location.reload( true );
				});
			} );

		});
		</script>
	<?php
	}

	protected function get_action_order(){
		if( ! isset( $_GET['post'] ) )
			die( 'No post included' );

		$post = get_post( $_GET['post'] );
		if( !$post || $post->post_type != APPTHEMES_ORDER_PTYPE )
			die( 'Bad post included' );

		return appthemes_get_order( $post->ID );
	}

	public function reset_order(){

		$order = $this->get_action_order();
		$order->pending();
		$order->clear_gateway();
		die();

	}

	public function reset_gateway(){

		$this->get_action_order()->clear_gateway();
		die();

	}

	public function activate_order(){

		$this->get_action_order()->activate();
		die();

	}

	public function pay_order(){

		$this->get_action_order()->paid();
		die();

	}

	public function complete_order(){

		$this->get_action_order()->complete();
		die();

	}

	public function fail_order(){

		$this->get_action_order()->failed();
		die();

	}

	public function refund_order(){

		$this->get_action_order()->refunded();
		die();

	}

}

class APP_Log_Message_Table{

	protected $log;
	public function __construct( APP_Log $log ){
		$this->log = $log;
	}

	public function display(){

		$this->admin_style();
		$messages = $this->log->get_log();

		echo '<table class="app-message-log widefat">';
		echo '<tr><th>' . __( 'Logged Date', APP_TD ) . '</th><th>' . __( 'Message', APP_TD ) . '</th></tr>';
		if( $messages ){
			foreach( $messages as $data ){
				echo '<tr class="' . esc_attr( $data['type'] ) . '">';
				echo '<td><span class="timestamp" >' . $data['time'] . '</span></td>';
				echo '<td><span class="message" >' . $data['message'] . '</span></td>';
				echo '</tr>';
			}
		}
		echo '</table>';
	}

	function admin_style(){
?>
<style type="text/css">
	.app-message-log td{
		padding: 5px;
	}
	.app-message-log td:first-child{
		width: 200px;
	}
	.app-message-log .major .message{
		font-weight: bold;
	}
	.app-message-log .minor .timestamp {
		color: #999;
	}
	.app-message-log .info .timestamp{
		display: none;
	}
</style>
	<?php
	}


}

/**
 * Displays a list of messages concerning this order
 *
 * @package Components\Payments\Admin\Metaboxes
 */
class APP_Order_Log_Messages extends APP_Meta_Box{

	/**
	 * Sets up the meta box with Wordpress
	 * See APP_Meta_Box::__construct()
	 */
	function __construct(){
		parent::__construct( 'order-log', 'Order Log', APPTHEMES_ORDER_PTYPE, 'advanced', 'default' );
	}

	public function display( $post ){
		$table = new APP_Log_Message_Table( new APP_Post_Log( $post->ID ) );
		$table->display();
	}
}
new APP_Order_Actions();
new APP_Order_Status();
new APP_Order_Author();
new APP_Order_Log_Messages();
