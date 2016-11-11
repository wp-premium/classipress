<?php
/**
 * Keeps track of all registered gateways and their options
 *
 * @package Components\Payments\Gateways
 */
class APP_Gateway_Registry{

	/**
	 * Options object containing the Gateway's options
	 * @var scbOptions
	 */
	public static $options;

	/**
	 * Currently registered gateways
	 * @var array
	 */
	public static $gateways;

	/**
	 * Currently registered escrow gateways
	 * @var array
	 */
	public static $escrow_gateways;

	/**
	 * Registers a gateway by creating a new instance of it
	 * @param  string $class_name Class to create an instance of
	 * @return void
	 */
	public static function register_gateway( $class_name ){

		if( ! is_string( $class_name ) || ! class_exists( $class_name ) )
			trigger_error( 'Expecting existing class name in APP_Gateway_Registry::register_gateway', E_USER_WARNING );

		$instance = new $class_name;

		if ( ! ( $instance instanceof APP_Gateway ) && ! ( $instance instanceof APP_Instant_Payment_Processor ) && ! ( $instance instanceof APP_Payment_Gateway ) ) {
			trigger_error( 'Payment gateways must implement APP_Instant_Payment_Processor', E_USER_WARNING );
		}

		$identifier = $instance->identifier();

		self::$gateways[$identifier] = $instance;
		ksort( self::$gateways );

		if ( $instance instanceof APP_Escrow_Payment_Processor ) {
			self::$escrow_gateways[$identifier] = $instance;
			ksort( self::$escrow_gateways );
		}

	}

	/**
	 * Returns an instance of a registered gateway
	 * @param  string $gateway_id Identifier of a registered gateway
	 * @return mixed              Instance of the gateway, or false on error
	 */
	public static function get_gateway( $gateway_id ){

		if( ! is_string( $gateway_id ) )
			trigger_error( 'Gateway ID must be a string', E_USER_WARNING );

		if ( !self::is_gateway_registered( $gateway_id ) )
			return false;

		return self::$gateways[$gateway_id];

	}

	/**
	 * Returns an array of registered gateways
	 * @return array Registered gatewasys
	 */
	public static function get_gateways( $service = 'instant' ){

		$gateways = self::$gateways;

		foreach ( self::$gateways as $key => $gateway ) {
			if ( $service && ! $gateway->supports( $service ) ) {
				unset( $gateways[ $key ] );
			}
		}

		return $gateways;
	}


	/**
	 * Returns an array of registered gateways
	 * @return array Registered gatewasys
	 */
	public static function get_escrow_gateways() {
		return self::get_gateways( 'escrow' );
	}

	/**
	 * Checks if a given gateway is registered
	 * @param  string  $gateway_id Identifier for registered gateway
	 * @return boolean             True if the gateway is registered, false otherwise
	 */
	public static function is_gateway_registered( $gateway_id ){

		if( ! is_string( $gateway_id ) )
			trigger_error( 'Gateway ID must be a string', E_USER_WARNING );

		return isset( self::$gateways[ $gateway_id ] );

	}

	/**
	 * Returns an array of active gateways
	 * @return array Active gateways
	 */
	public static function get_active_gateways( $service = 'instant' ) {

		$gateways = array();
		foreach ( self::get_gateways( $service ) as $gateway ) {

			if ( !self::is_gateway_enabled( $gateway->identifier(), $service ) ) {
				continue;
			}

			$gateways[ $gateway->identifier() ] = $gateway;
		}
		return $gateways;
	}

	/**
	 * Checks if a given gateway is enabled
	 * @param  string  $gateway_id Identifier for registered gateway
	 * @param  string  $service Optional supported service to check
	 * @return boolean             True if the gateway is enabled, false otherwise
	 */
	public static function is_gateway_enabled( $gateway_id, $service = 'instant' ){

		if ( ! is_string( $gateway_id ) ) {
			trigger_error( 'Gateway ID must be a string', E_USER_WARNING );
		}

		if ( 'escrow' == $service && isset( self::$options->gateways[ $service ] ) ) {
			$options = self::$options->gateways[ $service ];
		} else {
			$options = self::$options->gateways;
		}

		if ( ! isset( $options['enabled'] ) ){
			$options['enabled'] = array();
		}

		$enabled_gateways = $options['enabled'];
		return isset( $enabled_gateways[$gateway_id] ) && $enabled_gateways[$gateway_id];

	}

	/**
	 * Registers an instance of scbOptions as the options handler
	 * Warning: Only use if you know what you're doing
	 *
	 * @param  scbOptions $options Instance of scbOptions
	 * @return void
	 */
	public static function register_options( $options ){

		self::$options = $options;

	}

	/**
	 * Returns the registered instance of the options handler
	 * @return scbOptions
	 */
	public static function get_options(){

		return self::$options;

	}

	/**
	 * Returns the options for the given registered gateway
	 * @param  string $gateway_id Identifier for registered gateway
	 * @return array              Associative array of options. See APP_Gateway::form()
	 */
	public static function get_gateway_options( $gateway_id ){

		if( ! is_string( $gateway_id ) )
			trigger_error( 'Gateway ID must be a string', E_USER_WARNING );

		if( ! self::is_gateway_registered( $gateway_id ) )
			return false;

		$defaults = self::get_gateway_defaults( $gateway_id );

		if( isset( self::$options->gateways[ $gateway_id ] ) )
			$options = self::$options->gateways[ $gateway_id ];
		else
			$options = array();

		return wp_parse_args( $options, $defaults );

	}

	private static function get_gateway_defaults( $gateway_id ){

		$form = self::get_gateway( $gateway_id )->form();
		$fields = self::get_fields( $form );

		$defaults = array();
		foreach( $fields as $field ){

			$name = $field['name'];
			$value = isset( $field['default'] ) ? $field['default'] : '';

			$defaults[$name] = $value;
		}

		return $defaults;

	}

	/**
	 * Plucks off field arrays. Flattens array with sections
	 * @param  array $gateway_form Array representing form
	 * @return array               All fields in the array
	 */
	private static function get_fields( $gateway_form ){

		if( isset( $gateway_form['fields'] ) ){
			$gateway_form = array( $gateway_form );
		}

		$fields = array();
		foreach( $gateway_form as $section ){
			if( isset( $section['fields'] ) && is_array( $section['fields'] ) ){
				foreach( $section['fields'] as $field ){
					$fields[] = $field;
				}
			}
		}
		return $fields;

	}

}