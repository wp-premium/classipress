<?php
/**
 * AppThemes Framework load
 *
 * @package Framework
 */

define( 'APP_FRAMEWORK_DIR', dirname( __FILE__ ) );
if ( ! defined( 'APP_FRAMEWORK_DIR_NAME' ) ) {
	define( 'APP_FRAMEWORK_DIR_NAME', 'framework' );
}

if ( ! defined( 'APP_FRAMEWORK_URI' ) ) {
	define( 'APP_FRAMEWORK_URI', get_template_directory_uri() . '/' . APP_FRAMEWORK_DIR_NAME );
}

// Load scbFramework.
require_once dirname( __FILE__ ) . '/scb/load.php';

require_once dirname( __FILE__ ) . '/kernel/functions.php';

require_once dirname( __FILE__ ) . '/kernel/hook-deprecator.php';
require_once dirname( __FILE__ ) . '/kernel/deprecated.php';
require_once dirname( __FILE__ ) . '/kernel/hooks.php';

require_once dirname( __FILE__ ) . '/kernel/view-types.php';
require_once dirname( __FILE__ ) . '/includes/ajax/class-ajax-view.php';

if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	require_once dirname( __FILE__ ) . '/kernel/debug.php';
}

require_once dirname( __FILE__ ) . '/kernel/notices.php';

/**
 * Load additional external files.
 */
function _appthemes_after_scb_loaded() {
	if ( is_admin() ) {
		require_once dirname( __FILE__ ) . '/admin/functions.php';

		// TODO: pass 'class-dashboard.php' file to 'appthemes_register_feature()' when all themes will use Features API.
		require_once dirname( __FILE__ ) . '/admin/class-dashboard.php';
		require_once dirname( __FILE__ ) . '/admin/class-tooltips.php';
		require_once dirname( __FILE__ ) . '/admin/class-tabs-page.php';
		require_once dirname( __FILE__ ) . '/admin/class-settings.php';
		require_once dirname( __FILE__ ) . '/admin/class-system-info.php';
		require_once dirname( __FILE__ ) . '/admin/class-meta-box.php';
		require_once dirname( __FILE__ ) . '/admin/class-media-manager-metabox.php';
		require_once dirname( __FILE__ ) . '/admin/class-upgrader.php';

	}
}
scb_init( '_appthemes_after_scb_loaded' );

add_action( 'after_setup_theme', '_appthemes_load_features', 999 );
add_action( 'wp_enqueue_scripts', '_appthemes_register_scripts' );
add_action( 'admin_enqueue_scripts', '_appthemes_register_scripts' );
add_action( 'admin_enqueue_scripts', '_appthemes_admin_enqueue_scripts', 11 );

// Register framework features to be enqueued in the plugin or theme using Features API.
if ( function_exists( 'appthemes_register_feature' ) ) {
	appthemes_register_feature( 'app-comment-counts', dirname( __FILE__ ) . '/includes/comment-counts.php' );
	appthemes_register_feature( 'app-term-counts',    dirname( __FILE__ ) . '/includes/term-counts.php' );
	appthemes_register_feature( 'app-tables',         dirname( __FILE__ ) . '/includes/tables.php' );
	appthemes_register_feature( 'app-ajax-favorites', dirname( __FILE__ ) . '/includes/ajax/class-ajax-view-favorites.php' );
	appthemes_register_feature( 'app-ajax-delete',    dirname( __FILE__ ) . '/includes/ajax/class-ajax-view-delete-post.php' );
	appthemes_register_feature( 'app-user-meta-box',  dirname( __FILE__ ) . '/admin/class-user-meta-box.php' );
	appthemes_register_feature( 'app-media-manager',  dirname( __FILE__ ) . '/media-manager/media-manager.php' );

	appthemes_register_feature( 'app-dashboard', true );
	appthemes_register_feature( 'app-require-updater', true );
	appthemes_register_feature( 'app-versions', ( is_admin() ) ? dirname( __FILE__ ) . '/admin/versions.php' : true );
}

/**
 * Load framework features.
 */
