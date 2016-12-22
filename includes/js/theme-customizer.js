( function($) {

	// update colors for the currently selected color scheme
	update_customizer_colors( customizer_params['color_scheme'] );

	// Update the color defaults in real time...
	wp.customize( 'cp_options[stylesheet]', function( value ) {
		value.bind( function( new_color_scheme ) {
			update_customizer_colors( new_color_scheme );
		} );
	} );

	function update_customizer_colors( color_scheme ) {

		var settings = new Array(
			'cp_bgcolor',
			'cp_top_nav_bgcolor',
			'cp_top_nav_links_color',
			'cp_top_nav_text_color',
			'cp_header_bgcolor',
			'cp_main_nav_bgcolor',
			'cp_buttons_bgcolor',
			'cp_buttons_text_color',
			'cp_links_color',
			'cp_footer_bgcolor',
			'cp_footer_text_color',
			'cp_footer_links_color',
			'cp_footer_titles_color'
		);

		for ( i = 0; i < settings.length; i++ ) {

			var val = customizer_params['colors'][ color_scheme ];

			if ( typeof val === 'undefined' ) {
				return;
			}

			val = val[ settings[i] ];

			$('#customize-control-' + settings[i] + ' .wp-color-result').css( 'background-color', val );
			$('#customize-control-' + settings[i] + ' .color-picker-hex.wp-color-picker').val( val );
			$('#customize-control-' + settings[i] + ' .color-picker-hex.wp-color-picker').attr( 'data-default-color', val ).trigger('change');
		}

	}

} )( jQuery );
