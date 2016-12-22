<?php
/**
 * Admin Dashboard
 *
 * @package Framework\Dashboard
 */
class APP_Dashboard extends scbBoxesPage {

	const NEWS_FEED        = 'http://feeds2.feedburner.com/appthemes';
	const TUTORIALS_FEED   = 'http://feeds.feedburner.com/AppThemesTutorials/';
	const MARKETPLACE_FEED = 'http://feeds.feedburner.com/AppThemesMarketplace/';
	const FORUM_FEED       = 'http://forums.appthemes.com/external.php?type=RSS2';

	/**
	 * Constructor
	 *
	 * @param array $args {
	 *     Optional. An array of arguments.
	 *
	 *     @type string $page_title    Dashboard title. Default 'AppThemes Dashboard'
	 *     @type string $menu_title    Menu title. Default 'AppThemes'
	 *     @type string $submenu_title Submenu title. Default 'Dashboard'
	 *     @type string $page_slug     Page slug. Default 'app-dashboard'
	 *     @type string $toplevel      Menu level. Default 'menu'. Accepts 'menu', 'submenu'.
	 *     @type int    $position      Position in menu. Default 3.
	 *     @type string $screen_icon   Screen icon (obsolete). Default 'themes'.
	 *     @type int    $columns       Number of columns of widgets. Default 2. Accepts 1, 2, 3, 4.
	 *     @type array  $boxes {
	 *         An array of additional metaboxes with array of parameters.
	 *         Default 'stats', 'news', 'tutorials', 'marketplace', 'support_forum'.
	 *
	 *             @type string   $title         Title of the meta box.
	 *             @type callback $callback      Optional. Function that fills the box with the desired content.
	 *                                           The function should echo its output.
	 *                                           Default array( $this, '_intermediate_callback' )
	 *                                           Accepts function name or array(object, method).
	 *             @type string   $context       Optional. The context within the screen where the boxes
	 *                                           should display. Default 'normal'.
	 *                                           Accepts 'normal', 'side', 'column3', 'column4'.
	 *             @type string   $priority      Optional. The priority within the context where the boxes
	 *                                           should show ('high', 'low'). Default 'default'.
	 *             @type array    $callback_args Optional. Data that should be set as the $args property
	 *                                           of the box array (which is the second parameter passed
	 *                                           to your callback). Default array().
	 *     }
	 * }
	 */
	function __construct( $args = array() ) {

		$this->_setup_args( $args );
		$this->_setup_boxes();

		scbAdminPage::__construct();
	}

	protected function _setup_args( $args = array() ){

		$defaults = array(
			'page_title'    => __( 'AppThemes Dashboard', APP_TD ),
			'menu_title'    => 'AppThemes',
			'submenu_title' => __( 'Dashboard', APP_TD ),
			'page_slug'     => 'app-dashboard',
			'toplevel'      => 'menu',
			'position'      => 3,
			'screen_icon'   => 'themes',
			'columns'       => 2,
			'icon_url'      => "dashicons-at-appthemes",
			'boxes'         => array(
				'news' => array(
					'title'    => $this->box_icon( 'at-news' ) . __( 'News', APP_TD ),
				),
				'marketplace' => array(
					'title'    => $this->box_icon( 'at-marketplace' ) . __( 'Marketplace', APP_TD ),
					'context'  => 'side',
				),
				'tutorials' => array(
					'title'    => $this->box_icon( 'at-learn' ) . __( 'Tutorials', APP_TD ),
					'context'  => 'side',
				),
			),
		);

		if ( current_theme_supports( 'app-dashboard' ) ) {

			// additional boxes available only if theme support used
			// to avoid duplicates if class instatiated directly.
			$args['boxes']['stats'] = array(
				'title'    => $this->box_icon( 'at-chart-bar' ) .  __( 'Snapshot', APP_TD ),
				'priority' => 'high',
			);

			$args['boxes']['support_forum'] = array(
				'title'    => $this->box_icon( 'at-discussion' ) . __( 'Forums', APP_TD ),
				'priority' => 'low',
			);

			$args['boxes'] = wp_parse_args( $args['boxes'], $defaults['boxes'] );

			// numeric array, contains multiple sets of arguments
			// first item contains preferable set
			$args_sets = get_theme_support( 'app-dashboard' );
			if ( ! is_array( $args_sets ) ) {
				$args_sets = array();
			}
			foreach ( $args_sets as $args_set ) {
				foreach ( $args_set as $key => $arg ) {
					if ( ! isset( $args[ $key ] ) ) {
						$args[ $key ] = $arg;
					} elseif ( is_array( $arg ) ) { // boxes
						$args[ $key ] = wp_parse_args( (array) $args[ $key ], $arg );
					}
				}
			}
		}
		$this->args = wp_parse_args( $args, $defaults );
	}

