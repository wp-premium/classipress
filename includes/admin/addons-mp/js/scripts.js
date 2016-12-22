jQuery( document ).ready( function( $ ) {
	'use strict';

	$('.app-mp-addons-filter').on( 'change', function() {
		$('#app-addons-search').submit();
	});

	$('#current-page-selector').keypress( function(e) {
        if (event.keyCode == 13) {
            $('#plugin-filter').submit();
        }
    });

});

