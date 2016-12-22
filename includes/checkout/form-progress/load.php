<?php
/**
 * Generates and outputs a form progress CSS tree using dynamic checkout. Also works without dynamic checkout.
 * Progress bar is generated using pure CSS, with no images (http://blog.sathomas.me/post/tracking-progress-in-pure-css).
 *
 * Dynamic Checkout
 * If dynamic checkout is supported loads the progress steps automatically.
 * Steps are loaded automatically using the step ID but can be mapped to meanignful titles.
 *
 * Query String
 * If dynamic checkout is not supported, it will look in the URL query string for 'step' and 'app-checkout-type' as the current step and checkout name respectively.
 * Steps must be explicitly passed through add_theme_support().
 *
 * add_theme_support() usage:
 *
 * 'checkout_types' => array(
 * 	     'checkout-name' => array(
 * 			'steps' (optional for dynamic checkout) => array(
 * 				'step-1' => array( 'title' => __( 'Step 1 Title', APP_TD ) ),
 *				'step-2' => array( 'title' => __( 'Step 2 Title', APP_TD ) ),
 *				'step-3' => array( 'map_to' => 'step-2' ),
 *			),
 *			'exclude' (optional) => array( 'step-4' )
 *		 ),
 *	   ),
 * ) );
 *
 * Displaying form progress tree in a page
 * i.e: appthemes_display_form_progress()
 *
 * See function for notes on additional parameters
 *
 * @package Components\Checkouts\Progress-Form
 */

add_action( 'after_setup_theme', '_appthemes_load_form_progress', 999 );
add_action( 'wp_enqueue_scripts', '_appthemes_enqueue_form_progress_styles' );

/**
 * Load the form progress module
 */
function _appthemes_load_form_progress() {

	if ( !current_theme_supports( 'app-form-progress' ) )
		return;

	require dirname( __FILE__ ) . '/class-checkout-register.php';
	require dirname( __FILE__ ) . '/class-form-progress.php';

	appthemes_register_form_progress_types();
}

/**
 * Register checkout types for form progress
 */
function appthemes_register_form_progress_types() {

	extract( appthemes_form_progress_get_args(), EXTR_SKIP );

	if ( empty( $checkout_types ) )
		return;

	foreach( $checkout_types as $key => $params ) {

		if ( is_array( $params ) ) {
			$checkout_type = $key;
		} else {
			$checkout_type = $params;
			$params = array();
		}
		APP_Form_Progress_Checkout_Registry::register( $checkout_type, $params );
	}

}

/**
 * Retrieve form progress options
 */
function appthemes_form_progress_get_args( $option = '' ){

	static $args = array();

	if ( !current_theme_supports( 'app-form-progress' ) ) {
		return array();
	}

	if ( empty( $args ) ) {

		// numeric array, contains multiple sets of arguments
		// first item contains preferable set
		$args_sets = get_theme_support( 'app-form-progress' );

		if ( ! is_array( $args_sets ) ) {
			$args_sets = array();
		}

		foreach ( $args_sets as $args_set ) {
			foreach ( $args_set as $key => $arg ) {
				if ( ! isset( $args[ $key ] ) ) {
					$args[ $key ] = $arg;
				} elseif ( is_array( $arg ) ) {
					$args[ $key ] = array_merge_recursive( (array) $args[ $key ], $arg );
				}
			}
		}

		$defaults = array();
		$args = apply_filters( 'appthemes_form_progress_args', wp_parse_args( $args, $defaults ) );
	}


	if ( empty( $option ) ) {
		return $args;
	} else {
		return $args[ $option ];
	}

}

/**
 * Enqueue the progress tree CSS styles
 */
function _appthemes_enqueue_form_progress_styles() {

	if ( ! current_theme_supports( 'app-form-progress' ) || ! _appthemes_get_step_from_query() )
		return;

	wp_enqueue_style(
		'app-form-progress',
		get_template_directory_uri() . '/includes/checkout/form-progress/styles.css',
		false,
		'1.0'
	);
}

/**
 * Outputs the form progress step tree using pure CSS
 * @param array $params Additional parameters:
 *	- $walker string					The walker that outputs the progress tree
 *	- $classes['done','todo'] string	The progress CSS classes for 'done' and 'todo' steps
 */
function appthemes_display_form_progress( $params = array() ) {
	$form_progress = new APP_Form_Progress( $params );
	$form_progress->display();
}
