/*
 * Translate default messages for the jQuery UI Datepicker plugin.
 */
jQuery(document).ready(function($) {
	$.extend(true, $.datepicker.regional[""], datepickerL10n);
	$.datepicker.setDefaults( $.datepicker.regional[""] );
});