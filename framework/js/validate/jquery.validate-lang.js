/*
 * Translate default messages for the jQuery Validation plugin.
 */
jQuery.extend(jQuery.validator.messages, {
	required: validateL10n.required,
	remote: validateL10n.remote,
	email: validateL10n.email,
	url: validateL10n.url,
	date: validateL10n.date,
	dateISO: validateL10n.dateISO,
	number: validateL10n.number,
	digits: validateL10n.digits,
	creditcard: validateL10n.creditcard,
	equalTo: validateL10n.equalTo,
	maxlength: jQuery.validator.format(validateL10n.maxlength),
	minlength: jQuery.validator.format(validateL10n.minlength),
	rangelength: jQuery.validator.format(validateL10n.rangelength),
	range: jQuery.validator.format(validateL10n.range),
	max: jQuery.validator.format(validateL10n.max),
	min: jQuery.validator.format(validateL10n.min)
});
