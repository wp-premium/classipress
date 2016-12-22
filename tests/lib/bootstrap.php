<?php

define( 'APP_TESTS_LIB', dirname( __FILE__ ) );

if ( ! isset( $_SERVER['SERVER_NAME'] ) ) {
	$_SERVER['SERVER_NAME'] = 'www.build.dev';
}

function _tests_bootstrap_set_options() {
	$main_dir = basename( dirname( dirname( dirname( __FILE__ ) ) ) );

	if ( getenv( 'PLUGIN_SLUG' ) ) {
		$GLOBALS['wp_tests_options'] = array(
			'active_plugins' => array( $main_dir . '/' . getenv( 'PLUGIN_SLUG' ) ),
		);
	} else {
		$GLOBALS['wp_tests_options'] = array(
			'template' => $main_dir,
			'stylesheet' =>  $main_dir,
		);
	}
}
_tests_bootstrap_set_options();

function wp_mail( $to, $subject, $body, $headers = '', $attachments = array() ) {
	$args = compact( 'to', 'subject', 'body', 'headers', 'attachments' );

	do_action( '_wp_mail_sent', $args );
}

require dirname( __FILE__ ) . '/includes/bootstrap.php';

