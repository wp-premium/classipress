<?php
/**
 * Frontend escrow settings form.
 *
 * @package Components\Payments\Escrow
 */

class APP_Escrow_Settings_Form {

	protected $template;

	protected $fields;

	function __construct( $fields, $form_wrap = true, $args = array() ) {

		$defaults = array(
			'template' => dirname( __FILE__ ) . '/templates/manage-escrow.php',
		);
		$args = wp_parse_args( $args, $defaults );

		$this->fields = $fields;
		$this->template = $args['template'];

		$this->display( $form_wrap );
	}

	function display() {

		$template_path = appthemes_locate_template( basename( $this->template ) );

		// retrieve the user meta for each gateway field
		foreach( self::get_fields_names() as $field ) {
			$user_meta[ $field ] = get_user_option( $field );
		}

		// prepare the vars to pass to the escrow settings template
		$vars = array(
			'fields'	=> $this->fields,
			'formdata'  => $user_meta,
			'user'	    => wp_get_current_user(),
		);

		ob_start();

		if ( ! $template_path ) {

			extract( $vars );

			require $this->template;

		} else {
			appthemes_load_template( $template_path, $vars );
		}

		$output = ob_get_clean();

		$output .= html( 'input', array( 'type' => 'hidden', 'name' => 'action', 'value' => 'manage-escrow' ) );
		$output .= html( 'input', array( 'type' => 'submit', 'value' => esc_attr__( 'Save Changes', APP_TD ), 'class' => 'button' ) );

		$output = scbForms::form_wrap( $output, 'app-manage-escrow' );

		echo apply_filters( 'appthemes_escrow_settings_form', $output, $vars );
		do_action( 'appthemes_escrow_after_settings_form', $vars );
	}

	static function handle_form() {

		if ( empty( $_POST['action'] ) || 'manage-escrow' != $_POST['action'] ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'app-manage-escrow' ) ) {
			return;
		}

		$sanitized_user_meta = scbForms::validate_post_data( self::get_fields() );

		foreach( $sanitized_user_meta as $meta_key => $meta_value ) {
			update_user_option( get_current_user_id(), $meta_key, $meta_value );
		}

		appthemes_add_notice( 'saved-escrow-settings', __( 'Settings Saved.', APP_TD ) , 'success' );

	}

	static function get_fields( $gateway_id = '' ) {

		$gateways = APP_Gateway_Registry::get_active_gateways( 'escrow' );

		$fields = array();

		foreach( $gateways as $gateway ) {
			if ( $gateway_id && $gateway_id != $gateway->identifier() ) {
				continue;
			}
			$section = $gateway->user_form();
			$fields = array_merge( $fields, $section['fields'] );
		}
		return $fields;

	}

	static function get_fields_names( $gateway_id = '' ) {

		$fields = self::get_fields( $gateway_id );

		return wp_list_pluck( $fields, 'name' );
	}

}