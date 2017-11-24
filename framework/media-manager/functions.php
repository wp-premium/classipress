<?php
/**
 * Media manager helper functions.
 *
 * @package Framework\Media-Manager
 * @author  AppThemes
 * @since   2.0
 */

add_filter( 'map_meta_cap','_appthemes_media_capabilities', 15, 4 );


### Hooks Callbacks

/**
 * Meta capabilities for uploading files.
 *
 * Users need the 'upload_media' cap to be able to upload files
 * Users need the 'delete_post' cap to be able to delete files
 *
 * @since 1.0
 */
function _appthemes_media_capabilities( $caps, $cap, $user_id, $args ) {

	// check for an active media manager for the current user - skip otherwise
	if ( ! appthemes_get_active_media_manager() ) {
		return $caps;
	}

	switch( $cap ) {

		case 'upload_files':

			if ( ( user_can( $user_id, 'upload_media' ) || apply_filters( 'appthemes_allow_upload_files', false, $user_id ) ) ) {
				$caps = array( 'exist' );
			}
			break;

		case 'delete_post';

			$post = get_post( $args[0] );

			// Allow users to delete their uploaded files.
			if ( $user_id == $post->post_author && 'attachment' == $post->post_type ) {

				$mm_id = appthemes_get_active_media_manager();
				if ( $mm_id ) {
					$mm_options = appthemes_get_media_manager_options( $mm_id );
					// check if the active media manage allows deleting uploaded files (only own uploaded files can be deleted)
					if ( ! empty( $mm_options['filters']['delete_files'] ) ) {
						$caps = array( 'exist' );
					}
				}

			}
			break;

	}

	return $caps;
}


/**
 * Retrieve the 'get_theme_support()' args.
 *
 * @since 1.0
 */
function appthemes_media_manager_get_args( $option = '' ) {

	static $args = array();

	if ( ! current_theme_supports( 'app-media-manager' ) ) {
		return array();
	}

	if ( empty( $args ) ) {

		// Numeric array, contains multiple sets of arguments.
		// First item contains preferable set.
		$args_sets = get_theme_support( 'app-media-manager' );

		if ( ! is_array( $args_sets ) ) {
			$args_sets = array();
		}

		foreach ( $args_sets as $args_set ) {
			foreach ( $args_set as $key => $arg ) {
				if ( ! isset( $args[ $key ] ) ) {
					$args[ $key ] = $arg;
				} elseif ( 'delete_files' === $key && $arg ) {
					$args[ $key ] = true;
				} elseif ( is_array( $arg ) ) {
					$args[ $key ] = array_merge_recursive( (array) $args[ $key ], $arg );
				}
			}
		}

		$defaults = array(
			'file_limit'   => -1,            // 0 = disable, -1 = no limit
			'embed_limit'  => -1,            // 0 = disable, -1 = no limit
			'file_size'    => 1048577,       // limit file sizes to 1MB (in bytes), -1 = use WP default
			'mime_types'   => '',            // blank = any (accepts 'image', 'image/png', 'png, jpg', etc) (string|array)
			'delete_files' => false,         // allow deleting uploaded files - false = do not allow, true = allow
		);

		$args = wp_parse_args( $args, $defaults );

	}

	if ( empty( $option ) ) {
		return $args;
	} else if ( isset( $args[ $option ] ) ) {
		return $args[ $option ];
	} else {
		return false;
	}

}


### Helper Functions

