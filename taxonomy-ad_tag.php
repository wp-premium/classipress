<?php
/**
 * Taxonomy Template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */
?>


<div class="content">

	<div class="content_botbg">

		<div class="content_res">

			<div id="breadcrumb"><?php cp_breadcrumb(); ?></div>

			<!-- left block -->
			<div class="content_left">

				<?php $term = get_queried_object(); ?>

				<div class="shadowblock_out">

					<div class="shadowblock">

						<div id="catrss" class="catrss"><a class="dashicons-before catrss" href="<?php echo esc_url( get_term_feed_link( $term->term_id, $taxonomy ) ); ?>" title="<?php echo esc_url( sprintf( __( '%s RSS Feed', APP_TD ), $term->name ) ); ?>"></a></div>
						<h1 class="single dotted"><?php _e( 'Listings tagged with', APP_TD ); ?> '<?php echo $term->name; ?>' (<?php echo $wp_query->found_posts; ?>)</h1>

					</div><!-- /shadowblock -->

				</div><!-- /shadowblock_out -->


				<?php get_template_part( 'loop', 'ad_listing' ); ?>


			</div><!-- /content_left -->


			<?php get_sidebar(); ?>


			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
