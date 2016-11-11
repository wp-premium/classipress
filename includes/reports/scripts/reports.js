jQuery(document).ready(function($) {
	jQuery(".reports_form input[type=submit]").on( "click", function() {

		var form_div = jQuery(this).closest(".reports_form");
		var message_div = form_div.prev(".reports_message");
		var link_div = form_div.parent().parent().find(".reports_form_link");

		jQuery.ajax({
			type: 'POST',
			url: app_reports.ajax_url,
			dataType: "json",
			data: {
				action : "appthemes-add-report",
				report : form_div.find("select[name=report]").val(),
				type : form_div.find("input[name=type]").val(),
				id : form_div.find("input[name=id]").val(),
				nonce : form_div.find("input[name=nonce]").val()
			},
			beforeSend: function() {
				form_div.hide();
				link_div.hide();
				message_div.fadeIn(200);
			},
			error: function( XMLHttpRequest, textStatus, errorThrown ) {
				alert( 'Error: ' + errorThrown + ' - ' + textStatus + ' - ' + XMLHttpRequest );
			},
			success: function( data ) {
				message_div.html( data.message ).delay(2000).fadeOut('slow');
			}
		});

		return false;
	});
});