/**
 * Outputs the media manager HTML markup.
 *
 * @since 1.0
 *
 * @param int $object_id (optional) The post ID/user ID that the media relates to
 * @param array $atts (optional) Input attributes to be passed to the media manager:
 * 			'id'			   => the input ID - name used as meta key to store the media data
 *			'object'		   => the object to assign the attachments: 'post'(default)|'user'
 *			'class'			   => the input CSS class
 *			'title'			   => the input title
 *			'upload_text'	   => the text to be displayed on the upload button when there are no uploads yet
 *			'manage_text'	   => the text to be displayed on the upload button when uploads already exist
 *			'no_media_text'	   => the placeholder text to be displayed while there are no uploads
 *			'attachment_ids'   => default attachment ID's to be listed (int|array),
 *			'embed_urls'	   => default embed URL's to be listed (string|array),
 *			'attachment_params => the parameters to pass to the function that outputs the attachments (array)
 * 			'embed_params      => the parameters to pass to the function that outputs the embeds (array)
 * @param array $filters (optional) Filters to be passed to the media manager:
 *			'file_limit'	 => file limit - 0 = disable, -1 = no limit (default)
 *			'file_size'		 => file size (in bytes) - default = 1048577 (~1MB)
  *			'file_meta_type' => APP_ATTACHMENT_FILE (default), APP_ATTACHMENT_GALLERY - hook into 'appthemes_mm_allowed_file_meta_types()' to add others
 *			'embed_limit'	 => embed limit - 0 = disable, -1 = no limit (default)
 *			'embed_meta_type'=> APP_ATTACHMENT_EMBED (default) - hook into 'appthemes_mm_allowed_embed_meta_types()' to add others
 *			'mime_types'	 => the mime types accepted (default is empty - accepts any mime type) (string|array)
 *			'delete_files'   => allow deleting uploaded files - false = do not allow (default), true = allow
 */
function appthemes_media_manager( $object_id = 0, $atts = array(), $filters = array() ) {
	APP_Media_Manager::output_media_manager( $object_id, $atts, $filters );
}

/**
 * Enqueues the JS scripts that output WP's media manager.
 *
 * @since 1.0
 *
 * @param array $localization (optional) The localization params to be passed to wp_localize_script()
 * 		'post_id'			=> the existing post ID, if editing a post, or 0 for new posts (required for edits if 'post_id_field' is empty)
 *		'post_id_field'		=> an input field name containing the current post ID (required for edits if 'post_id' is empty)
 *		'ajaxurl'			=> admin_url( 'admin-ajax.php', 'relative' ),
 *		'ajax_nonce'		=> wp_create_nonce('app-media-manager'),
 *		'files_limit_text'	=> the files limit text to be displayed on the upload view
 *		'files_type_text'	=> the allowed file types to be displayed on the upload view
 *		'insert_media_title'=> the insert media title to be displayed on the upload view
 *		'embed_media_title'	=> the embed media title to be displayed on the embed view
 *		'embed_limit_text'	=> the embed limit to be displayed on the embed view
 *		'clear_embeds_text' => the text for clearing the embeds to be displayed on the embed view
 *		'allowed_embeds_reached_text' => the allowed embeds warning to be displayed when users reach the max embeds allowed
 */
function appthemes_enqueue_media_manager( $localization = array() ) {
	APP_Media_Manager::enqueue_media_manager( $localization );
}

/**
 * Handles media related post data.
 *
 * @since 1.0
 *
 * @param int $post_id The post ID to which the attachments will be assigned
 * @param array $fields (optional) The media fields that should be handled -
 * Expects the fields index type: 'attachs' or 'embeds' (e.g: $fields = array( 'attach' => array( 'field1', 'field2' ), 'embeds' => array( 'field1', 'field2' ) )
 * @param bool $duplicate (optional) Should the media files be duplicated, thus keeping the original file unattached
 * @return null|bool False if no media was processed, null otherwise
 */
function appthemes_handle_media_upload( $post_id, $fields = array(), $duplicate = false ) {
	APP_Media_Manager::handle_media_upload( $post_id, 'post', $fields, $duplicate );
}

/**
 * Handles media related user data.
 *
 * @since 1.0
 *
 * @param int $user_id The user ID to which the attachments will be assigned
 * @param array $fields (optional) The media fields that should be handled
 * @return null|bool False if no media was processed, null otherwise
 */
function appthemes_handle_user_media_upload( $user_id, $fields = array() ) {
	APP_Media_Manager::handle_media_upload( $user_id, 'user', $fields );
}

/**
 * Outputs the HTML markup for a list of attachment ID's.
 *
 * @since 1.0
 *
 * @param array $attachment_ids The list of attachment ID's to output
 * @param array $params The params to be used to output the attachments
 *		'show_description' => displays the attachment description (default is TRUE),
 *		'show_image_thumbs' => displays the attachment thumb (default is TRUE - images only, displays an icon on other mime types),
 * @param bool $echo Should the attachments be echoed or returned (default is TRUE)
 */
