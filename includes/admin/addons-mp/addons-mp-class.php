<?php
/**
 *  Marketplace add-ons main class.
 *
 * @package Components\Add-ons
 */

/**
 * The class responsible for displaying the Add-ons browser.
 */
class APP_Addons_List_Table extends WP_List_Table {

	const SERVER_URL    = 'https://marketplace.appthemes.com/wp-json';
	const API_NAMESPACE = 'mktplc/v1';

	/**
	 * Additional arguments for the list table.
	 * @var string
	 */
	protected $args;

	/**
	 * The list table page slug.
	 * @var string
	 */
	protected $menu_parent;

	/**
	 * The list table menu parent.
	 * @var string
	 */
	protected $page_slug;

	/**
	 * Additional arguments for the list table.
	 * @var string
	 */
	protected $tabs;

	/**
	 * The list of filters retrieved from REST API server.
	 * @var type
	 */
	protected $raw_filters = array();

	/**
	 * The errors returned during the items request.
	 * @var object
	 */
	protected $error;

	/**
	 * Constructor.
	 *
	 * Overrides the list class to display AppThemes add-ons.
	 *
	 * @param string $page_slug   The page slug name.
	 * @param [type] $menu_parent The menu parent name.
	 * @param array  $args        Additional args for the list.
	 */
	public function __construct( $page_slug, $menu_parent, $args = array() ) {

		$defaults = array(
			'tab'             => 'new',
			'page'            => 1,
			'addons_per_page' => 30,
		);
		$this->args = wp_parse_args( $args, $defaults );

		$this->page_slug = $page_slug;
		$this->menu_parent = $menu_parent;

		parent::__construct( $this->args );

		$this->prepare_items( $this->args['filters'] );
	}

	/**
	 * Prepares the items before they are displayed.
	 *
	 * @uses apply_filters() Calls 'appthemes_addons_mp_tabs_<screen_id>'
	 *
	 * @param array $filters_list A list of pre-set filter/values provided to the module.
	 */
	public function prepare_items( $filters_list = '' ) {

		$this->raw_filters = $this->fetch_mp_filters();

		$tabs_filter = wp_list_filter( $this->raw_filters, array( 'name' => 'view' ) );
		$tabs        = $tabs_filter[0]['values'];

		foreach ( $tabs as &$tab ) {
			$tab = translate( $tab, APP_TD );
		}

		$tabs = apply_filters( "appthemes_addons_mp_tabs_{$this->screen->id}", $tabs );

		// If a non-valid menu tab has been selected, And it's not a non-menu action.
		if ( empty( $this->args['tab'] ) || ( ! isset( $tabs[ $this->args['tab'] ] ) ) ) {
			$tab = key( $tabs );
		}

		$this->tabs = $tabs;

		// Set any applicable filters to the items list.
		$this->set_filters( $filters_list );

		// Get items from cache (if not expired) or from the REST API directly.
		$this->items = $this->fetch_mp_items();
	}

