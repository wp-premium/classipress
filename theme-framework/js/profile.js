jQuery( document ).ready( function( $ ){
	if ( $('.wp-pwd').is( ':hidden' ) ) {
		$('#pass1').prop( 'disabled', true );
		$('#pass2').prop( 'disabled', true );
		$('#pass1-text').prop( 'disabled', true );
	}
} );