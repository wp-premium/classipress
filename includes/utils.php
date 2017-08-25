<?php
/**
 * Utility functions and hook callbacks. These can go away at any time. Don't rely on them.
 */

// add a very low priority action to make sure any extra settings have been added to the permalinks global
add_action( 'admin_init', '_cp_enable_permalink_settings', 999999 );


/**
 * Temporary workaround for wordpress bug #9296 http://core.trac.wordpress.org/ticket/9296
 * Although there is a hook in the options-permalink.php to insert custom settings,
 * it does not actually save any custom setting which is added to that page.
 */
function _cp_enable_permalink_settings() {
	global $new_whitelist_options;

	// save hook for permalinks page
	if ( isset( $_POST['permalink_structure'] ) || isset( $_POST['category_base'] ) ) {
		check_admin_referer( 'update-permalink' );

		$option_page = 'permalink';

		$capability = 'manage_options';
		$capability = apply_filters( "option_page_capability_{$option_page}", $capability );

		if ( !current_user_can( $capability ) ) {
			wp_die( __( 'Cheatin&#8217; uh?', APP_TD ) );
		}

		// get extra permalink options
		$options = $new_whitelist_options[$option_page];

		if ( $options ) {
			foreach( $options as $option ) {
				$option = trim( $option );
				$value = null;
				if ( isset( $_POST[$option] ) ) {
					$value = $_POST[$option];
				}
				if ( !is_array( $value ) ) {
					$value = trim( $value );
				}
				$value = stripslashes_deep( $value );

				// get the old values to merge
				$db_option = get_option( $option );

				if ( is_array( $db_option ) ) {
					update_option( $option, array_merge( $db_option, $value ) );
				} else {
					update_option( $option, $value );
				}
			}
		}

		/**
		 *  Handle settings errors
		 */
		set_transient( 'settings_errors', get_settings_errors(), 30 );
	}
}

/**
 * Utility class to output tables in emails.
 */
class CP_Email_Membership_Table extends APP_Table{

	public function __construct( $items ){
		$this->items = $items;
	}

	public function display() {
		return parent::table( $this->items, array( 'width' => '70%' ) );
	}

	public function header( $data ) {

		$cells = $this->cells( array(
			__( 'User Email', APP_TD ),
			__( 'Membership Pack', APP_TD ),
			__( 'Expires', APP_TD ),
		), 'th' );
		return html( 'tr', array( 'style' => 'border-bottom: 1px solid #ccc;'), $cells );
	}

	protected function row( $data ){
		$cells = $this->cells( $data );
		return html( 'tr', array(), $cells );
	}

	protected function cells( $cells, $type = 'td' ) {

		$output = '';
		foreach ( $cells as $value ) {
			$output .= html( $type, array( 'align' => 'center' ), $value );
		}
		return $output;

	}

}


/**
 * Converts an hex color value to an rgba color.
 */
function hex2rgb( $colour, $alpha = 1 ) {
	if ( $colour[0] == '#' ) {
        $colour = substr( $colour, 1 );
	}
	if ( strlen( $colour ) == 6 ) {
	    list( $r, $g, $b ) = array( $colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5] );
	} elseif ( strlen( $colour ) == 3 ) {
	    list( $r, $g, $b ) = array( $colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2] );
	} else {
	    return false;
	}

	$r = hexdec( $r );
	$g = hexdec( $g );
	$b = hexdec( $b );

	$rgba = compact( 'r', 'g', 'b', 'alpha' );

	return sprintf( 'rgba(%s) ', implode( ', ', $rgba ) );
}

/**
 * Explodes string into array by non escaped delimiter.
 *
 * Example:
 *
 * $string = "1\,000, 2\,000"
 * $delimiter = ","
 *
 * Result: array( '1,000', '2,000' )
 *
 * @param string $delimiter The string to be used to split $string.
 * @param string $string    Given string to be splitted.
 *
 * @return array An array of splitted string parts.
 */
function cp_explode( $delimiter, $string ) {
	$pattern = "/(?<!\\\\)($delimiter)/";
	$items   = preg_split( $pattern, $string );

	foreach ( $items as &$item ) {
		$item = str_replace( "\\$delimiter", $delimiter, trim( $item ) );
	}

	return $items;
}
