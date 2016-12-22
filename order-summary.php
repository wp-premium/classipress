<?php
/**
 * Order Summary template.
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

			<?php do_action( 'appthemes_notices' ); ?>

			<div class="shadowblock_out">

				<div class="shadowblock">

					<?php appthemes_display_form_progress(); ?>

					<div class="post">

						<h2 class="single dotted"><?php _e( 'Order Summary', APP_TD ); ?></h1>

						<div class="order-summary">

							<?php the_order_summary(); ?>

						</div>

						<div class="clr"></div>

					</div><!--/post-->

				</div><!-- /shadowblock -->

			</div><!-- /shadowblock_out -->

			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
