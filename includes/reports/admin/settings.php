<?php
/**
 * Defines the Reports Settings Administration Panel
 *
 * @package Components\Reports\Admin\Settings
 */
class APP_Reports_Settings_Admin extends APP_Conditional_Tabs_Page {

	/**
	 * Sets up the page
	 *
	 * @return void
	 */
	function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title' => __( 'Reports Settings', APP_TD ),
			'menu_title' => __( 'Reports Settings', APP_TD ),
			'page_slug' => 'app-reports-settings',
			'parent' => 'app-dashboard',
			'screen_icon' => 'options-general',
			'admin_action_priority' => 11,
			'conditional_parent' => appthemes_reports_get_args( 'admin_top_level_page' ),
			'conditional_page' => appthemes_reports_get_args( 'admin_sub_level_page' ),
		);
	}


	/**
	 * Determinies what to create, settings page vs. tab
	 *
	 * @return bool
	 */
	function conditional_create_page() {
		$top_level = appthemes_reports_get_args( 'admin_top_level_page' );
		$sub_level = appthemes_reports_get_args( 'admin_sub_level_page' );

		if ( ! $top_level && ! $sub_level ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Setups tabs
	 *
	 * @return void
	 */
	function init_tabs() {

		$this->tabs->add( 'reports', __( 'Reports', APP_TD ) );

		$fields = array(
			array(
				'title' => __( 'Registered Users Only', APP_TD ),
				'name' => array( 'reports', 'users_only' ),
				'type' => 'checkbox',
				'desc' => __( 'Only allow registered users to report problems.', APP_TD ),
				'tip' => '',
			),
			array(
				'title' => __( 'Notification Email', APP_TD ),
				'name' => array( 'reports', 'send_email' ),
				'type' => 'checkbox',
				'desc' => __( 'Send me an email when a problem has been reported.', APP_TD ),
				'tip' => '',
			),
			array(
				'title' => __( 'Report Post Values', APP_TD ),
				'desc' => '<br />' . __( 'Enter the different options you want available for the report feature. Enter one per line.', APP_TD ),
				'type' => 'textarea',
				'sanitize' => array( $this, 'report_options_clean' ),
				'name' => array( 'reports', 'post_options' ),
				'extra' => array(
						'rows' => 10,
						'cols' => 50,
						'class' => 'large-text'
					),
				'tip' => '',
			),
		);

		if ( appthemes_reports_get_args( 'users' ) ) {
			$fields[] = array(
				'title' => __( 'Report User Options', APP_TD ),
				'desc' => '<br />' . __( 'Enter the different options you want available for the report feature. Enter one per line.', APP_TD ),
				'type' => 'textarea',
				'sanitize' => array( $this, 'report_options_clean' ),
				'name' => array( 'reports', 'user_options' ),
				'extra' => array(
						'rows' => 10,
						'cols' => 50,
						'class' => 'large-text'
					),
				'tip' => '',
			);
		}

		$this->tab_sections['reports']['general'] = array(
			'title' => '',
			'fields' => $fields,
		);

	}


	/**
	 * Cleaning report options
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public function report_options_clean( $string ) {
		$string = str_replace( array( "\r\n", "\r" ), "\n", $string );
		$string = str_replace( "\t", "", $string );
		$string = appthemes_clean( $string );

		return $string;
	}


}

