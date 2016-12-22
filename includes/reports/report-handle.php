<?php
/**
 * Reports handle
 *
 * @package Components\Reports
 */

/**
 * Helper class that extends WP comment hooks as meanignful reports hooks
 */
class APP_Report_Handle {

	/**
	 * The reports comment type
	 * @var string
	 */
	public static $comment_type;

	/**
	 * The default report data
	 * @var array
	 */
	public static $data = array(
		'args' => array(
			'comment_meta' => array(),
		),
	);


	/**
	 * Initializes the class by setting the comment type and some important WP comment hooks
	 *
	 * @param string $comment_type The comment type that identifies a comment
	 *
	 * @return void
	 */
	public static function init( $comment_type ) {

		self::$comment_type = $comment_type;

		add_action( 'pre_comment_on_post', array( __CLASS__, 'validate_comment' ) );
		add_action( 'transition_comment_status', array( __CLASS__, 'comment_status_transition' ), 10, 3 );
		add_action( 'wp_insert_comment', array( __CLASS__, 'insert_comment' ), 10, 2 );
		add_action( 'edit_comment', array( __CLASS__, 'edit_comment' ), 10 );
		add_action( 'comment_post_redirect', array( __CLASS__, 'redirect' ), 10, 2 );

		add_action( 'wp_ajax_appthemes-delete-report', array( __CLASS__, 'ajax_delete_report' ) );
		add_action( 'wp_ajax_nopriv_appthemes-add-report', array( __CLASS__, 'ajax_add_report' ) );
		add_action( 'wp_ajax_appthemes-add-report', array( __CLASS__, 'ajax_add_report' ) );
	}


	/**
	 * Validates a report before being inserted in the DB
	 * Redirects the user to a URL referer if is exists in the $_REQUEST, otherwise, ends execution with an error
	 *
	 * @uses apply_filters() Calls 'appthemes_validate_report'
	 *
	 * @param int $post_id
	 *
	 * @return void
	 */
	public static function validate_comment( $post_id ) {

		$type = ( isset( $_POST['comment_type'] ) ) ? trim( $_POST['comment_type'] ) : null;

		if ( self::$comment_type != $type ) {
			return;
		}

		$errors = apply_filters( 'appthemes_validate_report', _appthemes_reports_error_obj(), $post_id );
		if ( $errors->get_error_codes() ) {

			set_transient( 'app-notices', $errors, 60 * 60 );

			if ( isset( $_REQUEST['url_referer'] ) ) {
				$redirect_to = esc_url_raw( $_REQUEST['url_referer'] );
				wp_safe_redirect( $redirect_to );
				exit();
			} else {
				wp_die( $errors->get_error_message() );
			}
		}

	}


	/**
	 * Provides action hooks on reports status transitions
	 *
	 * @uses do_action() Calls 'appthemes_report_{$new_status}' (approve, hold)
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @param object $comment
	 *
	 * @return void
	 */
	public static function comment_status_transition( $new_status, $old_status, $comment ) {

		if ( self::$comment_type != $comment->comment_type ) {
			return;
		}

		// no change in or out
		if ( $new_status != 'approved' && $old_status != 'approved' ) {
			return;
		}

		$report = appthemes_get_report( $comment->comment_ID );

		do_action( 'appthemes_report_' . $new_status, $report );
	}


	/**
	 * Updates any meta data on edited reports
	 *
	 * @uses do_action() Calls 'appthemes_update_{$type}_report'
	 *
	 * @param int $id
	 *
	 * @return void
	 */
	public static function edit_comment( $id ) {

		$comment = get_comment( $id );

		$report = self::handle_report( $id, $comment );
		if ( ! $report ) {
			return false;
		}

		$type = $report->get_type();

		if ( 'user' == $type ) {
			$object_id  = $report->get_recipient_id();
		} else {
			$object_id = $report->get_post_ID();
		}

		do_action( "appthemes_update_{$type}_report", $report, $object_id );
	}


	/**
	 * Extends wp_insert_comment() by providing a report filter used to store additional data.
	 *
	 * @uses do_action() Calls 'appthemes_new_{$type}_report'
	 *
	 * @param int $id
	 * @param object $comment
	 *
	 * @return void
	 */
	public static function insert_comment( $id, $comment ) {

		$report = self::handle_report( $id, $comment );
		if ( ! $report ) {
			return false;
		}

		$type = $report->get_type();

		if ( 'user' == $type ) {
			$object_id = $report->get_recipient_id();
		} else {
			$object_id = $report->get_post_ID();
		}

		do_action( "appthemes_new_{$type}_report", $report, $object_id );
	}


