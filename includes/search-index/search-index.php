<?php
/**
 * Search Index
 *
 * @package Search-Index
 */

if ( ! defined( 'APP_SEARCH_INDEX_OPTION' ) ) {
	define( 'APP_SEARCH_INDEX_OPTION', 'appthemes_search_index' );
}

if ( is_admin() ) {
	require_once( dirname( __FILE__ ) . '/class-search-index.php' );
	add_action( 'save_post', array( 'APP_Search_Index', 'save_post' ), 100, 2 );
}

class APP_Build_Search_Index {

	private $args;

	public function __construct( $args = array() ) {

		if ( appthemes_get_search_index_status() || isset( $_GET['firstrun'] ) ) {
			return;
		}

		if ( is_admin() && isset( $_GET['tab'] ) && 'search_index' == $_GET['tab'] ) {
			return;
		}

		$defaults = array(
			'limit' => 10,
		);

		$this->args = wp_parse_args( $args, $defaults );

		$this->args['limit'] = apply_filters( 'appthemes_build_search_index_limit', $this->args['limit'] );

		$this->args['post_type'] = APP_SEARCH_INDEX::get_registered_post_types();
		if ( empty( $this->args['post_type'] ) ) {
			return;
		}

		if ( ! defined( 'DOING_AJAX' ) ) {
			add_action( 'init', array( $this, 'process' ), 101 );
		}

		add_filter( 'posts_clauses', array( $this, 'filter_before' ), 10, 2 );

		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'progress' ) );
		}
	}

	public function progress() {
		global $pagenow;

		if ( $pagenow !== 'admin.php' || ( empty( $_GET['page'] ) || ! in_array( $_GET['page'], array( 'app-system-info', 'app-settings' ) ) ) ) {
			if ( ! isset( $_GET['appthemes_build_search_index_progress_notice'] ) ) {
				return;
			}
		}

		$total = count( $this->get_items( array(
			'nopaging' => true,
			'fields' => 'ids',
		) ) );

		$indexed = count( $this->get_items( array(
			'nopaging' => true,
			'fields' => 'ids',
			'app_search_index_is_empty' => 0,
		) ) );

		if ( $total - $indexed <= 0 ) {
			return $this->conclude_bulk_update();
		}

		$percent_complete = round( ( ( $indexed / $total ) * 100 ), 2 );
		echo scb_admin_notice( html( 'strong', sprintf( __( 'Search Index Update Progress: %g%s complete.', APP_TD ), $percent_complete, '&#37;' ) ) );
	}

	private function conclude_bulk_update() {
		update_option( APP_SEARCH_INDEX_OPTION, 1 );
	}

	public function filter_before( $clauses, $query ) {
		$search_index_empty = $query->get( 'app_search_index_is_empty' );

		if ( isset( $query->query_vars['app_search_index_is_empty'] ) ) {
			if ( 1 == $query->query_vars['app_search_index_is_empty'] ) {
				$clauses['where'] .= " AND ( post_content_filtered = '' ) ";
			} else {
				$clauses['where'] .= " AND ( post_content_filtered != '' ) ";
			}
		}

		return $clauses;
	}

	public function process() {
		$items_processed = 0;

		$items = $this->get_items( array(
			'app_search_index_is_empty' => 1,
			'showposts' => $this->args['limit'],
		) );

		if ( empty( $items ) ) {
			$this->conclude_bulk_update();
			return $items_processed;
		}

		foreach ( $items as $item ) {
			$this->process_item( $item );
			$items_processed += 1;
		}

		return $items_processed;
	}

	public function get_items( $args = array() ) {
		$defaults = array(
			'post_type' => $this->args['post_type'],
			'post_status' => 'any',
			'cache_results' => false,
			'no_found_rows' => true,
		);

		$args = wp_parse_args( $defaults, $args );
		$items = new WP_Query( $args );

		return isset( $items->posts ) ? $items->posts : array();
	}

	public function process_item( $item ) {
		appthemes_update_search_index( $item, false );
	}
}


class APP_Search_Index {

	private static $post_types = array();