function appthemes_output_attachments( $attachment_ids, $params = array(), $echo = true ) {

	$defaults = array(
		'show_description' => true,
		'show_image_thumbs' => true,
	);
	$params = wp_parse_args( $params, $defaults );

	extract( $params );

	if ( empty( $attachment_ids ) ) {
		return;
	}

	$attachments = '';

	if ( ! $echo ) {
		ob_start();
	}

	foreach( (array) $attachment_ids as $attachment_id ) {
		appthemes_output_attachment( $attachment_id, $show_description, $show_image_thumbs );
	}

	if ( ! $echo ) {
		$attachments .= ob_get_clean();
	}

	if ( ! empty( $attachments ) ) {
		return $attachments;
	}

}

/**
 * Outputs the HTML markup for a specific attachment ID.
 *
 * @since 1.0
 *
 * @param int $attachment_id The attachment ID
 * @param bool $show_description (optional) Should the attachment description be displayed?
 * @param bool $show_image_thumbs (optional) Should images be prepended with thumbs? (defaults to mime type icons)
 * @return string The HTML markup
 */
function appthemes_output_attachment( $attachment_id, $show_description = true, $show_image_thumbs = true ) {

	$file = appthemes_get_attachment_meta( $attachment_id, $show_description );

	$title = $show_description ? $file['title'] : '';

	$link = html( 'a', array(
		'href' => $file['url'],
		'title' => $file['title'],
		'alt' => $file['alt'],
		'target' => '_blank',
	), $title );

	$mime_type = explode( '/', $file['mime_type'] );

	if ( $show_description ) {
		$attachment = get_post( $attachment_id );

		// In case the attachment was deleted somehow return earlier.
		if ( empty( $attachment ) ) {
			return;
		}

		$file = array_merge( $file, array(
			'caption'     => $attachment->post_excerpt,
			'description' => $attachment->post_content,
		) );
		$link .= html( 'p', array( 'class' =>  'file-description' ), $file['description'] );
	}

	if ( 'image' == $mime_type[0] && $show_image_thumbs ) {
		$thumb = wp_get_attachment_image( $attachment_id, 'thumb' );

		echo html( 'div', $thumb . ' ' . $link );
		return;
	}

	echo html( 'div', array(
		'class' => 'file-extension ' . appthemes_get_mime_type_icon_class( $file['mime_type'] ),
	), $link );

}

/**
 * Outputs embed attachments or the HTML markup for a single embed or list of embed attachments.
 *
 * @since 1.0
 *
 * @param int|array $attachment_ids A single embed attachment ID or list of embeds attachment ID's
 * @param array $params (optional)
 *              'embed' => true (default)|false - should the URL be automatically embed or simply outputed?
 */
function appthemes_output_embeds( $attachment_ids, $params = array(), $echo = true ) {

	$defaults = array(
		'embed' => true,
	);
	$params = wp_parse_args( $params, $defaults );

	extract( $params );

	$embeds = '';

	if ( ! $echo ) {
		ob_start();
	}

	foreach( (array) $attachment_ids as $attach_id ) {

		$attachment = get_post( $attach_id );

		if ( empty( $attachment) ) {
			continue;
		}

		$url = trim( $attachment->guid );

		echo html( 'br', '&nbsp;' );

		echo appthemes_get_oembed_url( $url );

		if ( ! $echo ) {
			$embeds .= ob_get_clean();
		}

	}

	if ( ! empty( $embeds ) ) {
		return $embeds;
	}
}

/**
 * Outputs an embeded URL or the HTML markup for a single URL or list of URL's.
 *
 * @since 1.0
 *
 * @param string|array $urls A single URL or list of URL's
 * @param array $params (optional)
 *		'embed' => true (default)|false - should the URL be automatically embed or simply outputed?
 */
