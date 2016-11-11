<?php
/**
 * Categories.
 *
 * @package ClassiPress\Categories
 * @author  AppThemes
 * @since   ClassiPress 3.4
 */


add_action( 'wp_ajax_nopriv_dropdown-child-categories', 'cp_addnew_dropdown_child_categories' );
add_action( 'wp_ajax_dropdown-child-categories', 'cp_addnew_dropdown_child_categories' );

add_action( 'edit_term', 'cp_edit_term_delete_transient' );


/**
 * Returns categories list.
 *
 * @param string $location (optional)
 *
 * @return string
 */
function cp_create_categories_list( $location = 'menu' ) {
	global $cp_options;

	$prefix = 'cat_' . $location . '_';

	$args['menu_cols'] = ( $location == 'menu' ? 3 : $cp_options->{$prefix . 'cols'} );
	$args['menu_depth'] = $cp_options->{$prefix . 'depth'};
	$args['menu_sub_num'] = $cp_options->{$prefix . 'sub_num'};
	$args['cat_parent_count'] = $cp_options->{$prefix . 'count'};
	$args['cat_child_count'] = $cp_options->{$prefix . 'count'};
	$args['cat_hide_empty'] = $cp_options->{$prefix . 'hide_empty'};
	$args['cat_nocatstext'] = true;
	$args['cat_order'] = 'ASC';
	$args['taxonomy'] = APP_TAX_CAT;

	return appthemes_categories_list( $args );
}


/**
 * Returns args for search categories dropdown.
 *
 * @param string $location (optional)
 *
 * @return array
 */
function cp_get_dropdown_categories_search_args( $location = 'bar' ) {
	global $cp_options;

	$defaults = array(
		'show_option_all' => __( 'All Categories', APP_TD ),
		'hierarchical' => $cp_options->cat_hierarchy,
		'hide_empty' => $cp_options->cat_hide_empty,
		'depth' => $cp_options->search_depth,
		'show_count' => $cp_options->cat_count,
		'pad_counts' => $cp_options->cat_count,
		'orderby' => 'name',
		'title_li' => '',
		'use_desc_for_title' => 1,
		'name' => 'scat',
		'selected' => cp_get_search_catid(),
		'taxonomy' => APP_TAX_CAT,
	);

	if ( $location == 'bar' ) {
		$defaults['class'] = 'searchbar';
		$defaults['tab_index'] = '2';
	}

	$location = sanitize_title_with_dashes( $location );
	$args = apply_filters( 'cp_dropdown_search_' . $location . '_args', $defaults );

	return $args;
}


/**
 * Displays dropdown list of categories with prices.
 *
 * @param array $args (optional)
 *
 * @return string
 */
