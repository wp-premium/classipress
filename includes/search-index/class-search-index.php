<?php

/**
 * Defines the Search Index Administration Panel
 *
 * @package Search-Index\Admin
 */
class APP_Search_Index_Admin extends APP_Conditional_Tabs_Page {


	function __construct( $options = null ) {
		parent::__construct( $options );
	}


	/**
	 * Sets up the page
	 * @return void
	 */
	function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title' => __( 'Search Index Updater', APP_TD ),
			'menu_title' => __( 'Search Index Updater', APP_TD ),
			'page_slug' => 'app-search-index',
			'parent' => 'app-dashboard',
			'screen_icon' => 'options-general',
			'admin_action_priority' => 11,
			'conditional_parent' => appthemes_search_index_get_args( 'admin_top_level_page' ),
			'conditional_page' => appthemes_search_index_get_args( 'admin_sub_level_page' ),
		);

		add_action( 'wp_ajax_build-search-index', array( $this, 'ajax_build_index' ) );
	}


	function conditional_create_page() {
		$top_level = appthemes_search_index_get_args( 'admin_top_level_page' );
		$sub_level = appthemes_search_index_get_args( 'admin_sub_level_page' );

		if ( ! $top_level && ! $sub_level ) {
			return true;
		} else {
			return false;
		}
	}


	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'jquery-ui-progressbar' );
		wp_enqueue_style( 'jquery-ui-style' );
	}


	function init_tabs() {

		$this->admin_tools();
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'admin_print_footer_scripts' ) );

		$this->tabs->add( 'search_index', __( 'Search Index', APP_TD ) );

		if ( appthemes_get_search_index_status() ) {

			$fields = array(
				array(
					'title' => __( 'Status', APP_TD ),
					'type' => 'text',
					'name' => array( 'search_index', '_blank' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => sprintf( '<span style="color:#0a0;font-weight:bold;">%s</span>', __( 'Built', APP_TD ) ),

				),
				array(
					'title' => __( 'Search Index', APP_TD ),
					'type' => 'submit',
					'name' => array( 'search_index', 'delete_index' ),
					'extra' => array(
						'class' => 'button-secondary',
						'onclick' => 'return confirm("' . __( 'You are about to completely delete the search index. Are you sure you want to proceed?', APP_TD ) . '");',
					),
					'value' => __( 'Delete Search Index', APP_TD ),
					'desc' => __( 'Flush out your index. You can always rebuild it again.', APP_TD ),
				),
			);

		} else if ( ! empty( $_POST['search_index']['update_index'] ) ) {

			$fields = array(
				array(
					'title' => '',
					'type' => 'text',
					'name' => array( 'search_index', '_blank' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => html( 'div', array( 'id' => 'search-index-message' ), __( 'The search index is being built...', APP_TD ) ) . $this->progress_bar(),
				),
			);

		} else {

			$fields = array(
				array(
					'title' => __( 'Build Speed', APP_TD ),
					'type' => 'radio',
					'name' => array( 'search_index', 'speed' ),
					'values' => array(
						'slow' => __( 'Slow', APP_TD ),
						'medium' => __( 'Medium', APP_TD ),
						'fast' => __( 'Fast', APP_TD ),
					),
					'desc' => '',
					'tip' => __( "If you're not on a dedicated server, use the slow or medium option.", APP_TD ),
				),
				array(
					'title' => '',
					'type' => 'submit',
					'name' => array( 'search_index', 'update_index' ),
					'extra' => array(
						'class' => 'button-secondary',
					),
					'value' => __( 'Create Search Index', APP_TD ),
					'desc' => '',
				),
			);

		}

		$this->tab_sections['search_index']['general'] = array(
			'title' => '',
			'fields' => $fields,
		);

	}


	public function admin_tools() {
		global $wpdb;

		if ( ! empty( $_POST['search_index']['delete_index'] ) ) {

			foreach ( APP_Search_Index::get_registered_post_types() as $post_type ) {
				$wpdb->update( $wpdb->posts, array( 'post_content_filtered' => '' ), array( 'post_type' => $post_type ) );
			}

			update_option( APP_SEARCH_INDEX_OPTION, 0 );

			wp_redirect( scbUtil::get_current_url() );
			exit();
		}

	}


	public static function get_index_speed( $speed = 'slow' ) {

		$limits = array(
			'fast' => 500,
			'medium' => 250,
			'slow' => 100,
		);

		if ( array_key_exists( $speed, $limits ) ) {
			return $limits[ $speed ];
		} else {
			return 100;
		}

	}


	function progress_bar() {
		return html( 'div', array( 'id' => 'search-index-progress-bar' ),
			html( 'div', array( 'id' => 'search-index-progress-bar-percent', 'style' => 'position:absolute; padding:4px;' ), '' )
		);
	}


	/**
	 * Handles updating search index via ajax
	 */
	public function ajax_build_index() {
		if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
			die( json_encode( array( 'success' => false, 'completed' => false, 'message' => __( 'Sorry, only post method allowed', APP_TD ) ) ) );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'build-index' ) ) {
			die( json_encode( array( 'success' => false, 'completed' => false, 'message' => __( 'Sorry, invalid request', APP_TD ) ) ) );
		}

		if ( appthemes_get_search_index_status() ) {
			die( json_encode( array( 'success' => true, 'completed' => true, 'message' => __( 'The search index has been built', APP_TD ) ) ) );
		}

		$speed = isset( $_POST['speed'] ) ? (int) $_POST['speed'] : 100;

		$index_builder = new APP_Build_Search_Index( array( 'limit' => $speed ) );
		$index_builder->process();
		die( json_encode( array( 'success' => true, 'completed' => false, 'message' => __( 'This has already run', APP_TD ) ) ) );
	}


	function admin_print_footer_scripts() {
		global $wpdb;
?>

<script type="text/javascript">
jQuery(document).ready(function($) {
	if ( $("form input[name^='search_index']").length ) {
		$('form p.submit').html('');
	}
});
</script>

<?php
		if ( ! empty( $_POST['search_index']['update_index'] ) ) {
			$total_listings = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_content_filtered = '' AND post_type IN ('" . implode( "', '", APP_Search_Index::get_registered_post_types() ) . "')" );
			$speed = isset( $_POST['search_index']['speed'] ) ? $_POST['search_index']['speed'] : 'slow';
			$speed = self::get_index_speed( $speed );
?>

<script type="text/javascript">
	jQuery(document).ready(function($){
		var si_total = <?php echo $total_listings; ?>;
		var si_speed = <?php echo $speed; ?>;
		var si_count = 1;
		var si_percent = 0;
		var si_nonce = "<?php echo wp_create_nonce( 'build-index' ); ?>";
		var si_timestart = new Date().getTime();
		var si_continue = true;

		// Create the progress bar
		$("#search-index-progress-bar").progressbar();
		$("#search-index-progress-bar-percent").html( "0%" );

		// Called after each indexing. Updates the progress bar.
		function appthemes_search_index_update_status( success, data ) {
			si_percent = ( si_count / si_total ) * 100;
			if ( si_percent > 100 ) {
				si_percent = 100;
			}
			$("#search-index-progress-bar").progressbar( "value", si_percent );
			$("#search-index-progress-bar-percent").html( Math.round( si_percent ) + "%" );
			si_count = si_count + si_speed;

			if ( ! success || data.completed == true ) {
				si_continue = false;
			}
		}

		// Called when all posts have been processed.
		function appthemes_search_index_finish( data ) {
			$("#search-index-message").html( data.message );
		}

		// Generate search index via AJAX
		function appthemes_search_index_process() {
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				dataType: "json",
				data: {
					action: "build-search-index",
					speed: si_speed,
					nonce: si_nonce
				},
				success: function( data ) {
					if ( data.success == true ) {
						appthemes_search_index_update_status( true, data );
					} else {
						appthemes_search_index_update_status( false, data );
					}

					if ( si_continue ) {
						setTimeout( appthemes_search_index_process, 1000 );
					} else {
						appthemes_search_index_finish( data );
					}
				},
				error: function( data ) {
					appthemes_search_index_update_status( false, data );

					if ( si_continue ) {
						appthemes_search_index_process();
					} else {
						appthemes_search_index_finish( data );
					}
				}
			});
		}

		appthemes_search_index_process();
	});
</script>

<?php
		}
	}


}

