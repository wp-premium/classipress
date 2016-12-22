<?php
/**
 * Notices API
 *
 * @package Framework\Notices
 */

/**
 * Notices class for displaying all types of notices
 */
class APP_Notices {

	static $notices;
	private static $transient_key = 'app-notice';

	public static function init() {
		self::$notices = new WP_Error();

		// store existing notices in transient on page redirects
		add_filter( 'wp_redirect', array( __CLASS__, 'save_notices' ), 10, 2 );

		// assign default notices outputter
		add_action( 'appthemes_display_notice', array( __CLASS__, 'outputter' ), 10, 2 );

		// pre-populate notices with existing transient notices
		$transient_notices = self::get_transient();
		if ( is_wp_error( $transient_notices ) ) {
			self::$notices = $transient_notices;
		}

	}

	/**
	 * Enqueues a new notice message into the notices array
	 *
	 * @param string $code The notice error code
	 * @param string $message The message to be displayed
	 * @param string $type (optional) The notice type. Default is 'error' but can be any string
	 * @return array The enqueued notices array
	 */
	static function add( $code, $message, $type = 'error' ) {
		self::$notices->add( $code, $message, array( 'type' => $type ) );
		return self::$notices;
	}

	/**
	 * Callback triggered on page redirects.
	 *
	 * Stores notices as transients to be able to display them later
	 *
	 * @param string $location The URL redirect
	 * @return string The URL redirect
	 */
	static function save_notices( $location, $status ) {
		self::set_transient( $expiration = 60 * 60 );
		return $location;
	}

	/**
	 * Retrieve notices by type. If no type is set, retrieves all enqueued notices
	 *
	 * @param string $type The notice type
	 * @return object A WP_Error object with all the requested notices
	 */
	static function get_notices( $type = '' ) {

		$notices_by_type = new WP_Error();

		foreach ( self::$notices->get_error_codes() as $error ) {
			$error_data = self::$notices->get_error_data( $error );
			if ( $error_data && empty( $error_data['type'] ) ) {
				$error_data['type'] = 'error';
			}
			if ( ( $type && $type == $error_data['type'] ) || ! $type ) {
				$notices_by_type->add( $error, self::$notices->get_error_message( $error ), $error_data );
			}
		}

		if ( ! $notices_by_type->get_error_codes() ) {
			return false;
		}
		return $notices_by_type;
	}

	/**
	 * Iterates though all the enqueued notices groups and displays them by type using appthemes_display_notice()
	 *
	 * @param string $type (optional) The notices type to be displayed. If empty, displays all enqueued notice types
	 */
	static function display( $type = '' ) {

		$notices_by_type = self::get_notices( $type );
		if ( ! $notices_by_type ) {
			return false;
		}

		$notices = array();

		foreach ( $notices_by_type->get_error_codes() as $error ) {
			$error_data = $notices_by_type->get_error_data( $error );
			$notices[ $error_data['type'] ][] = $notices_by_type->get_error_message( $error );
		}

		if ( ! $type ) {
			foreach ( $notices as $type => $messages ) {
				appthemes_display_notice( $type, $messages );
			}
		} elseif( isset( $notices[ $type ] ) ) {
			appthemes_display_notice( $type, $notices[ $type ] );
		}

		// clear the messages arrays
		unset( self::$notices->errors );
		unset( self::$notices->error_data );
	}

	/**
	 * Prints notices.
	 *
	 * @param string $class CSS class of notice block.
	 * @param array $msgs Messages to be displayed.
	 * @return void
	 */
	static function outputter( $class, $msgs ) {
	?>
		<div class="notice <?php echo esc_attr( $class ); ?>">
			<?php foreach ( $msgs as $msg ) { ?>
				<div><?php echo $msg; ?></div>
			<?php } ?>
		</div>
	<?php
	}

	/**
	 * Stores a notice using WP set_transient()
	 *
	 * Generates a unique key for the transient name based on the user ID (registered users) or IP (visitors)
	 *
	 * @param int $expiration (optional) Time until expiration in seconds, default 0
	 * @return bool False if value was not set and true if value was set.
	 */
	private static function set_transient( $expiration = 0 ) {

		// skip if the notices object is empty
		if ( ! self::$notices->get_error_code() ) {
			return false;
		}

		if ( is_user_logged_in() ) {
			$result = set_transient( self::$transient_key . '-' . get_current_user_id(), self::$notices, $expiration );
		} else {
			$result = appthemes_set_visitor_transient( self::$transient_key, self::$notices, $expiration );
		}

		return $result;
	}

	/**
	 * Retrieves any previously stored transient notices
	 *
	 * @return array The list of stored transient notices
	 */
	private static function get_transient() {

		if ( is_user_logged_in() ) {
			$transient_notices = get_transient( self::$transient_key . '-' . get_current_user_id() );
			if ( $transient_notices ) {
				delete_transient( self::$transient_key . '-' . get_current_user_id() );
			}
		} else {
			$transient_notices = appthemes_get_visitor_transient( self::$transient_key );
			if ( $transient_notices ) {
				appthemes_delete_visitor_transient( self::$transient_key );
			}
		}

		return $transient_notices;
	}

}


/**
 * Enqueues a message to the notices object
 *
 * Defaults to 'error' message but can be set to any type.
 * The message type is used as the CSS class on the output message.
 *
 * @param string|int $code Message code
 * @param string $message The message to display
 * @param string $type (optional) Message type (e.g: error (default), success )
 * @return object The notices object
 */
function appthemes_add_notice( $code, $message, $type = 'error' ) {
	return APP_Notices::add( $code, $message, $type );
}

/**
 * Iterates though all the enqueued notices groups and displays them by type using appthemes_display_notice()
 *
 * @param string $type (optional) The notices type to be displayed. If empty, displays all enqueued notice types
 */
function appthemes_display_notices( $type = '' ) {
	APP_Notices::display( $type );
}

/**
 * Retrieves notices by type. If no type is set, retrieves all enqueued notices. Defaults to all 'error' notices.
 *
 * @param string $type (optional) The notice types to retrieve
 * @return object A WP_Error object with all the notices from the requested type
 */
function appthemes_get_notices( $type = 'error' ) {
	return APP_Notices::get_notices( $type );
}

/**
 * Prints notices.
 *
 * @param string $class CSS class of notice block.
 * @param string|array|object $msgs Messages to be displayed. Single message string or an array of messages or WP_Error object
 * @return boolean Returns false if no passed messages
 */
function appthemes_display_notice( $class, $msgs ) {
	if ( is_string( $msgs ) ) {
		$msgs = (array) $msgs;
	} elseif ( is_wp_error( $msgs ) ) {
		$msgs = $msgs->get_error_messages();
	}

	if ( ! is_array( $msgs ) ) {
		return false;
	}

	do_action( 'appthemes_display_notice', $class, $msgs );
}