function appthemes_output_embed_urls( $urls, $params = array(), $echo = true ) {

	$defaults = array(
		'embed' => true,
	);
	$params = wp_parse_args( $params, $defaults );

	extract( $params );

	if ( empty( $urls ) ) {
		return;
	}

	$embeds = '';

	if ( ! $echo ) {
		ob_start();
	}

	foreach( (array) $urls as $url ) {
		$url = trim( $url );

		echo html( 'br', '&nbsp;' );

		echo appthemes_get_oembed_url( $url );

		if ( ! $echo ) {
			$embeds .= ob_get_clean();
		}
	}

	if ( ! empty( $embeds ) ) {
		return $embeds;
	}
}

/**
 * Attempts to fetch an embed HTML for a provided URL using oEmbed.
 *
 * @since 1.0
 *
 * @param type $url The embed URL
 * @param type $embed (optional) Should the URL be returned as a static URL or attempts to fetch the embed HTML for a provided URL using oEmbed
 * @return string The oEmbed URL on success or the URL passed in the first parameter on failure
 */
function appthemes_get_oembed_url( $url, $embed = true ) {

	if ( $embed ) {

		$oembed = wp_oembed_get( $url );
		if ( $oembed ) {
			$output = $oembed;
		} else {
			$output = $url;
		}

	} else {
		$output = $url;
	}

	return $output;
}

/**
 * Attempts to fetch an oembed object with metadata for a provided URL using oEmbed.
 *
 * @since 1.0
 *
 * @param string $url The URL to fetch the data from.
 * @return bool|string False on failure or the oembed object on success.
 */
function appthemes_get_ombed_object( $url ) {
	return APP_Media_Manager::get_oembed_object( $url );
}

/**
 * Queries the database for media manager attachments.
 * Uses the meta key '_app_attachment_type' to filter the available attachment types: gallery | file | embed
 *
 * @since 1.0
 *
 * @param int $post_id	The listing ID
 * @param array $filters (optional) Params to be used to filter the attachments query
 */
function appthemes_get_post_attachments( $post_id, $filters = array() ) {

	if ( ! $post_id ) {
		return array();
	}

	$defaults = array(
		'file_limit' => -1,
		'meta_type'	 => APP_ATTACHMENT_FILE,
		'mime_types' => '',
	);
	$filters = wp_parse_args( $filters, $defaults );

	extract( $filters );

	return get_posts( array(
		'post_type' 		=> 'attachment',
		'post_status' 		=> 'inherit',
		'post_parent' 		=> $post_id,
		'posts_per_page' 	=> $file_limit,
		'post_mime_type'	=> $mime_types,
		'orderby' 			=> 'menu_order',
		'order' 			=> 'asc',
		'meta_key'			=> '_app_attachment_type',
		'meta_value'		=> $meta_type,
		'fields'			=> 'ids',
	) );
}

/**
 * Collects and returns the meta info for a specific attachment ID.
 *
 * Meta retrieved: title, alt, url, mime type, file size
 *
 * @since 1.0
 *
 * @param int $attachment_id  The attachment ID
 * @return array Retrieves the attachment meta
 */
function appthemes_get_attachment_meta( $attachment_id ) {
	$filename = wp_get_attachment_url( $attachment_id );

	$title = trim( strip_tags( get_the_title( $attachment_id ) ) );
	$size = size_format( filesize( get_attached_file( $attachment_id ) ), 2 );
	$basename = basename( $filename );

	$meta = array (
		'title'     => ( ! $title ? $basename : $title ),
		'alt'       => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		'url'       => $filename,
		'mime_type' => get_post_mime_type( $attachment_id ),
		'size'      => $size,
	);
	return $meta;
}

/**
 * Retrieves the CSS class that should be used for a specific mime type icon.
 *
 * @since 1.0
 *
 * @uses apply_filters() Calls 'appthemes_mime_type_icon'
 *
 * @param string $mime_type
 * @return string The mime type icon CSS class
 */
