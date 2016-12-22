<?php
/**
 * Defines the Payments Settings Administration Panel
 *
 * @package Components\Payments\Admin\Settings
 */
class APP_Payments_Settings_Admin extends APP_Tabs_Page {

	/**
	 * Sets up the page
	 * @return void
	 */
	function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title' => __( 'Payments Settings', APP_TD ),
			'menu_title' => __( 'Settings', APP_TD ),
			'page_slug' => 'app-payments-settings',
			'parent' => 'app-payments',
			'screen_icon' => 'options-general',
			'admin_action_priority' => 11,
		);

	}

	/**
	 * Creates the tabs for the page
	 * @return void
	 */
	protected function init_tabs() {
		$this->tabs->add( 'general', __( 'General', APP_TD ) );

		$this->tab_sections['general']['regional'] = array(
			'title' => __( 'Regional', APP_TD ),
			'desc' => __( 'The following options affect how prices are displayed on your website.', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Currency', APP_TD ),
					'type' => 'select',
					'name' => 'currency_code',
					'values' => APP_Currencies::get_currency_string_array(),
				),
				array(
					'title' => __( 'Identifier', APP_TD ),
					'type' => 'select',
					'name' => 'currency_identifier',
					'values' => array(
						'symbol' => sprintf( __( 'Symbol (%s)', APP_TD ), '{symbol}' ),
						'code' => sprintf( __( 'Code (%s)', APP_TD ), '{code}' ),
					),
				),
				array(
					'title' => __( 'Position', APP_TD ),
					'type' => 'select',
					'name' => 'currency_position',
					'values' => array(
						'left' => sprintf( __( 'Left (%s1.00)', APP_TD ), '{symbol}' ),
						'right' => sprintf( __( 'Right (1.00%s)', APP_TD ), '{symbol}' ),
						'left_space' => sprintf( __( 'Left with space (%s 1.00)', APP_TD ), '{symbol}' ),
						'right_space' => sprintf( __( 'Right with space (1.00 %s)', APP_TD ), '{symbol}' ),
					),
				),
				array(
					'title' => __( 'Thousand Separator', APP_TD ),
					'type' => 'text',
					'name' => 'thousands_separator',
					'tip' => __( 'The thousand separator of displayed prices.', APP_TD ),
					'extra' => array(
						'class' => 'small-text',
					),
					'default' => ','
				),
				array(
					'title' => __( 'Decimal Separator', APP_TD ),
					'type' => 'text',
					'name' => 'decimal_separator',
					'tip' => __( 'The decimal separator of displayed prices.', APP_TD ),
					'extra' => array(
						'class' => 'small-text',
					),
					'default' => '.'
				),
			),
		);

		$this->tab_sections['general']['tax'] = array(
			'title' => __( 'Tax', APP_TD ),
			'desc' => __( 'The following options affect how taxes are applied to all purchases.', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Rate', APP_TD ),
					'type' => 'text',
					'name' => 'tax_charge',
					'desc' => __('%', APP_TD ),
					'tip' => __( 'Set to zero to disable taxes.', APP_TD ),
					'extra' => array(
						'class' => 'small-text',
					),
					'default' => 0
				),
			),
		);

		$this->tab_sections['general']['gateways'] = array(
			'title' => __( 'Installed Gateways', APP_TD ),
			'fields' => array(),
		);

		$this->tab_sections['general']['security'] = array(
			'title'  => __( 'Security', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Allow users view their orders list', APP_TD ),
					'tip'   => __( 'Enable this option if you allow users view their orders list in back-end, otherwise only editors and administrators can do that.', APP_TD ),
					'desc'  => __( 'Yes', APP_TD ),
					'type'  => 'checkbox',
					'name'  => 'allow_view_orders',
				)
			),
		);

		$gateways = APP_Gateway_Registry::get_gateways();
		foreach ( $gateways as $gateway ) {
			$this->tab_sections['general']['gateways']['fields'][] = $this->load_gateway_tabs( $gateway );
		}

		add_action( 'admin_notices', array( $this, 'disabled_gateway_warning' ) );
	}

	/**
	 * Displays notices if a gateway is disabled
	 * @return void
	 */
	function disabled_gateway_warning() {
		if ( isset( $_GET['tab'] ) ) {
			$gateway_id = $_GET['tab'];

			if ( APP_Gateway_Registry::is_gateway_registered( $gateway_id ) && !APP_Gateway_Registry::is_gateway_enabled( $gateway_id ) ) {
				$this->admin_msg( __( 'This gateway is currently <strong>disabled</strong>. Users cannot use it as a purchasing option. Go to the <a href="?page=app-payments-settings">General</a> tab to enable it.', APP_TD ) );
			}
		}
	}

	/**
	 * Loads the gateway form fields into tabs
	 * @param  string $gateway Gateway identifier
	 * @return array           Array for the checkbox to enable the gateway
	 */
	function load_gateway_tabs( $gateway ){

		$form_values = $gateway->form();
		$nicename = $gateway->identifier();

		if( array_key_exists( 'fields', $form_values ) ){

			// Wrap values
			foreach ( $form_values['fields'] as $key => $block ) {

				$value = $block['name'];
				$form_values['fields'][$key]['name'] = array( 'gateways', $nicename, $value );

			}

			$this->tab_sections[ $nicename ][ 'general_settings' ] = $form_values;
		}else{

			// Wrap values
			foreach ( $form_values as $s_key => $section ){
				foreach ( $section['fields'] as $key => $block ) {

					$value = $block['name'];
					$form_values[$s_key]['fields'][$key]['name'] = array( 'gateways', $nicename, $value );

				}
			}

			$this->tab_sections[ $nicename ] = $form_values;
		}

		// Only add a tab for gateways with a form
		$title = $gateway->display_name( 'admin' );
		if( $form_values ){
			$this->tabs->add( $nicename, $title );
			$title = html_link( add_query_arg( array(
				'page' => $this->args['page_slug'],
				'tab' => $nicename
			), 'admin.php' ), $title );
		}

		return array(
			'title' => $title,
			'type' => 'checkbox',
			'desc' => __( 'Enable', APP_TD ),
			'name' => array( 'gateways', 'enabled', $nicename ),
		);

	}

	public function before_rendering_field( $field ){

		if( 'currency_identifier' == $field['name'] || 'currency_position' == $field['name'] ){
			$currency = APP_Currencies::get_currency( APP_Gateway_Registry::get_options()->currency_code );
			foreach( $field['values'] as $key => $value ){
				$field['values'][$key] = str_replace( array( '{symbol}', '{code}' ), array( $currency['symbol'], $currency['code'] ), $value  );
			}
		}

		return $field;

	}
}

