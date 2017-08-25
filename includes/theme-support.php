<?php
/**
 * ClassiPress Theme Support
 * This file defines 'theme support' so WordPress knows what new features it can handle.
 */

global $cp_options;

// Theme supports
add_theme_support( 'app-versions', array(
	'update_page'     => 'admin.php?page=app-settings&firstrun=1',
	'current_version' => CP_VERSION,
	'option_key'      => 'cp_version',
) );

add_theme_support( 'app-wrapping' );

add_theme_support( 'app-search-index', array(
	'admin_page'           => true,
	'admin_top_level_page' => 'app-dashboard',
	'admin_sub_level_page' => 'app-system-info',
) );

add_theme_support( 'app-login', array(
	'login'         => 'tpl-login.php',
	'register'      => 'tpl-registration.php',
	'recover'       => 'tpl-password-recovery.php',
	'reset'         => 'tpl-password-reset.php',
	'redirect'      => $cp_options->disable_wp_login,
	'settings_page' => 'admin.php?page=app-settings&tab=advanced',
) );

add_theme_support( 'app-feed', array(
	'post_type'          => APP_POST_TYPE,
	'blog_template'      => 'index.php',
	'alternate_feed_url' => $cp_options->feedburner_url,
) );

add_theme_support( 'app-open-graph', array(
	'default_image' => get_header_image() ? get_header_image() : appthemes_locate_template_uri( 'images/cp_logo_black.png' ),
) );

add_theme_support( 'app-payments', array(
	'items'            => cp_get_addons(),
	'items_post_types' => array( APP_POST_TYPE ),
	'options'          => $cp_options,
) );

add_theme_support( 'app-price-format', array(
	'currency_default'    => $cp_options->currency_code,
	'currency_identifier' => $cp_options->currency_identifier,
	'currency_position'   => $cp_options->currency_position,
	'thousands_separator' => $cp_options->thousands_separator,
	'decimal_separator'   => $cp_options->decimal_separator,
	'hide_decimals'       => $cp_options->hide_decimals,
) );

add_theme_support( 'app-plupload', array(
	'max_file_size'  => $cp_options->max_image_size,
	'allowed_files'  => $cp_options->num_images,
	'disable_switch' => false,
) );

add_theme_support( 'app-stats', array(
	'cache'       => 'today',
	'table_daily' => 'cp_ad_pop_daily',
	'table_total' => 'cp_ad_pop_total',
	'meta_daily'  => 'cp_daily_count',
	'meta_total'  => 'cp_total_count',
) );

add_theme_support( 'app-reports', array(
	'post_type'            => array( APP_POST_TYPE ),
	'options'              => $cp_options,
	'admin_top_level_page' => 'app-dashboard',
	'admin_sub_level_page' => 'app-settings',
) );

add_theme_support( 'app-comment-counts' );

add_theme_support( 'post-thumbnails' );

add_theme_support( 'automatic-feed-links' );

add_theme_support( 'app-form-progress', array(
	'checkout_types' => array(
		'create-listing' => array(
			'steps' => array(
				'select-category'     => array( 'title' => __( 'Select Category', APP_TD ) ),
				'listing-details'     => array( 'title' => __( 'Details', APP_TD ) ),
				'listing-preview'     => array( 'title' => __( 'Preview', APP_TD ) ),
				'select-plan'         => array( 'title' => __( 'Options/Pay', APP_TD ) ),
				'gateway-select'      => array( 'map_to' => 'select-plan' ),
				'gateway-process'     => array( 'map_to' => 'select-plan' ),
				'listing-submit-free' => array( 'title' => __( 'Thank You', APP_TD ) ),
				'order-summary'       => array( 'title' => __( 'Thank You', APP_TD ) ),
			),
		),
		'membership-purchase' => array(
			'steps' => array(
				'select-membership'  => array( 'title' => __( 'Select Membership', APP_TD ) ),
				'preview-membership' => array( 'title' => __( 'Preview', APP_TD ) ),
				'gateway-select'     => array( 'title' => __( 'Pay', APP_TD ) ),
				'order-summary'      => array( 'title' => __( 'Thank You', APP_TD ) ),
				'gateway-process'    => array( 'map_to' => 'gateway-select' )
			),
		),
	),
) );

/**
 * AppThemes updater not found notice.
 *
 * @since 3.5
 */
add_theme_support( 'app-require-updater', true );

/**
 * Media Manager.
 *
 * @since 3.5
 */
add_theme_support( 'app-media-manager' );

/**
 * Add-ons Marketplace.
 *
 * @since 3.5
 */
add_theme_support( 'app-addons-mp', array(
	'product' => array( 472 ),
) );

/**
 * Add support for the language files and set location.
 *
 * @since 3.6.0
 */
function cp_setup_language_support() {
	/**
	 * We want more control over the language file location.
	 *
	 * @todo Remove this once the function has been deprecated from theme-framework.
	 *
	 * @since 3.6.0
	 */
	remove_action( 'appthemes_theme_framework_loaded', 'appthemes_load_textdomain' );

	/**
	 * Add support for language files.
	 *
	 * Looks in WP_LANG_DIR . '/themes/' first otherwise
	 * For example: wp-content/languages/themes/vantage-de_DE.mo
	 *
	 * Otherwise defaults to: "wp-content/themes/vantage/languages/$domain . '-' . $locale.mo"
	 *
	 * @since 3.6.0
	 */
	load_theme_textdomain( APP_TD, get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'cp_setup_language_support' );

/**
 * Sets the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 *
 * @since 3.6.0
 */
function cp_content_width() {
	/**
	 * Filter the content width in pixels.
	 *
	 * @since 3.6.0
	 *
	 * @param int $width The content width in pixels.
	 */
	$GLOBALS['content_width'] = apply_filters( 'cp_content_width', 560 );
}
add_action( 'after_setup_theme', 'cp_content_width', 0 );

/**
 * Adds reCaptcha support
 * @since 3.2
 *
 * @return void
 */
function cp_recaptcha_support() {
	global $cp_options;

	if ( ! $cp_options->captcha_enable ) {
		return;
	}

	add_theme_support( 'app-recaptcha', array(
		'theme'       => $cp_options->captcha_theme,
		'public_key'  => $cp_options->captcha_public_key,
		'private_key' => $cp_options->captcha_private_key,
	) );

	// Integrate recaptcha on the User Registration form.
	add_action( 'appthemes_before_login_template', 'cp_before_login_template' );
	add_filter( 'registration_errors', 'cp_recaptcha_verify' );
}
add_action( 'appthemes_init', 'cp_recaptcha_support' );

function cp_recaptcha_verify( $errors ) {

	$response = appthemes_recaptcha_verify();
	if ( is_wp_error( $response ) ) {

		foreach ( $response->get_error_codes() as $code ) {
			$errors->add( $code, $response->get_error_message( $code ) );
		}

	}

	return $errors;
}

function cp_before_login_template( $action ) {
	if ( 'register' !== $action ) {
		return;
	}

	appthemes_enqueue_recaptcha_scripts();
}
