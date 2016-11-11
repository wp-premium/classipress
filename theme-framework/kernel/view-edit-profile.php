<?php
/**
 * User Profile views
 *
 * @package ThemeFramework\Views
 */

/**
 * User Profile view page
 */
class APP_User_Profile extends APP_View_Page {

	function __construct() {
		parent::__construct( 'edit-profile.php', __( 'Edit Profile', APP_TD ) );
		add_action( 'init', array( $this, 'update' ) );
	}

	static function get_id() {
		return parent::_get_page_id( 'edit-profile.php' );
	}

	function update() {
		if ( ! isset( $_POST['action'] ) || 'app-edit-profile' != $_POST['action'] ) {
			return;
		}

		check_admin_referer( 'app-edit-profile' );

		require ABSPATH . '/wp-admin/includes/user.php';

		$r = edit_user( $_POST['user_id'] );

		if ( is_wp_error( $r ) ) {
			$this->errors = $r;
			foreach ( $this->errors->get_error_codes() as $error ) {
				appthemes_add_notice( $error, $this->errors->get_error_message( $error ), 'error' );
			}
		} else {
			do_action( 'personal_options_update', $_POST['user_id'] );

			appthemes_add_notice( 'updated-profile', __( 'Your profile has been updated.', APP_TD ), 'success' );

			$redirect_url = add_query_arg( array( 'updated' => 'true' ) );
			$redirect_url = esc_url_raw( $redirect_url );

			wp_redirect( $redirect_url );
			exit();
		}
	}

	function template_redirect() {
		// Prevent non-logged-in users from accessing the edit-profile.php page
		appthemes_auth_redirect_login();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	function enqueue_scripts() {
		wp_enqueue_script(
			'app-user-profile',
			APP_THEME_FRAMEWORK_URI . '/js/profile.js',
			array( 'user-profile' ),
			APP_THEME_FRAMEWORK_VER
		);
	}

}


/**
 * Returns user edit profile url.
 *
 * @return string
 */
function appthemes_get_edit_profile_url() {
	if ( $page_id = APP_User_Profile::get_id() ) {
		return get_permalink( $page_id );
	}

	return get_edit_profile_url( get_current_user_id() );
}


/**
 * Returns an array of user profile options for Display Name field.
 *
 * @param int $user_id (optional)
 *
 * @return array
 */
function appthemes_get_user_profile_display_name_options( $user_id = 0 ) {
	$public_display = array();

	if ( ! $user_id && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	if ( ! $user_id || ! $user = get_user_by( 'id', $user_id ) ) {
		return $public_display;
	}

	$public_display['display_nickname'] = $user->nickname;
	$public_display['display_username'] = $user->user_login;

	if ( ! empty( $user->first_name ) ) {
		$public_display['display_firstname'] = $user->first_name;
	}

	if ( ! empty( $user->last_name ) ) {
		$public_display['display_lastname'] = $user->last_name;
	}

	if ( ! empty( $user->first_name ) && ! empty( $user->last_name ) ) {
		$public_display['display_firstlast'] = $user->first_name . ' ' . $user->last_name;
		$public_display['display_lastfirst'] = $user->last_name . ' ' . $user->first_name;
	}

	// Only add this option if it isn't duplicated elsewhere
	if ( ! in_array( $user->display_name, $public_display ) ) {
		$public_display = array( 'display_displayname' => $user->display_name ) + $public_display;
	}

	$public_display = array_map( 'trim', $public_display );
	$public_display = array_unique( $public_display );

	return apply_filters( 'appthemes_get_user_profile_display_name_options', $public_display, $user_id );
}