	/**
	 * Handles report
	 *
	 * @uses apply_filters() Calls 'appthemes_handle_report'
	 *
	 * @param int $id
	 * @param object $comment
	 *
	 * @return object
	 */
	public static function handle_report( $id, $comment ) {

		if ( self::$comment_type != $comment->comment_type ) {
			return;
		}

		$report_data = apply_filters( 'appthemes_handle_report', self::$data );
		if ( ! $report_data || ! is_array( $report_data ) ) {
			return;
		}

		$report_data = wp_parse_args( $report_data, self::$data );

		extract( $report_data );

		if ( ! empty( $user_id ) ) {
			return appthemes_set_user_report( $user_id, $id, $args );
		} else {
			return appthemes_set_report( $id, $args );
		}
	}


	/**
	 * Provides a new hook to allow redirecting the user
	 *
	 * @uses apply_filters() Calls 'appthemes_report_post_redirect'
	 *
	 * @param string $location
	 * @param object $comment
	 *
	 * @return string
	 */
	public static function redirect( $location, $comment ) {

		if ( self::$comment_type != $comment->comment_type ) {
			return $location;
		}

		return apply_filters( 'appthemes_report_post_redirect', $location, $comment );
	}


	/**
	 * Handles removing reports via ajax
	 *
	 * @return void
	 */
	public static function ajax_delete_report() {
		if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
			die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, only post method allowed.', APP_TD ) ) ) );
		}

		$report_id = isset( $_POST['report_id'] ) ? (int) $_POST['report_id'] : 0;
		if ( $report_id < 1 ) {
			die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, item does not exist.', APP_TD ) ) ) );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'delete-report' ) ) {
			die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, invalid request.', APP_TD ) ) ) );
		}

		wp_delete_comment( $report_id );
		die( json_encode( array( 'success' => true, 'message' => __( 'Report has been removed.', APP_TD ) ) ) );
	}


	/**
	 * Handles adding reports via ajax
	 *
	 * @return void
	 */
	public static function ajax_add_report() {
		if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
			die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, only post method allowed.', APP_TD ) ) ) );
		}

		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		if ( $id < 1 ) {
			die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, item does not exist.', APP_TD ) ) ) );
		}

		if ( ! isset( $_POST['type'] ) || ! in_array( $_POST['type'], array( 'post', 'user' ) ) ) {
			die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, invalid item type.', APP_TD ) ) ) );
		}

		if ( $_POST['type'] == 'user' && ! appthemes_reports_get_args( 'users' ) ) {
			die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, invalid item type.', APP_TD ) ) ) );
		}

		if ( ! isset( $_POST['report'] ) || appthemes_clean( $_POST['report'] ) != $_POST['report'] ) {
			die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, invalid report message.', APP_TD ) ) ) );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'add-report' ) ) {
			die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, invalid request.', APP_TD ) ) ) );
		}

		$item = ( $_POST['type'] == 'post' ) ? get_post( $id ) : get_userdata( $id );
		if ( ! $item ) {
			die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, item does not exist.', APP_TD ) ) ) );
		}

		$options = appthemes_load_reports_options();

		if ( $options->get( array( 'reports', 'users_only' ) ) && ! is_user_logged_in() ) {
			die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, only registered users can report.', APP_TD ) ) ) );
		}

		$comment = array( 'comment_content' => appthemes_clean( $_POST['report'] ) );
		if ( $_POST['type'] == 'post' ) {
			$comment['comment_post_ID'] = $id;
			$report = appthemes_create_report( $comment );
			if ( ! $report ) {
				die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, could not create report.', APP_TD ) ) ) );
			}
			APP_Report_Comments_Email_Notify::notify_admin( $report );
		} else {
			$report = appthemes_create_user_report( $id, $comment );
			if ( ! $report ) {
				die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, could not create report.', APP_TD ) ) ) );
			}
		}

		die( json_encode( array( 'success' => true, 'message' => __( 'Thank you. Report has been submitted.', APP_TD ) ) ) );
	}

}


/**
 * Helper function to store error objects
 *
 * @return object
 */
function _appthemes_reports_error_obj() {
	static $errors;

	if ( ! $errors ) {
		$errors = new WP_Error();
	}

	return $errors;
}

