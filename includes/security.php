<?php
/**
 * Prevent visitors without permissions to access the WordPress backend.
 *
 * @package ClassiPress\Security
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */


/**
 * Checks permissions to access the WordPress backend.
 *
 * @return void
 */
function cp_security_check() {
	global $cp_options;

	$cp_access_level = $cp_options->admin_security;
	// if there's no value then give everyone access
	if ( empty( $cp_access_level ) ) {
		$cp_access_level = 'read';
	}

	// previous releases had incompatible capability with MU installs
	if ( 'install_themes' == $cp_access_level ) {
		$cp_access_level = 'manage_options';
	}

	$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
	$doing_admin_post = ( basename( $_SERVER['SCRIPT_FILENAME'] ) === 'admin-post.php' );

	if ( $cp_access_level == 'disable' || current_user_can( $cp_access_level ) || $doing_ajax || $doing_admin_post ) {
		return;
	}

	appthemes_add_notice( 'denied-admin-access', __( 'Site administrator has blocked your access to the back-office.', APP_TD ), 'error' );
	wp_redirect( CP_DASHBOARD_URL );
	exit();
}
add_action( 'admin_init', 'cp_security_check', 1 );


