<?php
/**
 * Template wrapper.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.2
 */
?>
<!DOCTYPE html>
<!--[if lt IE 7 ]> <html class="ie6" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 7 ]>    <html class="ie7" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 8 ]>    <html class="ie8" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 9 ]>    <html class="ie9" <?php language_attributes(); ?>> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html <?php language_attributes(); ?>> <!--<![endif]-->

<head>

	<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php bloginfo( 'charset' ); ?>" />
	<link rel="profile" href="http://gmpg.org/xfn/11" />

	<title><?php wp_title( '' ); ?></title>

	<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php echo appthemes_get_feed_url(); ?>" />
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />

	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1" />

	<?php if ( is_singular() && get_option( 'thread_comments' ) ) wp_enqueue_script( 'comment-reply' ); ?>

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
