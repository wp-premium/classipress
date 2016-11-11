<?php
/**
 * The AppThemes add-ons marketplace module.
 *
 * Mirrors WordPress plugins browser to display AppThemes Add-ons instead.
 *
 * @package Components\Add-ons
 */

add_action( 'admin_menu', '_appthemes_addons_mp_init' );


/**
 * Initializes the add-ons marketplace module.
 *
 * Optional Parameters:
 *
 * - 'url'   string		  (optional) The URL location of the module
 * - 'theme' string|array (optional) The default theme to which display add-ons
 * - 'mp'    array		  (optional) Expects an assoc. array of marketplaces slugs.
 *									 Each 'mp' should contain an associative array of 'scbAdminPage' class parameters.
 *									 Besides the menu page params, it also contain a 'filters' param allowing to control
 *									 which filters/values are displayed for this marketplace page content:
 *
 *			- '[menu-slug]' (array) The slug name for a marketplace page and menu
 *				- 'menu' (array)	The menu params.
 *					. see 'scbAdminPage' (https://github.com/scribu/wp-scb-framework/blob/54b521e37ed54244e19a58d497ac690efe7b578b/AdminPage.php#L5)
 *				- 'filters' (array) The marketplace filters visible to the user (shows all if omitted)
 *					- 'themes' (string|array|boolean)	  List of themes to display (shows all if omitted)
 *					- 'categories' (string|array|boolean) List of categories to display [valid: 'plugins', 'payment-gateways', 'child-themes', 'general-themes'] (shows all if omitted)
 *					- 'authors' (string|array|boolean)	  List of authors to display (shows all if omitted)
 *
 *			e.g (for a customized seller menu):
 *			...
 *				'mp' => array(
 *					'my-addons-mp' => array(
 *						'menu' => array(										// see 'scbAdminPage' class for all params
 *							'parent' => 'my-plugin-menu-slug',
 *						),
 *						// each omitted filter is always displayed unless set to 'False' to disable it
 *						'filters' => array(
 *								'themes'     => array( 'hirebee', 'vantage' ), // choose which theme(s) to display
 *								'categories' => false,						   // set to false to disable the categories filter
 *								'authors'    => 'seller_username',			   // choose which author(s) addons to display
 *						),
 *					),
 *				),
 *			...
 */
function _appthemes_addons_mp_init() {
	global $wp_version;

	if ( ! current_theme_supports( 'app-addons-mp' ) || version_compare( $wp_version, '4.0', '<' ) ) {
		return;
	}

	require_once dirname( __FILE__ ) . '/admin.php';
	require_once dirname( __FILE__ ) . '/addons-mp-class.php';

	$marketplace = _appthemes_get_addons_mp_args( 'mp' );

	// Create an instance of add-ons for each menu.
	foreach ( $marketplace as $page_slug => $args ) {
		new APP_Addons( $page_slug, $args );
	}

}

/**
 * Retrieves an associative array of options/values for the add-ons module or a single value if an option is given.
 *
 * @param string $option (optional) The option to retrieve values from.
 *
 *					Valid options:
 *						- 'url'   The URL location of the module (string).
 *						- 'theme' The default theme that the add-ons browser should point to.
 *						- 'mp'    The marketplace(s) parameters.
 *
 * @return mixed A single value if an option is given, or a list of options/values.
 *               False, if the option is invalid.
 */
function _appthemes_get_addons_mp_args( $option = '' ) {

	if ( ! current_theme_supports( 'app-addons-mp' ) ) {
		return array();
	}

	$args_sets = (array) get_theme_support( 'app-addons-mp' );

	$defaults = array(
		'url'     => get_template_directory_uri() . '/includes/admin/addons-mp',
		'theme'   => array(),
		'product' => array(),
		'mp'      => array(
			'app-addons-mp' => array(
				'menu' => array(
					'parent' => 'app-dashboard',
				),
			),
		),
	);

	foreach ( $args_sets as $slug => $args_set ) {

		$args_set = wp_parse_args( $args_set, $defaults );

		foreach ( $args_set as $key => $value ) {

			if ( ! isset( $options[ $key ] ) ) {
				$options[ $key ] = $value;
			} elseif ( is_array( $value ) ) {
				$options[ $key ] = array_merge_recursive( (array) $options[ $key ], $value );
			}
		}
	}

	// Backwards compat.
	// @todo: remove after themes change from 'theme' key to new 'product' key.
	if ( ! empty( $options['theme'] ) ) {
		$options['product'] = $options['theme'];
		unset( $options['theme'] );
	}

	// Make sure the 'theme' option is an array - cast it if needed.
	if ( ! empty( $options['product'] ) && is_string( $options['product'] ) ) {
		$options['product'] = (array) $options['product'];
	}

	// Make sure the main marketplace is always present and given priority.
	$options['mp'] = array_merge( $defaults['mp'], $options['mp'] );

	if ( empty( $option ) ) {
		return $options;
	} elseif ( isset( $options[ $option ] ) ) {
		return $options[ $option ];
	} else {
		return false;
	}

}

/**
 * Retrieves the marketplace page options for a given menu page slug.
 *
 * @param string $page_slug The mp page slug.
 * @param string $option (optional) The mp option to retrieve values from.
 * @return mixed A single value if an option is given, or a list of options/values.
 *               False, if the option is invalid.
 */
function _appthemes_get_addons_mp_page_args( $page_slug, $option = '' ) {

	$marketplace = _appthemes_get_addons_mp_args( 'mp' );

	if ( empty( $marketplace[ $page_slug ] ) ) {
		return false;
	}

	if ( empty( $option ) ) {
		return $marketplace[ $page_slug ];
	} elseif ( isset( $marketplace[ $page_slug ][ $option ] ) ) {
		return $marketplace[ $page_slug ][ $option ];
	} else {
		return false;
	}

}

/**
 * NOTE: Mirrors 'wp_list_filter()' but also matches objects with array values.
 *
 * @todo: move to framework
 *
 * Filters a list of objects, based on a set of key => value arguments.
 *
 * @param array   $list     An array of objects to filter.
 * @param array   $args     (optional) An array of key => value arguments to match
 *                          against each object. Default empty array.
 * @param string  $operator (optional) The logical operation to perform. 'AND' means
 *                          all elements from the array must match. 'OR' means only
 *                          one element needs to match. 'NOT' means no elements may
 *                          match. Default 'AND'.
 * @param boolean $match    (optional) Compare values using 'preg_match()'.
 * @return array            List of matches.
 */
function appthemes_wp_list_filter( $list, $args = array(), $operator = 'AND', $match = false ) {
	if ( ! is_array( $list ) ) {
		return array();
	}

	if ( empty( $args ) ) {
		return $list;
	}

	$operator = strtoupper( $operator );
	$count = count( $args );
	$filtered = array();

	foreach ( $list as $key => $obj ) {
		$to_match = (array) $obj;
		$matched = 0;
		foreach ( $args as $m_key => $m_value ) {
			if ( array_key_exists( $m_key, $to_match ) && ( ( ! $match && in_array( $m_value, (array) $to_match[ $m_key ] ) ) || ( $match && preg_match( "#{$m_value}#i", $to_match[ $m_key ] ) ) ) ) {
				$matched++;
			}
		}

		if ( ( 'AND' === $operator && $matched === $count )
		  || ( 'OR' === $operator && $matched > 0 )
		  || ( 'NOT' === $operator && 0 === $matched ) ) {
			$filtered[ $key ] = $obj;
		}
	}

	return $filtered;
}
