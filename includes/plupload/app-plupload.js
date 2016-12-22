
jQuery(document).ready(function() {

	var APP_Attachment = {
		init: function() {
			window.appFileCount = typeof window.appFileCount == 'undefined' ? 0 : window.appFileCount;
			this.maxFiles = parseInt(AppPluploadConfig.number, 10);

			jQuery('#app-attachment-upload-filelist').on('click', 'a.attachment-delete', this.removeAttachment);

			jQuery('.upload-flash-bypass').on('click', 'a', this.hideFlashUploader);
			jQuery('.upload-html-bypass').on('click', 'a', this.hideHtmlUploader);

			jQuery('#app-attachment-html-upload-form').on('click', '.clear-file', function() {
				jQuery(this).parent().html( jQuery(this).parent().html() );
				return false;
			});

			this.attachUploader();
			this.hideUploadBtn();
			this.hideHtmlUploader();
		},
		hideFlashUploader: function() {
			jQuery('#app-attachment-upload-pickfiles').hide();
			jQuery('p.upload-flash-bypass').hide();
			jQuery('#app-attachment-html-upload-form').show();
			jQuery('p.upload-html-bypass').show();
			APP_Attachment.hideHtmlFields();
			return false;
		},
		hideHtmlUploader: function() {
			jQuery('#app-attachment-html-upload-form').hide();
			jQuery('p.upload-html-bypass').hide();
			jQuery('p.upload-flash-bypass').show();
			APP_Attachment.showUploadBtn();
			return false;
		},
		hideHtmlFields: function() {
			if ( APP_Attachment.maxFiles === 0 ) {
				return;
			}

			jQuery('#app-attachment-html-upload-form li').each(function( index ) {
				if ( ( index + 1 ) > ( APP_Attachment.maxFiles - window.appFileCount ) ) {
					jQuery(this).hide();
				} else {
					jQuery(this).show();
				}
			});
		},
		hideUploadBtn: function() {

			if ( APP_Attachment.maxFiles !== 0 && window.appFileCount >= APP_Attachment.maxFiles ) {
				jQuery('#app-attachment-upload-pickfiles').hide();
			}
		},
		showUploadBtn: function() {
			if ( jQuery('#app-attachment-html-upload-form').is(':visible') ) {
				return;
			}

			if ( APP_Attachment.maxFiles !== 0 && window.appFileCount < APP_Attachment.maxFiles ) {
				jQuery('#app-attachment-upload-pickfiles').show();
			}
		},
		attachUploader: function() {
			if ( typeof plupload === 'undefined' ) {
				return;
			}

			var attachUploader = new plupload.Uploader(AppPluploadConfig.plupload);

			jQuery('#app-attachment-upload-pickfiles').click(function(e) {
				attachUploader.refresh();
				attachUploader.start();
				e.preventDefault();
			});

			attachUploader.init();

			attachUploader.bind('FilesAdded', function(up, files) {
				jQuery.each(files, function(i, file) {
					jQuery('#app-attachment-upload-filelist').append(
						'<div id="' + file.id + '" class="app-attachment-upload-progress">' +
						file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
						'</div>');

					window.appFileCount += 1;
					APP_Attachment.hideUploadBtn();
				});

				up.refresh();
				attachUploader.start();
			});

			attachUploader.bind('UploadProgress', function(up, file) {
				jQuery('#' + file.id + " b").html(file.percent + "%");
			});

			attachUploader.bind('Error', function(up, err) {
				jQuery('#app-attachment-upload-filelist').append(
					'<div class="error">' + APP_Attachment.errorMessage(up, err) + '</div>'
				);
				jQuery( '#app-attachment-upload-filelist .error' ).delay(5000).fadeOut('slow');

				up.refresh();
			});

			attachUploader.bind('FileUploaded', function(up, file, response) {
				var resp = jQuery.parseJSON(response.response);
				if ( resp.success ) {
					jQuery('#app-attachment-upload-filelist ul').append(resp.html);
				} else {
					window.appFileCount -= 1;
					APP_Attachment.showUploadBtn();
				}
				jQuery('#' + file.id).remove();
			});
			attachUploader.bind('StateChanged', function() {
				if ( attachUploader.files.length === ( attachUploader.total.uploaded + attachUploader.total.failed ) ) {
					jQuery('input[type="submit"]').prop('disabled', false);
				} else {
					jQuery('input[type="submit"]').prop('disabled', true);
				}
			});
		},
		removeAttachment: function(e) {
			e.preventDefault();

			if ( confirm(AppPluploadConfig.confirmMsg) ) {
				var el = jQuery(this),
				data = {
					'attach_id' : el.data('attach_id'),
					'nonce' : AppPluploadConfig.nonce,
					'action' : 'app_plupload_handle_delete'
				};

				jQuery.post(AppPluploadConfig.ajaxurl, data, function() {
					el.parent().parent().remove();

					window.appFileCount -= 1;
					APP_Attachment.showUploadBtn();
				});
			}
		},
		errorMessage: function(up, err) {
			var codes = {
				'-100' : pluploadL10n.default_error,
				'-200' : pluploadL10n.http_error,
				'-300' : pluploadL10n.io_error,
				'-400' : pluploadL10n.security_error,
				'-500' : pluploadL10n.default_error,
				'-600' : pluploadL10n.file_exceeds_size_limit.replace('%s', err.file.name),
				'-601' : pluploadL10n.invalid_filetype,
				'-602' : pluploadL10n.default_error,
				'-700' : pluploadL10n.not_an_image,
				'-701' : pluploadL10n.image_memory_exceeded,
				'-702' : pluploadL10n.image_dimensions_exceeded
			};
			if ( err.code in codes )
				return codes[ err.code ];
			else
				return pluploadL10n.default_error;
		}
	};


	APP_Attachment.init();

});
