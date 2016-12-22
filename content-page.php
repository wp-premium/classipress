<?php
/**
 * Page loop content template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.4
 */
?>

<div class="shadowblock_out">

	<div class="shadowblock">

		<div class="post">

			<?php appthemes_before_page_title(); ?>

			<h3 class="loop dotted"><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></h3>

			<?php appthemes_after_page_title(); ?>

			<?php appthemes_before_page_content(); ?>

			<?php the_excerpt(); ?>

			<?php appthemes_after_page_content(); ?>

		</div><!--/post-->

	</div><!-- /shadowblock -->

</div><!-- /shadowblock_out -->