	protected function _setup_boxes() {
		$boxes = $this->args['boxes'];

		foreach ( $boxes as $name => $args ) {
			$defaults = array(
				'name'          => $name,
				'title'         => ucfirst( $name ),
				'context'       => 'normal',
				'priority'      => 'default',
				'callback_args' => array()
			);

			// move clallback arg to the box args to avoid overriding in the scbBoxesPage::boxes_init()
			if ( isset( $args['callback'] ) ) {
				$args['callback_args']['callback'] = $args['callback'];
				unset( $args['callback'] );
			}

			$args = wp_parse_args( $args, $defaults );
			$this->boxes[] = array_values($args);
		}
	}

	public function _intermediate_callback( $_, $box ) {

		if ( isset( $box['args']['callback'] ) && ! empty( $box['args']['callback'] ) ) {
			$callback = $box['args']['callback'];
			unset($box['args']['callback']);
		} else {
			list( $name ) = explode( '-', $box['id'] );
			$callback = array( $this, $name . '_box' );
		}

		if ( is_array( $callback ) && ( count( $callback ) < 2 || ! method_exists( $callback[0], $callback[1] ) ) ) {
			$callback[0] = is_object( $callback[0] ) ? get_class( $callback[0] ) : $callback[0];
			trigger_error( "callback method $callback[0]::$callback[1]() doesn't exists", E_USER_WARNING );
			return;
		}

		if ( is_string( $callback ) && ! function_exists( $callback ) ) {
			trigger_error( "callback function $callback() doesn't exists", E_USER_WARNING );
			return;
		}

		call_user_func_array( $callback, $box['args'] );
	}

