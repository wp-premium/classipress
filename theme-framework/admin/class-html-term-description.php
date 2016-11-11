<?php
/**
 * HTML Term Description.
 * Allows HTML in term descriptions and shows a WYSIWYG editor.
 *
 * @package ThemeFramework\Admin\HTML-Term-Description
 */
class APP_HTML_Term_Description {

	/**
	 * Taxonomies.
	 * @var array
	 */
	protected $taxonomies = array();

	/**
	 * Current Taxonomy.
	 * @var string
	 */
	protected $current_taxonomy;

	/**
	 * WP Editor Settings.
	 * @var array
	 */
	protected $editor_settings = array();

	/**
	 * Constructor.
	 *
	 * @param string $taxonomy
	 * @param array $editor_settings (optional)
	 *
	 * @return void
	 */
	public function __construct( $taxonomy, $editor_settings = array() ) {

		if ( empty( $taxonomy ) ) {
			$taxonomy = array();
		} else if ( is_string( $taxonomy ) ) {
			$taxonomy = array( $taxonomy );
		}

		$this->taxonomies = $taxonomy;
		$this->current_taxonomy = false;
		$this->editor_settings = wp_parse_args( $editor_settings, $this->get_editor_settings() );

		add_action( 'load-edit-tags.php', array( $this, 'setup' ), 9 );
		add_action( 'admin_init', array( $this, 'setup' ), 9 );
	}

	/**
	 * Setup the term description.
	 *
	 * @return void
	 */
	final public function setup() {
		$taxonomies = $this->get_taxonomies();
		if ( empty( $taxonomies ) || ! is_array( $taxonomies ) ) {
			return;
		}

		// set filters on ajax add tag action
		if ( defined( 'DOING_AJAX' ) ) {
			$action = ! empty( $_POST['action'] ) ? $_POST['action'] : '';
			$taxonomy = ! empty( $_POST['taxonomy'] ) ? $_POST['taxonomy'] : '';
			if ( $action != 'add-tag' || ! in_array( $taxonomy, $taxonomies ) ) {
				return;
			}
		} else {
			$screen = get_current_screen();
			$taxonomy = $screen ? $screen->taxonomy : false;
			if ( ! $taxonomy || ! in_array( $taxonomy, $taxonomies ) ) {
				return;
			}
		}

		$this->current_taxonomy = $taxonomy;

		if ( ! $this->condition() ) {
			return;
		}

		add_action( $taxonomy . '_edit_form_fields', array( $this, 'render_field_edit' ), 1, 2 );
		add_action( $taxonomy . '_add_form_fields', array( $this, 'render_field_add' ), 1, 1 );

		$this->allow_html();
	}

	/**
	 * Additional checks before enabling HTML support for term descriptions.
	 *
	 * @return bool
	 */
	protected function condition() {
		return apply_filters( 'appthemes_html_term_description_condition', true, $this->get_current_taxonomy() );
	}

	/**
	 * Returns currently handled taxonomy.
	 *
	 * @return string|bool
	 */
	protected function get_current_taxonomy() {
		return $this->current_taxonomy;
	}

	/**
	 * Returns an array of taxonomies.
	 *
	 * @return array
	 */
	protected function get_taxonomies() {
		return apply_filters( 'appthemes_html_term_description_taxonomies', $this->taxonomies );
	}

	/**
	 * Returns an array of settings for WP Editor.
	 *
	 * @return string|bool
	 */
	protected function get_editor_settings() {

		$editor_defaults = array(
			'quicktags' => array( 'buttons' => 'em,strong,link' ),
			'textarea_name'	=> 'description',
			'quicktags' => true,
			'tinymce' => true,
			'editor_css' => '
				<style type="text/css">
					#wp-html-description-editor-container .wp-editor-area { height: 250px; }
					#wp-html-tag-description-editor-container .wp-editor-area { height: 150px; }
				</style>',
		);

		$editor_settings = ! empty( $this->editor_settings ) ? $this->editor_settings : $editor_defaults;

		return apply_filters( 'appthemes_html_term_description_editor_settings', $editor_settings, $this->get_current_taxonomy() );
	}

	/**
	 * Allow HTML for term description.
	 *
	 * @return void
	 */
	protected function allow_html() {
		remove_filter( 'pre_term_description', 'wp_filter_kses' );
		remove_filter( 'term_description', 'wp_kses_data' );

		add_filter( 'pre_term_description', 'wp_filter_post_kses' );
	}

	/**
	 * Add the WYSIWYG editor on the edit term page.
	 *
	 * @param object $term
	 * @param string $taxonomy
	 *
	 * @return void
	 */
	public function render_field_edit( $term, $taxonomy ) {
		$editor_settings = $this->get_editor_settings();
		if ( empty( $editor_settings ) || ! is_array( $editor_settings ) ) {
			return;
		}
	?>
		<tr>
			<th scope="row" valign="top"><label for="description"><?php _e( 'Description', APP_TD ); ?></label></th>
			<td><?php wp_editor( htmlspecialchars_decode( $term->description ), 'html-description', $editor_settings ); ?>
			<span class="description"><?php _e( 'The description is not prominent by default, however some themes may show it.', APP_TD ); ?></span></td>
			<script type="text/javascript">
				// Remove the non-html field
				jQuery( 'textarea#description' ).closest( '.form-field' ).remove();
			</script>
		</tr>
		<?php
	}

	/**
	 * Add the WYSIWYG editor on the add term page.
	 *
	 * @param string $taxonomy
	 *
	 * @return void
	 */
	public function render_field_add( $taxonomy ) {
		$editor_settings = $this->get_editor_settings();
		if ( empty( $editor_settings ) || ! is_array( $editor_settings ) ) {
			return;
		}
	?>
		<div>
			<label for="tag-description"><?php _e( 'Description', APP_TD ); ?></label>
			<?php wp_editor( '', 'html-tag-description', $editor_settings ); ?>
			<p class="description"><?php _e( 'The description is not prominent by default, however some themes may show it.', APP_TD ); ?></p>
			<script type="text/javascript">
				// Remove the non-html field
				jQuery( 'textarea#tag-description' ).closest( '.form-field' ).remove();
				jQuery(function() {
					// Trigger save
					jQuery( '#addtag #submit' ).click( function() {
						tinyMCE.triggerSave();
					});
				});
			</script>
		</div>
		<?php
	}
}
