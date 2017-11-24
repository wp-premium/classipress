<?php
/**
 * Taxonomy metabox implementation.
 *
 * @package Framework\Metaboxes
 */
class APP_Taxonomy_Meta_Box {

	/**
	 * Metabox ID.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Title.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Taxonomies.
	 *
	 * @var array
	 */
	private $taxonomies;

	/**
	 * Post meta data.
	 *
	 * @var array
	 */
	private $term_data = array();

	/**
	 * Action hooks.
	 *
	 * @var array
	 */
	protected $actions = array( 'admin_enqueue_scripts', 'admin_print_styles' );

	/**
	 * The contextual metabox renderer.
	 *
	 * @var APP_Meta_Box_Renderer
	 */
	protected $renderer;

	/**
	 * Sets up metabox.
	 *
	 * @param string $id
	 * @param string $title
	 * @param string|array $taxonomies (optional)
	 * @param string $context (optional)
	 * @param string $priority (optional)
	 *
	 * @return void
	 */
	public function __construct( $id, $title, $taxonomies = 'category', $priority = 10 ) {

		$this->id         = $id;
		$this->title      = $title;
		$this->taxonomies = (array) $taxonomies;

		add_action( 'load-edit-tags.php', array( $this, 'pre_register' ), $priority );
		add_action( 'wp_ajax_add-tag', array( $this, 'pre_register' ), 1 );
	}

	/**
	 * Retrieves the metabox id.
	 *
	 * @return string
	 */
	final public function get_id() {
		return $this->id;
	}

	/**
	 * Pre register the metabox.
	 *
	 * @return void
	 */
	final public function pre_register() {

		$taxonomy = ( ! empty( $_REQUEST['taxonomy'] ) ) ? wp_unslash( $_REQUEST['taxonomy'] ) : '';

		if ( ! in_array( $taxonomy, $this->taxonomies, true ) ) {
			return;
		}

		if ( wp_doing_ajax() && ! check_ajax_referer( 'add-tag', '_wpnonce_add-tag', false ) ) {
			return;
		}

		if ( ! $this->condition() ) {
			return;
		}

		if ( $this->get_term_id() ) {
			$this->term_data = $this->get_meta( $this->get_term_id() );
		}

		add_action( "{$taxonomy}_add_form_fields", array( $this, 'display' ) );
		add_action( "{$taxonomy}_edit_form_fields", array( $this, 'display' ) );

		add_action( "created_{$taxonomy}", array( $this, '_save' ) );
		add_action( "edited_{$taxonomy}", array( $this, '_save' ) );

		$this->init_tooltips();

		foreach ( $this->actions as $action ) {
			if ( method_exists( $this, $action ) ) {
				add_action( $action, array( $this, $action ) );
			}
		}
	}

	/**
	 * Additional checks before registering the metabox.
	 *
	 * @return bool
	 */
	protected function condition() {
		return true;
	}

	/**
	 * Filter data before display.
	 *
	 * @param array $form_data
	 * @param object $term
	 *
	 * @return array
	 */
	public function before_display( $form_data, $term ) {
		return $form_data;
	}

	/**
	 * Displays metabox content.
	 *
	 * @param WP_Term|string $term The term object or taxonomy slug.
	 *
	 * @return void
	 */
	public function display( $term ) {

		if ( is_string( $term ) && in_array( $term, $this->taxonomies, true ) ) {
			$term = new WP_Term( (object) array(
				'term_id'  => 0,
				'taxonomy' => $term,
			) );
		}

		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$form_fields = $this->form_fields();
		if ( ! $form_fields ) {
			return;
		}

		$form_data = $this->term_data;
		$error_fields = array();

		if ( isset( $form_data[ '_error_data_' . $this->get_id() ] ) ) {
			$data = maybe_unserialize( $form_data[ '_error_data_' . $this->get_id() ] );

			$error_fields = $data['fields'];
			$form_data = $data['data'];

			$this->display_notices( $data['messages'], 'error' );
		}

		wp_nonce_field( 'term_section_update', 'term_section_' . $this->get_id() );

		$this->set_renderer();

		$form_data = $this->before_display( $form_data, $term );

		$this->before_form( $term );
		echo $this->renderer->render( $form_fields, $form_data, $error_fields );
		$this->after_form( $term );

		$this->delete_meta( $term->term_id, '_error_data_' . $this->get_id() );
	}

	/**
	 * Sets the contextual renderer object.
	 */
	protected function set_renderer() {
		$taxonomy = get_current_screen()->taxonomy;
		$action   = current_action();

		if ( "{$taxonomy}_add_form_fields" === $action ) {
			$this->renderer = new APP_Taxonomy_Meta_Box_Section_Renderer();
		} else {
			$this->renderer = new APP_Meta_Box_Table_Renderer();
		}
	}

