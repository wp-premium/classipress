<?php
/**
 * Sidebar Popular Posts template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */

	global $post;

	// give us the most popular blog posts based on page views, last 3 months
	$lastmonths = appthemes_mysql_date( current_time( 'mysql' ), -90 );

	$args = array(
		'post_type' => 'post',
		'posts_per_page' => 5,
		'paged' => 1,
		'no_found_rows' => true,
		'date_start' => $lastmonths,
	);

	$popular = new CP_Popular_Posts_Query( $args );

	// give us the most popular blog posts based on page views, overall
	if ( ! $popular->have_posts() ) {
		unset( $args['date_start'] );
		$popular = new CP_Popular_Posts_Query( $args );
	}

?>


<ul class="pop-blog">

	<?php
	if ( $popular->have_posts() ) {

		while ( $popular->have_posts() ) {
			$popular->the_post();
	?>

		<li>

			<div class="post-thumb">
				<?php if ( has_post_thumbnail() ) { echo get_the_post_thumbnail( $post->ID, 'sidebar-thumbnail' ); } ?>
			</div>

			<h3><a href="<?php echo get_permalink( $post->ID ); ?>"><span class="colour"><?php echo esc_html( $post->post_title ); ?></span></a></h3>
			<p class="side-meta"><?php _e( 'by', APP_TD ); ?> <?php the_author_posts_link(); ?> <?php _e( 'on', APP_TD ); ?> <?php echo appthemes_date_posted( $post->post_date ); ?> - <a href="<?php echo get_permalink( $post->ID ); ?>#comment"><?php echo $post->comment_count; ?> <?php _e( 'Comments', APP_TD ); ?></a></p>
			<p><?php echo cp_get_content_preview( 160 ); ?></p>

		</li>

	<?php
		}

	} else { ?>

		<li><?php _e( 'There are no popular posts yet.', APP_TD ); ?></li>

	<?php
	}
	wp_reset_postdata();
	?>

</ul>
