<?php
/**
 * Numbers utils
 *
 * @package Components\Payments\Utils
 */

/**
 * Prorates a given length and price to a new length
 * @param  float $original_price  The original price
 * @param  int $original_length   The original length
 * @param  int $new_length        The new length
 * @return int                    The price prorated for the new length
 */
function appthemes_prorate( $original_price, $original_length, $new_length ){

	if( ! is_numeric( $original_price ) )
		trigger_error( 'Original Price must be numeric', E_USER_WARNING );

	if( ! is_numeric( $original_length ) || ! is_numeric( $new_length ) )
		trigger_error( 'Original Length and New Length must be numeric', E_USER_WARNING );

	$original_length = absint( $original_length );
	$new_length = absint( $new_length );

	$price_per_day = $original_price / $original_length;
	$new_price = $price_per_day * $new_length;

	return number_format( $new_price, 2 );

}

/**
 * Displays a formatted price. See appthemes_get_price
 *
 * @param  int $price                The numerical value to format
 * @param  string $override_currency The currency the value is in (defaults to 'default_currency')
 * @return string                    The formatted price
 */
function appthemes_display_price( $price, $override_currency = '', $override_format = '' ){

	echo appthemes_get_price( $price, $override_currency, $override_format  );

}

/**
 * Returns the price given the arguments in add_theme_support for 'app-price-format'
 * Note: if hide_decimals is turned on, the amount will be rounded.
 *
 * @param  int $price                The numerical value to format
 * @param  string $override_currency The currency the value is in (defaults to 'currency_default')
 * @param  string $override_format   The format the value is in (defaults to 'currency_identifier')
 * @return string                    The formatted price
 */
function appthemes_get_price( $price, $override_currency = '', $override_identifier = '' ){

	$format_args = appthemes_price_format_get_args();
	$decimals = ( $format_args['hide_decimals'] ) ? 0 : 2;

	$base_price = number_format( $price, $decimals, $format_args['decimal_separator'], $format_args['thousands_separator'] );

	$currency_code = ( empty( $override_currency ) ) ? $format_args['currency_default'] : $override_currency;

	$currency_identifier = ( empty( $override_identifier ) ) ? $format_args['currency_identifier'] : $override_identifier;

	$position = $format_args['currency_position'];
	$identifier = APP_Currencies::get_currency( $currency_code, $currency_identifier );

	return _appthemes_format_display_price( $base_price, $identifier, $position );
}

function _appthemes_format_display_price( $price, $identifier, $position = 'left' ){

	$formats = array(
		'left' => '{symbol}{price}',
		'left_space' => '{symbol} {price}',
		'right' => '{price}{symbol}',
		'right_space' => '{price} {symbol}'
	);

	$search = array( '{price}', '{symbol}' );
	$replace = array( $price, $identifier );
	return str_replace( $search, $replace, $formats[ $position ] );
}

function appthemes_display_mixed_price( $mixed_prices ){

	$strings = array();
	foreach( $mixed_prices as $currency => $amount ){
		$strings[] = appthemes_get_price( $amount, $currency, 'code' );
	}

	echo join( '</br> ', $strings );

}
