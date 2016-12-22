<?php
/**
 * Form Progress registry class
 *
 * @package Components\Checkouts\Progress-Form
 */
class APP_Form_Progress_Checkout_Registry{

	/**
	 * The checkout types list
	 * @var array
	 */
	protected static $checkout_types = array();

	/**
	 * The excluded steps list
	 * @var array
	 */
	protected static $exclude_steps = array();

	/**
	 * Registers valid checkout types
	 * @param int $id The checkout type
	 * @param array $params Additional parameters:
	 *	- $steps array		Valid steps list
	 *	- $exclude array	Steps to be excluded
	 */
	public static function register( $id, $params = array() ) {

		if ( ! is_string( $id ) )
			trigger_error( 'Item ID must be a string', E_USER_WARNING );

		$defaults = array(
			'steps' => array(),
			'exclude' => array(),
		);
		$params = wp_parse_args( $params, $defaults );

		extract( $params );

		// init checkout type
		self::$checkout_types[ $id ] = array();

		// store excluded steps
		self::$exclude_steps[ $id ] = $exclude;

		// register checkout steps
		self::register_steps( $id, $steps );
	}

	/**
	 * Register the steps to a specific checkout type
	 * @param int $id The checkout type
	 * @param array $steps The steps list
	 * @return void|boolean Returns FALSE if current checkout was not already registered
	 */
	public static function register_steps( $id, $steps ){

		if ( ! is_string( $id ) )
			trigger_error( 'Item ID must be a string', E_USER_WARNING );

		if ( ! self::is_registered( $id ) )
			return false;

		self::$checkout_types[ $id ] = array(
			'steps' => self::filter_steps( $id, $steps ),
		);
	}

	/**
	 * Checks if a checkout type is already registered
	 * @param int $id The checkout type
	 * @return boolean TRUE if already registered, or FALSE otherwise
	 */
	public static function is_registered( $id ){
		return isset( self::$checkout_types[ $id ] );
	}

	/**
	 * Retrieves the steps for a specific checkout type
	 * @param int $id The checkout type
	 * @return boolean|array The steps list, or FALSE if the checkout type is not registered
	 */
	public static function steps( $id ){
		if ( ! self::is_registered( $id ) )
			return false;

		return self::$checkout_types[ $id ]['steps'];
	}

	/**
	 * Retrieves the filtered list of steps
	 * @param int $id The checkout type
	 * @param array $steps The steps list
	 * @return array The filtered steps list
	 */
	protected static function filter_steps( $id, $steps ) {

		$exclude = array();

		if ( isset( self::$exclude_steps[ $id ] ) )
			$exclude = self::$exclude_steps[ $id ];

		$f_steps = array();
		foreach( $steps as $key => $step ) {

			if ( in_array( $key, $exclude ) )
				continue;

			$defaults = array(
				'title' => $key,
			);
			$f_steps[ $key ] = wp_parse_args( $step, $defaults );
		}
		return $f_steps;
	}
}
