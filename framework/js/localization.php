<?php
/**
 * Translates javascripts
 *
 * @package Framework\Localize-JS
 */
function _appthemes_localize_scripts() {
	global $wp_locale;

	// jQuery Validate
	wp_register_script( 'validate-lang', APP_FRAMEWORK_URI . '/js/validate/jquery.validate-lang.js', array( 'validate' ) );
	wp_localize_script( 'validate-lang', 'validateL10n', array(
		'required' =>    __( 'This field is required.', APP_TD ),
		'remote' =>      __( 'Please fix this field.', APP_TD ),
		'email' =>       __( 'Please enter a valid email address.', APP_TD ),
		'url' =>         __( 'Please enter a valid URL.', APP_TD ),
		'date' =>        __( 'Please enter a valid date.', APP_TD ),
		'dateISO' =>     __( 'Please enter a valid date (ISO).', APP_TD ),
		'number' =>      __( 'Please enter a valid number.', APP_TD ),
		'digits' =>      __( 'Please enter only digits.', APP_TD ),
		'creditcard' =>  __( 'Please enter a valid credit card number.', APP_TD ),
		'equalTo' =>     __( 'Please enter the same value again.', APP_TD ),
		'maxlength' =>   __( 'Please enter no more than {0} characters.', APP_TD ),
		'minlength' =>   __( 'Please enter at least {0} characters.', APP_TD ),
		'rangelength' => __( 'Please enter a value between {0} and {1} characters long.', APP_TD ),
		'range' =>       __( 'Please enter a value between {0} and {1}.', APP_TD ),
		'max' =>         __( 'Please enter a value less than or equal to {0}.', APP_TD ),
		'min' =>         __( 'Please enter a value greater than or equal to {0}.', APP_TD ),
	) );

	// jQuery UI Datepicker
	wp_register_script( 'jquery-ui-datepicker-lang', APP_FRAMEWORK_URI . '/js/jquery-ui/jquery.ui.datepicker-lang.js', array( 'jquery-ui-datepicker' ) );
	wp_localize_script( 'jquery-ui-datepicker-lang', 'datepickerL10n', array(
		'isRTL' =>       is_rtl(),
		'firstDay' =>    get_option( 'start_of_week' ),
		'dateFormat' =>  'yy-mm-dd',
		'closeText' =>   __( 'Done', APP_TD ),
		'prevText' =>    __( 'Prev', APP_TD ),
		'nextText' =>    __( 'Next', APP_TD ),
		'currentText' => __( 'Today', APP_TD ),
		'weekHeader' =>  __( 'Wk', APP_TD ),
		'monthNames' =>      array_values( $wp_locale->month ),
		'monthNamesShort' => array_values( $wp_locale->month_abbrev ),
		'dayNames' =>        array_values( $wp_locale->weekday ),
		'dayNamesShort' =>   array_values( $wp_locale->weekday_abbrev ),
		'dayNamesMin' => array(
			_x( 'Su', 'two-letter abbreviation of the weekday', APP_TD ),
			_x( 'Mo', 'two-letter abbreviation of the weekday', APP_TD ),
			_x( 'Tu', 'two-letter abbreviation of the weekday', APP_TD ),
			_x( 'We', 'two-letter abbreviation of the weekday', APP_TD ),
			_x( 'Th', 'two-letter abbreviation of the weekday', APP_TD ),
			_x( 'Fr', 'two-letter abbreviation of the weekday', APP_TD ),
			_x( 'Sa', 'two-letter abbreviation of the weekday', APP_TD ),
		),
	) );

}