	/**
	 * Displays notices.
	 *
	 * @param array|string $notices
	 * @param string $class (optional)
	 *
	 * @return void
	 */
	public function display_notices( $notices, $class = 'updated' ) {
		// Add inline class so the notices stays in metabox.
		$class .= ' inline';

		foreach ( (array) $notices as $notice ) {
			echo scb_admin_notice( $notice, $class );
		}
	}

	/**
	 * Display some extra HTML before the form.
	 *
	 * @param object $term
	 *
	 * @return void
	 */
	public function before_form( $term ) {}

	/**
	 * Returns an array of form fields.
	 *
	 * @return array
	 */
	public function form_fields() {
		return apply_filters( "appthemes_{$this->get_id()}_taxonomy_metabox_fields", $this->form() );
	}

	/**
	 * Returns an array of form fields.
	 *
	 * @return array
	 */
	public function form() {
		return array();
	}

	/**
	 * Display some extra HTML after the form.
	 *
	 * @param object $term
	 *
	 * @return void
	 */
	public function after_form( $term ) {}

	/**
	 * Makes sure that the saving occurs only for the post being edited.
	 *
	 * @param int $term_id
	 *
	 * @return void
	 */
	final public function _save( $term_id ) {

		if ( ! wp_verify_nonce( $_POST[ 'term_section_' . $this->get_id() ], 'term_section_update' ) ) {
			return;
		}

		$this->save( $term_id );
	}

	/**
	 * Saves metabox form data.
	 *
	 * @param int $term_id
	 *
	 * @return void
	 */
	protected function save( $term_id ) {
		$form_fields = $this->form_fields();

		$to_update = scbForms::validate_post_data( $form_fields );

		// Filter data.
		$to_update = $this->before_save( $to_update, $term_id );

		// Validate dataset.
		$is_valid = $this->validate_post_data( $to_update, $term_id );
		if ( $is_valid instanceof WP_Error && $is_valid->get_error_codes() ) {

			$error_data = array(
				'fields'   => $is_valid->get_error_codes(),
				'messages' => $is_valid->get_error_messages(),
				'data'     => $to_update,
			);
			$this->update_meta( $term_id, '_error_data_' . $this->get_id(), $error_data );

			return;
		}

		foreach ( $to_update as $key => $value ) {
			$this->update_meta( $term_id, $key, $value );
		}
	}

	/**
	 * Filter data before save.
	 *
	 * @param array $term_data
	 * @param int $term_id
	 *
	 * @return array
	 */
	protected function before_save( $term_data, $term_id ) {
		return $term_data;
	}

	/**
	 * Validate posted data.
	 *
	 * @param array $term_data
	 * @param int $term_id
	 *
	 * @return bool|object A WP_Error object if posted data are invalid.
	 */
	protected function validate_post_data( $term_data, $term_id ) {
		return false;
	}

	/**
	 * Returns an array of post meta.
	 *
	 * @param int $term_id
	 *
	 * @return array
	 */
	private function get_meta( $term_id ) {
		$meta = (array) $this->get_term_meta( $term_id );

		foreach ( $meta as $key => $values ) {
			$meta[ $key ] = maybe_unserialize( $meta[ $key ][0] );
		}

		return $meta;
	}

	/**
	 * Retrieves metadata for a term.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $key     Optional. The meta key to retrieve. If no key is
	 *                        provided, fetches all metadata for the item.
	 * @param bool   $single  Whether to return a single value. If false, an
	 *                        array of all values matching the `$item_id`/`$key`
	 *                        pair will be returned. Default: false.
	 *
	 * @return mixed If `$single` is false, an array of metadata values.
	 *               If `$single` is true, a single metadata value.
	 */
	protected function get_term_meta( $term_id, $key = '', $single = false ) {
		return get_term_meta( $term_id, $key, $single );
	}

	/**
	 * Update term meta field based on term ID.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with
	 * the same key and item ID.
	 *
	 * If the meta field for the item does not exist, it will be added.
	 *
	 * @param int    $term_id    Item ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if
	 *                           non-scalar.
	 * @param mixed  $prev_value Optional. Previous value to check before
	 *                           removing. Default empty.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful update,
	 *                  false on failure.
	 */
	protected function update_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_term_meta( $term_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Remove metadata matching criteria from a term.
	 *
	 * You can match based on the key, or key and value. Removing based on key
	 * and value, will keep from removing duplicate metadata with the same key.
	 * It also allows removing all metadata matching key, if needed.
	 *
	 * @param int    $term_id    Term ID.
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value. Must be serializable
	 *                           if non-scalar. Default empty.
	 *
	 * @return bool True on success, false on failure.
	 */
	protected function delete_meta( $term_id, $meta_key, $meta_value = '' ) {
		return delete_term_meta( $term_id, $meta_key, $meta_value );
	}

	/**
	 * Returns current term ID.
	 *
	 * @return int
	 */
	public function get_term_id() {

		if ( ! empty( $_REQUEST['tag_ID'] ) ) {
			return absint( $_REQUEST['tag_ID'] );
		}

		return 0;
	}

	/**
	 * Load tooltips for the current screen.
	 * Avoids loading multiple tooltip instances on metaboxes.
	 */
	public function init_tooltips() {
		if ( ! appthemes_get_instance( 'APP_ToolTips' ) ) {
			appthemes_add_instance( array(
				'APP_ToolTips' => array(
					get_current_screen()->id,
				),
			) );
		}
	}

}

/**
 * Abstract metabox renderer.
 */
abstract class APP_Meta_Box_Renderer {

