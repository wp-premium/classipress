<?php
/**
 * Post loop content template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.4
 */
?>

<div <?php post_class( 'shadowblock_out' ); ?> id="post-<?php the_ID(); ?>">

	<div class="shadowblock">

		<?php appthemes_before_blog_post_title(); ?>

		<h3 class="loop"><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></h3>

		<?php appthemes_after_blog_post_title(); ?>

		<?php appthemes_before_blog_post_content(); ?>

		<div class="entry-content">

			<?php if ( has_post_thumbnail() ) the_post_thumbnail( 'blog-thumbnail' ); ?>

			<?php the_content( __( 'Continue reading ...', APP_TD ) ); ?>

		</div>

		<?php appthemes_after_blog_post_content(); ?>

	</div><!-- #shadowblock -->

</div><!-- #shadowblock_out -->