	public function stats_box() {

		$users_stats = $this->_get_user_counts();
?>
		<style type="text/css">
			#stats ul {font-size: 12px;}
			div.stats-info {float:left;width:45%;}
			div.stats_overview { float: right; width: 45%; background: none repeat scroll 0 0 #F9F9F9; border: 1px solid #DFDFDF; -moz-border-radius: 5px;-webkit-border-radius: 5px;-o-border-radius: 5px;-khtml-border-radius: 5px;border-radius: 5px;}
			.stats_overview, .overview_today {float: left; width: 50%;}
			.stats_overview, .overview_previous {float: left; width: 50%;}
			.stats_overview p.overview_day { font-size: 12px !important;color: #666666; font-weight: bold; margin-top: 6px;}
			.stats_overview p {margin: 0;padding: 0;text-align: center;text-shadow: 0 1px 0 #FFFFFF;text-transform: uppercase;}
			.stats_overview h3 {text-align: center;text-shadow: 0 1px 0 #FFFFFF;}
			.stats_overview p.overview_count {color: #333333;font-size: 20px !important;font-weight: bold;}
			.stats_overview p.overview_type em {background: none repeat scroll 0 0 #FFFBE4;border-radius: 3px 3px 3px 3px;padding: 1px 5px 2px;}
			.stats_overview p.overview_type, .stats_overview p.overview_type_seek { color: #999999; font-size: 9px !important; margin-bottom: 7px;}
			.stats_overview p.overview_type_seek em { background: none repeat scroll 0 0 #FFFBE4; border-radius: 3px 3px 3px 3px;padding: 1px 5px 2px;}
		</style>

		<div class="stats_overview">
			<h3><?php _e( 'New Registrations', APP_TD ); ?></h3>
			<div class="overview_today">
				<p class="overview_day"><?php _e( 'Today', APP_TD ); ?></p>
				<p class="overview_count"><?php echo $users_stats['today']; ?></p>
				<p class="overview_type"><em><?php _e( 'Customers', APP_TD ); ?></em></p>
			</div>

			<div class="overview_previous">
				<p class="overview_day"><?php _e( 'Yesterday', APP_TD ); ?></p>
				<p class="overview_count"><?php echo $users_stats['yesterday']; ?></p>
				<p class="overview_type"><em><?php _e( 'Customers', APP_TD ); ?></em></p>
			</div>
		</div>

		<div class="stats-info">
<?php

		/**
		 * Hook to add app-specific stats sections
		 *
		 * @param array $sections {
		 *     An array of a sections and section items within. Where array key is
		 *     section slug and value is an array of section items
		 *
		 *     @type array $section {
		 *         An array of section items. Where key is item label and value is item text
		 *
		 *         @type string $item Item value
		 *     }
		 *     @type array $apps {
		 *         A single dimentional array of activated apps. Section slug 'apps'.
		 *
		 *         @type string $app App name and version (i.e 'Vantage 1.4.1')
		 *     }
		 * }
		 */
		$sections = apply_filters( 'appthemes_dashboard_stats_box', array() );

		// total users section
		$sections['total_users'][ __( 'Total Users', APP_TD ) ] = array(
			'text' => $users_stats['total_users'],
			'url' => 'users.php',
		);

		// revenue section
		if ( current_theme_supports( 'app-payments' ) ) {
			$date_week_ago = date( 'Y-m-d', strtotime( '-7 days', current_time( 'timestamp' ) ) );
			$sections['revenue'][ __( 'Last 7 Days', APP_TD ) ] = appthemes_get_price( appthemes_get_orders_revenue( $date_week_ago ) );
			$sections['revenue'][ __( 'Overall', APP_TD ) ] = appthemes_get_price( appthemes_get_orders_revenue() );
		}

		// merge 'apps' section and move to the end
		if ( isset( $sections['apps'] ) ) {
			$apps = $sections['apps'];
			unset( $sections['apps'] );
			if ( is_array( $apps ) ) {
				$sections['apps'][ __( 'Installed Apps', APP_TD ) ] = implode( ', ', $apps );
			}
		}

		// support links section
		$sections['support'][ __( 'Support', APP_TD ) ] = html( 'a', array( 'href' => 'http://forums.appthemes.com', 'target' => '_blank' ), __( 'Forums', APP_TD ) );
		$sections['support'][ __( 'Support', APP_TD ) ] .= ' | ' . html( 'a', array( 'href' => 'https://docs.appthemes.com/', 'target' => '_blank' ), __( 'Docs', APP_TD ) );

		foreach( $sections as $section ) {
			$this->_output_list( (array) $section );
		}

?>
		</div>
<?php
	}

	function news_box() {
		echo '<div class="rss-widget">';
		wp_widget_rss_output( self::NEWS_FEED, array( 'items' => 3, 'show_author' => 0, 'show_date' => 1, 'show_summary' => 1 ) );
		echo '</div>';
	}

	function tutorials_box() {
		echo '<div class="rss-widget">';
		wp_widget_rss_output( self::TUTORIALS_FEED, array( 'items' => 3, 'show_author' => 0, 'show_date' => 1, 'show_summary' => 1 ) );
		echo '</div>';
	}

	function marketplace_box() {
		echo '<div class="rss-widget">';
		wp_widget_rss_output( self::MARKETPLACE_FEED, array( 'items' => 3, 'show_author' => 0, 'show_date' => 1, 'show_summary' => 1 ) );
		echo '</div>';
	}

	public function support_forum_box() {
		echo '<div class="rss-widget">';
		wp_widget_rss_output( self::FORUM_FEED, array( 'items' => 5, 'show_author' => 0, 'show_date' => 1, 'show_summary' => 1 ) );
		echo '</div>';
	}

	protected function _get_user_counts() {
		global $wpdb;

		$users = (array) count_users();

		$capabilities_meta = $wpdb->prefix . 'capabilities';
		$date_today = date( 'Y-m-d', current_time( 'timestamp' ) );
		$date_yesterday = date( 'Y-m-d', strtotime( '-1 days', current_time( 'timestamp' ) ) );

		$users['today'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users INNER JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id WHERE $wpdb->usermeta.meta_key = %s AND ($wpdb->usermeta.meta_value NOT LIKE %s) AND $wpdb->users.user_registered >= %s", $capabilities_meta, '%administrator%', $date_today ) );
		$users['yesterday'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users INNER JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id WHERE $wpdb->usermeta.meta_key = %s AND ($wpdb->usermeta.meta_value NOT LIKE %s) AND $wpdb->users.user_registered BETWEEN %s AND %s", $capabilities_meta, '%administrator%', $date_yesterday, $date_today ) );

		return $users;
	}

	static function _get_listing_counts( $post_type = '' ) {

		$listings = (array) wp_count_posts( $post_type );

		$all = 0;
		foreach ( (array) $listings as $count ) {
			$all += $count;
		}
		$listings['all'] = $all;

		$yesterday_posts = new WP_Query( array(
			'post_type' => $post_type,
			'date_query' => array(
				array(
					'after' => '1 day ago',
				)
			)
		) );

		$listings['new'] = $yesterday_posts->post_count;

		return $listings;
	}

	protected function _output_list( $array, $begin = '<ul>', $end = '</ul>', $echo = true ) {

		$html = '';
		foreach ( $array as $title => $value ) {

			if ( is_array( $value ) ) {
				$html .= '<li>' . $title . ': <a href="' . $value['url'] . '">' . $value['text'] . '</a></li>';
			} else {
				$html .= '<li>' . $title . ': ' . $value . '</li>';
			}
		}

		$html = $begin . $html . $end;

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

	function page_init() {
		// This will be enqueued on all admin pages if exists
		if ( file_exists( get_template_directory() . '/includes/admin/admin.css' ) ) {
			wp_enqueue_style( 'app-admin', get_template_directory_uri() . '/includes/admin/admin.css' );
		}
		parent::page_init();
	}

	public function boxes_init() {
		add_screen_option( 'screen_columns', $this->args['columns'] );
		parent::boxes_init();
	}


	protected function box_icon( $class ) {

		return html( 'i', array(
			'class' => "box-icon at {$class} wp-ui-text-highlight",
		) );
	}

	function page_head() {
		wp_enqueue_style( 'dashboard' );

?>
<style type="text/css">
.metabox-prefs .box-icon {
	display: none;
}
.inside {
	clear: both;
	overflow: hidden;
}
.inside table {
	margin: 0 !important;
	padding: 0 !important;
}
.inside .form-table th {
	width: 30%;
	max-width: 200px;
	padding: 10px 0 !important;
}
.inside .widefat .check-column {
	padding-bottom: 7px !important;
}
.inside p,
.inside table {
	margin: 0 0 10px !important;
}
.inside p.submit {
	float: left !important;
	padding: 0 !important;
	margin-bottom: 0 !important;
}
.box-icon {
	padding-right: 10px;
}
#dashboard-widgets a.rsswidget {
	font-weight: 400;
}
#dashboard-widgets .rss-widget span.rss-date {
	margin-left: 12px;
	color: #777;
}

#dashboard-widgets.columns-1 .postbox-container {
	width: 100%!important;
}

@media only screen and (min-width: 800px) {
#dashboard-widgets.columns-2 .postbox-container {
	width: 50%!important;
	}
}

@media only screen and (max-width: 799px) {
	#dashboard-widgets .postbox-container {
		width: 100%!important;
	}
}
</style>
<?php
	}

	/**
	 * Displays page content.
	 *
	 * @return void
	 */
	protected function page_content() {
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		?>
		<div id="dashboard-widgets-wrap">
			<?php wp_dashboard(); ?>
		</div>
		<?php
	}
}

