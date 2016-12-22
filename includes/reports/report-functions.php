<?php
/**
 * Reports component API
 *
 * @package Components\Reports
 */

define( 'APP_REPORTS_CTYPE', 'report' );

// comments meta keys
define( 'APP_REPORTS_C_DATA_KEY', '_report_data' );

define( 'APP_REPORTS_C_RECIPIENT_KEY', '_report_recipient' );
define( 'APP_REPORTS_C_RECIPIENT_TYPE_KEY', '_report_recipient_type' );

// post meta keys
define( 'APP_REPORTS_P_DATA_KEY', '_report_data' );
define( 'APP_REPORTS_P_TOTAL_KEY', '_report_total' );
define( 'APP_REPORTS_P_STATUS_KEY', '_report_status' );

// user meta keys
define( 'APP_REPORTS_U_DATA_KEY', '_report_data' );
define( 'APP_REPORTS_U_TOTAL_KEY', '_report_total' );


/**
 * Retrieve a list of post reports
 *
 * @param array	$args (optional) WP_Comment_Query args to be used to fetch the reports
 *
 * @return array List of reports
 */
function appthemes_get_reports( $args = array() ) {
	return APP_Report_Factory::get_reports( $args );
}


### Post Reports


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
function appthemes_set_report( $comment_id, $args = array() ) {
	return APP_Report_Factory::set_post_report( $comment_id, $args );
}


/**
 * Creates and retrives an new post report object from a comment array
 *
 * @param object $comment The WP_Comment_Query array that will be used as the report object
 * @param array $args (optional) Additional optional params to be passed to the report object.
 *        Expects user ($args['user_meta']) or post meta ($args['post_meta']) as associative arrays.
 *        Meta passed trough 'user_meta' is stored in the reporter user meta and the 'post_meta' in the post meta for the post being reported
 *
 * @return object The report object
 */
function appthemes_create_report( $comment, $args = array() ) {
	return APP_Report_Factory::create_post_report( $comment, $args );
}


/**
 * Retrieves an existing post report
 *
 * @param int $report_id The report ID (WordPress comment ID)
 *
 * @return object The report object
 */
function appthemes_get_report( $report_id ) {
	return APP_Report_Factory::retrieve( $report_id );
}


/**
 * Retrieves the reports collection for a specific post
 *
 * @param int $post_id The post ID to retrieve reports from
 * @param array	$args (optional) WP_Comment_Query args to be used to fetch the report collection
 *
 * @return object The post report collection
 */
function appthemes_get_post_reports( $post_id, $args = array() ) {
	return APP_Report_Factory::get_post_reports( $post_id, $args );
}


/**
 * Retrieves the total reports for a post
 *
 * @param int $post_id The post ID
 * @param array $args (optional) WP_Comment_Query args to be used to fetch the report collection
 * @param type $cached (optional) If set to TRUE will fetch the meta value stored in the DB
 *
 * @return type
 */
function appthemes_get_post_total_reports( $post_id, $args = array(), $cached = false ) {
	$reports = appthemes_get_post_reports( $post_id, $args );
	return $reports->get_total_reports( $cached );
}


/**
 * Activates a report by setting the status to 'approve' (comment status)
 *
 * @param int $report_id The report ID
 *
 * @return void
 */
function appthemes_activate_report( $report_id ) {
	$report = appthemes_get_report( $report_id );

	$report->approve();
}


### User Reports


/**
 * Sets an existing WP comment as a user report object and retrieves it
 *
 * @param int $recipient_id The user ID that is being reported
 * @param int $comment_id The WordPress comment ID
 * @param array $args (optional) Additional optional params to be passed to the report object.
 *        Expects user ($args['user_meta']) or post meta ($args['post_meta']) as associative arrays.
 *        Meta passed trough 'user_meta' is stored in the reporter user meta and the 'post_meta' in the post meta for the post being reported
 *
 * @return object The report object
 */
function appthemes_set_user_report( $recipient_id, $comment_id, $args = array() ) {
	return APP_Report_Factory::set_user_report( $recipient_id, $comment_id, $args );
}


/**
 * Creates a new user report object from a comment array
 *
 * @param int $recipient_id The user ID that is being reported
 * @param object $comment The WP_Comment_Query array that will be used as the report object
 * @param array $args (optional) Additional optional params to be passed to the report object.
 *        Expects user ($args['user_meta']) or post meta ($args['post_meta']) as associative arrays.
 *        Meta passed trough 'user_meta' is stored in the reporter user meta and the 'post_meta' in the post meta for the post being reported
 *
 * @return object The report object
 */
function appthemes_create_user_report( $recipient_id, $comment, $args = array() ) {
	return APP_Report_Factory::create_user_report( $recipient_id, $comment, $args );
}


