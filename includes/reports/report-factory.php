<?php
/**
 * Reports factory class
 *
 * @package Components\Reports
 */
class APP_Report_Factory {


	/**
	 * Helper function to parse a list of parameteres against the base list
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	static function parse_args( $args ) {
		$default_args = array(
			'report_type'	=> 'post',
			'comment_meta' => array(),
			'post_meta' => array(),
			'user_meta' => array(),
		);
		return wp_parse_args( $args, $default_args );
	}


	### Post Reports


	/**
	 * Creates and retrives an new post report object
	 *
	 * @param object $comment The WP_Comment_Query array that will be used as the report object
	 * @param array $args (optional)	Additional optional params to be passed to the report object.
	 *        Expects user ($args['user_meta']) or post meta ($args['post_meta']) as associative arrays.
	 *        Meta passed trough 'user_meta' is stored in the reporter user meta and the 'post_meta' in the post meta for the post being reported
	 *
	 * @return object The report object
	 */
	static function create_post_report( $comment, $args = array() ) {
		$id = self::make( $comment, self::parse_args( $args ) );

		$report = self::set_post_report( $id, $args );

		return $report;
	}


	/**
	 * Sets an existing WP comment as a report object and retrieves it
	 *
	 * @param int $comment_id The WordPress comment ID
	 * @param array $args (optional) Additional optional params to be passed to the report object.
	 *        Expects user ($args['user_meta']) or post meta ($args['post_meta']) as associative arrays.
	 *        Meta passed trough 'user_meta' is stored in the reporter user meta and the 'post_meta' in the post meta for the post being reported
	 *
	 * @return object The report object
	 */
	static function set_post_report( $comment_id, $args = array() ) {

		$report = self::retrieve( $comment_id );
		if ( ! $report ) {
			return false;
		}

		$args = self::parse_args( $args );

		$report->set_type( 'post' );
		$report->set_recipient( $report->get_post_ID() );

		$report->set_meta( $args['comment_meta'] );

		if ( $report->is_approved() ) {
			self::add_post_report( $report->get_post_ID(), $report, $args['post_meta'] );
			self::add_user_report( $report->get_author_id(), $report, $args['user_meta'] );
		}

		return $report;
	}


	### User Reports


	/**
	 * Creates a new user report object
	 *
	 * @param int $recipient_id The user ID that is receiving the report
	 * @param object $comment The WP_Comment_Query array that will be used as the report object
	 * @param array $args (optional)	Additional optional params to be passed to the report object.
	 *        Expects user ($args['user_meta']) or post meta ($args['post_meta']) as associative arrays.
	 *        Meta passed trough 'user_meta' is stored in the reporter user meta and the 'post_meta' in the post meta for the post being reported
	 *
	 * @return object The report object
	 */
	static function create_user_report( $recipient_id, $comment, $args = array() ) {

		$id = self::make( $comment, self::parse_args( $args ) );

		$report = self::set_user_report( $recipient_id, $id, $args );

		return $report;
	}


	/**
	 * Sets an existing WP_Comment_Query array as a report object and retrieves it
	 *
	 * @param int $recipient_id The user ID that is receiving the report
	 * @param int $comment_id The WordPress comment ID
	 * @param array $args (optional) Additional optional params to be passed to the report object.
	 *        Expects user (user_meta) or post meta (post_meta) as associative arrays.
	 *        Meta passed trough 'user_meta' is stored in the reporter user meta and the 'post_meta' in the post meta for the post being reported
	 *
	 * @return object The report object
	 */
	static function set_user_report( $recipient_id, $comment_id, $args = array() ) {

		$report = self::retrieve( $comment_id );
		if ( ! $report ) {
			return false;
		}

		$args = self::parse_args( $args );

		$report->set_type( 'user' );
		$report->set_recipient( $recipient_id );

		$report->set_meta( $args['comment_meta'] );

		if ( $report->is_approved() ) {
			self::add_post_report( $report->get_post_ID(), $report, $args['post_meta'] );
			self::add_user_report( $report->get_author_id(), $report, $args['user_meta'] );
		}

		return $report;
	}


