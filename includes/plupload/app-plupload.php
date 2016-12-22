<?php
/**
 * Plupload API
 *
 * @package ClassiPress\Plupload
 */

add_action( 'wp_ajax_app_plupload_handle_upload', 'appthemes_plupload_handle_upload' );
add_action( 'wp_ajax_app_plupload_handle_delete', 'appthemes_plupload_handle_delete' );


/**
 * Generate html uploader form.
 *
 * @param int $post_id (optional)
 *
 * @return void
 */
function appthemes_plupload_form( $post_id = 0 ) {

	if ( ! current_theme_supports( 'app-plupload' ) ) {
		return;
	}

	list( $options ) = get_theme_support( 'app-plupload' );

	$attachments = array();
	if ( $post_id ) {
		$args = array(
			'post_type' => 'attachment',
			'numberposts' => -1,
			'post_status' => null,
			'post_parent' => $post_id,
			'order' => 'ASC',
		);
		$attachments = get_posts( $args );
	}
?>
	<li>
		<div class="labelwrapper"><label><?php _e( 'Images', APP_TD ); ?></label></div>
	</li>
	<li>
		<div id="app-attachment-upload-container">
			<div id="app-attachment-upload-filelist">
				<ul class="app-attachment-list">
					<script>window.appFileCount = <?php echo count( $attachments ); ?>;</script>
					<?php
						if ( $attachments ) {
							foreach ( $attachments as $attachment ) {
								echo appthemes_plupload_attach_html( $attachment->ID );
							}
						}
					?>
				</ul>
			</div>
			<div id="app-attachment-html-upload-form">
				<ul class="app-attachment-html-upload-fields">
					<?php
						$fields_count = $options['allowed_files'] - count( $attachments );
						if ( $fields_count > 0 ) {
							foreach ( range( 1, $fields_count ) as $i ) {
								$upload_field = html( 'input', array( 'type' => 'file', 'class' => 'fileupload', 'name' => 'image[]' ) );
								$clear_button = html( 'input', array( 'type' => 'button', 'class' => 'clear-file', 'value' => __( 'Clear', APP_TD ) ) );
								echo html( 'li', array( 'id' => 'upload_' . $i ), $upload_field . $clear_button );
							}
						}
					?>
				</ul>
			</div>
			<div class="app-attachment-info">
				<a id="app-attachment-upload-pickfiles" class="button" href="#"><?php _e( 'Add Image', APP_TD ); ?></a>
				<p class="small"><?php printf( __( 'You are allowed to upload %s file(s).', APP_TD ), $options['allowed_files'] ); ?> <?php printf( __( 'Maximum file size: %s KB.', APP_TD ), $options['max_file_size'] ); ?></p>
				<?php if ( ! isset( $options['disable_switch'] ) || ! $options['disable_switch'] ) { ?>
					<p class="small upload-flash-bypass"><?php _e( 'You are using the flash uploader. Problems? Try the <a href="#">browser uploader</a> instead.', APP_TD ); ?></p>
					<p class="small upload-html-bypass"><?php _e( 'You are using the browser uploader. Problems? Try the <a href="#">flash uploader</a> instead.', APP_TD ); ?></p>
				<?php } ?>
			</div>
		</div>
		<div class="clear"></div>
	</li>
<?php
}


/**
 * Generate html for uploaded attachment.
 *
 * @param int $attach_id
 *
 * @return string
 */
function appthemes_plupload_attach_html( $attach_id ) {
	$attachment = get_post( $attach_id );

	$html = '';
	$html .= '<li class="app-attachment">';
	$html .= '<span class="attachment-title">';
	$html .= sprintf( '<input type="text" name="app_attach_title[]" value="%s" placeholder="%s" class="text" />', esc_attr( $attachment->post_title ), esc_attr__( 'Change Title', APP_TD ) );
	$html .= '</span>';
	$html .= sprintf( '<span class="attachment-image">%s</span>', wp_get_attachment_image( $attachment->ID, 'thumbnail', false ) );
	//$html .= sprintf( '<span class="attachment-name">%s</span>', esc_attr( $attachment->post_title ) );
	$html .= sprintf( '<span class="attachment-actions"><a href="#" class="attachment-delete button" data-attach_id="%d">%s</a></span>', $attach_id, __( 'Delete', APP_TD ) );
	$html .= sprintf( '<input type="hidden" name="app_attach_id[]" value="%d" />', $attach_id );
	$html .= '</li>';

	return $html;
}


/**
 * Enqueue scripts for plupload.
 *
 * @param int $post_id (optional)
 *
 * @return void
 */