/**
 * Retrieves the reports collection for a specific user
 *
 * @param int $user_id The user ID to retrieve reports from
 * @param array	$args (optional) WP_Comment_Query args to be used to fetch the report collection
 *
 * @return object The user report collection
 */
function appthemes_get_user_reports( $user_id, $args = array() ) {
	return APP_Report_Factory::get_user_reports( $user_id, $args );
}


/**
 * Retrieves the report for a specific user
 *
 * @param int $user_id The user ID to retrieve reports from
 * @param int $post_id The post ID
 * @param array	$args (optional) WP_Comment_Query args to be used to fetch the report collection
 *
 * @return object The user report collection
 */
function appthemes_get_user_post_report( $user_id, $post_id, $args = array() ) {
	$args['post_id'] = $post_id;
	$reports = appthemes_get_user_reports( $user_id, $args );
	return reset( $reports->reports );
}


/**
 * Retrieves the report authored for a specific user
 *
 * @param int $user_id The user ID to retrieve reports from
 * @param int $post_id The post ID
 * @param array	$args (optional) WP_Comment_Query args to be used to fetch the report collection
 *
 * @return object The user report collection
 */
function appthemes_get_user_authored_post_report( $user_id, $post_id, $args = array() ) {
	$args['post_id'] = $post_id;
	$reports = appthemes_get_user_authored_reports( $user_id, $args );
	return reset( $reports->reports );
}


/**
 * Retrieves the total reports for a user
 *
 * @param int $user_id The user ID to retrieve the totals from
 * @param array $args (optional) WP_Comment_Query args to be used to fetch the report collection
 * @param type $cached (optional) If set to TRUE will fetch the meta value stored in the DB
 *
 * @return type
 */
function appthemes_get_user_total_reports( $user_id, $args = array(), $cached = false ) {
	$reports = appthemes_get_user_reports( $user_id, $args );
	return $reports->get_total_reports( $cached );
}


### User Authored Reports


/**
 * Retrieves the reports collection authored by a specific user
 *
 * @param int $user_id The reporter user ID
 * @param array	$args (optional) WP_Comment_Query args to be used to fetch the report collection
 *
 * @return object The user authored reports collection
 */
function appthemes_get_user_authored_reports( $user_id, $args = array() ) {
	$args = array(
		'user_id' => $user_id,
		'meta_key' => '',
		'meta_value' => '',
	);
	return APP_Report_Factory::get_user_reports( $user_id, $args );
}


/**
 * Retrieves the total reports given by a specific author
 *
 * @param int $user_id The reporter user ID
 * @param array $args (optional) WP_Comment_Query args to be used to fetch the report collection
 * @param type $cached (optional) If set to TRUE will fetch the meta value stored in the DB
 *
 * @return type
 */
function appthemes_get_user_authored_total_reports( $user_id, $args = array(), $cached = false ) {
	$reports = appthemes_get_user_authored_reports( $user_id, $args );
	return $reports->get_total_reports( $cached );
}


### Misc


/**
 * Returns an HTML form for reporting item
 *
 * @param int $id The post or user ID
 * @param string $type (optional) Type of reported item, post or user
 *
 * @return string The report form
 */
function appthemes_get_reports_form( $id, $type = 'post' ) {
	$options = appthemes_load_reports_options();
	$select_options_type = ( $type == 'post' ) ? 'post_options' : 'user_options';
	$select_options = $options->get( array( 'reports', $select_options_type ) );

	if ( empty( $select_options ) ) {
		return false;
	}

	if ( $type == 'user' && ! appthemes_reports_get_args( 'users' ) ) {
		return false;
	}

	if ( $options->get( array( 'reports', 'users_only' ) ) && ! is_user_logged_in() ) {
		return false;
	}

	$select_options = explode( "\n", $select_options );

	$select_html = '';
	foreach ( $select_options as $option ) {
		$select_html .= html( 'option', array( 'value' => $option ), $option );
	}
	$select_html = html( 'select', array( 'name' => 'report' ), $select_html );

	$nonce = wp_create_nonce( 'add-report' );

	$form = '<div class="reports_message"><span class="spinner"></span>' . __( 'Processing your request, Please wait....', APP_TD ) . '</div>';
	$form .= '<div class="reports_form">';
	$form .= '<form method="post" enctype="text/plain">';
	$form .= $select_html;
	$form .= html( 'input', array( 'type' => 'submit', 'name' => 'submit', 'value' => __( 'Report', APP_TD ) ) );
	$form .= html( 'input', array( 'type' => 'hidden', 'name' => 'type', 'value' => $type ) );
	$form .= html( 'input', array( 'type' => 'hidden', 'name' => 'id', 'value' => $id ) );
	$form .= html( 'input', array( 'type' => 'hidden', 'name' => 'nonce', 'value' => $nonce ) );
	$form .= '</form>';
	$form .= '</div>';

	return $form;
}

