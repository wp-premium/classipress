<?php
/**
 * Free Listing Received Template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.4
 */
?>


<div class="content">

	<div class="content_botbg">

		<div class="content_res">

			<div class="shadowblock_out">

				<div class="shadowblock">

					<?php appthemes_display_form_progress(); ?>

					<div id="step3">

						<h2 class="dotted"><?php _e( 'Ad Listing Received', APP_TD ); ?></h2>

						<?php do_action( 'appthemes_notices' ); ?>

						<div class="thankyou">
						<?php
							if ( 'publish' == get_post_status( $listing->ID ) ) {

								echo html( 'h3', __( 'Thank you! Your ad listing has been submitted and is now live.', APP_TD ) );
								echo html( 'p', __( 'Visit your dashboard to make any changes to your ad listing or profile.', APP_TD ) );
								echo html( 'a', array( 'href' => esc_url( get_permalink( $listing->ID ) ), 'class' => 'btn_orange' ), __( 'View your new ad listing', APP_TD ) );

							} else {

								echo html( 'h3', __( 'Thank you! Your ad listing has been submitted for review.', APP_TD ) );
								echo html( 'p', __( 'You can check the status by viewing your dashboard.', APP_TD ) );
								echo html( 'a', array( 'href' => esc_url( get_permalink( $listing->ID ) ), 'class' => 'btn_orange' ), __( 'View your new ad listing', APP_TD ) );

							}
						?>

						<?php do_action( 'cp_listing_form_end_free', $listing ); ?>
						</div>

					</div>

				</div><!-- /shadowblock -->

			</div><!-- /shadowblock_out -->

			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