function appthemes_plupload_enqueue_scripts( $post_id = 0 ) {

	if ( ! current_theme_supports( 'app-plupload' ) ) {
		return;
	}

	list( $options ) = get_theme_support( 'app-plupload' );

	wp_enqueue_script( 'app-plupload', get_template_directory_uri() . '/includes/plupload/app-plupload.js', array( 'jquery', 'plupload-handlers' ) );

	$app_plupload_config = array(
		'nonce' => wp_create_nonce( 'app_attachment' ),
		'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
		'confirmMsg' => __( 'Are you sure?', APP_TD ),
		'number' => $options['allowed_files'],
		'plupload' => array(
			'runtimes' => 'flash,silverlight,html5,html4',
			'browse_button' => 'app-attachment-upload-pickfiles',
			'container' => 'app-attachment-upload-container',
			'file_data_name' => 'app_attachment_file',
			'max_file_size' => $options['max_file_size'] . 'kb',
			'multi_selection' => false,
			'resize' => array( 'width' => 1000, 'height' => 1000, 'quality' => 80 ),
			'url' => admin_url( 'admin-ajax.php', 'relative' ),
			'flash_swf_url' => includes_url( 'js/plupload/plupload.flash.swf' ),
			'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
			'filters' => array( array( 'title' => __( 'Allowed Files', APP_TD ), 'extensions' => 'jpg,jpeg,gif,png' ) ),
			'multipart' => true,
			'urlstream_upload' => true,
			'multipart_params' => array(
				'post_id' => $post_id,
				'nonce' => wp_create_nonce( 'app_attachment_upload' ),
				'action' => 'app_plupload_handle_upload',
			)
		)
	);
	$app_plupload_config = apply_filters( 'appthemes_plupload_config', $app_plupload_config );

	wp_localize_script( 'app-plupload', 'AppPluploadConfig', $app_plupload_config );

}


/**
 * Handle upload of attachment and generates metadata.
 *
 * @return void
 */
function appthemes_plupload_handle_upload() {
	check_ajax_referer( 'app_attachment_upload', 'nonce' );

	$attach_id = false;
	$file_name = basename( $_FILES['app_attachment_file']['name'] );
	$file_type = wp_check_filetype( $file_name );
	$file_renamed = mt_rand( 1000, 1000000 ) . '.' . $file_type['ext'];
	$upload = array(
		'name' => $file_renamed,
		'type' => $file_type['type'],
		'tmp_name' => $_FILES['app_attachment_file']['tmp_name'],
		'error' => $_FILES['app_attachment_file']['error'],
		'size' => $_FILES['app_attachment_file']['size']
	);
	$file = wp_handle_upload( $upload, array( 'test_form' => false ) );

	if ( isset( $file['file'] ) ) {
		$file_loc = $file['file'];

		$attachment = array(
			'post_mime_type' => $file_type['type'],
			'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $file_name ) ),
			'post_content' => '',
			'post_status' => 'inherit'
		);

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$attach_id = wp_insert_attachment( $attachment, $file_loc, $post_id );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_loc );
		wp_update_attachment_metadata( $attach_id, $attach_data );
	}

	if ( $attach_id ) {
		do_action( 'appthemes_plupload_uploaded_attachment', $attach_id );

		$html = appthemes_plupload_attach_html( $attach_id );

		$response = array(
			'success' => true,
			'html' => $html,
		);

		die( json_encode( $response ) );
	}


	$response = array( 'success' => false );
	die( json_encode( $response ) );
}


/**
 * Deletes attachment.
 *
 * @return string
 */
function appthemes_plupload_handle_delete() {
	check_ajax_referer( 'app_attachment', 'nonce' );

	$attach_id = isset( $_POST['attach_id'] ) ? intval( $_POST['attach_id'] ) : 0;
	$attachment = get_post( $attach_id );

	if ( get_current_user_id() == $attachment->post_author || current_user_can( 'delete_private_pages' ) ) {
		wp_delete_attachment( $attach_id, true );
		do_action( 'appthemes_plupload_deleted_attachment', $attach_id );
		echo 'success';
	}

	exit;
}


/**
 * Associate previously uploaded attachments.
 *
 * @param int $post_id
 * @param array $attachments
 * @param array $titles (optional)
 * @param bool $print (optional)
 *
 * @return string
 */
function appthemes_plupload_associate_images( $post_id, $attachments, $titles = false, $print = false ) {
	$i = 0;
	$count = count( $attachments );

	if ( $count > 0 && $print ) {
		echo html( 'p', __( 'Your listing images are now being processed...', APP_TD ) );
	}

	foreach ( $attachments as $key => $attach_id ) {
		$update = array(
			'ID' => $attach_id,
			'post_parent' => $post_id
		);

		if ( isset( $titles[ $key ] ) ) {
			$title = wp_strip_all_tags( $titles[ $key ] );
			$update['post_title'] = $title;
			update_post_meta( $attach_id, '_wp_attachment_image_alt', $title );
		}

		wp_update_post( $update );
		do_action( 'appthemes_plupload_updated_attachment', $attach_id );

		if ( $print ) {
			echo html( 'p', sprintf( __( 'Image number %1$d of %2$s has been processed.', APP_TD ), $i+1, $count ) );
		}

		$i++;
	}

}

/**
 * Check state of app-plupload
 *
 * @return bool
 */
function appthemes_plupload_is_enabled() {
	if ( isset( $_REQUEST['app-plupload'] ) && $_REQUEST['app-plupload'] == 'disable' ) {
		return false;
	}

	if ( ! current_theme_supports( 'app-plupload' ) ) {
		return false;
	}

	return true;
}
