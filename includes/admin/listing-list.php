<?php
/**
 * Admin Listings lists.
 *
 * @package ClassiPress\Admin\Listings
 * @author  AppThemes
 * @since   ClassiPress 3.4
 */


// Ad Listing
add_filter( 'manage_' . APP_POST_TYPE . '_posts_columns', 'cp_ad_listing_manage_columns' );
add_filter( 'manage_edit-' . APP_POST_TYPE . '_sortable_columns', 'cp_ad_listing_manage_sortable_columns' );
add_filter( 'request', 'cp_ad_listing_manage_columns_orderby' );
add_action( 'manage_' . APP_POST_TYPE . '_posts_custom_column', 'cp_ad_listing_add_column_data', 10, 2 );
add_action( 'quick_edit_custom_box', 'cp_sticky_option_quick_edit' );

// Thumbnail for Ad Listings & Posts
add_filter( 'manage_post_posts_columns', 'cp_thumbnail_column', 11 );
add_filter( 'manage_' . APP_POST_TYPE . '_posts_columns', 'cp_thumbnail_column', 11 );
add_action( 'manage_post_posts_custom_column', 'cp_thumbnail_value', 11, 2 );
add_action( 'manage_' . APP_POST_TYPE . '_posts_custom_column', 'cp_thumbnail_value', 11, 2 );


/**
 * Modifies columns on admin ad listing page.
 *
 * @param array $columns
 *
 * @return array
 */
function cp_ad_listing_manage_columns( $columns ) {

	// Remove to change order of columns
	unset( $columns['comments'] );
	unset( $columns['date'] );

	$columns['title'] = __( 'Title', APP_TD );
	$columns['author'] = __( 'Author', APP_TD );
	$columns['taxonomy-' . APP_TAX_CAT ] = __( 'Category', APP_TD );
	$columns['taxonomy-' . APP_TAX_TAG ] = __( 'Tags', APP_TD );
	$columns['cp_price'] = __( 'Price', APP_TD );
	$columns['cp_daily_count'] = __( 'Views Today', APP_TD );
	$columns['cp_total_count'] = __( 'Views Total', APP_TD );
	$columns['cp_sys_expire_date'] = __( 'Expires', APP_TD );
	$columns['comments'] = '<div class="vers"><img src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" /></div>';
	$columns['date'] = __( 'Date', APP_TD );

	return $columns;
}


/**
 * Registers columns as sortable on admin ad listing page.
 *
 * @param array $columns
 *
 * @return array
 */
function cp_ad_listing_manage_sortable_columns( $columns ) {

	$columns['cp_price'] = 'cp_price';
	$columns['cp_daily_count'] = 'cp_daily_count';
	$columns['cp_total_count'] = 'cp_total_count';
	$columns['cp_sys_expire_date'] = 'cp_sys_expire_date';

	return $columns;
}


/**
 * Sets how the columns sorting should work on admin ad listing page.
 *
 * @param array $vars
 *
 * @return array
 */
function cp_ad_listing_manage_columns_orderby( $vars ) {

	if ( isset( $vars['orderby'] ) && 'cp_price' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => 'cp_price',
			'orderby' => 'meta_value_num',
		) );
	}

	if ( isset( $vars['orderby'] ) && 'cp_daily_count' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => 'cp_daily_count',
			'orderby' => 'meta_value_num',
		) );
	}

	if ( isset( $vars['orderby'] ) && 'cp_total_count' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => 'cp_total_count',
			'orderby' => 'meta_value_num',
		) );
	}

	return $vars;
}


/**
 * Displays ad listing custom columns data.
 *
 * @param string $column_index
 * @param int $post_id
 *
 * @return void
 */
function cp_ad_listing_add_column_data( $column_index, $post_id ) {

	$post = get_post( $post_id );
	if ( ! $post ) {
		return;
	}

	$post_meta = get_post_custom( $post_id );

	switch ( $column_index ) {

		case 'cp_sys_expire_date':
			if ( isset( $post_meta['cp_sys_expire_date'][0] ) && ! empty( $post_meta['cp_sys_expire_date'][0] ) ) {
				echo appthemes_display_date( $post_meta['cp_sys_expire_date'][0] );
			}
			break;

		case 'cp_price':
			cp_get_price( $post->ID, 'cp_price' );
			break;

		case 'cp_daily_count':
			if ( isset( $post_meta['cp_daily_count'][0] ) && ! empty( $post_meta['cp_daily_count'][0] ) ) {
				echo $post_meta['cp_daily_count'][0];
			}
			break;

		case 'cp_total_count':
			if ( isset( $post_meta['cp_total_count'][0] ) && ! empty( $post_meta['cp_total_count'][0] ) ) {
				echo $post_meta['cp_total_count'][0];
			}
			break;

	}
}


/**
 * Adds the sticky option to the quick edit area.
 *
 * @return void
 */
function cp_sticky_option_quick_edit() {
	global $post;

	// if post is a custom post type and only during the first execution of the action quick_edit_custom_box
	if ( $post->post_type != APP_POST_TYPE || did_action( 'quick_edit_custom_box' ) !== 1 ) {
		return;
	}
?>
	<fieldset class="inline-edit-col-right">
		<div class="inline-edit-col">
			<label class="alignleft">
				<input type="checkbox" name="sticky" value="sticky" />
				<span class="checkbox-title"><?php _e( 'Featured Ad (sticky)', APP_TD ); ?></span>
			</label>
		</div>
	</fieldset>
<?php
}


/**
 * Adds thumbnail column on admin ad listing & posts page.
 *
 * @param array $columns
 *
 * @return array
 */
function cp_thumbnail_column( $columns ) {

	$columns['thumbnail'] = __( 'Image', APP_TD );

	return $columns;
}


/**
 * Displays thumbnail custom column data.
 *
 * @param string $column_index
 * @param int $post_id
 *
 * @return void
 */
function cp_thumbnail_value( $column_index, $post_id ) {
	$thumb = false;
	$width = 50;
	$height = 50;

	if ( 'thumbnail' == $column_index ) {
		// Thumbnail of WP 2.9.
		$thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );

		if ( $thumbnail_id ) {
			$thumb = wp_get_attachment_image( $thumbnail_id, array( $width, $height ), true );
		} else {

			$attachment_ids = get_post_meta( $post_id, '_app_media', true );

			// Image from gallery.
			$attachments = get_children( array( 'post_parent' => $post_id, 'numberposts' => 1, 'post_type' => 'attachment', 'post__in' => $attachment_ids, 'post_mime_type' => 'image', 'orderby' => 'post__in', 'order' => 'ASC', ) );

			if ( ! empty( $attachments ) ) {
				$image = array_shift( $attachments );
				$thumb = wp_get_attachment_image( $image->ID, array( $width, $height ), true );
			}

		}

		if ( $thumb ) {
			echo $thumb;
		}
	}
}


