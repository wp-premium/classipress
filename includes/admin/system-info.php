<?php
/**
 * System Information.
 *
 * @package ClassiPress\Admin\SystemInfo
 * @author  AppThemes
 * @since   ClassiPress 3.3
 */


class CP_Theme_System_Info extends APP_System_Info {


	function __construct( $args = array(), $options = null ) {

		parent::__construct( $args, $options );

		add_action( 'admin_notices', array( $this, 'admin_tools' ) );
	}


	public function admin_tools() {

		if ( ! empty( $_POST['cp_tools']['flush_cache'] ) ) {
			$message = cp_flush_all_cache();
			echo scb_admin_notice( $message );
		}

		if ( ! empty( $_POST['cp_tools']['delete_tables'] ) ) {
			cp_delete_db_tables();
		}

		if ( ! empty( $_POST['cp_tools']['delete_options'] ) ) {
			cp_delete_all_options();
		}

	}


	function form_handler() {
		if ( empty( $_POST['action'] ) || ! $this->tabs->contains( $_POST['action'] ) ) {
			return;
		}

		check_admin_referer( $this->nonce );

		if ( ! empty( $_POST['cp_tools'] ) ) {
			return;
		} else {
			parent::form_handler();
		}
	}


	protected function init_tabs() {
		parent::init_tabs();

		$this->tabs->add( 'cp_tools', __( 'Advanced', APP_TD ) );

		$this->tab_sections['cp_tools']['cache'] = array(
			'title' => '',
			'fields' => array(
				array(
					'title' => __( 'Theme Cache', APP_TD ),
					'type' => 'submit',
					'name' => array( 'cp_tools', 'flush_cache' ),
					'extra' => array(
						'class' => 'button-secondary'
					),
					'value' => __( 'Flush ClassiPress Cache', APP_TD ),
					'desc' => __( 'Empty anything that ClassiPress has stored in cache (i.e. category drop-down menu, home page directory structure, etc).', APP_TD ),
				),
			),
		);


		$this->tab_sections['cp_tools']['uninstall'] = array(
			'title' => __( 'Uninstall Theme', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Database Tables', APP_TD ),
					'type' => 'submit',
					'name' => array( 'cp_tools', 'delete_tables' ),
					'extra' => array(
						'class' => 'button-secondary',
						'onclick' => 'return cp_confirmBeforeDeleteTables();',
					),
					'value' => __( 'Delete ClassiPress Tables', APP_TD ),
					'desc' => __( 'You will lose any custom fields, forms, and ad packs that you have created.', APP_TD ),
				),
				array(
					'title' => __( 'Config Options', APP_TD ),
					'type' => 'submit',
					'name' => array( 'cp_tools', 'delete_options' ),
					'extra' => array(
						'class' => 'button-secondary',
						'onclick' => 'return cp_confirmBeforeDeleteOptions();',
					),
					'value' => __( 'Delete ClassiPress Options', APP_TD ),
					'desc' => __( 'All values saved on the settings, pricing, gateways, etc admin pages will be erased from the wp_options table.', APP_TD ),
				),
			),
		);


	}


	function page_footer() {
		parent::page_footer();
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	if ( $("form input[name^='cp_tools']").length ) {
		$('form p.submit').html('');
	}
});
</script>
<?php
	}


}

