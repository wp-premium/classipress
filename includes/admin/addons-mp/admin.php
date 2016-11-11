<?php
/**
 * Admin setup class for the AppThemes add-ons marketplace.
 *
 * @package Components\Add-ons
 */

/**
 * Main admin class for displaying the add-ons markeplace browser.
 */
class APP_Addons extends scbAdminPage {

	/**
	 * Constructor.
	 *
	 * Setup the Add-ons admin page.
	 *
	 * @param string     $page_slug The admin page slug.
	 * @param array      $args      Additional args for the admin menu.
	 * @param boolean    $file      Optional file name to pass to the parent class, if any.
	 * @param scbOptions $options   Optional 'scbOptions' object.
	 */
	function __construct( $page_slug, $args = array(), $file = false, $options = null ) {

		ob_start();

		$this->page_title();

		$page_title = ob_get_clean();

		$defaults = array(
			'menu_title'            => __( 'Add-ons', APP_TD ),
			'page_title'            => $page_title,
			'page_slug'             => $page_slug,
			'parent'                => 'app-dashboard',
			'action_link'           => false,
			'admin_action_priority' => 99,
		);
		$this->args = wp_parse_args( $args['menu'], $defaults );

		parent::__construct( $file, $options );
	}

	/**
	 * Condition check for displaying the add-ons page.
	 *
	 * @return boolean True if the add-ons page should be displayed, False otherwise.
	 */
	function condition() {
		return ! empty( $_GET['page'] ) && $this->args['page_slug'] === $_GET['page']; // Input var okay.
	}

	/**
	 * Additional setup code for the add-ons page.
	 */
	function setup() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 21 );
		add_action( 'admin_init', array( $this, 'maybe_add_pagination' ) );

		add_action( 'appthemes_first_run', array( $this, 'clear_cache' ) );
		add_action( 'appthemes_addons_mp_popular', array( $this, 'display_addons_mp_table' ), 10, 2 );
		add_action( 'appthemes_addons_mp_new', array( $this, 'display_addons_mp_table' ), 10, 2 );
	}

	/**
	 * Enqueue registered admin JS scripts and CSS styles.
	 *
	 * @param string $hook The current hook name.
	 */
	public function enqueue_admin_scripts( $hook ) {

		if ( ! $this->condition() ) {
			return;
		}

		wp_enqueue_script(
			$this->args['page_slug'],
			_appthemes_get_addons_mp_args( 'url' ) . '/js/scripts.js',
			array( 'jquery' ),
			'1.0'
		);

		wp_enqueue_style(
			$this->args['page_slug'],
			_appthemes_get_addons_mp_args( 'url' ) . '/css/styles.css',
			array(),
			'1.0'
		);

	}

	/**
	 *  Outputs the main page title.
	 */
	protected function page_title() {
?>
		<h2>
			<?php echo __( 'Marketplace Add-ons', APP_TD ); ?>
			<a href="https://marketplace.appthemes.com/" class="add-new-h2"><?php _e( 'Browse Marketplace', APP_TD ); ?></a>
			<a href="https://www.appthemes.com/themes/" class="add-new-h2"><?php _e( 'Browse Themes', APP_TD ); ?></a>
		</h2>
<?php
	}

	/**
	 * Outputs the content for the current tab.
	 *
	 * @uses do_action() Calls 'appthemes_addons_mp_$tab'.
	 */
	public function page_content() {
		$tab = empty( $_REQUEST['tab'] ) ? 'new' : wp_strip_all_tags( wp_unslash( $_REQUEST['tab'] ) ); // Input var okay.
		$paged = ! empty( $_REQUEST['paged'] ) ? (int) $_REQUEST['paged'] : 1; // Input var okay.

		$tab = esc_attr( $tab );
		$paged = esc_attr( $paged );

		$filters = _appthemes_get_addons_mp_page_args( $this->args['page_slug'], 'filters' );

		$args = array(
			'tab'     => $tab,
			'page'    => $paged,
			'filters' => $filters,
		);
		$table = new APP_Addons_List_Table( $this->args['page_slug'], $this->args['parent'], $args );

		// Outputs the tabs, filters and search bar.
		$table->views();

		// Hooked tab contents.
		do_action( "appthemes_addons_mp_{$tab}", $table );
	}

	/**
	 * Outputs the add-ons browser.
	 *
	 * @param object $table A 'WP_List_Table' object.
	 */
	public function display_addons_mp_table( $table ) {

		if ( $table->screen->id !== $this->pagehook ) {
			return;
		}
?>
		<br class="clear" />
		<form id="plugin-filter" action="" method="post">
			<?php $table->display(); ?>
		</form>
<?php
	}

	/**
	 * Adds the 'paged' query arg to the URL if present on the '$_POST' object.
	 */
	public function maybe_add_pagination() {

		if ( ! $this->condition() ) {
			return;
		}

		if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) { // Input var okay.
			$location = remove_query_arg( '_wp_http_referer', wp_unslash( $_SERVER['REQUEST_URI'] ) );

			if ( ! empty( $_REQUEST['paged'] ) ) {
				$location = add_query_arg( 'paged', (int) $_REQUEST['paged'], $location );
			}

			$location = esc_url_raw( $location );

			wp_redirect( $location );
			exit;
		}

	}

	/**
	 * Clears any previously cached content to refresh current data.
	 */
	public function clear_cache() {
		delete_transient( '_appthemes-addons-mp-popular' );
		delete_transient( '_appthemes-addons-mp-new' );
	}

}
