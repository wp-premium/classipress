<?php
/**
 * Provides a frontend media manager using the built-in WordPress media uploader.
 *
 * @todo enforce allowed video|audio embeds
 * @todo find better way to disable default WP media views instead of using jQuery via the APP-ITEM flag to hide them
 * @package Framework\Media-Manager
 */

define( 'APP_MEDIA_MANAGER_VERSION', '2.0' );

define( 'APP_ATTACHMENT_FILE', 'file' );		// DEFAULT - meta type assigned to attachments
define( 'APP_ATTACHMENT_GALLERY', 'gallery' );  // suggested meta type for image attachments that are displayed as gallery images
define( 'APP_ATTACHMENT_EMBED', 'embed' );		// suggested meta type for embeds

require_once dirname( __FILE__ ) . '/functions.php';
require_once dirname( __FILE__ ) . '/media-manager-types.php';



### Main Class

class APP_Media_Manager {

	protected static $attach_ids_inputs = '_app_attach_ids_fields';
	protected static $embed_url_inputs = '_app_embed_urls_fields';

	protected static $default_filters;

	private function init_hooks() {
		add_action( 'appthemes_media_manager', array( __CLASS__, 'output_hidden_inputs' ), 10, 5 );
		add_action( 'ajax_query_attachments_args', array( __CLASS__, 'restrict_media_library' ), 5 );
		add_action( 'wp_ajax_app_manage_files', array( __CLASS__ , 'ajax_refresh_attachments' ) );

		add_action( 'wp_ajax_app_get_media_manager_options', array( __CLASS__, 'ajax_get_media_manager_options' ) );
		add_action( 'wp_ajax_nopriv_app_get_media_manager_options', array( __CLASS__, 'ajax_get_media_manager_options' ) );

		add_action( 'wp_ajax_app_delete_media_manager_transients', array( __CLASS__, 'ajax_delete_transients' ) );
		add_action( 'wp_ajax_nopriv_app_delete_media_manager_transients', array( __CLASS__, 'ajax_delete_transients' ) );

		add_action( 'add_attachment', array( __CLASS__, 'set_attachment_mm_id' ) );

		add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'validate_upload_restrictions' ) );
	}

	function __construct() {
		$this->init_hooks();

		$params = appthemes_media_manager_get_args();

		extract( $params );

		self::$default_filters = $params;
	}

	/**
	 * Enqueues the JS scripts that output WP's media uploader.
	 */
	static function enqueue_media_manager( $localization = array() ) {

		wp_register_script(
			'app-media-manager',
			APP_FRAMEWORK_URI . '/media-manager/scripts/media-manager.js',
			array( 'jquery' ),
			APP_MEDIA_MANAGER_VERSION,
			true
		);

		wp_enqueue_style(
			'app-media-manager',
			APP_FRAMEWORK_URI . '/media-manager/style.css'
		);

		$defaults = array(
			'post_id'                     => 0,
			'post_id_field'               => '',
			'ajaxurl'                     => admin_url( 'admin-ajax.php', 'relative' ),
			'ajax_nonce'                  => wp_create_nonce( 'app-media-manager' ),
			'files_limit_text'            => __( 'Allowed files', APP_TD ),
			'files_type_text'             => __( 'Allowed file types', APP_TD ),
			'insert_media_title'          => __( 'Insert Media', APP_TD ),
			'embed_media_title'           => __( 'Insert from URL', APP_TD ),
			'file_size_text'              => __( 'Maximum upload file size', APP_TD ),
			'embed_limit_text'            => __( 'Allowed embeds', APP_TD ),
			'clear_embeds_text'           => __( 'Clear Embeds (clears any previously added embeds)', APP_TD ),
			'allowed_embeds_reached_text' => __( 'No more embeds allowed', APP_TD ),
		);
		$localization = wp_parse_args( $localization, $defaults );

		wp_localize_script( 'app-media-manager', 'app_uploader_i18n', $localization );

		wp_enqueue_script( 'app-media-manager' );

		wp_enqueue_media();
	}

	/**
	 * Outputs the media manager HTML markup.
	 *
	 * @uses do_action() Calls 'appthemes_media_manager'
	 *
	 */
	static function output_media_manager( $object_id = 0, $atts = array(), $filters = array() ) {

		// Make sure we have a unique ID for each outputted file manager.
		if ( empty( $atts['id'] ) ) {
			$attach_field_id = uniqid('id');
		} else {
			$attach_field_id = $atts['id'];
		}

		// Parse the custom filters for the outputted media manager.
		$filters = wp_parse_args( $filters, self::$default_filters );

		// Allow using 'meta_type' or 'file_meta_type' as filter name.
		if ( ! empty( $filters['meta_type'] ) ) {
			$filters['file_meta_type'] = $filters['meta_type'];
		}

		// Media manager fieldset attributes.
		$defaults = array(
			'id'                => $attach_field_id,
			'object'            => 'post',
			'class'             => 'files',
			'title'             => '',
			'upload_text'       => __( 'Add Media', APP_TD ),
			'manage_text'       => __( 'Manage Media', APP_TD ),
			'no_media_text'     => __( 'No media added yet', APP_TD ),
			'attachment_ids'    => '',
			'embed_urls'        => '',
			'attachment_params' => array(),
			'embed_params'      => array(),
		);
		$atts = wp_parse_args( $atts, $defaults );

		$meta_object = APP_Media_Manager_Meta_Type_Object_Factory::get_instance( $atts['object'] );

		if ( ! $meta_object ) {
			return;
		}

		if ( ! empty( $filters['mime_types'] ) ) {

			// Extract, correct and flatten the mime types.
			if ( ! is_array( $filters['mime_types'] ) ) {

				// Keep the original required mime types to display to the user.
				$filters['file_types'] = $filters['mime_types'];

				$mime_types = explode( ',', $filters['mime_types'] );
			} else {
				$mime_types = $filters['mime_types'];

				// Keep the original required mime types to display to the user.
				$filters['file_types'] = implode( ',', $filters['mime_types'] );
			}
			$mime_types = appthemes_get_mime_types_for( $mime_types );
			$filters['mime_types'] = implode( ',', $mime_types );
		}

		if ( empty( $atts['attachment_ids'] ) && $object_id ) {

			$attachment_ids = $meta_object->get_meta( $object_id, $attach_field_id, true );

			if ( ! empty( $attachment_ids ) ) {

				// Check if the attachments stored in meta are still valid by querying the DB to retrieve all the valid ID's.
				$args = array(
					'fields'   => 'ids',
					'post__in' => $attachment_ids,
					'orderby'  => 'post__in'
				);
				$atts['attachment_ids'] = self::get_post_attachments( $meta_object->get_parent_id( $object_id ), $args );

				// Refresh the post meta.
				if ( ! empty( $atts['attachment_ids'] ) ) {
					$meta_object->update_meta( $object_id, $attach_field_id, $atts['attachment_ids'] );
				}
			}

		}

		// Get all the embeds for the current post ID, if editing a post.
		if ( empty( $atts['embed_urls'] ) && $object_id ) {

			$embeds_attach_ids = $meta_object->get_meta( $object_id, $attach_field_id .'_embeds', true );

			if ( ! empty( $embeds_attach_ids ) ) {

				// Check if the attachments stored in meta are still valid by querying the DB to retrieve all the valid ID's.
				$args = array(
					'meta_value' => appthemes_get_mm_allowed_meta_types('embed'),
					'post__in'   => $embeds_attach_ids,
				);

				$curr_embed_attachments = self::get_post_attachments( $meta_object->get_parent_id( $object_id ), $args );

				if ( ! empty( $curr_embed_attachments ) ) {
					$atts['embed_urls'] = wp_list_pluck( $curr_embed_attachments, 'guid' );
					$embeds_attach_ids  = wp_list_pluck( $curr_embed_attachments, 'ID' );

					// refresh the post meta
					$meta_object->update_meta( $object_id,  $attach_field_id .'_embeds', array_keys( $embeds_attach_ids ) );
				}

			}

		}

		$atts['button_text'] = ( ! empty( $atts['attachment_ids'] ) ? $atts['manage_text'] : $atts['upload_text']  );

		// Look for a custom template before using the default one.
		$located = locate_template( 'media-manager.php' );

		if ( ! $located ) {
			require APP_FRAMEWORK_DIR . '/media-manager/template/media-manager.php';
		} else {
			require $located;
		}

		$options = array(
			'attributes' => $atts,
			'filters' => $filters
		);

		update_option( "app_media_manager_{$attach_field_id}", $options );

		do_action( 'appthemes_media_manager', $attach_field_id, $atts['attachment_ids'], $atts['embed_urls'], $filters, $atts['object'] );
	}

	/**
	 * Process all posted inputs that contain attachment ID's that need to be assigned to a post or user.
	 */
	static function handle_media_upload( $object_id, $type = 'post', $fields = array(), $duplicate = false ) {

		$attach_ids_inputs = self::$attach_ids_inputs;
		$embed_url_inputs = self::$embed_url_inputs;

		if ( ! $fields ) {
			if ( ! empty( $_POST[ $attach_ids_inputs ][ $type ] ) ) {
				$fields['attachs'] = $_POST[ $attach_ids_inputs ][ $type ];
			}

			if ( ! empty( $_POST[ $embed_url_inputs ][ $type ] ) ) {
				$fields['embeds'] = $_POST[ $embed_url_inputs ][ $type ];
			}
		}

		if ( empty( $fields ) ) {
			return;
		}

		$attachs = array();

		// Handle normal attachments.
		foreach( (array) $fields['attachs'] as $field ) {
			$media = self::handle_media_field( $object_id, $field, $type, $duplicate );
			if ( ! empty( $media ) ) {
				$attachs = array_merge( $media, $attachs );
			}
		}

		// Handle embed attachments.
		foreach( (array) $fields['embeds'] as $field ) {
			$media = self::handle_embed_field( $object_id, $field, $type );
			if ( ! empty( $media ) ) {
				$attachs = array_merge( $media, $attachs );
			}
		}

		$meta_object = APP_Media_Manager_Meta_Type_Object_Factory::get_instance( $type );

		if ( ! $meta_object ) {
			return;
		}

		// Clear previous attachments by checking if they are present on the updated attachments list.
		self::maybe_clear_old_attachments( $meta_object->get_parent_id( $object_id ), $attachs );
	}

	/**
	 * Handles embedded media related posted data and retrieves an updated list of all the embed attachments for the current object.
	 *
	 * @uses do_action() Calls 'appthemes_handle_embed_field'
	 *
	 */
	private static function handle_embed_field( $object_id, $field, $type = 'post' ) {

		$meta_object = APP_Media_Manager_Meta_Type_Object_Factory::get_instance( $type );

		if ( ! $meta_object ) {
			return;
		}

		// User cleared the embeds.
		if ( empty( $_POST[ $field ] ) ) {

			// Delete the embed url's from the object meta.
			$meta_object->delete_meta( $object_id, $field );
			$media = array();

		} else {

			$embeds = explode( ',', wp_strip_all_tags( $_POST[ $field ] ) );

			foreach( $embeds as $embed ) {

				$embed = trim( $embed );

				// Try to get all the meta data from the embed URL to populate the attachment 'post_mime_type'.
				// The 'post_mime_type' is stored in the following format: <mime type>/<provider-name>-iframe-embed ( e.g: video/youtube-iframe-embed, video/vimeo-iframe-embed, etc ).
				// If the provider is not recognized by WordPress the 'post_mime_type' will default to <mime type>/iframe-embed ( e.g: video/iframe-embed ).
				$oembed = self::get_oembed_object( $embed );

				$iframe_type = ( ! empty( $oembed->provider_name ) ? strtolower( $oembed->provider_name ) . '-' : '' ) . 'iframe-embed';
				$type = ( ! empty( $oembed->type ) ? $oembed->type : 'unknown' );
				$title = ( ! empty( $oembed->title ) ? $oembed->title : __( 'Unknown', APP_TD ) );

				$attachment = array(
					'post_title'     => $title,
					'post_content'   => $embed,
					'post_parent'    => $object_id, // treating WP bug https://core.trac.wordpress.org/ticket/29646
					'guid'           => $embed,
					'post_mime_type' => sprintf( '%1s/%2s', $type, $iframe_type ),
				);

				// Assign the embed URL to the object as a normal file attachment.
				$attach_id = wp_insert_attachment( $attachment, '', $object_id );

				if ( is_wp_error( $attach_id ) ) {
					continue;
				}

				$media[] = (int) $attach_id;

				if ( isset( $_POST[ $field . '_meta_type' ] ) && in_array( $_POST[ $field .'_meta_type' ], appthemes_get_mm_allowed_meta_types('embed') ) ) {
					$meta_type = $_POST[ $field .'_meta_type' ];
				} else {
					$meta_type = APP_ATTACHMENT_EMBED;
				}

				update_post_meta( $attach_id, '_app_attachment_type', $meta_type );
			}

			// Store the embed url's on the object meta.
			$meta_object->update_meta( $object_id, $field, $media );

		}

		do_action( 'appthemes_handle_embed_field', $object_id, $field, $type );

		return $media;
	}

	/**
	 * Handles media related posted data and retrieves an updated list of all the attachments for the current object.
	 *
	 * @uses do_action() Calls 'appthemes_handle_media_field'
	 *
	 * @todo: maybe set '$duplicate' param to 'true' by default
	 */
	private static function handle_media_field( $object_id, $field, $type = 'post', $duplicate = false ) {

		$meta_object = APP_Media_Manager_Meta_Type_Object_Factory::get_instance( $type );

		if ( ! $meta_object ) {
			return;
		}

		// User cleared the attachments.
		if ( empty( $_POST[ $field ] ) ) {

			// Delete the attachments from the object meta.
			$meta_object->delete_meta( $object_id, $field );
			$media = array();

		} else {

			$attachments = explode( ',', wp_strip_all_tags( $_POST[ $field ] ) );
			$menu_order  = 0;
			$parent_id   = $meta_object->get_parent_id( $object_id );

			foreach( $attachments as $attachment_id ) {

				$attachment = get_post( $attachment_id );

				if ( $attachment->post_parent != $parent_id ) {

					$attachment->post_date = '';
					$attachment->post_date_gmt = '';

					$filename = get_attached_file( $attachment_id );
					$generate_meta = false;

					// If '$duplicate' is set to TRUE and the attachment already has a parent, clone it and assign it to the post.
					// Otherwise, the attachment will not change and will simply change parents.
					if ( $duplicate && $attachment->post_parent ) {
						$attachment->ID = 0;
						$generate_meta = true;
					}

					// Treating WP bug https://core.trac.wordpress.org/ticket/29646.
					$attachment->post_parent = $parent_id;

					// Update the attachment.
					$attach_id = wp_insert_attachment( $attachment, $filename, $parent_id );
					if ( is_wp_error( $attach_id ) ) {
						continue;
					}

					if ( $generate_meta ) {
						// Include the 'wp_generate_attachment_metadata()' dependency file.
						require_once( ABSPATH . 'wp-admin/includes/image.php' );

						// Generate the metadata for the cloned attachment, and update the database record.
						wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $filename ) );
					}


				} else {
					$attach_id = $attachment_id;
				}

				if ( isset( $_POST[ $field .'_meta_type' ] ) && in_array( $_POST[ $field .'_meta_type' ], appthemes_get_mm_allowed_meta_types('file') ) ) {
					$meta_type = $_POST[ $field .'_meta_type' ];
				} else {
					$meta_type = APP_ATTACHMENT_FILE;
				}

				$media[] = (int) $attach_id;

				wp_update_post( array(
					'ID'         => $attach_id,
					'menu_order' => $menu_order++,
				) );

				update_post_meta( $attach_id, '_app_attachment_type', $meta_type );
			}

			// Store the attachments on the object meta.
			$meta_object->update_meta( $object_id, $field, $media );

		}

		do_action( 'appthemes_handle_media_field', $object_id, $field, $type );

		return $media;
	}

	/**
	 * Outputs the hidden inputs that act as helpers for the media manager JS.
	 */
	static function output_hidden_inputs( $attach_field_id, $attachment_ids, $embed_urls, $filters, $object_type ) {

		$embeds_input = $attach_field_id . '_embeds';

		// Input for the media manager unique nonce.
		wp_nonce_field( "app_mm_nonce_{$attach_field_id}", "app_mm_nonce_{$attach_field_id}" );

		// Input for the attachment ID's selected by the user in the media manager.
		echo html( 'input', array( 'name' => $attach_field_id, 'type' => 'hidden', 'value' => implode( ',', (array) $attachment_ids ) ) );

		// Input with all the field names that contain attachment ID's.
		echo html( 'input', array( 'name' => self::$attach_ids_inputs . '[' . $object_type . '][]','type' => 'hidden', 'value' => $attach_field_id ) );

		// Input for the embed URL's selected by the user in the media manager.
		echo html( 'input', array( 'name' => $embeds_input, 'type' => 'hidden', 'value' => implode( ',', (array) $embed_urls ) ) );

		// Input with all the field names that contain embed URL's.
		echo html( 'input', array( 'name' => self::$embed_url_inputs . '[' . $object_type . '][]','type' => 'hidden', 'value' => $embeds_input ) );

		// Input for normal attachments meta type.
		if ( ! empty( $filters['file_meta_type'] ) ) {
			echo html( 'input', array( 'class' => $attach_field_id,	'type' => 'hidden',	'name' => $attach_field_id . '_meta_type', 'value' => $filters['file_meta_type'] ) );
		}

		// Input for embed attachments meta type.
		if ( ! empty( $filters['embed_meta_type'] ) ) {
			echo html( 'input', array( 'class' => $attach_field_id,	'type' => 'hidden',	'name' => $embeds_input . '_meta_type', 'value' => $filters['embed_meta_type'] ) );
		}

	}

	/**
	 * Refreshes the attachments/embed list based on the user selection.
	 */
	static function ajax_refresh_attachments() {

		if ( ! check_ajax_referer( 'app_mm_nonce_' . $_POST['mm_id'], 'mm_nonce' ) ) {
			die();
		}

		extract( $_POST );

		$attachment_ids = $embed_attach_ids = array();

		// Retrieve the options for the current media manager.
		$media_manager_options = appthemes_get_media_manager_options( $mm_id );

		if ( isset( $_POST['attachments'] ) ) {
			$attachment_ids = array_merge( $attachment_ids, $_POST['attachments'] );
			$attachment_ids = array_map( 'intval', $attachment_ids );
			$attachment_ids = array_unique( $attachment_ids );
		}

		if ( ! empty( $_POST['embed_urls'] ) ) {
			$posted_embed_urls = sanitize_text_field( $_POST['embed_urls'] );
			$embed_urls = explode( ',', $posted_embed_urls );
		}

		if ( ! empty( $attachment_ids ) ) {
			$attachments = appthemes_output_attachments( $attachment_ids, $media_manager_options['attributes']['attachment_params'], $echo = false );
			echo json_encode( array( 'output' => $attachments ) );
		}

		if ( ! empty( $embed_urls ) ) {
			$embeds = appthemes_output_embed_urls( $embed_urls, $media_manager_options['attributes']['embed_params'], $echo = false );
			echo json_encode( array( 'url' => $posted_embed_urls, 'output' => $embeds ) );
		}

		die();
	}

	/**
	 * Restrict media library to files uploaded by the current user with
	 * no parent or whose parent is the current post ID.
	 */
	static function restrict_media_library( $query ) {
		global $current_user;

		// Make sure we're restricting the library only on the frontend media manager.
		if ( ! appthemes_get_active_media_manager() ) {
			return $query;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
		   $query['author'] = $current_user->ID;

		   if ( empty( $_REQUEST['post_id'] ) ) {
			   $query['post_parent'] = 0;
		   } else {
			   $query['post_parent'] = $_REQUEST['post_id'];
		   }

		}

		return $query;
	}

	/**
	 * Validates the files the current user is trying to upload by checking their mime types
	 * and the preset file limit.
	 */
	static function validate_upload_restrictions( $file ) {

		if ( empty( $_POST['app_mime_types'] ) && empty( $_POST['app_file_size'] ) && empty( $_POST['app_file_limit'] ) ) {
			return $file;
		}

		$mm_id = sanitize_text_field( $_POST['app_mm_id'] );

		$options = appthemes_get_media_manager_options( $mm_id );

		// Secure mime types.
		if ( ! empty( $_POST['app_mime_types'] ) ) {

			// Check if the mime types limit where hacked.
			if ( empty( $options['filters'] ) || $_POST['app_mime_types'] != $options['filters']['mime_types'] ) {
				$file['error'] = __( 'Sorry, allowed mime types do not seem to be valid.', APP_TD );
				return $file;
			}

			// Can be 'mime_type/extension', 'extension' or 'mime_type'.
			$allowed = explode( ',', $_POST['app_mime_types'] );

			$file_type = wp_check_filetype( $file['name'] );
			$mime_type = explode( '/', $file_type['type'] );

			$not_allowed = true;

			// Check if extension and mime type are allowed.
			if ( in_array( $mime_type[0], $allowed ) || in_array( $file_type['type'], $allowed ) || in_array( $file_type['ext'], $allowed ) ) {
				$not_allowed = false;
			}

			if ( $not_allowed ) {

				$allowed_mime_types = get_allowed_mime_types();

				// First pass to check if the mime type is allowed.
				if ( ! in_array( $file['type'], $allowed_mime_types ) ) {

					// Double check if the extension is invalid by looking at the allowed extensions keys.
					foreach ( $allowed_mime_types as $ext_preg => $mime_match ) {
						$ext_preg = '!^(' . $ext_preg . ')$!i';
						if ( preg_match( $ext_preg, $file_type['ext'] ) ) {
							$not_allowed = false;
							break;
						}
					}

				}

				if ( $not_allowed ) {
					$file['error'] = __( 'Sorry, you cannot upload this file type for this field.', APP_TD );
					return $file;
				}

			}

		}

		// Secure file size.
		if ( ! empty( $_POST['app_file_size'] ) ) {

			// Check if the file size limit was hacked.
			if ( empty( $options['filters'] ) || $_POST['app_file_size'] != $options['filters']['file_size'] ) {
				$file['error'] = __( 'Sorry, the allowed file size does not seem to be valid.', APP_TD );
				return $file;
			}

			$file_size = sanitize_text_field( $_POST['app_file_size'] );

			if ( $file['size'] > $file_size ) {
				$file['error'] = __( 'Sorry, you cannot upload this file as it exceeds the size limitations for this field.', APP_TD );
				return $file;
			}

		}

		// Secure file limit.
		if ( ! empty( $_POST['app_file_limit'] ) ) {

			$args = array(
				'post_type'   => 'attachment',
				'author'      => get_current_user_id(),
				'post_parent' => ! empty( $_POST['post_id'] ) ? $_POST['post_id'] : 0,
				'nopaging'    => true,
				'post_status' => 'any',
				// Limit files considering the media manager parent ID since each available media manager on a form can have it's own file limits.
				'meta_key'    => '_app_media_manager_parent',
				'meta_value'  => $mm_id,
			);

			$attachments = new WP_Query( $args );

			if ( $attachments->found_posts && $attachments->found_posts > $_POST['app_file_limit'] && '-1' != $_POST['app_file_limit'] ) {
				$file['error'] = __( 'Sorry, you\'ve reached the file upload limit for this field.', APP_TD );
				return $file;
			}

		}

		return $file;
	}

	/**
	 * Get the attachments for a given object.
	 */
	static function get_post_attachments( $object_id, $args = array() ) {

		// Get the current attached embeds.
		$defaults = array(
			'post_parent' => $object_id,
			'meta_key'    => '_app_attachment_type',
			'meta_value'  =>  appthemes_get_mm_allowed_meta_types('file'),
		);
		$args = wp_parse_args( $args, $defaults );

		$curr_attachments = get_children( $args );

		return $curr_attachments;
	}

	/**
	 * Unassigns or deletes any previous attachments that are not present on the current attachment enqueue list.
	 */
	static function maybe_clear_old_attachments( $object_id, $attachments = array(), $delete = false ) {

		$args = array(
			'meta_value' => appthemes_get_mm_allowed_meta_types(),
		);

		if ( ! empty( $attachments ) ) {
			$args['post__not_in'] = $attachments;
		}

		$old_attachments = self::get_post_attachments( $object_id, $args );

		// Unattach or delete.
		foreach( $old_attachments as $old_attachment ) {

			$type = get_post_meta( $old_attachment->ID, '_app_attachment_type', true );

			// Delete embeds by default since they cannot be re-attached again.
			if ( in_array( $type, appthemes_get_mm_allowed_meta_types('embed') ) || $delete ) {
				wp_delete_attachment( $old_attachment->ID );
			} else {
				// Unattach normal attachments to allow re-attaching them later.
				$old_attachment->post_parent = 0;
				wp_insert_attachment( $old_attachment );
			}
		}

	}

   /**
    * Attempts to fetch an oembed object with metadata for a provided URL using oEmbed.
    */
   static function get_oembed_object( $url ) {
		require_once( ABSPATH . WPINC . '/class-oembed.php' );
		$oembed = _wp_oembed_get_object();

		$oembed_provider_url = $oembed->discover( $url );
		$oembed_object = $oembed->fetch( $oembed_provider_url, $url );

		return empty( $oembed_object ) ? false : $oembed_object;
   }

	/**
	 * Ajax callback to retrieves the db options for a specific media manager ID.
	 */
	static function ajax_get_media_manager_options() {

		if ( empty( $_POST['mm_id'] ) ) {
		   die();
		}

		if ( ! check_ajax_referer( 'app_mm_nonce_' . $_POST['mm_id'], 'mm_nonce' ) ) {
		   die();
		}

		$mm_id = $_POST['mm_id'];

		$options = appthemes_get_media_manager_options( $mm_id );

		// Set a transient for the opened media manager ID/user ID to help identify the current media manager when there's multiple mm's on same form.
		set_transient( 'app_media_manager_id_' . get_current_user_id(), $mm_id, 60 * 60 * 5 ); // keep transient for 5 minutes

		echo json_encode( $options );

		die();
	}

	/**
	 * Delete any stored transients when media manager UI is closed.
	 */
	static function ajax_delete_transients() {
		$user_id = get_current_user_id();
		delete_transient( 'app_media_manager_id_'.$user_id );
		die();
	}

   /**
	* Assign a meta key containing the media manager parent ID AND a default attach type to each new media attachment added through the media manager.
	*/
   static function set_attachment_mm_id( $attach_id ) {

	   // Get the active media manager ID for the current user.
	   $mm_id = appthemes_get_active_media_manager();

	   if ( $mm_id ) {
		   update_post_meta( $attach_id, '_app_media_manager_parent', $mm_id );
		   update_post_meta( $attach_id, '_app_attachment_type', APP_ATTACHMENT_FILE );
	   }

   }

}
