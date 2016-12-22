<?php
/**
 * Template Name: Full Width Page
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 1.0
 */
?>


<div class="content">

	<div class="content_botbg">

		<div class="content_res">

			<div id="breadcrumb"><?php cp_breadcrumb(); ?></div>

			<?php appthemes_before_page_loop(); ?>

			<div class="shadowblock_out">

				<div class="shadowblock">

					<div class="post">

					<?php if ( have_posts() ) : ?>

						<?php while ( have_posts() ) : the_post(); ?>

							<?php appthemes_before_page(); ?>

							<?php appthemes_before_page_title(); ?>

							<h1 class="single dotted"><?php the_title(); ?></h1>

							<?php appthemes_after_page_title(); ?>

							<?php appthemes_before_page_content(); ?>

							<?php the_content(); ?>

							<?php appthemes_after_page_content(); ?>

							<?php appthemes_after_page(); ?>

						<?php endwhile; ?>

						<?php appthemes_after_page_endwhile(); ?>

					<?php else : ?>

						<?php appthemes_page_loop_else(); ?>

						<?php _e( 'No content found.', APP_TD ); ?>

					<?php endif; ?>

						<div class="clr"></div>

					</div><!--/post-->

				</div><!-- /shadowblock -->

			</div><!-- /shadowblock_out -->

			<div class="clr"></div>

			<?php appthemes_after_page_loop(); ?>

			<?php if ( comments_open() ) comments_template(); ?>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
