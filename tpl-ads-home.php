<?php
/**
 * Template Name: Ads Home Template
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.3
 */
?>


<div class="content">

	<div class="content_botbg">

		<div class="content_res">

			<?php get_template_part( 'featured' ); ?>

			<!-- left block -->
			<div class="content_left">


				<?php if ( $cp_options->home_layout == 'directory' ) { ?>

					<div class="shadowblock_out">

						<div class="shadowblock">

							<h2 class="dotted"><?php _e( 'Ad Categories', APP_TD ); ?></h2>

							<div id="directory" class="directory <?php cp_display_style( 'dir_cols' ); ?>">

								<?php echo cp_create_categories_list( 'dir' ); ?>

								<div class="clr"></div>

							</div><!--/directory-->

						</div><!-- /shadowblock -->

					</div><!-- /shadowblock_out -->

				<?php } ?>


				<div class="tabcontrol">

					<?php $ad_tabs = cp_get_ads_listing_tabs(); ?>

					<ul class="tabnavig">

						<?php $tab_cnt = 1; ?>

						<?php foreach ( $ad_tabs as $ad_tab => $ad_tab_args ) { ?>

							<li>
								<a href="#block<?php echo $tab_cnt++; ?>" id="<?php echo esc_attr( $ad_tab ); ?>"<?php if ( 2 < $tab_cnt ) { echo ' class="dynamic-content"'; } ?>>
									<span class="big"><?php echo esc_html( $ad_tab_args['title'] ); ?></span>
								</a>
							</li>

						<?php } ?>

					</ul>

					<?php $tab_cnt = 1; ?>

					<?php foreach ( $ad_tabs as $ad_tab => $ad_tab_args ) { ?>

						<!-- tab block -->
						<div id="block<?php echo $tab_cnt; ?>">

							<div class="clr"></div>

							<?php if ( 1 === $tab_cnt && function_exists( $ad_tab_args['callback'] ) ) {

								call_user_func( $ad_tab_args['callback'] );

							} else { ?>

								<div class="post-block-out post-block <?php echo esc_attr( $ad_tab ); ?>-placeholder"><!-- dynamically loaded content --></div>

							<?php } ?>

							<?php $tab_cnt++; ?>

						</div><!-- /tab block -->

					<?php } ?>

				</div><!-- /tabcontrol -->

			</div><!-- /content_left -->


			<?php get_sidebar(); ?>


			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
