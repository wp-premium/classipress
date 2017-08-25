<?php
/**
 * Enqueue of scripts and styles.
 *
 * @package ClassiPress\Enqueue
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */

add_action( 'wp_enqueue_scripts', 'cp_load_scripts' );
add_action( 'wp_enqueue_scripts', 'cp_style_changer', 11 );
add_action( 'wp_enqueue_scripts', 'cp_load_styles' );
add_action( 'wp_print_styles', '_cp_inline_styles', 99 );

/**
 * Enqueue scripts.
 *
 * @return void
 */
if ( ! function_exists( 'cp_load_scripts' ) ) :
function cp_load_scripts() {
	global $cp_options;

	// Minimize prod or show expanded in dev.
	$min = cp_get_enqueue_suffix();

	// Load google cdn hosted scripts if enabled.
	if ( $cp_options->google_jquery ) {
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', "https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery{$min}.js", false, '1.12.4' );
	}

	// Needed for single ad sidebar email & comments on pages, edit ad & profile pages, ads, blog posts.
	if ( is_singular() ) {
		wp_enqueue_script( 'validate' );
		wp_enqueue_script( 'validate-lang' );
	}

	// Search autocomplete and slider on certain pages.
	wp_enqueue_script( 'jquery-ui-autocomplete' );

	// Advanced search sidebar and home page carousel.
	wp_enqueue_script( 'jquery-ui-slider' );

	// Convert header menu into select list on mobile devices.
	wp_enqueue_script( 'tinynav', get_template_directory_uri() . "/includes/js/tinynav{$min}.js", array( 'jquery' ), '1.1' );

	// Transform tables on mobile devices
	wp_enqueue_script( 'footable' );

	// Adds touch events to jQuery UI on mobile devices.
	if ( wp_is_mobile() ) {
		wp_enqueue_script( 'jquery-touch-punch' );
	}

	// Styles select elements.
	if ( ! wp_is_mobile() && $cp_options->selectbox ) {
		wp_enqueue_script( 'selectbox', get_template_directory_uri() . "/includes/js/jquery.selectBox{$min}.js", array( 'jquery' ), '1.2.0' );
	}

	if ( $cp_options->enable_featured && is_page_template( 'tpl-ads-home.php' ) ) {
		wp_enqueue_script( 'jqueryeasing', get_template_directory_uri() . "/includes/js/easing{$min}.js", array( 'jquery' ), '1.3' );
		wp_enqueue_script( 'jcarousellite', get_template_directory_uri() . "/includes/js/jcarousellite{$min}.js", array( 'jquery', 'jquery-ui-slider' ), '1.9.2' );
	}

	// Load the theme script.
	wp_enqueue_script( 'theme-scripts', get_template_directory_uri() . "/includes/js/theme-scripts{$min}.js", array( 'jquery' ), CP_VERSION );

	// Comment reply script for threaded comments.
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// Only load the general.js if available in child theme.
	if ( file_exists( get_stylesheet_directory() . '/general.js' ) ) {
		wp_enqueue_script( 'general', get_stylesheet_directory_uri() . '/general.js', array( 'jquery' ), '1.0' );
	}

	// Only load cufon if it's been enabled.
	if ( $cp_options->cufon_enable ) {
		wp_enqueue_script( 'cufon-yui', get_template_directory_uri() . '/includes/js/cufon-yui.js', array( 'jquery' ), '1.0.9i' );
		wp_enqueue_script( 'cufon-font-vegur', get_template_directory_uri() . '/includes/fonts/Vegur_400-Vegur_700.font.js', array( 'cufon-yui' ) );
		wp_enqueue_script( 'cufon-font-liberation', get_template_directory_uri() . '/includes/fonts/Liberation_Serif_400.font.js', array( 'cufon-yui' ) );
	}

	// Load the gravatar hovercards.
	if ( $cp_options->use_hovercards ) {
		wp_enqueue_script( 'gprofiles', 'https://s.gravatar.com/js/gprofiles.js', array( 'jquery' ), '1.0', true );
	}

	// Only load gmaps when we need it.
	if ( is_singular( APP_POST_TYPE ) ) {

		$gmap_params = array(
			'language' => $cp_options->gmaps_lang,
			'region'   => $cp_options->gmaps_region,
		);

		if ( $cp_options->api_key ) {
			$gmap_params['key'] = $cp_options->api_key;
		}

		$google_maps_url = add_query_arg( $gmap_params, 'https://maps.googleapis.com/maps/api/js' );
		wp_enqueue_script( 'google-maps', $google_maps_url, array( 'jquery' ), '3.0' );
	}

	if ( is_singular() || is_home() ) {
		wp_enqueue_script( 'colorbox' );
	}

	$listing_id = 0;

	if ( ! empty( $_GET['action'] ) && 'change' === $_GET['action'] ) {
		$checkout = appthemes_get_checkout();
		$listing_id = $checkout ? $checkout->get_data( 'listing_id' ) : false;
	} elseif( ! empty( $_GET['listing_renew'] ) ) {
		$listing_id = (int) $_GET['listing_renew'];
	}

	/* Script variables */
	$params = array(
		'appTaxTag'              => APP_TAX_TAG,
		'require_images'         => ( $cp_options->ad_images && $cp_options->require_images ),
		'ad_parent_posting'      => $cp_options->ad_parent_posting,
		'ad_currency'            => $cp_options->curr_symbol,
		'currency_position'      => $cp_options->currency_position,
		'home_url'               => home_url( '/' ),
		'ajax_url'               => admin_url( 'admin-ajax.php', 'relative' ),
		'nonce'                  => wp_create_nonce('cp-nonce'),
		'text_processing'        => __( 'Processing...', APP_TD ),
		'text_require_images'    => __( 'Please upload at least one image.', APP_TD ),
		'text_before_delete_ad'  => __( 'Are you sure you want to delete this ad?', APP_TD ),
		'text_mobile_navigation' => __( 'Navigation', APP_TD ),
		'loader'                 => get_template_directory_uri() . '/images/loader.gif',
		'listing_id'             => $listing_id,
	);
	wp_localize_script( 'theme-scripts', 'classipress_params', $params );

	$params = array(
		'empty'    => __( 'Strength indicator', APP_TD ),
		'short'    => __( 'Very weak', APP_TD ),
		'bad'      => __( 'Weak', APP_TD ),
		'good'     => __( 'Medium', APP_TD ),
		'strong'   => __( 'Strong', APP_TD ),
		'mismatch' => __( 'Mismatch', APP_TD ),
	);
	wp_localize_script( 'password-strength-meter', 'pwsL10n', $params );
}
endif;