	/**
	 * Fetches the add-ons from cache (if not expired) or from the marketplace
	 * REST API, directly.
	 *
	 * @param int $limit The number of add-ons to retrieve.
	 * @return array      The list of add-ons.
	 */
	private function fetch_mp_items() {

		$filters = $this->get_filter_list();
		$args  = array_map( 'esc_attr', $_GET ); // Input var okay.

		// Get the first theme from the related parameter.
		$active_product = _appthemes_get_addons_mp_args( 'product' );
		$active_product = array_pop( $active_product );

		if ( ! isset( $args['product'] ) && $active_product ) {
			$args['product'] = $active_product;
		}

		foreach ( $args as $key => $arg ) {
			if ( empty( $arg ) || empty( $filters[ $key ][ $arg ] ) ) {
				unset( $args[ $key ] );
				continue;
			}
		}

		// Look for a keyword search.
		if ( ! empty( $_GET['s'] ) ) { // Input var okay.
			$args['search'] = $_GET['s'];
		}

		$args['per_page'] = $this->args['addons_per_page'];
		$args['page']     = $this->args['page'];
		$args['view']     = $this->args['tab'];

		$query_url  = add_query_arg( $args, self::SERVER_URL . '/' . self::API_NAMESPACE . '/items' );
		$query_hash = substr( md5( $query_url ), 0, 21 );

		if ( ! $response = get_transient( '_appthemes-addons-mp-response-' . $query_hash ) ) {
			$response = $this->remote_get( 'items', $args );
			$items    = $response['items'];

			if ( is_object( $items ) && $items->message ) {
				$this->error = new WP_Error( $items->code, $items->message, $items->data );
				return array();
			}

			$response['items'] = $this->get_addons_from_feed( $items );
			set_transient( '_appthemes-addons-mp-response-' . $query_hash, $response, DAY_IN_SECONDS );
		}

		$this->set_pagination_args( array(
			'total_items' => $response['total'],
			'per_page'    => $this->args['addons_per_page'],
		) );

		return $response['items'];
	}

	/**
	 * Fetches the add-ons filters from cache (if not expired) or from the marketplace REST API, directly.
	 *
	 * @return array      The list of add-ons filters.
	 */
	private function fetch_mp_filters() {
		$query_hash = substr( md5( self::SERVER_URL . '/' . self::API_NAMESPACE . '/filters' ), 0, 21 );

		if ( ! $filters = get_transient( "_appthemes-addons-mp-filters-$query_hash" ) ) {
			$response = $this->remote_get( 'filters' );
			$filters  = $response['items'];

			if ( is_object( $filters ) && $filters->message ) {
				$this->error = new WP_Error( $filters->code, $filters->message, $filters->data );
				return array();
			}

			// Super elegant recursively cast a PHP object to array.
			$filters = json_decode( json_encode( $filters ), true );
			set_transient( "_appthemes-addons-mp-filters-$query_hash", $filters, DAY_IN_SECONDS );
		}

		return $filters;
	}

	/**
	 * Given the list of possible filters/values builds the filters list to be used on the add-ons browser
	 *
	 * @param array $filters_list A list of filter/values provided to the module.
	 */
	public function set_filters( $filters_list ) {

		$filters = array();

		// Add-ons Products Filter.
		foreach ( $this->raw_filters as $raw_filter ) {
			$name = $raw_filter['name'];
			if ( 'view' === $name || ! isset( $raw_filter['values'] ) ) {
				continue;
			}

			if ( isset( $filters_list[ $name ] ) && ! $filters_list[ $name ] ) {
				continue;
			}

			foreach ( $raw_filter['values'] as &$value ) {
				$value = translate( $value, APP_TD );
			}

			if ( ! empty( $filters_list[ $name ] ) ) {
				$all_values = call_user_func_array( 'array_merge', array_values( $raw_filter['values'] ) );
				$raw_filter['values'] = array_intersect_key( $all_values, array_flip( (array) $filters_list[ $name ] ) );
			}

			$filters[ $name ] = $raw_filter['values'];
		}

		$this->args['filters'] = $filters;
	}

	/**
	 * Outputs the available Add-ons tabs.
	 */
	protected function get_views() {
		$display_tabs = array();

		$admin_url = strpos( $this->menu_parent, '.php' ) === false ? self_admin_url( 'admin.php' ) : self_admin_url( $this->menu_parent );

		foreach ( (array) $this->tabs as $action => $text ) {
			$class = ( $action === $this->args['tab'] ) ? ' current' : '';
			$href = add_query_arg( array( 'page' => $this->page_slug, 'tab' => $action ), $admin_url );
			$display_tabs[ admin_url( "addons-install-{$action}" ) ] = "<a href='" . esc_url( $href ) . "' class='" . esc_attr( $class ) . "'>" . $text . '</a>';
		}

		return $display_tabs;
	}

