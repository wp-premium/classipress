<?php
/**
 * Load Reports component
 *
 * @package Components\Reports
 */

add_action( 'after_setup_theme', '_appthemes_load_reports', 998 );

define( 'APP_REPORTS_VERSION', '1.0' );


/**
 * Setup Reports.
 *
 * @return void
 */
function _appthemes_load_reports() {

	if ( ! current_theme_supports( 'app-reports' ) ) {
		return;
	}

	require_once( APP_FRAMEWORK_DIR . '/admin/class-meta-box.php' );
	require_once( APP_FRAMEWORK_DIR . '/admin/class-user-meta-box.php' );
	require_once( APP_FRAMEWORK_DIR . '/admin/class-tabs-page.php' );

	// Reports
	require_once( dirname( __FILE__ ) . '/report-functions.php' );
	require_once( dirname( __FILE__ ) . '/report-comments.php' );
	require_once( dirname( __FILE__ ) . '/report-factory.php' );
	require_once( dirname( __FILE__ ) . '/report-class.php' );
	require_once( dirname( __FILE__ ) . '/report-handle.php' );
	require_once( dirname( __FILE__ ) . '/report-notify-class.php' );
	require_once( dirname( __FILE__ ) . '/report-enqueue.php' );

	$options = appthemes_load_reports_options();

	if ( is_admin() ) {

		require_once( dirname( __FILE__ ) . '/admin/admin.php' );
		require_once( dirname( __FILE__ ) . '/admin/metabox.php' );
		require_once( dirname( __FILE__ ) . '/admin/settings.php' );

		new APP_Report_Admin( $options );
		new APP_Report_Post_Metabox();
		if ( appthemes_reports_get_args( 'users' ) ) {
			new APP_Report_User_Metabox();
		}
	}

	extract( appthemes_reports_get_args(), EXTR_PREFIX_ALL, 'report' );

	// inits comment hooks to help handle custom comment types
	APP_Report_Comments::init( $report_comment_type, $report_auto_approve );

	// init email notfications
	APP_Report_Comments_Email_Notify::init( $report_comment_type );

	// init reports data handling
	APP_Report_Handle::init( $report_comment_type );
}


/**
 * Returns reports options object.
 *
 * @return object
 */
function appthemes_load_reports_options() {

	$options = appthemes_reports_get_args( 'options' );

	if ( $options && is_a( $options, 'scbOptions' ) ) {
		return $options;
	}

	$defaults = array(
		'reports' => array(
			'post_options' =>
				__( 'Offensive Content', APP_TD ) . "\n" .
				__( 'Spam', APP_TD ) . "\n" .
				__( 'Other', APP_TD ),
			'user_options' =>
				__( 'Offensive Content', APP_TD ) . "\n" .
				__( 'Spam', APP_TD ) . "\n" .
				__( 'Other', APP_TD ),
			'users_only' => 0,
			'send_email' => 1,
		),
	);
	$options = new scbOptions( 'app_reports', false, $defaults );

	return $options;
}


/**
 * Returns reports theme support args.
 *
 * @param string $option (optional)
 *
 * @return mixed
 */
function appthemes_reports_get_args( $option = '' ) {

	if ( ! current_theme_supports( 'app-reports' ) ) {
		return array();
	}

	list( $args ) = get_theme_support( 'app-reports' );

	$defaults = array(
		'comment_type' => APP_REPORTS_CTYPE,
		'post_type' => array( 'post' ),
		'auto_approve' => true,
		'options' => false,
		'users' => false,
		'admin_top_level_page' => false,
		'admin_sub_level_page' => false,
		'url' => get_template_directory_uri() . '/includes/reports',
	);

	$final = wp_parse_args( $args, $defaults );

	if ( empty( $option ) ) {
		return $final;
	} elseif ( isset( $final[ $option ] ) ) {
		return $final[ $option ];
	} else {
		return false;
	}
}

