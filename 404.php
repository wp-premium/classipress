<?php
/**
 * The template for displaying 404 pages (Not Found).
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 1.0
 */
?>


<div class="content">

	<div class="content_botbg">

		<div class="content_res">

			<div id="breadcrumb"><?php cp_breadcrumb(); ?></div>


			<!-- left block -->
			<div class="content_left">

				<div class="shadowblock_out">

					<div class="shadowblock">

						<h1 class="single dotted"><?php _e( 'Whoops! Page Not Found.', APP_TD ); ?></h1>

						<p><?php _e( 'The page or ad listing you are trying to reach no longer exists or has expired.', APP_TD ); ?></p>

						<div class="pad25"></div>

					</div><!-- /shadowblock -->

				</div><!-- /shadowblock_out -->

				<div class="clr"></div>

				<?php appthemes_advertise_content(); ?>

				<div class="clr"></div>

			</div><!-- /content_left -->


			<?php get_sidebar(); ?>


			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
