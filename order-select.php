<?php
/**
 * Order Gateway template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.3
 */
?>

<div class="content">

	<div class="content_botbg">

		<div class="content_res">

			<div id="breadcrumb"><?php cp_breadcrumb(); ?></div>

			<div class="shadowblock_out">

				<div class="shadowblock">

					<?php appthemes_display_form_progress(); ?>

					<div class="post">

						<h2 class="single dotted"><?php _e( 'Order Summary', APP_TD ); ?></h2>

						<div class="pad20"></div>

						<div class="order-summary">

							<?php the_order_summary();?>

							<form action="<?php echo appthemes_get_step_url(); ?>" method="POST">
								<p><?php _e( 'Please select a method for processing your payment:', APP_TD ); ?></p>
								<?php appthemes_list_gateway_dropdown(); ?>
								<p class="btn1">
									<button class="btn_orange" type="submit"><?php _e( 'Continue &rsaquo;&rsaquo;', APP_TD ); ?></button>
								</p>
							</form>

						</div>

						<div class="clr"></div>

						<div class="clr"></div>

					</div><!--/post-->

				</div><!-- /shadowblock -->

			</div><!-- /shadowblock_out -->

			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
