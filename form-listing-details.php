<?php
/**
 * Listing Submit Details Template.
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

						<form name="mainform" id="mainform" class="form_step" action="<?php echo appthemes_get_step_url(); ?>" method="post" enctype="multipart/form-data">
							<?php wp_nonce_field( $action ); ?>

							<ol>

								<span class="form-fields">

									<li>
										<div class="labelwrapper"><label><?php _e( 'Category', APP_TD ); ?></label></div>
										<div class="ad-static-field"><strong><?php echo $category->name; ?></strong>&nbsp;&nbsp;<small><a href="<?php echo $select_category_url; ?>"><?php _e( '(change)', APP_TD ); ?></a></small></div>
									</li>


									<?php cp_show_form( $category->term_id, $listing ); ?>

								</span>

								<p class="btn1">
									<input type="submit" name="step1" id="step1" class="btn_orange" value="<?php _e( 'Continue &rsaquo;&rsaquo;', APP_TD ); ?>" />
								</p>

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
