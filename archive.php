<?php
/**
 * Archive template.
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

			<div class="content_left">

				<?php get_template_part( 'loop' ); ?>

				<div class="clr"></div>

			</div><!-- /content_left -->

			<?php get_sidebar( 'blog' ); ?>

			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
