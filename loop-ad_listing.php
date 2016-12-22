<?php
/**
 * Main loop for displaying ads.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */
?>

<?php appthemes_before_loop(); ?>

<?php if ( have_posts() ) : ?>

	<?php while ( have_posts() ) : the_post(); ?>

		<?php appthemes_before_post(); ?>

		<?php get_template_part( 'content', APP_POST_TYPE ); ?>

		<?php appthemes_after_post(); ?>

	<?php endwhile; ?>

	<?php appthemes_after_endwhile(); ?>

<?php else: ?>

	<?php appthemes_loop_else(); ?>

<?php endif; ?>

<?php appthemes_after_loop(); ?>

<?php wp_reset_query(); ?>