	/**
	 * Outputs the Add-ons filters.
	 */
	protected function get_filters() {

		// Get the first theme from the related parameter.
		$active_product = _appthemes_get_addons_mp_args( 'product' );
		$active_product = array_pop( $active_product );

		$filters = '';

		// Get all available filters.
		if ( ! ( $filter_list = $this->get_filter_list() ) ) {
			return $filters;
		}

		foreach ( $filter_list as $name => $values ) {
			$default = 'product' === $name ? $active_product : '';
			$filter = array(
				'name'    => $name,
				'title'   => '',
				'type'    => 'select',
				'values'  => $values,
				'default' => $default,
				'extra'   => array( 'class' => 'app-mp-addons-filter' ),
			);

			$filters .= html( 'li', scbForms::input( $filter, $_GET ) );
		}

		return $filters;
	}

	/**
	 * Override parent views so we can use the filter bar display.
	 *
	 * @uses do_action() Calls 'appthemes_addons_mp_before_table'
	 */
	public function views() {
		$views = $this->get_views();

		/** This filter is documented in wp-admin/inclues/class-wp-list-table.php */
		$views = apply_filters( "views_{$this->screen->id}", $views );
?>
		<div class="wp-filter app-mp-addons">
			<ul class="filter-links">
				<?php
				if ( ! empty( $views ) ) {
					foreach ( $views as $class => $view ) {
						$class = esc_attr( $class );
						$views[ $class ] = "\t<li class='" . esc_attr( $class ) ."'>$view";
					}

					echo implode( " </li>\n", $views ) . "</li>\n";
				}
				?>
			</ul>

			<?php $this->search_form(); ?>
		</div>
		<?php
		/**
		 * Fires before the add-ons mp table is displayed.
		 *
		 * Recommended for marketplace marketing campaigns.
		 */
		do_action( 'appthemes_addons_mp_before_table' );
		?>
<?php
	}

	/**
	 * Outputs all the Add-ons page content.
	 */
	public function display() {
		$singular = $this->_args['singular'];

		$data_attr = '';

		if ( $singular ) {
			$data_attr = " data-wp-lists='list:$singular'";
		}

		$this->display_tablenav( 'top' );
?>
		<div class="wp-list-table app-mp-addons <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">

			<div id="the-list" <?php echo esc_attr( $data_attr ); ?> >
				<?php $this->display_rows_or_placeholder(); ?>
			</div>
		</div>
<?php
		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Outputs the pagination bar.
	 *
	 * @param string $which The position for the pagination bar: 'top' or 'bottom'.
	 */
	protected function display_tablenav( $which ) {

		if ( 'top' === $which ) :
			wp_referer_field();
		?>

			<div class="tablenav top">
				<div class="alignleft actions">
					<?php
					/**
					 * Fires before the add-ons mp table header pagination is displayed.
					 */
					do_action( 'appthemes_addons_mp_table_header' ); ?>
				</div>
				<?php $this->pagination( $which ); ?>
				<br class="clear" />
			</div>

		<?php else : ?>

			<div class="tablenav bottom">
				<?php $this->pagination( $which ); ?>
				<br class="clear" />
			</div>

		<?php
		endif;
	}

	/**
	 * Retrieve a list of CSS classes to be used on the table listing.
	 *
	 * @return array The list of CSS classes.
	 */
	protected function get_table_classes() {
		return array( 'widefat', $this->_args['plural'] );
	}

	/**
	 * Outputs the Add-ons search form.
	 */
	private function search_form() {

		if ( isset( $_REQUEST['s'] ) ) { // Input var okay.
			$term = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ); // Input var okay.
		} else {
			$term = '';
		}
?>
		<form id="app-addons-search" class="search-form search-plugins" method="get" action="">
			<ul class="filter-links addons-filter">
				<?php echo $this->get_filters(); ?>
				<li>
					<div class="app-mp-addons-filter">
						<span class="screen-reader-text"><?php echo __( 'Search Add-ons', APP_TD ); ?></span>
						<input type="search" name="s" value="<?php echo esc_attr( $term ) ?>" class="wp-filter-search" placeholder="<?php echo esc_attr__( 'Search Add-ons', APP_TD ); ?>">
					</div>
					<input type="submit" name="" id="search-submit" class="button screen-reader-text" value="<?php echo esc_attr__( 'Search Add-ons', APP_TD ); ?>">
				</li>
			</ul>
			<?php appthemes_pass_request_var( 'page' ); ?>
			<?php appthemes_pass_request_var( 'tab' ); ?>
			<?php appthemes_pass_request_var( 'post_type' ); ?>
		</form>
<?php
	}