function appthemes_get_mime_type_icon_class( $mime_type ) {

	if ( ! $mime_type ) {
		$mime_type = 'generic';
	}

	$file_ext_ico = array(
		'pdf'          => 'file-pdf',
		'msword'       => 'file-word',
		'vnd.ms-excel' => 'file-excel',
		'csv'          => 'file-excel',
		'image'        => 'file-image',
		'video'        => 'file-video',
		'audio'        => 'file-audio',
		'other'        => 'file-other',
	);

	$mime_type = explode( '/' , $mime_type );

	if ( is_array( $mime_type ) ) {
		// Simplify the mime match for image types by using the 'image' part (i.e: image/png, image/jpg, etc).
		if ( in_array( $mime_type[0], array( 'video', 'audio', 'image' ) ) ) {
			$mime_type = $mime_type[0];
		} elseif( ! empty( $mime_type[1]) ) {
			$mime_type = $mime_type[1];
		} else {
			$mime_type = $mime_type[0];
		}

	}

	if ( ! isset( $file_ext_ico[ $mime_type ] ) ) {
		$mime_type = 'other';
	}
	return apply_filters( 'appthemes_mime_type_icon', $file_ext_ico[ $mime_type ], $mime_type );
}

/**
 * Compares full/partial mime types or file extensions and tries to retrieve a list of related mime types.
 *
 * examples:
 * 'image'	=> 'image/png', 'image/gif', etc
 * 'pdf'	=> 'application/pdf'
 *
 * @since 1.0
 *
 * @param mixed $mime_types_ext The full/partial mime type or file extension to search
 * @return array The list of mime types if found, or an empty array
 */
function appthemes_get_mime_types_for( $mime_types_ext ) {

	$normalized_mime_types = array();

	$all_mime_types = wp_get_mime_types();

	// Sanitize the file extensions/mime types.
	$mime_types_ext = array_map( 'trim', (array) $mime_types_ext );
	$mime_types_ext = preg_replace( "/[^a-z\/]/i", '', $mime_types_ext );

	foreach( $mime_types_ext as $mime_type_ext ) {

		if ( isset( $all_mime_types[ $mime_type_ext ] ) ) {
			$normalized_mime_types[] = $all_mime_types[ $mime_type_ext ];
		} elseif( in_array( $mime_type_ext, $all_mime_types ) ) {
			$normalized_mime_types[] = $mime_type_ext;
		} else {

			// Try to get the full mime type from extension (e.g.: png, .jpg, etc ) or mime type parts (e.g.: image, application).
			foreach ( $all_mime_types as $exts => $mime ) {
				$mime_parts = explode( '/', $mime );

				if ( preg_match( "!({$exts})$|({$mime_parts[0]})!i", $mime_type_ext ) ) {
					$normalized_mime_types[] = $mime;
				}
			}
		}
	}
	return $normalized_mime_types;
}

/**
 * Retrieves all the attributes and filters set for a specific media manager ID.
 *
 * @since 1.0
 *
 * @param string $mm_id The media manager ID to retrieve the options from.
 * @return array An associative array with all the options for the media manager.
 */
function appthemes_get_media_manager_options( $mm_id ) {
	return get_option( "app_media_manager_{$mm_id}" );
}

/**
 * Retrieves the currently active (opened) media manager ID.
 *
 * @since 1.0
 *
 * @return string The media manager ID.
 */
function appthemes_get_active_media_manager( $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();
	return get_transient('app_media_manager_id_'.$user_id);
}

/**
 * Retrieves allowed attachments meta types.
 *
 * @since 1.0
 *
 * @uses apply_filters() Calls 'appthemes_mm_allowed_meta_types'
 * @uses apply_filters() Calls 'appthemes_mm_allowed_file_meta_types'
 * @uses apply_filters() Calls 'appthemes_mm_allowed_embed_meta_types'
 *
 * @param string $type The attachment type: 'file' or 'embed', or all types, if empty
 */
function appthemes_get_mm_allowed_meta_types( $type = '' ) {

	$meta_types = array(
		'file' => array( APP_ATTACHMENT_FILE, APP_ATTACHMENT_GALLERY ),
		'embed' => array( APP_ATTACHMENT_EMBED ),
	);

	if ( empty( $type ) ) {
		$meta_types = array_merge( $meta_types['file'], $meta_types['embed'] );
	} elseif ( empty( $meta_types[ $type ] ) ) {
		$meta_types = $meta_types['file'];
	} else {
		$meta_types = $meta_types[ $type ];
		$type = '_' . $type;
	}

	return apply_filters( "appthemes_mm_allowed{$type}_meta_types", $meta_types );
}
