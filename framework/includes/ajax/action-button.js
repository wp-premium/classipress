/**
 * Basic ajax action buttons handler
 *
 * @requires jquery.js
 * @author   AppThemes
 * @version  1.0
 */

(function( $ ) {
	/**
	 * Basic plugin to handle any ajax buttons
	 *
	 * @param {object} options Plugin settings
	 */
	$.fn.appThemesActionButton = function( options ) {

		/**
		 * Store initial selector in this variable to been able to bind handler
		 * to newly created elements
		 * @type @this;@pro;selector
		 */
		var currSelector = this.selector;

		/**
		 * Rebinds handler to given  jQuery element collection.
		 *
		 * Using event namespace prevents duplicate event triggering and
		 * preserves external events for given elements
		 *
		 * @param {object} el jQuery element to bind handler to
		 */
		function rebind( el ) {
			el.off( 'click.appThemesActionButton' )
				.on( 'click.appThemesActionButton', settings.clickHandler );
		}

		/**
		 * Overridable properties and methods
		 *
		 * @type @exp;$@call;extend
		 */
		var settings = $.extend({

			action            : false,
			action_var        : false,
			currSelector      : currSelector,
			rebind            : function( element ) { rebind( element ); },
			spinnerProcessing : function( element ) {},
			showNotice        : function( notice ) {},
			askConfirm        : function() { return true; },

			clickHandler : function( eventObj ){

				eventObj.preventDefault();
				var element = $(this);

				if ( settings.askConfirm() ) {
					settings.spinnerProcessing( element );
					$.post(
						AppThemes.ajaxurl,
						settings.getPostData( element ),
						function( data ){
							settings.ajaxSuccess( data, element );
						},
						"json"
					);
				}

				return false;
			},


			getPostData : function( element ) {

				var data = element.data();

				var postdata = {
					action      : settings.action,
					current_url : AppThemes.current_url
				};

				$.extend( postdata, data );

				return postdata;
			},

			ajaxSuccess : function( data, element ){

				settings.showNotice( data.notice );

				if ( data.html ) {
					element.replaceWith( data.html );
					settings.rebind( $( settings.currSelector ) );
				}

				if( data.redirect ) {
					return;
				}
			}

		}, options );

		settings.rebind( this );
	};
}( jQuery ));

jQuery(document).ready(function( $ ) {

	/**
	 * Handle Favorite buttons
	 *
	 * @param {object} options Plugin settings
	 */
	$.fn.appThemesFavoriteButton = function ( options ) {

		var settings = $.extend( {}, $.fn.appThemesActionButton.prototype, {
			action     : 'appthemes_favorites',
			action_var : 'favorite'
		}, options );

		this.appThemesActionButton( settings );
	};

});

jQuery(document).ready(function( $ ) {

	/**
	 * Handle Delete Listing buttons
	 *
	 * @param {object} options Plugin settings
	 */
	$.fn.appThemesDeleteButton = function ( options ) {
		var settings = $.extend( {}, $.fn.appThemesActionButton.prototype, {
			action     : 'appthemes_delete_post',
			action_var : 'delete',
			remove     : function( element ) { element.remove(); },

			ajaxSuccess : function( data, element ){
				this.showNotice( data.notice );
				this.remove( element );
			}
		}, options );
		this.appThemesActionButton( settings );
	};
});