	/**
	 * Retrieves all the add-ons from an RSS list as an array of objects.
	 *
	 * @param array $items List of {@see SimplePie_Item} objects.
	 * @return array       List of add-ons as stdClass objects.
	 */
	public function get_addons_from_feed( $items ) {

		$addons     = array();
		$filters    = $this->get_filter_list();
		$authors    = $filters['author'];
		$all_cats   = $filters['cat'];
		$all_prods  = $filters['product'];

		foreach ( $items as $item ) {

			// Get the add-ons meta.
			$addon = new stdClass();

			$addon->title       = $item->title->rendered;
			$addon->description = $item->excerpt->rendered;

			$addon->date        = $item->date;
			$addon->human_date  = human_time_diff( strtotime( $item->date ) );

			if ( ! empty( $authors[ $item->author ] ) ) {
				$addon->author      = $authors[ $item->author ];
				$addon->author_link = html( 'a', array( 'href' => esc_url( sprintf( 'https://www.appthemes.com/members/%1$s/seller/', $addon->author ) ), 'target' => 'blank' ), $addon->author );
			} else {
				$addon->author      = '';
				$addon->author_link = '';
			}

			$addon->category_desc = implode( ', ', $item->_mkt_item_category );

			// Requirements.
			$addon->compats = implode( ', ', $item->_mkt_item_compats );

			// Strip all HTML tags from the description.
			$addon->description = wp_trim_words( wp_strip_all_tags( $addon->description ), 50, '...' );

			// Custom RSS tags.
			$link_args = array(
				'utm_source'   => 'addons',
				'utm_medium'   => 'wp-admin',
				'utm_campaign' => 'Add-ons%20Module',
			);

			$addon->link = $item->link;
			$addon->link = add_query_arg( $link_args, $addon->link );

			// Use the custom permalink tag for the item title link.
			$addon->title = html( 'a', array( 'href' => esc_url( $addon->link ), 'target' => 'blank' ), $addon->title );

			// Thumbnail.
			$addon->image = ! empty( $item->_mkt_thumbnail[0] ) ? html( 'img', array( 'src' => $item->_mkt_thumbnail[0] ) ) : '';

			// Custom meta.
			$addon->last_updated   = $item->modified_gmt;
			$addon->last_updated_h = human_time_diff( strtotime( $addon->last_updated ) );
			$addon->price          = '$' . $item->_mkt_item_price;
			$addon->rating         = $item->_mkt_item_rating;
			$addon->votes          = $item->_mkt_item_votes;

			$addons[] = $addon;
		}

		return $addons;
	}

