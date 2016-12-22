<?php
/**
 * Listing Submit Category Select Template.
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

					<div id="step1">

						<h2 class="dotted"><?php _e( 'Submit Your Listing', APP_TD ); ?></h2>

						<?php do_action( 'appthemes_notices' ); ?>

						<p class="dotted">&nbsp;</p>

						<form name="mainform" id="mainform" class="form_step" action="<?php echo appthemes_get_step_url(); ?>" method="post">
							<?php wp_nonce_field( $action ); ?>

							<ol class="form-fields cat-select">

								<li>
									<div class="labelwrapper"><label><?php _e( 'Cost Per Listing', APP_TD ); ?></label></div>
									<div class="ad-static-field"><?php cp_cost_per_listing(); ?></div>
									<div class="clr"></div>
								</li>

								<li>
									<div class="labelwrapper"><label><?php _e( 'Select a Category', APP_TD ); ?></label></div>
									<div id="ad-categories" style="display:block; margin-left:170px;">
										<div id="catlvl0">
											<?php cp_dropdown_categories_prices(); ?>
											<div style="clear:both;"></div>
										</div>
									</div>
									<div id="ad-categories-footer" class="button-container">
										<input type="submit" name="getcat" id="getcat" class="btn_orange" value="<?php _e( 'Go &rsaquo;&rsaquo;', APP_TD ); ?>" />
										<div id="chosenCategory"><input id="ad_cat_id" name="cat" type="input" value="-1" /></div>
										<div style="clear:both;"></div>
									</div>
									<div style="clear:both;"></div>
								</li>

							</ol>

							<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>" />
							<input type="hidden" name="ID" value="<?php echo esc_attr( $listing->ID ); ?>" />
						</form>

					</div>

				</div><!-- /shadowblock -->

			</div><!-- /shadowblock_out -->

			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