if ( ! function_exists( 'cp_dropdown_categories_prices' ) ) {
	function cp_dropdown_categories_prices( $args = '' ) {
		$defaults = array(
			'show_option_all'  => '',
			'show_option_none' => __( 'Select one', APP_TD ),
			'orderby'          => 'name',
			'order'            => 'ASC',
			'show_last_update' => 0,
			'show_count'       => 0,
			'hide_empty'       => 0,
			'child_of'         => 0,
			'exclude'          => '',
			'echo'             => 1,
			'selected'         => 0,
			'hierarchical'     => 1,
			'name'             => 'cat',
			'id'               => 'ad_cat_id',
			'class'            => 'dropdownlist',
			'depth'            => 1,
			'tab_index'        => 0,
			'taxonomy'         => APP_TAX_CAT,
			// custom
			'listing_id'       => 0,
			'level'            => 0,
		);

		$defaults['selected'] = ( is_category() ) ? get_query_var( 'cat' ) : 0;

		$r = wp_parse_args( $args, $defaults );

		// The listing ID, if available, is passed by the sub-categories ajax callback.
		$listing_id = (int) $r['listing_id'];
		unset( $r['listing_id'] );

		if ( empty( $r['selected'] ) ) {

			if ( ! empty( $_GET['action'] ) && 'change' === $_GET['action'] ) {
				$checkout = appthemes_get_checkout();
				$listing_id = $checkout ? $checkout->get_data( 'listing_id' ) : false;
			} elseif( ! empty( $_GET['listing_renew'] ) ) {
				$listing_id = (int) $_GET['listing_renew'];
			}

			// Auto select the parent child category if available.
			if ( $listing_id ) {
				$listing = get_post( $listing_id );
				$categories = wp_get_post_terms( $listing->ID, APP_TAX_CAT );

				$parent = $categories[0]->parent;

				if ( empty( $parent ) ) {

					$r['selected'] = $categories[0]->term_id;

				} else {

					$terms[0] = $categories[0];

					$levels = 1;

					while ( $parent > 0 ) {
						$terms[ $levels ] = get_term( $parent, APP_TAX_CAT );
						$parent = $terms[ $levels ]->parent;
						$levels++;
					}
					$terms = array_reverse( $terms );

					if ( ! empty( $terms[ $r['level'] ] ) ) {
						$r['selected'] = $terms[ $r['level'] ]->term_id;
					}

				}

			}
		}

		$r['include_last_update_time'] = $r['show_last_update'];
		extract( $r );

		$tab_index_attribute = '';
		if ( (int) $tab_index > 0 ) {
			$tab_index_attribute = " tabindex=\"$tab_index\"";
		}

		// TODO: remove dirty fix, consider to use 2 parameters array: one for
		// get_categories() another for cp_category_dropdown_tree()
		unset( $r['name'] );
		$categories = get_categories( $r );
		$name = esc_attr( $name );
		$r['name'] = $name;
		$class = esc_attr( $class );
		$id = $id ? esc_attr( $id ) : $name;

		$output = '';
		if ( ! empty( $categories ) ) {
			$output = "<select name='$name' id='$id' class='$class' $tab_index_attribute>\n";

			if ( $show_option_all ) {
				$show_option_all = apply_filters( 'list_cats', $show_option_all );
				$selected = ( '0' === strval( $r['selected'] ) ) ? " selected='selected'" : '';
				$output .= "\t<option value='0'$selected>$show_option_all</option>\n";
			}

			if ( $show_option_none ) {
				$show_option_none = apply_filters( 'list_cats', $show_option_none );
				$selected = ( '-1' === strval( $r['selected'] ) ) ? " selected='selected'" : '';
				$output .= "\t<option value='-1'$selected>$show_option_none</option>\n";
			}

			if ( $hierarchical ) {
				$depth = $r['depth']; // Walk the full depth.
			} else {
				$depth = -1; // Flat.
			}

			$output .= cp_category_dropdown_tree( $categories, $depth, $r );
			$output .= "</select>\n";
		}

		$output = apply_filters( 'wp_dropdown_cats', $output );

		if ( $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}
}


/**
 * Helper function for generating dropdown list of categories with prices.
 * Determines when the price should be displayed.
 *
 * @param object $category
 * @param array $args
 *
 * @return string
 */
function cp_category_dropdown_price( $category, $args ) {
	global $cp_options;

	if ( $cp_options->price_scheme != 'category' || ! cp_payments_is_enabled() ) {
		return '';
	}

	if ( $cp_options->ad_parent_posting == 'no' && $category->parent == 0 ) {
		return '';
	}

	if ( $cp_options->ad_parent_posting == 'whenEmpty' && $category->parent == 0 ) {
		$child_terms = get_terms( $args['taxonomy'], array( 'parent' => $category->term_id, 'number' => 1, 'hide_empty' => 0 ) );
		if ( ! empty( $child_terms ) ) {
			return '';
		}
	}

	$prices = $cp_options->price_per_cat;
	$cat_price = ( isset( $prices[ $category->term_id ] ) ) ? (float) $prices[ $category->term_id ] : 0;

	return ' - ' . appthemes_get_price( $cat_price );
}


/**
 * Helper function for cp_dropdown_categories_prices()
 *
 * @return string
 */
function cp_category_dropdown_tree() {
	$args = func_get_args();

	if ( empty( $args[2]['walker'] ) || ! is_a( $args[2]['walker'], 'Walker' ) ) {
		$walker = new cp_CategoryDropdown;
	} else {
		$walker = $args[2]['walker'];
	}

	return call_user_func_array( array( &$walker, 'walk' ), $args );
}


/**
 * Walker class for generating dropdown list of caregories with prices.
 */
class cp_CategoryDropdown extends Walker {
	var $tree_type = 'category';
	var $db_fields = array( 'parent' => 'parent', 'id' => 'term_id' );

	function start_el( &$output, $category, $depth = 0, $args = array(), $current_object_id = 0 ) {
		global $cp_options;

		$pad = str_repeat( '&nbsp;', $depth * 3 );
		$cat_name = apply_filters( 'list_cats', $category->name, $category );

		// dont display terms without children when parent category posting is disabled
		if ( $cp_options->ad_parent_posting == 'no' && $category->parent == 0 ) {
			$child_terms = get_terms( $args['taxonomy'], array( 'parent' => $category->term_id, 'number' => 1, 'hide_empty' => 0 ) );
			if ( empty( $child_terms ) ) {
				return;
			}
		}

		$output .= "\t<option class=\"level-$depth\" value=\"" . $category->term_id . "\" " . selected( in_array( $category->term_id, (array) $args['selected'] ), true, false ) . ">";
		$output .= $pad . $cat_name;
		$output .= cp_category_dropdown_price( $category, $args );
		$output .= '</option>' . "\n";
	}
}


/**
 * Outputs JSON response with child categories dropdown list.
 *
 * @return void
 */
function cp_addnew_dropdown_child_categories() {
	global $cp_options;

	if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
		die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, only post method allowed.', APP_TD ) ) ) );
	}

	$parent_cat = isset( $_POST['cat_id'] ) ? (int) $_POST['cat_id'] : 0;
	if ( $parent_cat < 1 ) {
		die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, item does not exist.', APP_TD ) ) ) );
	}

	$terms = (array) get_terms( APP_TAX_CAT, array( 'child_of' => $parent_cat, 'hide_empty' => 0 ) );
	if ( empty( $terms ) ) {
		die( json_encode( array( 'success' => false, 'message' => __( 'Sorry, no results found.', APP_TD ) ) ) );
	}

	$args = array(
		'echo'       => 0,
		'child_of'   => $parent_cat,
		'level'      => (int) $_POST['level'],
		'listing_id' => (int) $_POST['listing_id'],
	);

	$result = cp_dropdown_categories_prices( $args );

	// return the result to the ajax post
	die( json_encode( array( 'success' => true, 'html' => $result ) ) );
}


/**
 * Deletes transient to refresh cat menu.
 *
 * @return void
 */
function cp_edit_term_delete_transient() {
	delete_transient( 'cp_cat_menu' );
}
