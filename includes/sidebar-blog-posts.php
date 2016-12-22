<?php
/**
 * Sidebar Recent Posts template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */
?>

<?php $blog_posts = new WP_Query( array( 'posts_per_page' => $count, 'post_type' => 'post', 'no_found_rows' => true ) ); ?>

<ul class="from-blog">

	<?php if ( $blog_posts->have_posts() ) : ?>

		<?php while( $blog_posts->have_posts() ) : $blog_posts->the_post(); ?>

			<li>

				<div class="post-thumb">
					<?php if ( has_post_thumbnail() ) { echo get_the_post_thumbnail( $post->ID, 'sidebar-thumbnail' ); } ?>
				</div>

				<h3><a href="<?php the_permalink(); ?>"><?php if ( mb_strlen( get_the_title() ) >= 40 ) echo mb_substr( get_the_title(), 0, 40 ) . '...'; else the_title(); ?></a></h3>

				<p class="side-meta"><?php _e( 'by', APP_TD ); ?> <?php the_author_posts_link(); ?> <?php _e( 'on', APP_TD ); ?> <?php echo appthemes_date_posted( get_the_date( "Y-m-d H:i:s" ) ); ?> - <?php comments_popup_link( __( '0 Comments', APP_TD ), __( '1 Comment', APP_TD ), __( '% Comments', APP_TD ) ); ?></p>

				<p><?php echo cp_get_content_preview( 160 ); ?></p>

			</li>

		<?php endwhile; ?>

	<?php else: ?>

		<li><?php _e( 'There are no blog articles yet.', APP_TD ); ?></li>

	<?php endif; ?>

	<?php wp_reset_postdata(); ?>

</ul>
