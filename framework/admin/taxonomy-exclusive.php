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

	/**
	 * Start the element output.
	 *
	 * @see Walker::start_el()
	 *
	 * @param string $output   Passed by reference. Used to append additional content.
	 * @param object $category The current term object.
	 * @param int    $depth    Depth of the term in reference to parents. Default 0.
	 * @param array  $args     An array of arguments. @see wp_terms_checklist()
	 * @param int    $id       ID of the current term.
	 */
	function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
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
