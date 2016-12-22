<?php
/**
 * Taxonomy Exclusive
 *
 * @package Framework\Taxonomy
 */

/**
 * Makes a post have at most a single term in a given taxonomy
 *
 * See http://core.trac.wordpress.org/ticket/14877
 */
class APP_Tax_Exclusive {

	function __construct( $taxonomy, $post_type ) {
		$this->taxonomy = $taxonomy;
		$this->post_type = $post_type;

		add_action( 'wp_terms_checklist_args', array( $this, 'category_checklist' ), 10, 2 );
	}

	function category_checklist( $args, $post_id ) {
		if ( $this->post_type == get_post_type( $post_id ) && $this->taxonomy == $args['taxonomy'] ) {
			$args['walker'] = new APP_Category_Walker;
			$args['checked_ontop'] = false;
		}

		return $args;
	}
}


/**
 * Override the 'Walker_Category_Checklist' method, 'start_el',
 * to replace checkboxes with radio buttons.
 */
class APP_Category_Walker extends Walker_Category_Checklist {

	function start_el( &$output, $category, $depth, $args, $id = 0 ) {
		extract( $args );
		if ( empty( $taxonomy ) ) {
			$taxonomy = 'category';
		}

		if ( $taxonomy == 'category' ) {
			$name = 'post_category';
		} else {
			$name = 'tax_input[' . $taxonomy . ']';
		}

		$class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';

		$output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>";
		$output .= '<label class="selectit">';
		$output .= '<input value="' . $category->term_id . '" type="radio" name="' . $name . '[]" id="in-' . $taxonomy . '-' . $category->term_id . '"';
		$output .= checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ';
		$output .= esc_html( apply_filters( 'the_category', $category->name ) );
		$output .= '</label>';
	}
}

