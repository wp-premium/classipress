<?php
/**
 * Template wrapper.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   3.2.0
 * @since   3.6.0 Updated head section for modern standards.
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<title><?php wp_title( '' ); ?></title>

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

	<?php appthemes_before(); ?>

	<div class="container">

		<?php if ( $cp_options->debug_mode ) { ?><div class="debug"><h3><?php _e( 'Debug Mode On', APP_TD ); ?></h3><?php print_r( $wp_query->query_vars ); ?></div><?php } ?>

		<?php appthemes_before_header(); ?>
		<?php get_header( app_template_base() ); ?>
		<?php appthemes_after_header(); ?>

		<?php get_template_part( 'searchbar' ); ?>

		<?php load_template( app_template_path() ); ?>

		<?php appthemes_before_footer(); ?>
		<?php get_footer( app_template_base() ); ?>
		<?php appthemes_after_footer(); ?>

	</div><!-- /container -->

	<?php wp_footer(); ?>

	<?php appthemes_after(); ?>

</body>

</html>
