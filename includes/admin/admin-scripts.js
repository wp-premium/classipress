/**
 * Admin jQuery functions
 * Written by AppThemes
 *
 * http://www.appthemes.com
 *
 * Built for use with the jQuery library
 *
 *
 */


jQuery(document).ready(function($) {

	/* initialize the tooltip feature */
	$('.widefat .tip-icon').on('click', function(ev) {
		var tip_row = $(this).closest('tr');

		var tip_show = tip_row.next('.tip-show');

		if ( tip_show.length ) {
			tip_show.remove();
		} else {
			tip_show = $('<tr class="tip-show">').html(
				$('<td colspan="3">').html( tip_row.find('.tip-content').html() )
			);

			tip_row.after( tip_show );
		}
	});

	/* admin option pages tabs */
	$("div#tabs-wrap").tabs( {
		fx: {opacity: 'toggle', duration: 200},
		show: function() {
			$('div#tabs-wrap').tabs('option', 'selected');
		}
	});

	/* strip out all the auto classes since they create a conflict with the calendar */
	$('#tabs-wrap').removeClass('ui-tabs ui-widget ui-widget-content ui-corner-all');
	$('ul.ui-tabs-nav').removeClass('ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all');
	$('div#tabs-wrap div').removeClass('ui-tabs-panel ui-widget-content ui-corner-bottom');

	/* clear text field, hide image preview */
	$(".delete_button").click(function(el) {
		var id = $(this).attr("rel");
		$("#" + id).val("");
		$("#" + id + "_image img").hide();
	});

	/* check all categories button */
	$('#form-categorydiv a.checkall').toggle(
		function(){
			$('#categorychecklist input:checkbox').prop('checked', true);
			$(this).html(classipress_admin_params.text_uncheck_all);
			return false;
		},
		function(){
			$('#categorychecklist input:checkbox').prop('checked', false);
			$(this).html(classipress_admin_params.text_check_all);
			return false;
		}
	);


});


/* Used for deleting theme database tables */
function cp_confirmBeforeDeleteTables() {
	return confirm(classipress_admin_params.text_before_delete_tables);
}


/* Used for deleting theme options */
function cp_confirmBeforeDeleteOptions() {
	return confirm(classipress_admin_params.text_before_delete_options);
}


