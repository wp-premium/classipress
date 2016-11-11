jQuery(document).ready(function($) {

	/* Frontend Media Manager */

	var file_frame;
	var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id

	// check for an input containing the current post ID if one is passed
	if ( ( app_uploader_i18n.post_id_field != '' ) ) {
		// retrieve the post ID from an input
		var post_id = $('input[name='+app_uploader_i18n.post_id_field+']').val();
	} else {
		// retrieve the post ID from parameter
		var post_id = app_uploader_i18n.post_id;
	}

	post_id = parseInt( post_id );

	$(document).on( 'click', '.upload_button', function( event ) {

		event.preventDefault();

		// each media manager field on the same form is grouped and assigned a unique group ID
		var current_group_id = $(this).attr('group_id');
		var current_group_id_embeds = current_group_id+'_embeds';
		var media_placeholder = $( '#'+current_group_id+' .media_placeholder' );
		var nonce = $('#app_mm_nonce_'+current_group_id).val();

		if ( undefined === $('input[name='+current_group_id+']').html() ) {
			return;
		}

		// init with default WP limits

		var mime_types = '';
		var file_types = '';
		var meta_type = '';
		var file_size = '';
		var file_limit = -1;
		var embed_limit = -1;

		// dynamically retrieve the filter options for the current media manager
		$.ajax({
			type: 'POST',
			url: app_uploader_i18n.ajaxurl,
			data: {
				action: 'app_get_media_manager_options',
				mm_id: current_group_id,
				mm_nonce: nonce,
			},
			async: false,
			dataType: 'json'
		}).done( function(response) {

			if ( '-1' != response ) {

				var filters = response.filters;

				mime_types = filters.mime_types;
				file_types = filters.file_types;
				meta_type = filters.meta_type;
				file_limit = filters.file_limit;
				embed_limit = filters.embed_limit;
				file_size = filters.file_size;

			}

		} );


		// Set the wp.media post id so the uploader grabs the ID we want when initialised
		// Skip it if the 'post_id' is empty
		if ( post_id ) {
			wp.media.model.settings.post.id = post_id;
		}

		// Create the media frame.
		file_frame = wp.media.frames.file_frame = wp.media({
			title: $(this).data('uploader_title'),
			button: {
			  text: $(this).data('uploader_button_text'),
			},
			library: {
				post_mime_type: mime_types,
			},
			frame: 'post',
			state: ( file_limit != 0 ? 'app-upload-media' : 'app-embed-media' ),
			multiple: true,  // allow multiple files to be selected
		});

		// disable default view states
		file_frame.states.remove('embed');
		file_frame.states.remove('gallery');
		file_frame.states.remove('gallery-edit');
		file_frame.states.remove('gallery-library');

		// create custom states for the add and embed Views

		if ( file_limit != 0 ) {

			file_frame.states.add([

				new wp.media.controller.Library({
					id:         'app-upload-media',
					title:		'APP-ITEM ' + app_uploader_i18n.insert_media_title,
					toolbar:    'main-insert',
					filterable: false,
					searchable: true,
					library:    wp.media.query( file_frame.options.library ),
					multiple:   file_frame.options.multiple ? 'reset' : false,
					editable:   true,
					displayUserSettings: false,
					displaySettings: false,
					allowLocalEdits: true,
				}),

			]);

		}

		if ( embed_limit != 0 ) {

			file_frame.states.add([

				// Embed states.
				new wp.media.controller.Embed({
					id: 'app-embed-media',
					toolbar: 'main-embed',
					title: 'APP-ITEM ' + app_uploader_i18n.embed_media_title,
				}),

			]);

		}

		// params to be passed to the WP ajax uploader request
		wp.media.frames.file_frame.uploader.options.uploader['params']['app_media_manager'] = true;
		wp.media.frames.file_frame.uploader.options.uploader['params']['app_mm_id'] = current_group_id;
		wp.media.frames.file_frame.uploader.options.uploader['params']['app_mime_types'] = mime_types;
		wp.media.frames.file_frame.uploader.options.uploader['params']['app_file_limit'] = file_limit;
		wp.media.frames.file_frame.uploader.options.uploader['params']['app_file_size'] = file_size;

		// when the media view is closed, process the selected attachments or embeds
		file_frame.on( 'close', function() {

			// retrieve all the selected files
			var attachments = file_frame.state().get('selection');
			var embed_urls = '';

			// if no file were attached look for embeds
			if ( undefined === attachments ) {
				embed_urls = file_frame.state().props.get('url');

				// maybe clear all existing embeds if requested by user
				if ( $('.clear-embeds').is(':checked') && ! embed_urls ) {
					embed_urls = 'clear';
				}

			} else {

				var gallery_ids = new Array();
				var index = 0;

				// enqueue the attachments
				attachments.each( function( attachment ) {

					if ( attachment['id'] && ( ( index >= 0 && index < file_limit ) || file_limit < 0 ) ) {
						gallery_ids[index] = attachment['id'];
						index++;
					}
				});

				attachments = gallery_ids;
			}

			if ( attachments || embed_urls ) {

				var data = {
					action: 'app_manage_files',
					mm_nonce: nonce,
					mm_id: current_group_id,
					post_id: post_id,
					attachments: gallery_ids,
					embed_urls: embed_urls,
					meta_type: meta_type,
					file_limit: file_limit,
					mime_types: mime_types,
				};

				// dynamically create the list of selected images and display them in the form
				$.post( app_uploader_i18n.ajaxurl, data, function(response) {

					var upload_button = $( 'input[group_id='+current_group_id+'].upload_button' );
					var button_text = $( upload_button ).attr('upload_text');

					var has_content_attach = Boolean( $('input[name='+current_group_id+']').val() );
					var has_content_embed = Boolean( $('input[name='+current_group_id_embeds+']').val() );

					var output = '';

					if ( response ) {
						output = response.output;
					}

					// manage attachments if user is returning from the gallery view
					// if 'attachments' is empty (not 'undefined'), it's because the user un-selected the attachments
					if ( undefined != attachments ) {

						// check if the user selected any attachments
						if ( attachments.length == 0 ) {
							has_content_attach = false;
						} else {
							has_content_attach = true;
						}

						// output the attachments HTML or clear them if requested by the user
						$( '.media-attachments', media_placeholder ).html( output );

						// store the attachments on a hidden input
						$('input[name='+current_group_id+']').val( attachments );

					} else {

						// manage embeds if user is returning from the embed view and added an URL
						if ( embed_urls && 'clear' != embed_urls ) {

							var curr_embeds = $('input[name='+current_group_id_embeds+']').val();

							if ( curr_embeds ) {
								// clear any previous embeds if requested by the user ('Clear Embeds' is checked)
								if ( ! $('.clear-embeds').is(':checked') ) {
									curr_embeds = curr_embeds.split(',');
								} else {
									curr_embeds = '';
								}
							}

							// append or add new embeds while the embed limit is not reached
							if ( embed_limit < 0 || ( embed_limit && curr_embeds.length < embed_limit ) ) {

								if ( curr_embeds.length > 0 ) {
									embed_urls = $('input[name='+current_group_id_embeds+']').val() + ', ' + response.url;
									output = $( '.media-embeds', media_placeholder ).html() + '<br/>' + output;
								}

								// output all the embeded URL's
								$( '.media-embeds', media_placeholder ).html( output );

								// store the embeds on a hidden input
								$('input[name='+current_group_id_embeds+']').val( embed_urls );

								has_content_embed = true;

							} else {

								alert( app_uploader_i18n.allowed_embeds_reached_text );
							}

						} else {

							// maybe clear embeds content if user requested it
							if ( ! $('input[name='+current_group_id_embeds+']').val() || 'clear' == embed_urls ) {
								$( '.media-embeds', media_placeholder ).html('');
								$('input[name='+current_group_id_embeds+']').val('');
								has_content_embed = false;
							}
						}

					}

					// update the content and the buttons context
					if ( has_content_attach || has_content_embed ) {
						$( '.no-media',media_placeholder ).hide();
						button_text = $( upload_button ).attr('manage_text');
					} else {
						$( '.no-media',media_placeholder ).show();
					}

					$( upload_button ).val( button_text );

				}, "json" );

			}

			// restore the main post ID
			wp.media.model.settings.post.id = wp_media_post_id;

			file_frame = '';
		});

		// pre-select attachments and remove sidebar items
		file_frame.on( 'open', function() {

			// clear
			$('.app-media-settings').remove();

			// mark custom menu items by searching for the APP-ITEM flag
			$('.media-frame-menu .media-menu-item:contains("APP-ITEM '+app_uploader_i18n.insert_media_title+'")').addClass('app-item');
			$('.media-frame-menu .media-menu-item:contains("APP-ITEM '+app_uploader_i18n.embed_media_title+'")').addClass('app-item');

			// remove any default non APP-ITEM menu items
			$('.media-frame-menu .media-menu-item:not(.app-item)').remove();

			// pre-select attached files when the media manager is displayed
			var selection = file_frame.state().get('selection');

			 if ( file_limit != 0 && undefined != $('input[name='+current_group_id+']').val() ) {

				var ids = $('input[name='+current_group_id+']').val().trim();

				if ( '' !== ids ) {

					ids = ids.split(',');

					ids.forEach( function(id) {
					  	attachment = wp.media.attachment(id);
					  	attachment.fetch();
				  		selection.add( attachment ? [ attachment ] : [] );
				   });

				}

			 }

		});

		// clear any transients when UI is closed
		file_frame.on( 'close', function() {

			$.ajax({
				type: 'POST',
				url: app_uploader_i18n.ajaxurl,
				data: {
					action: 'app_delete_media_manager_transients',
				},
				async: false,
				dataType: 'json'
			}).done( function(response) {
				// do nothing
			} );

		});

		// open the media manager modal
		file_frame.modal.$el.hide();
		file_frame.open();

		// display file upload/embed restrictions on each tab change
		$(document).on( 'click', '.media-menu-item', function() {

			// hide screen readers text
			$('.screen-reader-text').hide();

			$('.media-frame-content select.attachment-filters').css( { 'max-width' :'calc(75% - 12px)' } );

			$('a').css( 'text-decoration', 'none' );

			display_custom_notes();
		});

		// display file upload/embed restrictions on mouse move for the first time the media UI is open
		$(document).on( 'mousemove', '.media-modal-content', function() {
			display_custom_notes();
		})

		// display file upload/embed restrictions on each tab change
		function display_custom_notes() {

			// remove the upload size default tag
			$('.max-upload-size').remove();

			// remove embed settings (title, etc)
			$('.embed-link-settings .setting').remove();

			// restore menu item titles by removing the APP-ITEM flag
			$( '.media-menu-item.app-item, .media-frame-title h1').each( function() {
				var text = $(this).text().replace( 'APP-ITEM', '' ).trim();
				$(this).text( text );
			});

			// custom notes to display on the media view

			$('.app-media-settings').remove();

			// *** FILE SIZE ***

			// convert bytes to specific size unit
			if ( file_size > 0 ) {

				var sizes = new Array( 'KB', 'MB', 'GB' );

				var file_size_unit = file_size;

				for ( u = -1; file_size_unit >= 1024 && u < sizes.length - 1; u++ ) {
					file_size_unit /= 1024;
				}

				if ( u < 0 ) {
					file_size_unit = 0;
					u = 0;
				} else {
					file_size_unit = parseInt( file_size_unit );
				}

				$('.post-upload-ui').append( '<p class="app-media-settings app-file-size-restrictions app-file-size">' + app_uploader_i18n.file_size_text + ': ' + file_size_unit + sizes[u] + '</p>');
			}

			// *** EMBEDS ***

			$('.embed-link-settings').append('<div class="app-media-settings app-embed-settings" style="margin-top: 20px;"><label class="clear-embeds" style="margin-top: 10px;"><input class="clear-embeds" type="checkbox"> ' + app_uploader_i18n.clear_embeds_text + '</input></label></div>');

			if ( embed_limit > 0 ) {
				$('.app-embed-settings').append( '<p class="app-embed-restrictions app-embed-limit">' + app_uploader_i18n.embed_limit_text + ': ' + embed_limit + '</p>');
			}

			// *** ATTACHMENTS ***

			if ( file_limit > 0 ) {
				$('.post-upload-ui').append( '<p class="app-media-settings app-file-restrictions app-file-limit">' + app_uploader_i18n.files_limit_text + ': ' + file_limit + '</p>');
			}
			if ( file_types ) {
				$('.post-upload-ui').append( '<p class="app-media-settings app-file-restrictions app-file-types">' + app_uploader_i18n.files_type_text + ': ' + file_types + '</p>');
			}

		}

		$('.media-menu-item:first').trigger('click');

	});


	// restore the main ID when the add media button is pressed
	$('a.media-button').on('click', function() {
		wp.media.model.settings.post.id = wp_media_post_id;
	});

});
