<?php
/**
 * Listing Submit Preview Template.
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

					<div id="step2">

						<h2 class="dotted"><?php _e( 'Review Your Listing', APP_TD ); ?></h2>

						<?php do_action( 'appthemes_notices' ); ?>

						<form name="mainform" id="mainform" class="form_step steps-review" action="<?php echo appthemes_get_step_url(); ?>" method="post" enctype="multipart/form-data">
							<?php wp_nonce_field( $action ); ?>

							<ol>

								<?php
									// pass in the form post array and show the ad summary based on the formid
									echo cp_show_review( $posted_fields );
								?>

							</ol>

							<div class="pad10"></div>

							<div class="license"><?php cp_display_message( 'terms_of_use' ); ?></div>

							<div class="clr"></div>

							<p class="terms">
								<?php _e( 'By clicking the proceed button below, you agree to our terms and conditions.', APP_TD ); ?>
								<br />
								<?php _e( 'Your IP address has been logged for security purposes:', APP_TD ); ?> <?php echo appthemes_get_ip(); ?>
							</p>

							<p class="btn2">
								<input type="button" name="goback" class="btn_orange" value="<?php _e( 'Go back', APP_TD ); ?>" onClick="location.href='<?php echo appthemes_get_step_url( appthemes_get_previous_step() ); ?>';return false;" />
								<input type="submit" name="step2" id="step2" class="btn_orange" value="<?php _e( 'Continue &rsaquo;&rsaquo;', APP_TD ); ?>" />
							</p>

							<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>" />
							<input type="hidden" name="ID" value="<?php echo esc_attr( $listing->ID ); ?>" />
						</form>

						<div class="clr"></div>

					</div>

				</div><!-- /shadowblock -->

			</div><!-- /shadowblock_out -->

			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
