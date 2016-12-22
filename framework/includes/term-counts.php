<?php
/**
 * Term Counts
 *
 * @package Framework\Term-Counts
 */

add_filter( 'get_terms', '_appthemes_pad_term_counts', 10, 3 );
add_action( 'edited_term_taxonomy', '_appthemes_update_post_term_count', 10, 2 );

/**
 * Add count of children to parent count.
 * Recalculates term counts by including items from child terms.
 * Assumes all relevant children are already in the $terms argument.
 *
 * @param array $terms
 * @param array $taxonomies
 * @param array $args
 *
 * @return array Terms
 */
function _appthemes_pad_term_counts( $terms, $taxonomies, $args ) {
	global $wpdb;

	if ( ! current_theme_supports( 'app-term-counts' ) ) {
		return $terms;
	}

	if ( ! isset( $args['app_pad_counts'] ) || ! $args['app_pad_counts'] || ! is_array( $terms ) ) {
		return $terms;
	}

	$taxonomy = $taxonomies[0];
	if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
		return $terms;
	}

	// we probably no need check for term_hierarchy since it doesn't affect
	// anything, but prevents correct child-less parent term counting
	//$term_hier = _get_term_hierarchy( $taxonomy );
	//if ( empty( $term_hier ) ) {
		//return $terms;
	//}

	$options = _appthemes_get_term_count_args();

	$key = md5( serialize( $args ) . serialize( $taxonomies ) );
	$last_changed = wp_cache_get( 'last_changed', 'app_terms' );
	if ( ! $last_changed ) {
		$last_changed = time();
		wp_cache_set( 'last_changed', $last_changed, 'app_terms' );
	}
	$cache_key = "app_get_terms:$key:$last_changed";
	$cache = wp_cache_get( $cache_key, 'app_terms' );
	if ( false !== $cache ) {
		return $cache;
	}

	$term_items = array();

	$term_ids = array();
	$terms_by_id = array();

	foreach ( (array) $terms as $key => $term ) {
		$terms_by_id[ $term->term_id ] = & $terms[ $key ];
		$term_ids[ $term->term_taxonomy_id ] = $term->term_id;
	}

	$post_types = esc_sql( $options['post_type'] );
	$post_statuses = esc_sql( $options['post_status'] );

	if ( $term_ids && $post_types && $post_statuses ) {
		$results = $wpdb->get_results( "SELECT object_id, term_taxonomy_id FROM $wpdb->term_relationships INNER JOIN $wpdb->posts ON object_id = ID WHERE term_taxonomy_id IN (" . implode( ',', array_keys( $term_ids ) ) . ") AND post_type IN ('" . implode( "', '", $post_types ) . "') AND post_status IN ('" . implode( "', '", $post_statuses ) . "') ");
	} else {
		$results = array();
	}

	foreach ( $results as $row ) {
		$id = $term_ids[ $row->term_taxonomy_id ];
		$term_items[ $id ][ $row->object_id ] = isset( $term_items[ $id ][ $row->object_id ] ) ? ++$term_items[ $id ][ $row->object_id ] : 1;
	}

	// Touch every ancestor's lookup row for each post in each term.
	foreach ( $term_ids as $term_id ) {
		$child = $term_id;
		while ( ! empty( $terms_by_id[ $child ] ) && $parent = $terms_by_id[ $child ]->parent ) {
			if ( ! empty( $term_items[ $term_id ] ) ) {
				foreach ( $term_items[ $term_id ] as $item_id => $touches ) {
					$term_items[ $parent ][ $item_id ] = isset( $term_items[ $parent ][ $item_id ] ) ? ++$term_items[ $parent ][ $item_id ]: 1;
				}
			}
			$child = $parent;
		}
	}

	// Transfer the touched cells, remove empty if need or set their count to 0.
	foreach ( $terms_by_id as $id => $term_by_id ) {
		if ( isset( $term_items[ $id ] ) || ! $args['hide_empty'] ) {
			$terms_by_id[ $id ]->count = ( isset( $term_items[ $id ] ) ) ? count( $term_items[ $id ] ) : 0;
		} else {
			unset( $terms_by_id[ $id ] );
		}
	}

	wp_cache_add( $cache_key, $terms_by_id, 'app_terms', 86400 ); // One day.

	return $terms_by_id;
}


/**
 * Updates post term count.
 *
 * @param int|array $term
 * @param object|string $taxonomy
 *
 * @return void
 */
function _appthemes_update_post_term_count( $term, $taxonomy ) {
	global $wpdb;

	if ( ! current_theme_supports( 'app-term-counts' ) ) {
		return;
	}

	// args passed to this function are inconsistent
	if ( is_array( $term ) ) {
		foreach ( $term as $term_id ) {
			_appthemes_update_post_term_count( $term_id, $taxonomy );
		}
		return;
	}
	if ( is_object( $taxonomy ) ) {
		$taxonomy = $taxonomy->name;
	}

	$options = _appthemes_get_term_count_args();

	$post_types = esc_sql( $options['post_type'] );
	$post_statuses = esc_sql( $options['post_status'] );

	if ( in_array( $taxonomy, $options['taxonomy'] ) ) {
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships INNER JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->term_relationships.object_id WHERE $wpdb->posts.post_type IN ('" . implode( "', '", $post_types ) . "') AND $wpdb->posts.post_status IN ('" . implode( "', '", $post_statuses ) . "') AND $wpdb->term_relationships.term_taxonomy_id = %d", $term ) );
		$wpdb->update( $wpdb->term_taxonomy, array( 'count' => $count ), array( 'term_taxonomy_id' => $term ) );
	}

}


/**
 * Returns term count args.
 *
 * @param string $option (optional)
 *
 * @return mixed
 */
function _appthemes_get_term_count_args( $option = '' ) {

	static $args = array();

	if ( ! current_theme_supports( 'app-term-counts' ) ) {
		return array();
	}

	if ( empty( $args ) ) {

		$args_sets = get_theme_support( 'app-term-counts' );

		if ( ! is_array( $args_sets ) ) {
			$args_sets = array();
		}

		foreach ( $args_sets as $args_set ) {
			foreach ( $args_set as $key => $arg ) {
				if ( ! isset( $args[ $key ] ) ) {
					$args[ $key ] = $arg;
				} elseif ( is_array( $arg ) ) {
					$args[ $key ] = array_merge_recursive( (array) $args[ $key ], $arg );
				}
			}
		}

		$defaults = array(
			'post_type' => array(),
			'post_status' => array(),
			'taxonomy' => array(),
		);

		$args = wp_parse_args( $args, $defaults );

	}

	if ( empty( $option ) ) {
		return $args;
	} else {
		return $args[ $option ];
	}
}
