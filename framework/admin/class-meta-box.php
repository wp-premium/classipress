<?php
/**
 * Extended version of scbPostMetabox class that creates metaboxes on the post editing page.
 *
 * @package Framework\Metaboxes
 */
class APP_Meta_Box extends scbPostMetabox {

	/**
	 * The cache of the field names used in `is_protected_meta`.
	 * @var array
	 */
	protected $field_names;

	/**
	 * Protected version of parent's Private property $id.
	 *
	 * @var string
	 */
	protected $box_id;

	/**
	 * Sets up metabox.
	 *
	 * @param string $id
	 * @param string $title
	 * @param string|array $post_types (optional)
	 * @param string $context (optional)
	 * @param string $priority (optional)
	 *
	 * @return void
	 */
	public function __construct( $id, $title, $post_types = 'post', $context = 'advanced', $priority = 'default' ) {

		$this->actions[] = 'admin_print_styles';
		$this->actions[] = 'add_meta_boxes';
		$this->box_id = $id;

		parent::__construct( $id, $title, array(
			'post_type' => $post_types,
			'context' => $context,
			'priority' => $priority
		) );

	}

	/**
	 * Returns an array of form fields.
	 *
	 * @return array
	 */
	public function form_fields() {
		return apply_filters( "appthemes_{$this->box_id}_metabox_fields", $this->form() );
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
	 * Returns table row.
	 *
	 * @param array $row
	 * @param array $formdata
	 * @param array $errors (optional)
	 *
	 * @return string
	 */
	public function table_row( $row, $formdata, $errors = array() ) {
		if ( empty( $row['tip'] ) ) {
			$tip = '';
		} else {
			$tip  = html( "i", array(
				'class' => 'at at-tip',
				'data-tooltip' => APP_ToolTips::supports_wp_pointer() ? $row['tip'] : __( 'Click for more info', APP_TD ),
			) );

			if ( ! APP_ToolTips::supports_wp_pointer() ) {
				$tip .= html( "div class='tip-content'", $row['tip'] );
			}
		}

		if ( isset( $row['desc'] ) ) {
			$row['desc'] = html( 'span class="app-description"', $row['desc'] );
		}

		$input = scbForms::input( $row, $formdata );

		// If row has an error, highlight it
		$style = ( in_array( $row['name'], $errors ) ) ? 'style= "background-color: #FFCCCC"' : '';

		return html( 'tr',
			html( "th $style scope='row'", html( 'label for="'.esc_attr( $row['title'] ).'"', $row['title'] ) . $tip ),
			html( "td $style", $input )
		);
	}

	/**
	 * Returns current post ID.
	 *
	 * @return int
	 */
	public function get_post_id() {
		global $post;

		if ( isset( $post ) && is_object( $post ) ) {
			return $post->ID;
		}

		if ( ! empty( $_GET['post'] ) ) {
			return absint( $_GET['post'] );
		}

		if ( ! empty( $_POST['ID'] ) ) {
			return absint( $_POST['ID'] );
		}

		if ( ! empty( $_POST['post_ID'] ) ) {
			return absint( $_POST['post_ID'] );
		}

		return 0;
	}

	/**
	 * Helper function for initializing additional code on new metaboxes.
	 */
	public function add_meta_boxes() {
		// init tooltips here since at this time the meta-box pre-registration is done,
		// we already know the current screen and the 'condition()' has been checked
		$this->init_tooltips();

		// Avoid appearance own meta fields on the standard Custom Fields metabox.
		add_filter( 'is_protected_meta', array( $this, 'is_protected_meta' ), 10, 2 );
	}

	/**
	 * Hides current metabox fields from the standard "Custom Fields" metabox.
	 *
	 * @param boolean $protected The state of the current meta field.
	 * @param string  $meta_key  The meta key.
	 *
	 * @return boolean True if the key is protected, false otherwise.
	 */
	public function is_protected_meta( $protected, $meta_key ) {

		if ( $protected ) {
			return $protected;
		}

		if ( ! isset( $this->field_names ) ) {
			$this->field_names = wp_list_pluck( $this->form_fields(), 'name' );
		}

		if ( in_array( $meta_key, $this->field_names ) ) {
			$protected = true;
		}

		return $protected;
	}

	/**
	 * Load tooltips for the current screen.
	 * Avoids loading multiple tooltip instances on metaboxes.
	 */
	public function init_tooltips() {

		if ( ! appthemes_get_instance('APP_ToolTips') ) {
			appthemes_add_instance( array( 'APP_ToolTips' => array( get_current_screen()->id ) ) );
		}

	}

	/**
	 * Inline stylings.
	 */
	function admin_print_styles() {
?>
		<style type="text/css">
			<?php echo ".post-type-".get_current_screen()->id; ?> table.form-table th {
				position: relative;
				padding-right: 24px;
			  }
			.app-description {
				font-size: 13px;
				font-style: italic;
			}
		</style>
<?php
	}

}

