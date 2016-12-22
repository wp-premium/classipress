<?php
/**
 * General settings page
 *
 * @package Framework\Settings
 */
class APP_Settings extends APP_Tabs_Page {

	function setup() {

		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title'            => __( 'Settings', APP_TD ),
			'menu_title'            => __( 'Settings', APP_TD ),
			'page_slug'             => 'app-settings',
			'parent'                => 'app-dashboard',
			'screen_icon'           => 'options-general',
			'admin_action_priority' => 9,
		);

	}

	protected function init_tabs() {
		$_SERVER['REQUEST_URI'] = esc_url_raw( remove_query_arg( array( 'firstrun' ), $_SERVER['REQUEST_URI'] ) );
	}

}