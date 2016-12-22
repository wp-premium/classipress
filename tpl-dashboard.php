<?php
/**
 * Template Name: User Dashboard
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */
?>

<div class="content user-dashboard">

	<div class="content_botbg">

		<div class="content_res">

			<!-- left block -->
			<div class="content_left">

				<div class="shadowblock_out">

					<div class="shadowblock">

						<h1 class="single dotted"><?php _e( 'My Ads', APP_TD ); ?></h1>

						<?php do_action( 'appthemes_notices' ); ?>

						<div id="ads" class="ads_section">
							<?php get_template_part('dashboard-ads'); ?>
						</div>

					</div><!-- /shadowblock -->

				</div><!-- /shadowblock_out -->

			</div><!-- /content_left -->

			<?php get_sidebar( 'user' ); ?>

			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
