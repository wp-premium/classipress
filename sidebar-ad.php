<?php
/**
 * Ad listing Sidebar template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 1.0
 */

global $current_user, $gmap_active, $cp_options;

// make sure google maps has a valid address field before showing tab
$gmap_active = false;
$location_fields = get_post_custom();
$_fields = array( 'cp_zipcode', 'cp_country', 'cp_state', 'cp_street', 'cp_city' );
foreach ( $_fields as $i ) {
	if ( ! empty( $location_fields[ $i ] ) && ! empty( $location_fields[ $i ][0] ) ) {
		$gmap_active = true;
		break;
	}
}

?>

<!-- right sidebar -->
<div class="content_right">

	<div class="tabprice">

		<ul class="tabnavig">
			<?php if ( $gmap_active ) { ?><li><a href="#priceblock1"><span class="big"><?php _e( 'Map', APP_TD ); ?></span></a></li><?php } ?>
			<li><a href="#priceblock2"><span class="big"><?php _e( 'Contact', APP_TD ); ?></span></a></li>
			<li><a href="#priceblock3"><span class="big"><?php _e( 'Poster', APP_TD ); ?></span></a></li>
		</ul>


		<?php if ( $gmap_active ) { ?>

			<!-- tab 1 -->
			<div id="priceblock1" class="sidebar-block">

				<div class="clr"></div>

				<div class="singletab">

					<?php get_template_part( 'includes/sidebar', 'gmap' ); ?>

				</div><!-- /singletab -->

			</div>

		<?php } ?>


		<!-- tab 2 -->
		<div id="priceblock2" class="sidebar-block">

			<div class="clr"></div>

			<div class="singletab">

			<?php if ( ( $cp_options->ad_inquiry_form && is_user_logged_in() ) || ! $cp_options->ad_inquiry_form ) {

				get_template_part( 'includes/sidebar', 'contact' );

			} else {
			?>
				<div class="pad25"></div>
				<p class="dashicons-before contact_msg center"><strong><?php _e( 'You must be logged in to inquire about this ad.', APP_TD ); ?></strong></p>
				<div class="pad100"></div>
			<?php } ?>

			</div><!-- /singletab -->

		</div><!-- /priceblock2 -->


		<!-- tab 3 -->
		<div id="priceblock3" class="sidebar-block">

			<div class="clr"></div>

			<div class="postertab">

				<div class="priceblocksmall dotted">


					<div id="userphoto">
						<p class='image-thumb'><?php appthemes_get_profile_pic( get_the_author_meta( 'ID' ), get_the_author_meta( 'user_email' ), 140 ); ?></p>
					</div>

					<ul class="member">

						<li><span><?php _e( 'Listed by:', APP_TD ); ?></span>
							<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>"><?php the_author_meta( 'display_name' ); ?></a>
						</li>

						<li><span><?php _e( 'Member Since:', APP_TD ); ?></span> <?php echo appthemes_display_date( get_the_author_meta( 'user_registered' ), 'date', true ); ?></li>

					</ul>

					<?php cp_author_info( 'sidebar-ad' ); ?>

					<div class="pad5"></div>

					<div class="clr"></div>

				</div>

				<div class="pad5"></div>

				<h3><?php _e( 'Other items listed by', APP_TD ); ?> <?php the_author_meta( 'display_name' ); ?></h3>

				<div class="pad5"></div>

				<ul>

				<?php $other_items = new WP_Query( array( 'posts_per_page' => 5, 'post_type' => APP_POST_TYPE, 'post_status' => 'publish', 'author' => get_the_author_meta( 'ID' ), 'orderby' => 'rand', 'post__not_in' => array( $post->ID ), 'no_found_rows' => true ) ); ?>

				<?php if ( $other_items->have_posts() ) : ?>

					<?php while ( $other_items->have_posts() ) : $other_items->the_post(); ?>

						<li class="dashicons-before"><a href="<?php esc_url( the_permalink() ); ?>"><?php the_title(); ?></a></li>

					<?php endwhile; ?>

				<?php else: ?>

					<li><?php _e( 'No other ads by this poster found.', APP_TD ); ?></li>

				<?php endif; ?>

				<?php wp_reset_postdata(); ?>

				</ul>

				<div class="pad5"></div>

				<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>" class="btn"><span><?php _e( 'Latest items listed by', APP_TD ); ?> <?php the_author_meta( 'display_name' ); ?> &raquo;</span></a>

			</div><!-- /singletab -->

		</div><!-- /priceblock3 -->

	</div><!-- /tabprice -->


	<?php appthemes_before_sidebar_widgets( 'ad' ); ?>

	<?php if ( ! dynamic_sidebar( 'sidebar_listing' ) ) : ?>

	<!-- no dynamic sidebar so don't do anything -->

	<?php endif; ?>

	<?php appthemes_after_sidebar_widgets( 'ad' ); ?>


</div><!-- /content_right -->
