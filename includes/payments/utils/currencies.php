<?php
/**
 * Currencies utils
 *
 * @package Components\Payments\Utils
 */

APP_Currencies::init();

/**
 * Helps define currencies, their display preferences, and
 * presenting price and currency information
 */
class APP_Currencies{

	/**
	 * Holds a list of all currently registered currencies
	 * @var array
	 */
	private static $currencies;

	/**
	 * Initializes the registered currency list.
	 * Allows for localization of currency names.
	 */
	public static function init(){
		self::$currencies = array(
			'USD' => array(
				'symbol' => '&#36;',
				'name' => __( 'US Dollars', APP_TD ),
			),
			'EUR' => array(
				'symbol' => '&euro;',
				'name' => __( 'Euros', APP_TD ),
			),
			'GBP' => array(
				'symbol' => '&pound;',
				'name' => __( 'Pounds Sterling', APP_TD ),
			),
			'AUD' => array(
				'symbol' => '&#36;',
				'name' => __( 'Australian Dollars', APP_TD ),
			),
			'BRL' => array(
				'symbol' => 'R&#36;',
				'name' => __( 'Brazilian Real', APP_TD ),
			),
			'CAD' => array(
				'symbol' => '&#36;',
				'name' => __( 'Canadian Dollars', APP_TD ),
			),
			'CZK' => array(
				'symbol' => 'K&#269;',
				'name' => __( 'Czech Koruna', APP_TD ),
			),
			'DKK' => array(
				'symbol' => 'kr',
				'name' => __( 'Danish Krone', APP_TD ),
			),
			'HKD' => array(
				'symbol' => '&#36;',
				'name' => __( 'Hong Kong Dollar', APP_TD ),
			),
			'HUF' => array(
				'symbol' => 'Ft',
				'name' => __( 'Hungarian Forint', APP_TD ),
			),
			'ILS' => array(
				'symbol' => '&#8362;',
				'name' => __( 'Israeli Shekel', APP_TD ),
			),
			'JPY' => array(
				'symbol' => '&yen;',
				'name' => __( 'Japanese Yen', APP_TD ),
			),
			'MYR' => array(
				'symbol' => 'RM',
				'name' => __( 'Malaysian Ringgits', APP_TD ),
			),
			'MXN' => array(
				'symbol' => '&#36;',
				'name' => __( 'Mexican Peso', APP_TD ),
			),
			'NZD' => array(
				'symbol' => '&#36;',
				'name' => __( 'New Zealand Dollar', APP_TD ),
			),
			'NOK' => array(
				'symbol' => 'kr',
				'name' => __( 'Norwegian Krone', APP_TD ),
			),
			'PHP' => array(
				'symbol' => 'P',
				'name' => __( 'Philippine Pesos', APP_TD ),
			),
			'PLN' => array(
				'symbol' => 'z&#322;',
				'name' => __( 'Polish Zloty', APP_TD ),
			),
			'RUB' => array(
				'symbol' => '&#1088;&#1091;&#1073;',
				'name' => __( 'Russian Ruble', APP_TD ),
			),
			'SGD' => array(
				'symbol' => '&#36;',
				'name' => __( 'Singapore Dollar', APP_TD ),
			),
			'SEK' => array(
				'symbol' => 'kr',
				'name' => __( 'Swedish Krona', APP_TD ),
			),
			'CHF' => array(
				'symbol' => 'Fr',
				'name' => __( 'Swiss Franc', APP_TD ),
			),
			'TWD' => array(
				'symbol' => '&#36;',
				'name' => __( 'Taiwan New Dollar', APP_TD ),
			),
			'THB' => array(
				'symbol' => '&#3647;',
				'name' => __( 'Thai Baht', APP_TD ),
			),
			'TRY' => array(
				'symbol' => '&#8356;',
				'name' => __( 'Turkish Lira', APP_TD ),
			),
		);

	}

	/**
	 * Adds a currency to the list of registered currencies if the currency does not
	 * already exist.
	 * Allows plugins and themes to define their own custom currencies.
	 *
	 * @param string $currency_code Currency code used to identify currency.
	 * @param array $args Array of options related to this currency
	 * 	'symbol' => The symbol denoting the type of currency. IE: $ for USD
	 * 	'name' => The formal name of the currency
	 * 	'display' => The formatting for displaying the currency as a price.
	 * 		Will automatically replace strings '{symbol}' and '{price}'
	 * @return true on success, false on failure
	 */
	public static function add_currency( $currency_code, $args = array() ){

		$currency_code = strtoupper( $currency_code );
		if( isset( self::$currencies[ $currency_code ] ) )
			return false;

		self::update_currency( $currency_code, $args );
		return true;
	}