	/**
	 * Renders the metabox.
	 *
	 * @param array $form_fields The form fields.
	 * @param array $form_data   The form data.
	 * @param array $errors      The errors data.
	 */
	final public function render( $form_fields, $form_data, $errors = array() ) {
		$output = '';
		foreach ( $form_fields as $form_field ) {
			$output .= $this->render_row( $form_field, $form_data, $errors );
		}

		$output = $this->wrap( $output );

		return $output;
	}

	/**
	 * Renders the field row.
	 *
	 * @param array $form_field The form field.
	 * @param array $form_data  The form data.
	 * @param array $errors     The errors data.
	 *
	 * @return string Generated rows HTML.
	 */
	abstract function render_row( $form_field, $form_data, $errors );

	/**
	 * Wraps the generated rows.
	 *
	 * @param string $rows Generated rows HTML.
	 *
	 * @return string Wrapped rows.
	 */
	abstract function wrap( $rows );

}

/**
 * Metabox table renderer.
 */
class APP_Meta_Box_Table_Renderer extends APP_Meta_Box_Renderer {
	/**
	 * Renders the field row.
	 *
	 * @param array $form_field The form field.
	 * @param array $form_data  The form data.
	 * @param array $errors     The errors data.
	 *
	 * @return string Generated rows HTML.
	 */
	function render_row( $form_field, $form_data, $errors ) {
		if ( empty( $form_field['tip'] ) ) {
			$tip = html( 'td class="at-help"', '&nbsp;' );
		} else {
			$tip  = html( 'td class="at-help"', html( 'i', array(
				'class' => 'at at-tip',
				'data-tooltip' => APP_ToolTips::supports_wp_pointer() ? $form_field['tip'] : __( 'Click for more info', APP_TD ),
			) ) );

			if ( ! APP_ToolTips::supports_wp_pointer() ) {
				$tip .= html( "div class='tip-content'", $form_field['tip'] );
			}
		}

		$desc = '';

		if ( isset( $form_field['desc'] ) ) {
			$desc = html( 'p class="description"', $form_field['desc'] );
			$form_field['desc'] = '';
		}

		$input = scbForms::input( $form_field, $form_data );

		// If row has an error, highlight it.
		$style = ( in_array( $form_field['name'], $errors, true ) ) ? 'style= "background-color: #FFCCCC"' : '';

		return html( 'tr',
			html( "th $style scope='row'", html( 'label for="' . esc_attr( $form_field['title'] ) . '"', $form_field['title'] ) . $tip ),
			html( "td $style", $input, $desc )
		);
	}

	/**
	 * Wraps the generated rows.
	 *
	 * @param string $rows Generated rows HTML.
	 *
	 * @return string Wrapped rows.
	 */
	function wrap( $rows ) {
		return scbForms::table_wrap( $rows );
	}
}

/**
 * Metabox table renderer.
 */
class APP_Taxonomy_Meta_Box_Section_Renderer extends APP_Meta_Box_Renderer {
	/**
	 * Renders the field row.
	 *
	 * @param array $form_field The form field.
	 * @param array $form_data  The form data.
	 * @param array $errors     The errors data.
	 *
	 * @return string Generated rows HTML.
	 */
	function render_row( $form_field, $form_data, $errors ) {

		if ( empty( $form_field['tip'] ) ) {
			$tip = '';
		} else {
			$tip  = html( 'span class="at-help"', html( 'i', array(
				'class' => 'at at-tip',
				'data-tooltip' => APP_ToolTips::supports_wp_pointer() ? $form_field['tip'] : __( 'Click for more info', APP_TD ),
			) ) );

			if ( ! APP_ToolTips::supports_wp_pointer() ) {
				$tip .= html( "div class='tip-content'", $form_field['tip'] );
			}
		}

		$desc = '';

		if ( isset( $form_field['desc'] ) ) {
			$desc = html( 'p class="description"', $form_field['desc'] );
			$form_field['desc'] = '';
		}

		$input = scbForms::input( $form_field, $form_data );

		// If row has an error, highlight it.
		$style = ( in_array( $form_field['name'], $errors, true ) ) ? 'style= "background-color: #FFCCCC"' : '';

		return html( 'div class="form-field"',
			html( 'label for="' . esc_attr( $form_field['name'] ) . '"', $form_field['title'] . '&nbsp;' . $tip ),
			html( "p $style", $input ),
			$desc
		);

	}

	/**
	 * Wraps the generated rows.
	 *
	 * @param string $rows Generated rows HTML.
	 *
	 * @return string Wrapped rows.
	 */
	function wrap( $rows ) {
		return $rows;
	}
}
