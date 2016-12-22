<?php
/**
 * Loop for displaying most popular ads.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */

global $cp_options;
?>

<?php appthemes_before_loop(); ?>

<?php if ( $query = cp_get_popular_ads() ) : ?>

	<?php while ( $query->have_posts() ) : $query->the_post(); ?>

		<?php appthemes_before_post(); ?>

		<?php get_template_part( 'content', APP_POST_TYPE ); ?>

		<?php appthemes_after_post(); ?>

	<?php endwhile; ?>

	<?php appthemes_after_endwhile(); ?>

<?php else: ?>

	<?php appthemes_loop_else(); ?>

<?php endif; ?>

<?php appthemes_after_loop(); ?>

<?php wp_reset_postdata(); ?>
