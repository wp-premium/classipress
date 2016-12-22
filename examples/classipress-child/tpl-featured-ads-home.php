<?php
// NOTE: This is just example of modifying template files,
// remove it from your child theme if you don't wish to have that homepage.


// query the sticky featured ads
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
query_posts( array( 'post_type' => APP_POST_TYPE, 'post__in' => get_option( 'sticky_posts' ), 'ignore_sticky_posts' => 0, 'paged' => $paged ) );

// singular or plural for counter
$foundtxt = _n( 'There are currently %s featured ad', 'There are currently %s featured ads', $wp_query->found_posts, APP_TD );
?>

<div class="content">

	<div class="content_botbg">

		<div class="content_res">

			<!-- left block -->
			<div class="content_left">

				<div class="shadowblock_out">

					<div class="shadowblock">

						<h1 class="single dotted"><?php _e( 'Featured Listings', APP_TD ); ?></h1>

						<p><?php printf( $foundtxt, '<span>' . $wp_query->found_posts . '</span>' ); ?></p>

					</div><!-- /shadowblock -->

				</div><!-- /shadowblock_out -->

				<?php get_template_part( 'loop', 'ad_listing' ); ?>

			</div><!-- /content_left -->

			<?php get_sidebar(); ?>

			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