	/**
	 * Outputs a given add-on using custom markup.
	 *
	 * @uses apply_filters() Calls 'appthemes_addons_mp_markup_<screen_id>'.
	 * @uses do_action()	 Calls 'appthemes_addons_mp_addon_after'.
	 *
	 * @param object $addon The add-on object to output.
	 */
	public function single_row( $addon ) {

		ob_start();
?>
		<div class="plugin-card">
			<div class="plugin-card-top">
				<a href="<?php echo esc_url( $addon->link ); ?>" target="_new" class="thickbox plugin-icon"><?php echo $addon->image; ?></a>
				<div class="name-top">
					<div class="name column-name">
						<h4><?php echo $addon->title; ?></h4>
					</div>
					<div class="action-links price-meta">
						<ul class="plugin-action-buttons price">
							<li><?php echo $addon->price; ?></li>
						</ul>
					</div>
				</div>
				<div class="desc column-description">
					<p><?php echo $addon->description; ?></p>
					<p class="authors">
						<cite><?php echo sprintf( __( 'By %1$s', APP_TD ), $addon->author_link ); ?> </cite>
					</p>
				</div>
			</div>
			<div class="plugin-card-bottom">
				<div class="vers column-rating">
					<?php wp_star_rating( array( 'rating' => (double) $addon->rating, 'number' => $addon->votes ) ); ?>
					<span class="num-ratings">(<?php echo number_format_i18n( $addon->votes ); ?>)</span>
				</div>
				<div class="column-updated">
					<strong><?php echo __( 'Last Updated:', APP_TD ); ?></strong>
					<span title="<?php echo esc_attr( $addon->last_updated ); ?>"><?php echo sprintf( __( '%1$s ago', APP_TD ), $addon->last_updated_h ); ?></span>
				</div>
				<div class="column-category">
					<strong><?php echo __( 'Category:', APP_TD ); ?></strong> <span title="<?php echo esc_attr( $addon->category_desc ); ?>"><?php echo $addon->category_desc; ?></span>
				</div>
				<div class="column-requirements">
					<strong><?php echo __( 'Compatibilities:', APP_TD ); ?></strong> <span title="<?php echo esc_attr( $addon->compats ); ?>"><?php echo $addon->compats; ?></span>
				</div>
			</div>
			<?php
			/**
			 * Fires after the all the content for each plugin is displayed.
			 *
			 * Recommended for add-ons marketing campaigns: discounts codes, etc.
			 */
			do_action( 'appthemes_addons_mp_addon_after', $addon ); ?>
		</div>
<?php
		$output = ob_get_clean();

		echo apply_filters( "appthemes_addons_mp_markup_{$this->screen->id}", $output, $addon );
	}

	/**
	 * Outputs the no items message.
	 */
	public function no_items() {

		if ( isset( $this->error ) ) {
			$message = $this->error->get_error_message() . '<p class="hide-if-no-js"><a href="#" class="button" onclick="document.location.reload(); return false;">' . __( 'Try again', APP_TD ) . '</a></p>';
		} else {
			$message = __( 'No add-ons match your request.', APP_TD );
		}
		echo '<div class="no-plugin-results">' . $message . '</div>';
	}

	/**
	 * Retrieves a list of all the available Add-ons filters.
	 *
	 * @return array An associative array of available filters.
	 */
	public function get_filter_list() {
		return apply_filters( "appthemes_addons_mp_filters_{$this->screen->id}", $this->args['filters'] );
	}

	/**
	 * Do a request to REST API server.
	 *
	 * @param string $type The request route.
	 * @param array  $args The request arguments.
	 *
	 * @return array An associative array with a list of retrieved items and their total.
	 */
	protected function remote_get( $type, $args = array() ) {

		$server_url = self::SERVER_URL;
		$namespace  = self::API_NAMESPACE;

		$args    = array_merge( array( 'per_page' => 100 ), $args );
		$api_url = add_query_arg( $args, "{$server_url}/{$namespace}/{$type}" );
		$api_url = esc_url_raw( $api_url );

		$raw_response = wp_remote_get( $api_url );

		if ( is_wp_error( $raw_response ) ) {
			return array(
				'items' => $raw_response,
				'total' => 0,
			);
		}

		$response['items'] = json_decode( wp_remote_retrieve_body( $raw_response ) );
		$response['total'] = wp_remote_retrieve_header( $raw_response, 'x-wp-total' );

		return $response;
	}
}
