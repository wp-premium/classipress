<?php
/**
 * Order Checkout template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.3
 */
?>

<?php
$order = get_order();
if (  $order && APPTHEMES_ORDER_PENDING == $order->get_status() && get_query_var('bt_end') ) {
	appthemes_load_template( 'order-summary.php' );
	return;
}
?>

<div class="content">

	<div class="content_botbg">

		<div class="content_res">

			<div id="breadcrumb"><?php cp_breadcrumb(); ?></div>

			<div class="shadowblock_out">

				<div class="shadowblock">

					<?php appthemes_display_form_progress(); ?>

					<div class="post">

						<?php if ( ! empty( $gateway ) ) : ?>

							<h2 class="single dotted"><?php echo sprintf( __( 'Pay with %s', APP_TD ), $gateway ); ?></h2>

						<?php endif; ?>

						<div class="order-gateway">

							<?php
								process_the_order();

								// Retrieve updated order object
								$order = get_order();

								if ( in_array( $order->get_status(), array( APPTHEMES_ORDER_COMPLETED, APPTHEMES_ORDER_ACTIVATED ) ) ) {
									$redirect_to = get_post_meta( $order->get_id(), 'complete_url', true );
									echo html( 'a', array( 'href' => $redirect_to ), __( 'Continue', APP_TD ) );
									echo html( 'script', 'location.href="' . $redirect_to . '"' );
								}
							?>

						</div>

					</div><!--/post-->

				</div><!-- /shadowblock -->

			</div><!-- /shadowblock_out -->

			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