/**
 * Enqueue Add New page form scripts.
 *
 * @return void
 */
if ( ! function_exists( 'cp_load_form_scripts' ) ) :
function cp_load_form_scripts() {

	// Minimize prod or show expanded in dev.
	$min = cp_get_enqueue_suffix();

	wp_enqueue_script( 'validate' );
	wp_enqueue_script( 'validate-lang' );

	wp_enqueue_script( 'easytooltip', get_template_directory_uri() . "/includes/js/easyTooltip{$min}.js", array( 'jquery' ), '1.0' );
}
endif;


/**
 * Enqueue color scheme styles.
 *
 * @return void
 */
if ( ! function_exists( 'cp_style_changer' ) ) :
function cp_style_changer() {
	global $cp_options;

	if ( ! wp_style_is('app-form-progress') && current_theme_supports( 'app-form-progress' ) ) {
		// enqueue the form progress before the main stylesheet to be able to override it
		_appthemes_enqueue_form_progress_styles();
	}

	// Load the theme stylesheet.
	wp_enqueue_style( 'at-main', get_stylesheet_uri(), array(), CP_VERSION );

	// Load the rtl theme stylesheet.
	wp_style_add_data( 'at-main', 'rtl', 'replace' );

	// turn off stylesheets if customers want to use child themes
	if ( ! $cp_options->disable_stylesheet ) {
		$child_theme = $cp_options->stylesheet ? $cp_options->stylesheet : 'aqua.css';
		$stylesheet = '/styles/' . $child_theme;
		$from_child_theme = is_child_theme() && file_exists( get_stylesheet_directory() . $stylesheet );
		$stylesheet_url = ( $from_child_theme ? get_stylesheet_directory_uri() : get_template_directory_uri() ) . $stylesheet;
		wp_enqueue_style( 'at-color', $stylesheet_url, array( 'at-main' ), CP_VERSION );
	}

	// Load our IE 7 version-specific stylesheet.
	wp_enqueue_style( 'at-ie7', get_stylesheet_directory_uri() . '/styles/ie7.css', array( 'at-main' ), CP_VERSION );
	wp_style_add_data( 'at-ie7', 'conditional', 'IE 7' );

	// Load our IE 8 version-specific stylesheet.
	wp_enqueue_style( 'at-ie8', get_stylesheet_directory_uri() . '/styles/ie8.css', array( 'at-main' ), CP_VERSION );
	wp_style_add_data( 'at-ie8', 'conditional', 'IE 8' );

	if ( file_exists( get_template_directory() . '/styles/custom.css' ) ) {
		wp_enqueue_style( 'at-custom', get_template_directory_uri() . '/styles/custom.css', false );
	}

	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'open-sans' );
}
endif;


/**
 * Enqueue styles.
 *
 * @return void
 */
if ( ! function_exists( 'cp_load_styles' ) ) :
function cp_load_styles() {

	// load colorbox only on single page
	if ( is_singular() || is_home() ) {
		wp_enqueue_style( 'colorbox' );
	}

	wp_enqueue_style( 'jquery-ui-style' );

}
endif;

/**
 * Overrides known CSS styles loaded after the main stylesheet.
 */
function _cp_inline_styles() {

	// override Critic plugin CSS styles
	if ( wp_style_is('critic') ) {
		$custom_css = "
			#critic-review-wrap{margin-top: 0;}
			#critic-review-wrap .critic-reviews-title { margin-bottom:25px; }
			#critic-review-wrap .critic-review { margin-bottom:30px; }
			#criticform input[type='text'], #criticform textarea { width: 100%; }
		";

		wp_add_inline_style( 'critic', $custom_css );
	}

}
