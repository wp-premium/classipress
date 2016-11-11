<?php
/**
 * Escrow admin related settings.
 *
 * @package Components\Payments\Escrow\Admin
 */

if ( is_admin() ) {
	add_action( 'init', '_appthemes_register_payments_escrow_settings', 25 );
}

/**
 * Registers the payments escrow settings page.
 *
 * @return void
 */
function _appthemes_register_payments_escrow_settings() {
	if ( appthemes_is_escrow_enabled() ) {
		new APP_Payments_Escrow_Settings_Admin( APP_Gateway_Registry::get_options() );
	}
}

/**
 * Defines the escrow settings administration panel.
 */
class APP_Payments_Escrow_Settings_Admin extends APP_Conditional_Tabs_Page {

	/**
	 * Sets up the page.
	 */
	function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_slug' => 'app-escrow-settings',
			'parent' => 'app-payments-settings',
			'conditional_parent' => 'app-payments',
			'conditional_page' => 'app-payments-settings',
		);
	}

	function conditional_create_page(){
		return false;
	}

	/**
	 * Creates the tab for the page.
	 */
	function init_tabs() {

		$this->tabs->add_after( 'general', 'escrow', __( 'Escrow', APP_TD ) );

		$this->tab_sections['escrow']['settings'] = array(
			'title' => __( 'Settings', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Enable escrow', APP_TD ),
					'desc' => __( 'Yes', APP_TD ),
					'tip' => __( 'A premier/business account is required for allowing customers to process recurring payments via PayPal', APP_TD ),
					'type' => 'checkbox',
					'name' => array( 'escrow', 'enabled' )
				),
				array(
					'title' => __( 'Retained Amount', APP_TD ),
					'tip' => __( 'The amount to retain on each escrow transaction.', APP_TD ),
					'type' => 'text',
					'name' => array( 'escrow', 'retain_amount' ),
					'extra' => array(
						'class' => 'small-text',
					),
				),
				array(
					'title' => __( 'Retain Type', APP_TD ),
					'tip' => __( 'Choose whether you want to receive a flat amount or a percentage based amount.', APP_TD ),
					'type' => 'select',
					'name' => 'escrow_retain_type',
					'name' => array( 'escrow', 'retain_type' ),
					'choices' => array(
						'flat' => __( 'Flat', APP_TD ),
						'percent' => __( 'Percentage', APP_TD ),
					),
				),
			),
		);

		$this->tab_sections['escrow']['gateways'] = array(
			'title' => __( 'Installed Escrow Gateways', APP_TD ),
			'fields' => array(),
		);

		$gateways = APP_Gateway_Registry::get_escrow_gateways();
		foreach ( $gateways as $gateway ) {
			$this->tab_sections['escrow']['gateways']['fields'][] = $this->load_gateway_tabs( $gateway );
		}

		add_action( 'admin_notices', array( $this, 'disabled_gateway_warning' ) );
		add_action( 'admin_notices', array( $this, 'invalid_account_type_warning' ) );
	}

	/**
	 * Displays notices if a gateway is disabled.
	 */
	function disabled_gateway_warning() {
		if ( isset( $_GET['tab'] ) ) {
			$gateway_id = $_GET['tab'];

			$gateway = APP_Gateway_Registry::get_gateway( $gateway_id );

			if ( APP_Gateway_Registry::is_gateway_registered( $gateway_id ) && $gateway->supports('escrow') && ! APP_Gateway_Registry::is_gateway_enabled( $gateway_id, 'escrow' ) ) {
				$this->admin_msg( __( 'This gateway is currently <strong>disabled</strong>. It cannot be used on escrow transactions. Go to the <a href="?page=app-payments-settings&tab=escrow">Escrow</a> tab to enable it.', APP_TD ) );
			}
		}
	}

	/**
	 * Displays notices if the user does have a premier/business account.
	 */
	function invalid_account_type_warning() {
		$options = APP_Gateway_Registry::get_options();

		if ( APP_Gateway_Registry::is_gateway_enabled( 'paypal', 'escrow' ) && empty( $options->gateways['paypal']['business_account'] ) ) {
			$this->admin_msg( __( '<strong>Important:</strong> You need a Premier or Verified Business PayPal account to be able to use the PayPal Adaptive service.', APP_TD ) );
		}
	}

	/**
	 * Loads the gateway form fields into tabs
	 *
	 * @param string $gateway Gateway identifier
	 *
	 * @return array Array for the checkbox to enable the gateway
	 */
	function load_gateway_tabs( $gateway ) {

		$form_values = $gateway->form();
		$nicename = $gateway->identifier();

		if ( array_key_exists( 'fields', $form_values ) ){

			// Wrap values
			foreach ( $form_values['fields'] as $key => $block ) {
				$value = $block['name'];
				$form_values['fields'][$key]['name'] = array( 'gateways', $nicename, $value );
			}

			$this->tab_sections[ $nicename ][ 'general_settings' ] = $form_values;

		} else {

			// Wrap values
			foreach ( $form_values as $s_key => $section ) {
				foreach ( $section['fields'] as $key => $block ) {
					$value = $block['name'];
					$form_values[$s_key]['fields'][$key]['name'] = array( 'gateways', $nicename, $value );
				}
			}

			$this->tab_sections[ $nicename ] = $form_values;
		}

		// Only add a tab for gateways with a form
		$title = $gateway->display_name( 'admin' );
		if ( $form_values ){
			$this->tabs->add( $nicename, $title );
			$title = html_link( add_query_arg( array(
				'page' => $this->args['page_slug'],
				'tab' => $nicename
			), 'admin.php' ), $title );
		}

		$gateway_name = array( 'gateways', 'escrow', 'enabled', $nicename );

		return array(
			'title' => $title,
			'type' => 'checkbox',
			'desc' => __( 'Enable', APP_TD ),
			'name' => $gateway_name,
		);

	}

}
