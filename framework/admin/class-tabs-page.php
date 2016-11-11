<?php
/**
 * Settings pages
 *
 * @package Framework\Settings
 */

require_once dirname(__FILE__) . '/class-list.php';

abstract class APP_Tabs_Page extends scbAdminPage {

	public $tabs;
	public $tab_sections;

	abstract protected function init_tabs();

	function __construct( $options = null ) {
		parent::__construct( false, $options );

		$this->tabs = new APP_List;
	}

	function page_loaded() {
		$this->init_tabs();

		do_action( 'tabs_' . $this->pagehook, $this );

		appthemes_add_instance( array( 'APP_ToolTips' => array( $this->pagehook ) ) );

		parent::page_loaded();
	}

	function form_handler() {
		if ( empty( $_POST['action'] ) || ! $this->tabs->contains( $_POST['action'] ) ) {
			return;
		}

		check_admin_referer( $this->nonce );

		foreach ( $this->tab_sections[ $_POST['action'] ] as &$section ) {

			if ( isset( $section['options'] ) && is_a( $section['options'], 'scbOptions' ) ) {
				$options =& $section['options'];
			} else {
				$options =& $this->options;
			}

			$to_update = scbForms::validate_post_data( $section['fields'], null, $options->get() );

			$options->update( $to_update );
		}

		do_action( 'tabs_' . $this->pagehook . '_form_handler', $this );
		add_action( 'admin_notices', array( $this, 'admin_msg' ) );
	}

	// A generic page header
	function page_header() {
		echo "<div class='wrap'>\n";
	}

	function page_footer() {
		parent::page_footer();
?>
		<style type="text/css">
			table.form-table th {
				position: relative;
				padding-right: 24px;
			}
			table.form-table td fieldset label {
				display: block;
			}
		</style>
<?php
	}

	function page_content() {

		do_action( 'tabs_' . $this->pagehook . '_page_content', $this );

		if ( isset( $_GET['firstrun'] ) ) {
			do_action( 'appthemes_first_run' );
		}

		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';

		$tabs = $this->tabs->get_all();

		if ( ! isset( $tabs[ $active_tab ] ) ) {
			$active_tab = key( $tabs );
		}

		$current_url = scbUtil::get_current_url();

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab_id => $tab_title ) {
			$class = 'nav-tab';

			if ( $tab_id == $active_tab ) {
				$class .= ' nav-tab-active';
			}

			$href = esc_url( add_query_arg( 'tab', $tab_id, $current_url ) );

			echo ' ' . html( 'a', compact( 'class', 'href' ), $tab_title );
		}
		echo '</h2>';

		echo '<form method="post" action="">';
		echo '<input type="hidden" name="action" value="' . $active_tab . '" />';
		wp_nonce_field( $this->nonce );

		foreach ( $this->tab_sections[ $active_tab ] as $section_id => $section ) {
			if ( isset( $section['title'] ) ) {
				echo html( 'h3 class="title"', $section['title'] );
			}

			if ( isset( $section['desc'] ) ) {
				echo html( 'p', $section['desc'] );
			}

			if ( isset( $section['renderer'] ) ) {
				call_user_func( $section['renderer'], $section, $section_id );
			} else {
				if ( isset( $section['options'] ) && is_a( $section['options'], 'scbOptions' ) ) {
					$formdata = $section['options'];
				} else {
					$formdata = $this->options;
				}
				$this->render_section( $section['fields'], $formdata->get() );
			}
		}

		echo '<p class="submit"><input type="submit" class="button-primary" value="' . esc_attr__( 'Save Changes', APP_TD ) . '" /></p>';
		echo '</form>';
	}

	private function render_section( $fields, $formdata = false ) {
		$output = '';

		foreach ( $fields as $field ) {
			$output .= $this->table_row( $this->before_rendering_field( $field ), $formdata );
		}

		echo $this->table_wrap( $output );
	}

	public function table_row( $field, $formdata = false ) {

		if ( empty( $field['tip'] ) ) {
			$tip = '';
		} else {
			$tip  = html( 'i', array(
				'class' => 'at at-tip',
				'data-tooltip' => APP_ToolTips::supports_wp_pointer() ? $field['tip'] : __( 'Click for more info', APP_TD ),
			) );

			if ( ! APP_ToolTips::supports_wp_pointer() ) {
				$tip .= html( "div class='tip-content'", $field['tip'] );
			}
		}

		if ( isset( $field['desc'] ) ) {
			// wrap textareas and regular-text fields in <p> tag
			// TODO: doesn't catch wrap_upload() instances for buttons
			if ( in_array( $field['type'], array( 'text', 'textarea', 'submit' ) ) ) {
				if ( ! isset( $field['extra']['class'] ) || strpos( $field['extra']['class'], 'small-text' ) === false ) {
					$field['desc'] = html( 'p class="description"', $field['desc'] );
				}
			}
		}

		$input = scbForms::input( $field, $formdata );

		// wrap radio buttons in a <fieldset> tag following what WP also does
		if ( 'radio' == $field['type'] ) {
			$input = html( 'fieldset', $input );
		}

		return html( "tr",
			html( "th scope='row app-row'", html( 'label for="'.esc_attr( $field['title'] ).'"', $field['title'] ) . $tip ),
			html( "td", $input )
		);
	}

	/**
	 * Useful for adding dynamic descriptions to certain fields.
	 *
	 * @param array field arguments
	 * @return array modified field arguments
	 */
	protected function before_rendering_field( $field ) {
		return $field;
	}

}

/**
 * Allows for the optional creation of a tabbed page, or the insertion of a tab
 * into a different page.
 */
abstract class APP_Conditional_Tabs_Page extends APP_Tabs_Page {

	function __construct( $options ) {

		if ( $this->conditional_create_page() ) {
			parent::__construct( $options );
		} else {
			$this->setup();
			add_action( 'admin_init', array( $this, 'tab_register' ) );
		}

	}

	abstract function conditional_create_page();

	function setup_external_page( $page ) {
		$this->tabs = &$page->tabs;
		$this->tab_sections = &$page->tab_sections;
	}

	function tab_register() {
		global $admin_page_hooks;

		$top_level = $this->args['conditional_parent'];
		$sub_level = $this->args['conditional_page'];

		if ( ! isset( $admin_page_hooks[ $top_level ] ) ) {
			return;
		}

		$top_page_hook = $admin_page_hooks[ $top_level ];

		$hook = 'tabs_%s_page_%s';
		$hook = sprintf( $hook, $top_page_hook, $sub_level );
		add_action( $hook, array( $this, 'setup_external_page' ), 9 );
		add_action( $hook, array( $this, 'init_tabs' ) );
	}

}