	public static function register( $post_type, $args = array() ) {
		$defaults = array(
			'post_fields' => array( 'post_title' => true, 'post_content' => true ),
			'meta_keys' => array(),
			'taxonomies' => array(),
		);
		$args = wp_parse_args( $args, $defaults );
		self::$post_types[ $post_type ] = apply_filters( 'appthemes_search_index_register_post_type', $args, $post_type );
	}

	public static function save_post( $post_id, $post ) {
		if ( ! in_array( $post->post_type, self::get_registered_post_types() ) ) {
			return;
		}

		self::update_search_index( $post, false );
	}

	public static function update_search_index( $post, $return_updated_post = true ) {

		if ( $post instanceof WP_Post ) {
			$post_id = $post->ID;
		} elseif ( is_numeric( $post ) ) {
			$post_id = $post;
			$post = get_post( $post_id );
		} else {
			return false;
		}

		$post_type = $post->post_type;
		$args = self::$post_types[ $post_type ];

		$args = apply_filters( 'appthemes_update_search_index_' . $post_type, $args, $post );

		$index_array = array();

		if ( isset( $args['post_fields']['post_title'] ) ) {
			$index_array[] = $post->post_title;

			if ( false !== strpos( $post->post_title, "'" ) ) {
				$index_array[] = str_replace( "'", "", $post->post_title );
			}

		}

		if ( isset( $args['post_fields']['post_content'] ) ) {
			$content = wp_strip_all_tags( $post->post_content, true );
			$content = strip_shortcodes( $content );
			$index_array[] = $content;
		}

		if ( ! empty( $args['meta_keys'] ) ) {
			foreach ( $args['meta_keys'] as $meta_key ) {
				$meta_values = get_post_meta( $post->ID, $meta_key );

				if ( ! empty( $meta_values ) ) {
					foreach ( $meta_values as $meta_value ) {
						$index_array[] = $meta_value;
					}
				}
			}
		}

		if ( ! empty( $args['taxonomies'] ) ) {
			$terms = wp_get_object_terms( $post->ID, $args['taxonomies'] );

			foreach ( $terms as $term ) {
				$index_array[] = $term->name;
			}
		}

		$index_array = apply_filters( 'appthemes_update_search_index_array_' . $post_type, $index_array, $args, $post );

		$index_array = array_map( 'trim', $index_array );

		foreach ( $index_array as $k => $v ) {
			if ( empty( $v ) ) {
				unset( $index_array[ $k ] );
			}
		}

		$index_array = array_unique( $index_array );

		$index_string = implode( ', ', $index_array );

		self::save_search_index( $post->ID, $index_string );
		return $return_updated_post ? get_post( $post->ID ) : true;
	}

	public static function save_search_index( $post_id, $index_string = '' ) {
		global $wpdb;

		$wpdb->update( $wpdb->posts, array( 'post_content_filtered' => $index_string ), array( 'ID' => $post_id ) );
	}

	public static function get_registered_post_types() {
		return array_keys( self::$post_types );
	}
}


function appthemes_search_index_get_args( $option = '' ) {

	static $args = array();

	if( ! current_theme_supports( 'app-search-index' ) ) {
		return array();
	}

	if ( empty( $args ) ) {

		$args_sets = get_theme_support( 'app-search-index' );

		if ( ! is_array( $args_sets ) ) {
			$args_sets = array();
		}

		foreach ( $args_sets as $args_set ) {
			foreach ( $args_set as $key => $arg ) {
				if ( ! isset( $args[ $key ] ) ) {
					$args[ $key ] = $arg;
				} elseif ( 'admin_page' === $key && $arg ) {
					$args[ $key ] = true;
				}
			}
		}

		$defaults = array(
			'admin_page' => false,
			'admin_top_level_page' => false,
			'admin_sub_level_page' => false,
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


function appthemes_update_search_index( $post, $return_updated_post = true ) {
	return APP_Search_Index::update_search_index( $post, $return_updated_post );
}


function appthemes_get_search_index_status() {
	return get_option( APP_SEARCH_INDEX_OPTION, 0 );
}
