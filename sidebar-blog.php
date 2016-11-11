<?php
/**
 * Blog Sidebar template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 1.0
 */

global $current_user;
?>

<!-- right sidebar -->
<div class="content_right">

	<!-- start tabs -->
	<div class="tabprice">

		<ul class="tabnavig">
			<li><a href="#priceblock1"><?php _e( 'Popular', APP_TD ); ?></a></li>
			<li><a href="#priceblock2"><?php _e( 'Comments', APP_TD ); ?></a></li>
			<li><a href="#priceblock3"><?php _e( 'Tags', APP_TD ); ?></a></li>
		</ul>


		<!-- popular tab 1 -->
		<div id="priceblock1">

			<div class="clr"></div>

			<?php get_template_part( 'includes/sidebar', 'popular' ); ?>

		</div>


		<!-- comments tab 2 -->
		<div id="priceblock2">

			<div class="clr"></div>

			<?php get_template_part( 'includes/sidebar', 'comments' ); ?>

		</div><!-- /priceblock2 -->


		<!-- tag cloud tab 3 -->
		<div id="priceblock3">

			<div class="clr"></div>

			<div class="pricetab">

				<div id="tagcloud">

					<?php wp_tag_cloud( array( 'smallest' => 9, 'largest' => 16 ) ); ?>

				</div>

				<div class="clr"></div>

			</div>

		</div>

	</div><!-- end tabs -->


	<?php appthemes_before_sidebar_widgets( 'blog' ); ?>

	<?php if ( ! dynamic_sidebar( 'sidebar_blog' ) ) : ?>

	<!-- no dynamic sidebar so don't do anything -->

	<?php endif; ?>

	<?php appthemes_after_sidebar_widgets( 'blog' ); ?>


</div><!-- /content_right -->
