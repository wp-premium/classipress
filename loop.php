<?php
/**
 * Main loop for displaying blog posts.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 1.0
 */

// hack needed for "<!-- more -->" to work with templates
global $more;
$more = 0;
?>

<?php appthemes_before_blog_loop(); ?>

<?php if ( have_posts() ) : ?>

	<?php while ( have_posts() ) : the_post() ?>

		<?php appthemes_before_blog_post(); ?>

		<?php get_template_part( 'content', get_post_type() ); ?>

		<?php appthemes_after_blog_post(); ?>

	<?php endwhile; ?>

	<?php appthemes_after_blog_endwhile(); ?>

<?php else: ?>

	<?php appthemes_blog_loop_else(); ?>

<?php endif; ?>

<?php appthemes_after_blog_loop(); ?>
