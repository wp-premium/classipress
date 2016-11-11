<?php
/**
 * AppThemes Theme Framework load
 *
 * @package ThemeFramework
 */

define( 'APP_THEME_FRAMEWORK_VER', '1.0' );
define( 'APP_THEME_FRAMEWORK_DIR', dirname( __FILE__ ) );
if ( ! defined( 'APP_THEME_FRAMEWORK_DIR_NAME' ) ) {
	define( 'APP_THEME_FRAMEWORK_DIR_NAME', 'theme-framework' );
}

if ( ! defined( 'APP_THEME_FRAMEWORK_URI' ) ) {
	define( 'APP_THEME_FRAMEWORK_URI', get_template_directory_uri() . '/' . APP_THEME_FRAMEWORK_DIR_NAME );
}

require_once dirname( __FILE__ ) . '/kernel/functions.php';

// Theme specific items.
if ( appthemes_in_template_directory() || apply_filters( 'appthemes_force_load_theme_specific_items', false ) ) {
	// Default filters.
	add_filter( 'wp_title', 'appthemes_title_tag', 9 );
	add_action( 'wp_head', 'appthemes_favicon' );
	add_action( 'admin_head', 'appthemes_favicon' );

	appthemes_load_textdomain();
}

require_once dirname( __FILE__ ) . '/kernel/view-edit-profile.php';

require_once dirname( __FILE__ ) . '/kernel/mail-from.php';
require_once dirname( __FILE__ ) . '/kernel/social.php';

// Load the breadcrumbs trail.
if ( ! is_admin() && ! function_exists( 'breadcrumb_trail' ) ) {
	require_once dirname( __FILE__ ) . '/kernel/breadcrumb-trail.php';
}


add_action( 'after_setup_theme', '_appthemes_load_theme_features', 999 );
add_action( 'wp_enqueue_scripts', '_appthemes_register_theme_scripts' );
add_action( 'admin_enqueue_scripts', '_appthemes_register_theme_scripts' );

// Register framework features to be enqueued in the plugin or theme using Features API.
if ( function_exists( 'appthemes_register_feature' ) ) {
	appthemes_register_feature( 'app-wrapping',       dirname( __FILE__ ) . '/includes/wrapping.php' );
	appthemes_register_feature( 'app-login',          dirname( __FILE__ ) . '/includes/views-login.php' );
	appthemes_register_feature( 'app-feed', true );
	appthemes_register_feature( 'app-html-term-description', dirname( __FILE__ ) . '/admin/class-html-term-description.php' );
}

/**
 * Load framework features.
 */
function _appthemes_load_theme_features() {

	// Checks if Features API used to load framework (temporary solution).
	// TODO: remove this checking and direct file loadings when all themes will use Features API.
	$is_feature_api = function_exists( 'appthemes_register_feature' );

	if ( current_theme_supports( 'app-wrapping' ) && ! $is_feature_api ) {
		require_once dirname( __FILE__ ) . '/includes/wrapping.php';
	}

	if ( current_theme_supports( 'app-login' ) ) {

		if ( ! $is_feature_api ) {
			require_once dirname( __FILE__ ) . '/includes/views-login.php';
		}

		list( $templates ) = get_theme_support( 'app-login' );

		new APP_Login( $templates['login'] );
		new APP_Registration( $templates['register'] );
		new APP_Password_Recovery( $templates['recover'] );
		new APP_Password_Reset( $templates['reset'] );
	}

	if ( current_theme_supports( 'app-feed' ) ) {
		add_filter( 'request', 'appthemes_modify_feed_content' );
	}

	if ( is_admin() && current_theme_supports( 'app-html-term-description' ) ) {
		if ( ! $is_feature_api ) {
			require_once dirname( __FILE__ ) . '/admin/class-html-term-description.php';
		}

		$args_sets = get_theme_support( 'app-html-term-description' );

		if ( ! is_array( $args_sets ) ) {
			$args_sets = array();
		}

		foreach ( $args_sets as $args ) {
			$args = wp_parse_args( (array) $args, array( 'taxonomy' => '', 'editor_settings' => array() ) );
			new APP_HTML_Term_Description( $args['taxonomy'], $args['editor_settings'] );
		}
	}

	do_action( 'appthemes_theme_framework_loaded' );
}

/**
 * Register frontend/backend scripts and styles for later enqueue.
 */
function _appthemes_register_theme_scripts() {

	require_once APP_THEME_FRAMEWORK_DIR . '/js/localization.php';

	wp_register_script( 'colorbox', APP_THEME_FRAMEWORK_URI . '/js/colorbox/jquery.colorbox.min.js', array( 'jquery' ), '1.6.1' );
	wp_register_style( 'colorbox', APP_THEME_FRAMEWORK_URI . '/js/colorbox/colorbox.css', false, '1.6.1' );
	wp_register_style( 'font-awesome', APP_THEME_FRAMEWORK_URI . '/styles/font-awesome.min.css', false, '4.2.0' );

	wp_register_script( 'footable', APP_THEME_FRAMEWORK_URI . '/js/footable/jquery.footable.min.js', array( 'jquery' ), '2.0.3' );
	wp_register_script( 'footable-grid', APP_THEME_FRAMEWORK_URI . '/js/footable/jquery.footable.grid.min.js', array( 'footable' ), '2.0.3' );
	wp_register_script( 'footable-sort', APP_THEME_FRAMEWORK_URI . '/js/footable/jquery.footable.sort.min.js', array( 'footable' ), '2.0.3' );
	wp_register_script( 'footable-filter', APP_THEME_FRAMEWORK_URI . '/js/footable/jquery.footable.filter.min.js', array( 'footable' ), '2.0.3' );
	wp_register_script( 'footable-striping', APP_THEME_FRAMEWORK_URI . '/js/footable/jquery.footable.striping.min.js', array( 'footable' ), '2.0.3' );
	wp_register_script( 'footable-paginate', APP_THEME_FRAMEWORK_URI . '/js/footable/jquery.footable.paginate.min.js', array( 'footable' ), '2.0.3' );
	wp_register_script( 'footable-bookmarkable', APP_THEME_FRAMEWORK_URI . '/js/footable/jquery.footable.bookmarkable.min.js', array( 'footable' ), '2.0.3' );

	_appthemes_localize_theme_scripts();
}