	public static function update_currency( $currency_code, $args = array() ){

		$currency_code = strtoupper( $currency_code );

		$defaults = array(
			'symbol' => $currency_code,
			'name' => $currency_code,
		);
		$args = wp_parse_args( $args, $defaults );

		self::$currencies[ $currency_code ] = $args;

	}

	/**
	 * Returns a currency's array of information, or a part of the array
	 * @param  string $currency_code Currency code used to identify currency
	 * @param  string $part          (optional) Part of array to return
	 * @return array|string          Full array of currency information or
	 * 									part of the array specified
	 */
	public static function get_currency( $currency_code, $part = '' ){

		if( isset( self::$currencies[ $currency_code ] ) ){
			$currency = self::$currencies[ $currency_code ];
		}else{
			return false;
		}

		$currency = array_merge( $currency, array(
			'code' => $currency_code
		));

		if( empty( $part ) ){
			return $currency;
		}else{
			return $currency[ $part ];
		}

	}

	/**
	 * Returns an array of currently registered currencies
	 * @return array
	 */
	public static function get_currencies(){

		return self::$currencies;

	}

	/**
	 * Returns a currency's formal name
	 * @param  string $currency_code Currency code used to identify currency
	 * @return string                Currency's formal name
	 */
	public static function get_name( $currency_code ){
		return self::get_currency( $currency_code, 'name' );
	}

	/**
	 * Returns a currency's denotation symbol
	 * @param  string $currency_code Currency code used to identify currency
	 * @return string                Currency's denotation symbol
	 */
	public static function get_symbol( $currency_code ){
		return self::get_currency( $currency_code, 'symbol' );
	}

	/**
	 * Returns a currency's formatting string.
	 * @param  string $currency_code Currency code used to identify currency
	 * @return string                Currency's formatting string. See add_currency()
	 */
	public static function get_display(){
		_deprecated_function( __FUNCTION__, '3/19/13', 'none' );
		return '{symbol}{price}';
	}

	/**
	 * Returns information array about the current currency, as specified by the
	 * current theme, or part of the array if specified
	 * @param  string $part (optional) Part of the array
	 * @return array|string Full array of currency information or
	 * 							part of the array is specified.
	 */
	public static function get_current_currency( $part = '' ){
		$args = appthemes_price_format_get_args();
		return self::get_currency( $args['currency_default'], $part );
	}

	/**
	 * Returns the formal name of the current currency. See get_current_currency()
	 * @return string Formal name of current currency
	 */
	public static function get_current_name(){
		return self::get_current_currency( 'name' );
	}

	/**
	 * Returns the denotation symbol of the current currency. See get_current_currency()
	 * @return string Current currency's denotation symbol
	 */
	public static function get_current_symbol(){
		return self::get_current_currency( 'symbol' );
	}

	/**
	 * Returns the formatting string for the current currency.
	 * See get_current_currency() and add_currency()
	 * @return string Current currency's formatting string
	 */
	public static function get_current_display(){
		_deprecated_function( __FUNCTION__, '3/19/13', 'none' );
		return '{symbol}{price}';
	}

	/**
	 * Returns a formatted string of a currency for display purposes.
	 * Features the name of the currency with the symbol in parenthesis.
	 * @param  string $currency_code Currency code used to identify currency
	 * @return string                Formatted string
	 */
	public static function get_currency_string( $currency_code ){

		extract( self::get_currency( $currency_code ) );
		return $name . ' (' . $symbol . ')';

	}

	/**
	 * Returns an array of formatted strings for all registered currencies.
	 * See get_currency_string()
	 * @return array Associate Array of formatted strings, with currency codes as keys
	 */
	public static function get_currency_string_array(){

		$result = array();
		foreach( self::$currencies as $key => $currency ){
			$result[ $key ] = $currency['name'] . ' (' . $currency['symbol'] . ')';
		}
		return $result;

	}

	/**
	 * Formats a price according to the formatting string given to a currency
	 * See add_currency()
	 * @param  string $number        Amount of currency to be displayed
	 * @param  string $currency_code Currency code used to identify currency
	 * @return string                Formatted price
	 */
	public static function get_price( $number, $currency_code = '', $identifier = '' ){

		$args = appthemes_price_format_get_args();

		if( empty( $currency_code ) )
			$currency_code = $args['currency_default'];

		if( empty( $identifier ) )
			$identifier = $args['currency_identifier'];

		extract( self::get_currency( $currency_code ) );

		if( $identifier == 'symbol' ){

			return $symbol . $number;

		}else{

			return $number . ' ' . $code;

		}

	}

	/**
	 * Checks if the given currency code is registered
	 * @param  string  $currency Currency code to check
	 * @return boolean           True if regsitered, false if not.
	 */
	public static function is_valid( $currency_code ){

	    if( isset( self::$currencies[ $currency_code] )){
			return true;
	    }else{
			return false;
	    }

	}

}