function _appthemes_load_features() {

	// Checks if Features API used to load framework (temporary solution).
	// TODO: remove this checking and direct file loadings when all themes will use Features API.
	$is_feature_api = function_exists( 'appthemes_register_feature' );

	if ( is_admin() && current_theme_supports( 'app-versions' ) && ! $is_feature_api ) {
		require_once dirname( __FILE__ ) . '/admin/versions.php';
	}

	if ( current_theme_supports( 'app-comment-counts' ) && ! $is_feature_api ) {
		require_once dirname( __FILE__ ) . '/includes/comment-counts.php';
	}

	if ( current_theme_supports( 'app-term-counts' ) && ! $is_feature_api ) {
		require_once dirname( __FILE__ ) . '/includes/term-counts.php';
	}

	if ( current_theme_supports( 'app-ajax-favorites' ) ) {
		if ( ! $is_feature_api ) {
			require_once dirname( __FILE__ ) . '/includes/ajax/class-ajax-view-favorites.php';
		}

		new APP_Ajax_View_Favorites();
	}

	if ( current_theme_supports( 'app-ajax-delete' ) ) {
		if ( ! $is_feature_api ) {
			require_once dirname( __FILE__ ) . '/includes/ajax/class-ajax-view-delete-post.php';
		}

		new APP_Ajax_View_Delete_Post();
	}

	if ( current_theme_supports( 'app-media-manager' ) ) {
		if ( ! $is_feature_api ) {
			require_once dirname( __FILE__ ) . '/media-manager/media-manager.php';
		}
		// Init media manager.
		new APP_Media_Manager;
	}

	if ( is_admin() && current_theme_supports( 'app-dashboard' ) ) {
		// Init dashboard.
		new APP_Dashboard( array( 'admin_action_priority' => 8 ) );
		new APP_Settings;
		new APP_System_Info( array( 'admin_action_priority' => 99 ) );
	}

	if ( is_admin() && ! class_exists( 'APP_Upgrader' ) && current_theme_supports( 'app-require-updater' ) ) {
		add_action( 'admin_notices', '_appthemes_no_updater_plugin_warning' );
	}

	// Init notices.
	APP_Notices::init();

	do_action( 'appthemes_framework_loaded' );
}

/**
 * Register frontend/backend scripts and styles for later enqueue.
 */
function _appthemes_register_scripts() {

	require_once APP_FRAMEWORK_DIR . '/js/localization.php';

	wp_register_style( 'jquery-ui-style', APP_FRAMEWORK_URI . '/styles/jquery-ui/jquery-ui.min.css', false, '1.11.2' );
	wp_register_style( 'appthemes-icons', APP_FRAMEWORK_URI . '/styles/font-appthemes.css', false, '1.0.0' );
	wp_register_style( 'appthemes-admin', APP_FRAMEWORK_URI . '/styles/admin-appthemes.css', false, '1.0.0' );
	wp_register_script( 'validate', APP_FRAMEWORK_URI . '/js/validate/jquery.validate.min.js', array( 'jquery' ), '1.15.0' );

	// Generic JS data.
	wp_localize_script( 'jquery', 'AppThemes', array(
		'ajaxurl'     => admin_url( 'admin-ajax.php', 'relative' ),
		'current_url' => scbUtil::get_current_url(),
	) );

	_appthemes_localize_scripts();
}

/**
 * Enqueue backend scripts and styles.
 */
function _appthemes_admin_enqueue_scripts() {
	wp_enqueue_style( 'appthemes-icons' );
	wp_enqueue_style( 'appthemes-admin' );
}

/**
 * Display AppThemes updater missing notice warning.
 */
function _appthemes_no_updater_plugin_warning() {
	$link = html_link( 'https://www.appthemes.com/blog/new-plugin-to-keep-your-themes-healthy/', 'AppThemes Updater' );
	$msg = sprintf( __( 'The %s plugin is not detected. We strongly recommend you download and activate it so you can receive the latest updates when available. It also protects your theme from being overwritten by a similarly named theme.', APP_TD ), $link );

	echo wp_kses_post( scb_admin_notice( $msg, 'notice notice-warning' ) );
}