	/**
	 * Inserts a new comment as a report comment type and retrieves the new ID
	 *
	 * @param array $comment The WP_Comment_Query array that will be used as the report object
	 * @param array $args (optional) Additional optional params to be passed to the report object.
	 *        Expects user (user_meta) or post meta (post_meta) as associative arrays.
	 *        Meta passed trough 'user_meta' is stored in the reporter user meta and the 'post_meta' in the post meta for the post being reported
	 *
	 * @return int The report id
	 */
	static protected function make( $comment, $args = array() ) {

		$defaults = array(
			'comment_post_ID' => 0,
			'comment_type' => appthemes_reports_get_args( 'comment_type' ),
			'user_id' => get_current_user_id(),
		);
		$defaults = wp_parse_args( $defaults, wp_get_current_commenter() );

		$comment = wp_parse_args( $comment, $defaults );

		$id = wp_insert_comment( $comment );

		return $id;
	}


	/**
	 * Retrieves an existing report
	 *
	 * @param int $report_id The WordPress comment ID
	 *
	 * @return object The report object
	 */
	static function retrieve( $report_id ) {

		if ( ! is_numeric( $report_id ) ) {
			trigger_error( 'Invalid report id given. Must be numeric', E_USER_WARNING );
		}

		$comment = get_comment( $report_id );
		if ( ! $comment || $comment->comment_type != appthemes_reports_get_args( 'comment_type' ) ) {
			return false;
		}

		$report = new APP_Single_Report( $comment );

		return $report;
	}


	/**
	 * Adds a new report to a post report collection
	 *
	 * @param int $post_id The post ID to assign the new report
	 * @param object $report The report object being added to the collection
	 * @param array $meta (optional) Additional post meta to store in the report collection meta
	 *
	 * @return object The updated report collection
	 */
	static function add_post_report( $post_id, $report, $meta = array() ) {

		$post_reports = new APP_Post_Reports( $post_id );
		$post_reports->add_report( $report );

		if ( $meta ) {
			$post_reports->set_meta( $meta );
		}

		return $post_reports;
	}


	/**
	 * Adds a new report to a user report collection
	 *
	 * @param int $user_id The user ID to assign the new report
	 * @param object $report The report object being added to the collection
	 * @param array $meta (optional) Additional post meta to store in the report collection meta
	 *
	 * @return object The updated report collection
	 */
	static function add_user_report( $user_id, $report, $meta = array() ) {

		$user_reports = new APP_User_Reports( $user_id );
		$user_reports->add_report( $report );

		if ( $meta ) {
			$user_reports->set_meta( $meta );
		}

		return $user_reports;
	}


	/**
	 * Retrieves the reports collection for a specific post
	 *
	 * @param int $post_id The post ID to retrieve reports from
	 * @param array	$args (optional) WP_Comment_Query args to be used to fetch the report collection
	 *
	 * @return object The post report collection
	 */
	public static function get_post_reports( $post_id, $args = array() ) {
		return new APP_Post_Reports( $post_id, $args );
	}


	/**
	 * Retrieves the reports collection for a specific user
	 *
	 * @param int $user_id The user ID to retrieve reports from
	 * @param array	$args (optional) WP_Comment_Query args to be used to fetch the report collection
	 *
	 * @return object The user report collection
	 */
	public static function get_user_reports( $user_id, $args = array() ) {
		return new APP_User_Reports( $user_id, $args );
	}


	/**
	 * Helper method to retrieve reports comment types
	 *
	 * @param array	$args (optional) WP_Comment_Query args to be used to fetch the reports
	 *
	 * @return array List of comments
	 */
	private static function _get_reports( $args = array() ) {
		$defaults = array(
			'status' => 'approve',
		);
		$args = wp_parse_args( $args, $defaults );

		$args['type'] = appthemes_reports_get_args( 'comment_type' );
		return get_comments( $args );
	}


	/**
	 * Retrieve a list of reports
	 *
	 * @param array	$args (optional) WP_Comment_Query args to be used to fetch the reports
	 *
	 * @return array List of reports
	 */
	public static function get_reports( $args = array() ) {
		$reports = array();

		$reports_comments = self::_get_reports( $args );

		foreach ( $reports_comments as $report ) {
			$reports[] = self::retrieve( $report->comment_ID );
		}
		return $reports;
	}

}

