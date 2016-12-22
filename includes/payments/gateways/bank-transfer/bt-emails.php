<?php
/**
 * Bank Transfer emails API
 *
 * @package Components\Payments\Gateways\Bank-Transfer
 */
function appthemes_bank_transfer_pending_email( $post ) {

	$content = '';

	$content .= html( 'p', __( 'A new order is waiting to be processed. Once you receive payment, you should mark the order as completed.', APP_TD ) );

	$order_link = html_link( get_edit_post_link( $post ), __( 'Review this order', APP_TD ) );

	$all_orders = html_link(
		admin_url( 'edit.php?post_status=tr_pending&post_type=transaction' ),
		__( 'review all pending orders', APP_TD ) );

	// translators: <Single Order Link> or <Link to All Orders>
	$content .= html( 'p',  sprintf( __( '%1$s or %2$s', APP_TD ), $order_link, $all_orders ) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$subject = sprintf( __( '[%1$s] Pending Order #%2$d', APP_TD ), $blogname, $post->ID );

	if( ! function_exists( 'appthemes_send_email' ) )
		return false;

	$email = array( 'to' => get_option( 'admin_email' ), 'subject' => $subject, 'message' => $content );
	$email = apply_filters( 'appthemes_email_admin_bt_pending', $email, $post );

	appthemes_send_email( $email['to'], $email['subject'], $email['message'] );
}
