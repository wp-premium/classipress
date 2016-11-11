<?php
/**
 * Comment Counts
 *
 * @package Framework\Comment-Counts
 */

add_action( 'wp_update_comment_count', '_appthemes_update_comment_count', 10, 3 );

/**
 * Updates the comment count for the post.
 * Recalculates comment count by excluding specified comment types, eg. report, review.
 *
 * @param int $post_id Post ID
 * @param int $new New comment count
 * @param int $old Previous comment count
 *
 * @return void
 */
function _appthemes_update_comment_count( $post_id, $new, $old ) {
	global $wpdb;

	if ( ! current_theme_supports( 'app-comment-counts' ) ) {
		return;
	}

	$args_sets = get_theme_support( 'app-comment-counts' );
	$options = array( 'exclude_type' => array() );

	if ( ! is_array( $args_sets ) ) {
		$args_sets = array();
	}

	foreach ( $args_sets as $args_set ) {
		foreach ( $args_set as $key => $arg ) {
			if ( ! isset( $options[ $key ] ) ) {
				$options[ $key ] = $arg;
			} elseif ( is_array( $arg ) ) {
				$options[ $key ] = array_merge_recursive( (array) $options[ $key ], $arg );
			}
		}
	}

	$exclude_types = apply_filters( 'appthemes_ctypes_count_exclude', $options['exclude_type'] );

	if ( empty( $exclude_types ) || ! is_array( $exclude_types ) ) {
		return;
	}

	$post = get_post( $post_id );

	$exclude_types = esc_sql( $exclude_types );

	$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved = '1' AND comment_type NOT IN ( '" . implode( "', '", $exclude_types ) . "' )", $post_id ) );
	$wpdb->update( $wpdb->posts, array( 'comment_count' => $count ), array( 'ID' => $post_id ) );

	clean_post_cache( $post );
}

