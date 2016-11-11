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

		$tabs = array(
			'new'	  => __( 'New', APP_TD ),
			'popular' => __( 'Popular', APP_TD ),
		);

		$tabs = apply_filters( "appthemes_addons_mp_tabs_{$this->screen->id}", $tabs );

		// If a non-valid menu tab has been selected, And it's not a non-menu action.
		if ( empty( $this->args['tab'] ) || ( ! isset( $tabs[ $this->args['tab'] ] ) ) ) {
			$tab = key( $tabs );
		}

		$this->tabs = $tabs;

		// Get items from cache (if not expired) or from the RSS feed directly.
		$this->items = $this->fetch_mp_items();

		// Something went wrong - skip earlier.
		if ( ! $this->items ) {
			return;
		}

		// Set any applicable filters to the items list.
		$this->set_filters( $filters_list );

		// Apply filters to the list of items.
		$this->set_items_filtered();
	}

	/**
	 * Fetches the add-ons from cache (if not expired) or from the marketplace RSS feed, directly.
	 *
	 * @param int $limit The number of add-ons to retrieve from the RSS feed.
	 * @return array      The list of add-ons from the RSS feed.
	 */
	private function fetch_mp_items( $limit = 0 ) {

		$addons = array();

		include_once( ABSPATH . WPINC . '/feed.php' );

		if ( function_exists( 'fetch_feed' ) ) {

			if ( ( $addons = get_transient( '_appthemes-addons-mp-' . $this->args['tab'] ) ) === false ) {

				$feed = fetch_feed( 'https://feeds.feedburner.com/AppThemesMarketplace' );

				if ( ! is_wp_error( $feed ) ) {
					$limit = $feed->get_item_quantity( $limit );

					// Don't cache results since we're using our own transient.
					$feed->set_cache_duration( 0 );

					// Set the add-ons limit and array for parsing the feed.
					$items = $feed->get_items( 0, $limit );

					if ( $items ) {
						$addons = $this->get_addons_from_feed( $items );

						// Cache for one day.
						set_transient( '_appthemes-addons-mp-popular', $addons['popular'], 60 * 60 * 24 );
						set_transient( '_appthemes-addons-mp-new', $addons['new'], 60 * 60 * 24 );

						// Retrieve the addons list sorted as requested by the user (popular or new).
						$addons = $addons[ $this->args['tab'] ];
					}
				} else {
					$this->error = $feed;
				}
			}
		}
		return $addons;
	}

	/**
	 * Given the list of possible filters/values builds the filters list to be used on the add-ons browser
	 *
	 * @param array $filters_list A list of filter/values provided to the module.
	 */
	public function set_filters( $filters_list ) {

		$filters = array();

		// Add-ons Products Filter.
		if ( ( isset( $filters_list['products'] ) && $filters_list['products'] ) || ! isset( $filters_list['products'] ) ) {

			$products = $this->get_all_products();

			if ( ! empty( $filters_list['products'] ) ) {

				// Merge all products on a single list for easier filter check.
				$all_products = call_user_func_array( 'array_merge', array_values( $products ) );

				$filters['product'] = array_intersect_key( $all_products, array_flip( (array) $filters_list['products'] ) );
			} else {
				$filters['product'] = $products;
			}
			$filters['product'] = array_merge( array( '' => __( 'All Products', APP_TD ) ), $filters['product'] );

		}

		// Add-ons Categories Filter.
		if ( ( isset( $filters_list['categories'] ) && $filters_list['categories'] ) || ! isset( $filters_list['categories'] ) ) {

			$categories = $this->get_all_categories();

			if ( ! empty( $filters_list['categories'] ) ) {
				$filters['category'] = array_intersect_key( $categories, array_flip( (array) $filters_list['categories'] ) );
			} else {
				$filters['category'] = $categories;
			}
			$filters['category'] = array_merge( array( '' => __( 'All Categories', APP_TD ) ), $filters['category'] );

		}

		// Add-ons Authors Filter - builds the list based on the existing items.
		if ( ( isset( $filters_list['authors'] ) && $filters_list['authors'] ) || ! isset( $filters_list['authors'] ) ) {

			// Pluck the authors from the addons list and create an associative array of authors.
			$authors = array_values( wp_list_pluck( $this->items, 'author' ) );
			$authors = array_combine( array_values( $authors ), array_values( $authors ) );

			// Sort the authors alphabetically.
			natcasesort( $authors );

			$filters['author'][''] = __( 'All Authors', APP_TD );
			$filters['author'] = array_merge( $filters['author'], $authors );

			if ( ! empty( $filters_list['authors'] ) ) {
				$filters['author'] = array_intersect_key( $filters['author'], array_flip( (array) $filters_list['authors'] ) );
			} else {
				$filters['author'] = $filters['author'];
			}
		}
		$this->args['filters'] = $filters;
	}

	/**
	 * Applies any user filters and pagination to the list of items (add-ons).
	 */
	public function set_items_filtered() {

		$filter_by = $this->get_filter_by();
		$this->items = appthemes_wp_list_filter( $this->items, $filter_by );

		// Look for a keyword search.
		if ( ! empty( $_GET['s'] ) ) { // Input var okay.

			$keyword = sanitize_text_field( wp_unslash( $_GET['s'] ) ); // Input var okay.

			$keyword = wp_strip_all_tags( $keyword );
			$filter_by = array( 'title' => $keyword, 'description' => $keyword );

			$this->items = appthemes_wp_list_filter( $this->items, $filter_by, $operator = 'OR', $match = true );
		}

		$this->set_pagination_args( array(
			'total_items' => count( $this->items ),
			'per_page'    => $this->args['addons_per_page'],
		) );

		// Limit the add-ons list based on the current page.
		$this->items = array_slice( $this->items, ( $this->args['page'] - 1 ) * $this->args['addons_per_page'], $this->args['addons_per_page'] );
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

		// Get any user requested filters.
		$filter_by = $this->get_filter_by();

		// Iterate through all the filters to build the drop-downs.
		foreach ( $filter_list as $key => $filter ) {

			$options = '';

			foreach ( $filter as $group => $items ) {

				$group_options = '';

				foreach ( (array) $items as $slug => $title ) {

					$value = ! empty( $filter_by[ $key ] ) ? $filter_by[ $key ]  : '';

					$atts['value'] = $slug ? $slug : $group;

					if ( $atts['value'] === $value ) {
						$atts['selected'] = 'selected';
					} else {
						unset( $atts['selected'] );
					}
					$option = html( 'option', $atts, $title );

					if ( $slug ) {
						$group_options .= $option;
					} else {
						$options .= $option;
					}
				}

				// Group dropdown items if requested.
				if ( ! empty( $group_options ) ) {
					$options .= html( 'optgroup', array( 'label' => $group ), $group_options );
				}
			}

			$filters .= html( 'select', array( 'name' => esc_attr( "$key" ), 'class' => 'app-mp-addons-filter' ), $options );
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
			</ul>
			<label>
				<span class="screen-reader-text"><?php echo __( 'Search Add-ons', APP_TD ); ?></span>
				<input type="search" name="s" value="<?php echo esc_attr( $term ) ?>" class="wp-filter-search" placeholder="<?php echo esc_attr__( 'Search Add-ons', APP_TD ); ?>">
			</label>
			<input type="submit" name="" id="search-submit" class="button screen-reader-text" value="<?php echo esc_attr__( 'Search Add-ons', APP_TD ); ?>">
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

		$defaults = array(
			'category' => array(),
			'product'  => array(),
		);
		$filters = wp_parse_args( $this->get_filter_list(), $defaults );

		$addons = '';

		foreach ( $items as $item ) {

			// Get the add-ons meta.
			$addon = new stdClass();

			$addon->title       = $item->get_title();
			$addon->description = $item->get_description();

			$addon->date        = $item->get_date( 'Y-m-d' );
			$addon->human_date  = human_time_diff( strtotime( $item->get_date() ) );

			$author             = $item->get_item_tags( 'http://purl.org/dc/elements/1.1/', 'creator' );
			$addon->author      = $author[0]['data'];
			$addon->author_link = html( 'a', array( 'href' => esc_url( sprintf( 'https://www.appthemes.com/members/%1$s/seller/', $addon->author ) ), 'target' => 'blank' ), $addon->author );

			// Categorize the add-on.
			$categories  = $item->get_categories();
			$categories  = wp_list_pluck( $categories, 'term' );
			$categories_slugs = array_map( 'sanitize_title', (array) $categories );

			$all_cats = array_keys( $this->get_all_categories() );
			$all_themes = array_keys( $this->get_all_themes() );

			// Some child themes do not have the 'child-theme' term assigned - try to assign it here by checking if the term matches a theme and it's not already categorized.
			if ( ! array_intersect( $categories_slugs, $all_cats ) && array_intersect( $categories_slugs, $all_themes ) ) {
				$categories_slugs[] = 'child-themes';
				$addon->category_desc = __( 'Child Themes', APP_TD );
			} else {
				$addon->category_desc = $categories[0];
			}

			// Add-ons with no theme specified means they are compatible with ALL themes.
			// That said, enqueue each theme to the add-on 'category' property.
			if ( ! array_intersect( $categories_slugs, $all_themes ) ) {
				$categories_slugs = array_merge( $categories_slugs, $all_themes );
			}

			$addon->category = $categories_slugs;
			$addon->product  = array_diff( $categories_slugs, array( 'plugins' ) );

			// Requirements.
			$requirements        = array_udiff( $categories, $filters['category'], array( 'plugins' ), 'strcasecmp' );
			$addon->requirements = implode( ', ', $requirements );

			// Strip all HTML tags from the description.
			$description = wp_strip_all_tags( $addon->description );
			$addon->description	  = wp_trim_words( $description, 50, '...' );

			// Thumbnail.
			$addon->image = '';

			if ( ! empty( $item->data['child']['']['thumbnail'][0]['child']['']['img'][0]['attribs'][''] ) ) {
				$image = $item->data['child']['']['thumbnail'][0]['child']['']['img'][0]['attribs'][''];
				$addon->image = html( 'img', array( 'src' => $image['src'], 'width' => $image['width'], 'height' => $image['height'], 'alt' => ! empty( $image['alt'] ) ? $image['alt'] : '' ) );
			}

			// Custom RSS tags.
			$link_args = array(
				'utm_source'   => 'addons',
				'utm_medium'   => 'wp-admin',
				'utm_campaign' => 'Add-ons%20Module',
			);

			$addon->link = $item->data['child']['']['permalink'][0]['data'];
			$addon->link = add_query_arg( $link_args, $addon->link );

			// Use the custom permalink tag for the item title link.
			$addon->title = html( 'a', array( 'href' => esc_url( $addon->link ), 'target' => 'blank' ), $addon->title );

			// Custom RSS feed meta.
			$addon->author_username = $item->data['child']['']['author_username'][0]['data'];
			$addon->author_link     = html( 'a', array( 'href' => esc_url( trailingslashit( $item->data['child']['']['author_url'][0]['data'] ) ), 'target' => 'blank' ), $addon->author_username );
			$addon->last_updated    = date( 'Y-m-d', (int) $item->data['child']['']['last_updated'][0]['data'] );
			$addon->last_updated_h  = human_time_diff( strtotime( $addon->last_updated ) );
			$addon->price           = '$'.$item->data['child']['']['price'][0]['data'];
			$addon->rating          = $item->data['child']['']['rating'][0]['data'];
			$addon->votes           = $item->data['child']['']['votes'][0]['data'];
			$addon->rank            = (int) $item->data['child']['']['rank'][0]['data'];

			if ( ! $addon->rank ) {
				$addon->rank = 9999;
			}

			$addons['popular'][ $addon->rank ][] = $addon;
			$addons['new'][ strtotime( $addon->date ) ][] = $addon;
		}

		ksort( $addons['popular'], SORT_NUMERIC );
		krsort( $addons['new'], SORT_NUMERIC );

		$addons['popular'] = call_user_func_array( 'array_merge', $addons['popular'] );
		$addons['new'] = call_user_func_array( 'array_merge', $addons['new'] );

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
					<strong><?php echo __( 'Requirements:', APP_TD ); ?></strong> <span title="<?php echo esc_attr( $addon->requirements ); ?>"><?php echo $addon->requirements; ?></span>
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
	 * Retrieves the requested user selected filters values, if any.
	 * Otherwise, assigns default selected filter values for each filter that contains only a single value.
	 *
	 * E.g:
	 *	 - categories => array( 'plugins' ); // in this case, since the categories filter.
	 *										    only has one possible value, select it by default.
	 *
	 * @return array An associative array of selected filter/values.
	 */
	public function get_filter_by() {

		$active_product = _appthemes_get_addons_mp_args( 'product' );
		$active_product = array_pop( $active_product );

		$filters = $this->get_filter_list();

		$params = array_map( 'esc_attr', $_GET ); // Input var okay.
		$filter_by = wp_parse_args( $params, $filters );

		// Make sure the 'filter_by' only contains valid filter keys.
		$filter_by = array_intersect_key( $filter_by, $filters );

		// Iterate through the valid filters and try assign a default value if none selected.
		foreach ( $filter_by as $filter => $items ) {

			$values[ $filter ] = array();

			// Flatten any grouped items in the current filter.
			foreach ( (array) $items as $item ) {

				if ( is_array( $item ) ) {
					$values[ $filter ] = array_merge( $values[ $filter ], $item );
				} else {
					$values[ $filter ][] = $item;
				}

			}

			if ( empty( $values[ $filter ] ) ) {
				$values[ $filter ] = $items;
			}

			// Get rid of the empty arrays to have a real count of this filter items.
			$values[ $filter ] = array_filter( $values[ $filter ], 'strlen' );

			if ( ! $values[ $filter ] ) {
				// User selected 'All' - show all results for this filter.
				unset( $filter_by[ $filter ] );
			} elseif ( ! is_array( $values[ $filter ] ) ) {
				// User selected a value for this filter.
				continue;
			} else {

				if ( count( $values[ $filter ] ) > 1 ) {

					// Default to the active product if available on the list of items.
					if ( 'product' === $filter && $active_product && isset( $values[ $filter ][ $active_product ] ) ) {
						$filter_by[ $filter ] = $active_product;
					} else {
						// Default to 'All' - show all results for this filter.
						unset( $filter_by[ $filter ] );
					}

				} else {
					// One value only filter (use it as the default value if none other requested in '$_GET').
					$filter_by[ $filter ] = reset( $values[ $filter ] );
				}

			}

		}
		return $filter_by;
	}

	/**
	 * Retrieves all AppThemes products.
	 *
	 * @return array An associative array of slug/products.
	 */
	public function get_all_products() {

		return array(
			__( 'Themes', APP_TD )  => $this->get_all_themes(),
			__( 'Plugins', APP_TD ) => $this->get_all_plugins(),
		);

	}

	/**
	 * Retrieves a complete list of the AppThemes themes.
	 *
	 * @return array An associative array of slug/theme name.
	 */
	public function get_all_themes() {

		return array(
			'classipress'    => 'ClassiPress',
			'clipper'        => 'Clipper',
			'hirebee'        => 'HireBee',
			'ideas'          => 'Ideas',
			'jobroller'      => 'JobRoller',
			'qualitycontrol' => 'Quality Control',
			'taskerr'        => 'Taskerr',
			'vantage'        => 'Vantage',
		);

	}

	/**
	 * Retrieves a complete list of the AppThemes plugins.
	 *
	 * @return array An associative array of slug/plugin name.
	 */
	public function get_all_plugins() {

		$plugins = array(
			'pay2post' => 'Pay2Post',
		);

		return $plugins;
	}

	/**
	 * Retrieves a complete list of the AppThemes themes.
	 *
	 * @return array An associative array of slug/theme name
	 */
	public function get_all_categories() {

		return array(
			'plugins'          => __( 'Plugins', APP_TD ),
			'payment-gateways' => __( 'Payment Gateways', APP_TD ),
			'child-themes'     => __( 'Child Themes', APP_TD ),
			'general-themes'   => __( 'General Themes', APP_TD ),
		);
	}

}
