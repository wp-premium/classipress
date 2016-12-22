<?php
/**
 * Membership Preview Template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.4
 */

global $current_user;
?>


<div class="content">

	<div class="content_botbg">

		<div class="content_res">

			<div class="shadowblock_out">

				<div class="shadowblock">

					<?php appthemes_display_form_progress(); ?>

					<div id="step2">

						<h2 class="dotted"><?php _e( 'Review Your Membership Purchase', APP_TD ); ?></h2>

						<?php do_action( 'appthemes_notices' ); ?>

						<form name="mainform" id="mainform" class="form_step" action="<?php echo appthemes_get_step_url(); ?>" method="post" enctype="multipart/form-data">
							<?php wp_nonce_field( $action ); ?>

							<ol>

								<li>
									<div class="labelwrapper"><label><strong><?php if ( $renew ) { _e( 'Membership Renewal:', APP_TD ); } else { _e( 'Membership:', APP_TD ); } ?></strong></label></div>
									<div id="active_membership_pack" class="ad-static-field"><?php echo stripslashes( $membership->pack_name ); ?></div>
									<div class="clr"></div>
								</li>

								<li>
									<div class="labelwrapper"><label><strong><?php _e( 'Benefit:', APP_TD ); ?></strong></label></div>
									<div id="active_membership_pack" class="ad-static-field"><?php echo cp_get_membership_package_benefit_text( $membership->ID ); ?></div>
									<div class="clr"></div>
								</li>

								<li>
									<div class="labelwrapper"><label><strong><?php _e( 'Length:', APP_TD ); ?></strong></label></div>
									<div id="active_membership_pack" class="ad-static-field"><?php if ( $renew ) printf( __( '%s more days', APP_TD ), $membership->duration ); else printf( _n( '%d day', '%d days', $membership->duration, APP_TD ), $membership->duration ); ?></div>
									<div class="clr"></div>
								</li>

								<?php if ( $renew ) { ?>
									<li>
										<div class="labelwrapper"><label><strong><?php _e( 'Previous Expiration:', APP_TD ); ?></strong></label></div>
										<div id="active_membership_pack" class="ad-static-field"><?php echo appthemes_display_date( $current_user->membership_expires ); ?></div>
										<div class="clr"></div>
									</li>

									<li>
										<div class="labelwrapper"><label><strong><?php _e( 'New Expiration:', APP_TD ); ?></strong></label></div>
										<div id="active_membership_pack" class="ad-static-field">
											<?php echo appthemes_display_date( appthemes_mysql_date( $current_user->membership_expires, $membership->duration ) ); ?>
										</div>
										<div class="clr"></div>
									</li>
								<?php } ?>

								<li>
									<div class="labelwrapper"><label><?php _e( 'Price:', APP_TD ); ?></label></div>
									<div id="review" class="ad-static-field"><?php appthemes_display_price( $membership->price ); ?></div>
									<div class="clr"></div>
								</li>

								<hr class="bevel-double" />
								<div class="clr"></div>

								<?php do_action( 'appthemes_purchase_fields', CP_PACKAGE_MEMBERSHIP_PTYPE ); ?>

							</ol>

							<div class="pad10"></div>

							<div class="license">
								<?php cp_display_message( 'terms_of_use' ); ?>
							</div>

							<div class="clr"></div>

							<p class="terms">
								<?php _e( 'By clicking the proceed button below, you agree to our terms and conditions.', APP_TD ); ?>
								<br />
								<?php _e( 'Your IP address has been logged for security purposes:', APP_TD ); ?> <?php echo appthemes_get_ip(); ?>
							</p>

							<p class="btn2">
								<input type="button" name="goback" class="btn_orange" value="<?php _e( 'Go back', APP_TD ); ?>" onClick="location.href='<?php echo appthemes_get_step_url( appthemes_get_previous_step() ); ?>';return false;" />
								<input type="submit" name="step2" id="step2" class="btn_orange" value="<?php echo esc_attr_e( 'Continue &rsaquo;&rsaquo;', APP_TD ); ?>" />
							</p>

							<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>" />
						</form>

						<div class="clr"></div>

					</div>

				</div><!-- /shadowblock -->

			</div><!-- /shadowblock_out -->

			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
