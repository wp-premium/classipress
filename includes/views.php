<?php
/**
 * Views.
 *
 * @package ClassiPress\Views
 * @author  AppThemes
 * @since   ClassiPress 3.2
 */


/**
 * Blog Archive page view.
 */
class CP_Blog_Archive extends APP_View_Page {

	private static $_template;

	/**
	 * Sets up page view.
	 *
	 * @return void
	 */
	public function __construct() {
		self::$_template = 'index.php';
		parent::__construct( self::$_template, __( 'Blog', APP_TD ) );
	}

	/**
	 * Returns page ID.
	 *
	 * @return int
	 */
	public static function get_id() {
		return self::_get_page_id( self::$_template );
	}
}


/**
 * Listings Home page view.
 */
class CP_Ads_Home extends APP_View_Page {

	private static $_template;

	/**
	 * Sets up page view.
	 *
	 * @return void
	 */
	public function __construct() {
		self::$_template = 'tpl-ads-home.php';
		parent::__construct( self::$_template, __( 'Ad Listings', APP_TD ) );
	}

	/**
	 * Returns page ID.
	 *
	 * @return int
	 */
	public static function get_id() {
		return self::_get_page_id( self::$_template );
	}
}


/**
 * Create Listing page view.
 */
class CP_Add_New extends APP_View_Page {

	private static $_template;

	/**
	 * Sets up page view.
	 *
	 * @return void
	 */
	public function __construct() {
		self::$_template = 'create-listing.php';
		parent::__construct( self::$_template, __( 'Create Listing', APP_TD ) );
	}

	/**
	 * Returns page ID.
	 *
	 * @return int
	 */
	public static function get_id() {
		return self::_get_page_id( self::$_template );
	}

	public function template_include( $path ) {

		appthemes_setup_checkout( 'create-listing', get_permalink( self::get_id() ) );
		$template = locate_template( 'create-listing.php' );

		$step_found = appthemes_process_checkout();
		if ( ! $step_found ) {
			return locate_template( '404.php' );
		}

		return $template;
	}

	function template_redirect() {
		global $cp_options;

		appthemes_require_login( array(
			'login_text' => __( 'You must first login to post an ad listing.', APP_TD ),
			'login_register_text' => __( 'You must first login or <a href="%s">register</a> to post an ad listing.', APP_TD )
		) );

		// if not meet membership requirement, redirect to membership purchase page
		cp_redirect_membership();

		if ( ! current_user_can( 'edit_posts' ) ) {
			appthemes_add_notice( 'denied-listing-edit', __( 'You are not allowed to submit ad listings.', APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		// redirect to renew page
		if ( isset( $_GET['listing_renew'] ) ) {
			wp_redirect( add_query_arg( 'listing_renew', $_GET['listing_renew'], get_permalink( CP_Renew_Listing::get_id() ) ) );
			exit();
		}

		// load up the validate
		add_action( 'wp_enqueue_scripts', 'cp_load_form_scripts' );
	}

	/**
	 * Displays notices.
	 *
	 * @return void
	 */
	function notices() {
		global $cp_options, $current_user;

		if ( $cp_options->charge_ads && $cp_options->enable_membership_packs ) {
			$membership = cp_get_user_membership_package( $current_user->ID );
			$step = _appthemes_get_step_from_query();
			if ( $membership && in_array( $step, array( 'listing-details', 'select-category' ) ) ) {
				appthemes_display_notice( 'success', sprintf( __( 'You have active membership pack "%s". Membership benefit will apply on the review page before publishing an ad.', APP_TD ), $membership->pack_name ) );
			}
		}

		parent::notices();
	}

}


/**
 * Renew Listing page view.
 */
class CP_Renew_Listing extends APP_View_Page {

	private static $_template;

	/**
	 * Sets up page view.
	 *
	 * @return void
	 */
	public function __construct() {
		self::$_template = 'renew-listing.php';
		parent::__construct( self::$_template, __( 'Renew Listing', APP_TD ) );
	}

	/**
	 * Returns page ID.
	 *
	 * @return int
	 */
	public static function get_id() {
		return self::_get_page_id( self::$_template );
	}

	public function template_include( $path ) {

		appthemes_setup_checkout( 'renew-listing', add_query_arg( 'listing_renew', $_GET['listing_renew'], get_permalink( self::get_id() ) ) );
		$template = locate_template( self::$_template );

		$step_found = appthemes_process_checkout();
		if ( ! $step_found ) {
			return locate_template( '404.php' );
		}

		return $template;
	}

	function template_redirect() {

		appthemes_require_login( array(
			'login_text' => __( 'You must first login to renew an ad listing.', APP_TD ),
			'login_register_text' => __( 'You must first login or <a href="%s">register</a> to renew an ad listing.', APP_TD )
		) );

		// if not meet membership requirement, redirect to membership purchase page
		cp_redirect_membership();

		if ( ! current_user_can( 'edit_posts' ) ) {
			appthemes_add_notice( 'denied-listing-edit', __( 'You are not allowed to renew ad listings.', APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		// redirect to dashboard if can't renew
		self::can_renew_ad();

		// load up the validate
		add_action( 'wp_enqueue_scripts', 'cp_load_form_scripts' );
	}

	static function can_renew_ad() {
		global $cp_options, $current_user;

		if ( ! $cp_options->allow_relist ) {
			appthemes_add_notice( 'renew-disabled', __( 'You can not relist this ad. Relisting is currently disabled.', APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		if ( ! isset( $_GET['listing_renew'] ) || $_GET['listing_renew'] != appthemes_numbers_only( $_GET['listing_renew'] ) ) {
			appthemes_add_notice( 'renew-invalid-id', __( 'You can not relist this ad. Invalid ID of an ad.', APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		$post = get_post( $_GET['listing_renew'] );
		if ( ! $post ) {
			appthemes_add_notice( 'renew-invalid-id', __( 'You can not relist this ad. Invalid ID of an ad.', APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		if ( $post->post_author != $current_user->ID ) {
			appthemes_add_notice( 'renew-invalid-author', __( "You can not renew this ad. It's not your ad.", APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		// validate expire date only on non order steps
		$step = _appthemes_get_step_from_query();
		if ( empty( $step ) || ! in_array( $step, array( 'gateway-select', 'gateway-process', 'order-summary' ) ) ) {
			$expire_time = strtotime( get_post_meta( $post->ID, 'cp_sys_expire_date', true ) );
			if ( $expire_time > current_time( 'timestamp' ) ) {
				appthemes_add_notice( 'renew-not-expired', __( 'You can not relist this ad. Ad is not expired.', APP_TD ), 'error' );
				wp_redirect( CP_DASHBOARD_URL );
				exit();
			}
		}

	}

	/**
	 * Displays notices.
	 *
	 * @return void
	 */
	function notices() {
		global $cp_options, $current_user;

		if ( $cp_options->charge_ads && $cp_options->enable_membership_packs ) {
			$membership = cp_get_user_membership_package( $current_user->ID );
			$step = _appthemes_get_step_from_query();
			if ( $membership && in_array( $step, array( 'listing-details', 'select-category' ) ) ) {
				appthemes_display_notice( 'success', sprintf( __( 'You have active membership pack "%s". Membership benefit will apply on the review page before publishing an ad.', APP_TD ), $membership->pack_name ) );
			}
		}

		parent::notices();
	}

}


/**
 * Purchase Membership page view.
 */
class CP_Membership extends APP_View_Page {

	private static $_template;

	/**
	 * Sets up page view.
	 *
	 * @return void
	 */
	public function __construct() {
		self::$_template = 'purchase-membership.php';
		parent::__construct( self::$_template, __( 'Purchase Membership', APP_TD ) );
	}

	/**
	 * Returns page ID.
	 *
	 * @return int
	 */
	public static function get_id() {
		return self::_get_page_id( self::$_template );
	}

	public function template_include( $path ) {

		appthemes_setup_checkout( 'membership-purchase', get_permalink( self::get_id() ) );
		$step_found = appthemes_process_checkout();
		if ( ! $step_found ) {
			return locate_template( '404.php' );
		}

		return locate_template( self::$_template );
	}

	function template_redirect() {

		appthemes_require_login( array(
			'login_text' => __( 'You must first login to purchase a membership.', APP_TD ),
			'login_register_text' => __( 'You must first login or <a href="%s">register</a> to purchase a membership.', APP_TD )
		) );

		// redirect to dashboard if payments disabled
		if ( ! cp_payments_is_enabled( 'membership' ) ) {
			appthemes_add_notice( 'payments-disabled', __( 'Payments are currently disabled. You cannot purchase anything.', APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		// load up the relavent javascript
		add_action( 'wp_enqueue_scripts', 'cp_load_form_scripts' );

	}

}


/**
 * Edit Listing page view.
 */
class CP_Edit_Item extends APP_View_Page {

	private static $_template;

	private $error;

	/**
	 * Sets up page view.
	 *
	 * @return void
	 */
	public function __construct() {
		self::$_template = 'edit-listing.php';
		parent::__construct( self::$_template, __( 'Edit Listing', APP_TD ) );
	}

	/**
	 * Returns page ID.
	 *
	 * @return int
	 */
	public static function get_id() {
		return self::_get_page_id( self::$_template );
	}

	public function template_include( $path ) {

		appthemes_setup_checkout( 'edit-listing', add_query_arg( 'listing_edit', $_GET['listing_edit'], get_permalink( self::get_id() ) ) );
		$step_found = appthemes_process_checkout();
		if ( ! $step_found ) {
			return locate_template( '404.php' );
		}

		return locate_template( self::$_template );
	}

	function template_redirect() {
		global $cp_options;

		appthemes_require_login( array(
			'login_text' => __( 'You must first login to edit an ad listing.', APP_TD ),
			'login_register_text' => __( 'You must first login or <a href="%s">register</a> to edit an ad listing.', APP_TD )
		) );

		if ( ! current_user_can( 'edit_posts' ) ) {
			appthemes_add_notice( 'denied-listing-edit', __( 'You are not allowed to edit ad listings.', APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		// redirect to dashboard if can't edit ad
		self::can_edit_ad();

		// redirect to renew page
		if ( isset( $_GET['listing_renew'] ) ) {
			wp_redirect( add_query_arg( 'listing_renew', $_GET['listing_renew'], get_permalink( CP_Renew_Listing::get_id() ) ) );
			exit();
		}

		// add js files to wp_head. tiny_mce and validate
		add_action( 'wp_enqueue_scripts', 'cp_load_form_scripts' );

	}

	static function can_edit_ad() {
		global $current_user, $cp_options;

		if ( ! isset( $_GET['listing_edit'] ) || $_GET['listing_edit'] != appthemes_numbers_only( $_GET['listing_edit'] ) ) {
			appthemes_add_notice( 'edit-invalid-id', __( 'You can not edit this ad. Invalid ID of an ad.', APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		if ( ! $cp_options->ad_edit ) {
			appthemes_add_notice( 'edit-disabled', __( 'You can not edit this ad. Editing is currently disabled.', APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		$post = get_post( $_GET['listing_edit'] );
		if ( ! $post ) {
			appthemes_add_notice( 'edit-invalid-id', __( 'You can not edit this ad. Invalid ID of an ad.', APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		if ( ! $cp_options->moderate_edited_ads && $post->post_status == 'pending' ) {
			appthemes_add_notice( 'edit-pending', __( 'You can not edit this ad. Ad is not yet approved.', APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		if ( $post->post_type != APP_POST_TYPE ) {
			appthemes_add_notice( 'edit-invalid-type', __( 'You can not edit this ad. This is not an ad.', APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		if ( $post->post_author != $current_user->ID ) {
			appthemes_add_notice( 'edit-invalid-author', __( "You can not edit this ad. It's not your ad.", APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

		if ( cp_is_listing_expired( $post->ID ) ) {
			appthemes_add_notice( 'edit-expired', __( 'You can not edit this ad. Ad is expired.', APP_TD ), 'error' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();
		}

	}

}


/**
 * User Dashboard page view.
 */
class CP_User_Dashboard extends APP_View_Page {

	private static $_template;

	/**
	 * Sets up page view.
	 *
	 * @return void
	 */
	public function __construct() {
		self::$_template = 'tpl-dashboard.php';
		parent::__construct( self::$_template, __( 'Dashboard', APP_TD ) );
	}

	/**
	 * Returns page ID.
	 *
	 * @return int
	 */
	public static function get_id() {
		return self::_get_page_id( self::$_template );
	}

	function template_redirect() {
		appthemes_auth_redirect_login(); // if not logged in, redirect to login page
		nocache_headers();

		// process actions if needed
		self::process_actions();
	}

	static function process_actions() {
		global $current_user;

		$allowed_actions = array( 'pause', 'restart', 'delete', 'setSold', 'unsetSold' );

		if ( ! isset( $_GET['action'] ) || ! in_array( $_GET['action'], $allowed_actions ) ) {
			return;
		}

		if ( ! isset( $_GET['aid'] ) || ! is_numeric( $_GET['aid'] ) ) {
			return;
		}

		$d = trim( $_GET['action'] );
		$post_id = appthemes_numbers_only( $_GET['aid'] );

		// make sure ad exist
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type != APP_POST_TYPE ) {
			return;
		}

		// make sure author matches
		if ( $post->post_author != $current_user->ID ) {
			return;
		}

		$expire_time = strtotime( get_post_meta( $post->ID, 'cp_sys_expire_date', true ) );
		$is_expired = ( current_time( 'timestamp' ) > $expire_time && $post->post_status == 'draft' );
		$is_pending = ( $post->post_status == 'pending' );

		if ( $d == 'pause' && ! $is_expired && ! $is_pending ) {
			wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'draft' ) );
			appthemes_add_notice( 'paused', __( 'Ad has been paused.', APP_TD ), 'success' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();

		} elseif ( $d == 'restart' && ! $is_expired && ! $is_pending ) {
			wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'publish' ) );
			appthemes_add_notice( 'restarted', __( 'Ad has been published.', APP_TD ), 'success' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();

		} elseif ( $d == 'delete' ) {
			cp_delete_ad_listing( $post->ID );
			appthemes_add_notice( 'deleted', __( 'Ad has been deleted.', APP_TD ), 'success' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();

		} elseif ( $d == 'setSold' ) {
			update_post_meta( $post->ID, 'cp_ad_sold', 'yes' );
			appthemes_add_notice( 'marked-sold', __( 'Ad has been marked as sold.', APP_TD ), 'success' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();

		} elseif ( $d == 'unsetSold' ) {
			update_post_meta( $post->ID, 'cp_ad_sold', 'no' );
			appthemes_add_notice( 'unmarked-sold', __( 'Ad has been unmarked as sold.', APP_TD ), 'success' );
			wp_redirect( CP_DASHBOARD_URL );
			exit();

		}

	}

}


/**
 * User Dashboard page view.
 */
class CP_User_Dashboard_Orders extends CP_User_Dashboard {

	private static $_template;

	/**
	 * Sets up page view.
	 *
	 * @return void
	 */
	public function __construct() {
		global $wp;

		$wp->add_query_var('order_status');

		self::$_template = 'tpl-dashboard-orders.php';
		APP_View_Page::__construct( self::$_template, __( 'Orders', APP_TD ) );
	}

	/**
	 * Returns page ID.
	 *
	 * @return int
	 */
	public static function get_id() {
		return self::_get_page_id( self::$_template );
	}

}


/**
 * Edit Profile page view.
 */
class CP_User_Profile extends APP_User_Profile {

	private static $_template;

	/**
	 * Sets up page view.
	 *
	 * @return void
	 */
	public function __construct() {
		self::$_template = 'tpl-profile.php';

		APP_View_Page::__construct( self::$_template, __( 'Edit Profile', APP_TD ) );
		add_action( 'init', array( $this, 'update' ) );
	}

	/**
	 * Returns page ID.
	 *
	 * @return int
	 */
	public static function get_id() {
		return self::_get_page_id( self::$_template );
	}

}


/**
 * Listings Categories page view.
 */
class CP_Ads_Categories extends APP_View_Page {

	private static $_template;

	/**
	 * Sets up page view.
	 *
	 * @return void
	 */
	public function __construct() {

		self::$_template = 'tpl-categories.php';

		parent::__construct( self::$_template, __( 'Categories', APP_TD ) );

		// Replace any children the "Categories" menu item might have with the category dropdown
		add_filter( 'wp_nav_menu_objects', array( $this, 'disable_children' ), 10, 2 );
		add_filter( 'walker_nav_menu_start_el', array( $this, 'insert_dropdown' ), 10, 4 );
	}

	/**
	 * Returns page ID.
	 *
	 * @return int
	 */
	public static function get_id() {
		return self::_get_page_id( self::$_template );
	}

	public function disable_children( $items, $args ) {
		foreach ( $items as $key => $item ) {
			if ( $item->object_id == self::get_id() ) {
				$item->current_item_ancestor = false;
				$item->current_item_parent = false;
				$menu_id = $item->ID;
			}
		}

		if ( isset( $menu_id ) ) {
			foreach ( $items as $key => $item ) {
				if ( $item->menu_item_parent == $menu_id ) {
					unset( $items[ $key ] );
				}
			}
		}

		return $items;
	}

	public function insert_dropdown( $item_output, $item, $depth, $args ) {

		// make sure the categories dropdown is only displayed on the header menu
		if ( 'menu-header' != $args->menu_id ) {
			return $item_output;
		}

		if ( $item->object_id == self::get_id() && $item->object == 'page' ) {
			$item_output .= '<div class="adv_categories" id="adv_categories">' . cp_create_categories_list( 'menu' ) . '</div>';
		}
		return $item_output;
	}

}


/**
 * Single post view.
 */
class CP_Post_Single extends APP_View {

	/**
	 * Check if this class should handle the current view.
	 *
	 * @return bool
	 */
	public function condition() {
		return is_single();
	}

	/**
	 * Show parent categories.
	 *
	 * @param array $trail
	 *
	 * @return array
	 */
	function breadcrumbs( $trail ) {
		$categories = get_the_terms( get_queried_object_id(), 'category' );

		if ( ! $categories ) {
			return $trail;
		}

		$category = reset( $categories );
		$category = (int) $category->term_id;
		$chain = array_reverse( get_ancestors( $category, 'category' ) );
		$chain[] = $category;

		$new_trail = array( $trail[0] );

		foreach ( $chain as $cat ) {
			$cat_obj = get_term( $cat, 'category' );
			$new_trail[] = html_link( get_term_link( $cat_obj ), $cat_obj->name );
		}

		$new_trail[] = array_pop( $trail );

		return $new_trail;
	}

}


/**
 * Single Listing view.
 */
class CP_Ad_Single extends APP_View {

	/**
	 * Check if this class should handle the current view.
	 *
	 * @return bool
	 */
	public function condition() {
		return is_singular( APP_POST_TYPE );
	}

	function template_redirect() {
		global $cp_options;

		// enqueue reports scripts and styles
		add_action( 'wp_enqueue_scripts', 'appthemes_reports_enqueue_scripts' );
		add_action( 'wp_enqueue_scripts', 'appthemes_reports_enqueue_styles' );

		// enqueue recaptcha if recaptcha enabled
		if ( $cp_options->captcha_enable ) {
			appthemes_enqueue_recaptcha_scripts();
		}
	}

	/**
	 * Displays notices.
	 *
	 * @return void
	 */
	function notices() {
		$post = get_queried_object();

		if ( $post->post_status == 'pending' ) {
			if ( cp_have_pending_payment( $post->ID ) ) {
				appthemes_display_notice( 'warning', __( 'This ad listing is currently pending and awaiting payment.', APP_TD ) );
			} else {
				appthemes_display_notice( 'warning', __( 'This ad listing is currently pending and must be approved by an administrator.', APP_TD ) );
			}
		} else if ( $post->post_status == 'draft' ) {
			$expire_time = strtotime( get_post_meta( $post->ID, 'cp_sys_expire_date', true ) );
			if ( current_time( 'timestamp' ) > $expire_time ) {
				appthemes_display_notice( 'success', __( 'This ad listing is expired.', APP_TD ) );
			} else {
				appthemes_display_notice( 'success', __( 'This ad listing is paused.', APP_TD ) );
			}
		}

		parent::notices();
	}

	/**
	 * Show parent categories instead of listing archive.
	 *
	 * @param array $trail
	 *
	 * @return array
	 */
	function breadcrumbs( $trail ) {
		$categories = cp_get_listing_categories( get_queried_object_id() );

		if ( ! $categories ) {
			return $trail;
		}

		$category = reset( $categories );
		$category = (int) $category->term_id;
		$chain = array_reverse( get_ancestors( $category, APP_TAX_CAT ) );
		$chain[] = $category;

		$new_trail = array( $trail[0] );

		foreach ( $chain as $cat ) {
			$cat_obj = get_term( $cat, APP_TAX_CAT );
			$new_trail[] = html_link( get_term_link( $cat_obj ), $cat_obj->name );
		}

		$new_trail[] = array_pop( $trail );

		return $new_trail;
	}

}


/**
 * Listings Archive view.
 */
class CP_Ads_Archive extends APP_View {

	/**
	 * Check if this class should handle the current view.
	 *
	 * @return bool
	 */
	public function condition() {
		return is_post_type_archive( APP_POST_TYPE ) && ! is_tax() && ! is_admin();
	}

	function parse_query( $wp_query ) {
		$wp_query->set( 'ignore_sticky_posts', 1 );
		$sort = ( ! empty( $_GET['sort'] ) ) ? $_GET['sort'] : '';
		if ( $sort == 'random' ) {
			$wp_query->set( 'orderby', 'rand' );
		} else if ( $sort == 'popular' ) {
			$wp_query->set( '_popular_posts_total', true );
		}
	}

	function posts_clauses( $clauses, $wp_query ) {
		global $wpdb;

		if ( $wp_query->get( '_popular_posts_total' ) ) {
			$clauses['join'] .= " INNER JOIN $wpdb->cp_ad_pop_total ON ($wpdb->posts.ID = $wpdb->cp_ad_pop_total.postnum) ";
			$clauses['where'] .= " AND $wpdb->cp_ad_pop_total.postcount > 0 ";
			$clauses['orderby'] = "$wpdb->cp_ad_pop_total.postcount DESC";
		}

		return $clauses;
	}

	function breadcrumbs( $trail ) {
		$new_trail = array( $trail[0] );

		if ( ! empty( $_GET['sort'] ) && $_GET['sort'] == 'random' ) {
			$new_trail[] = __( 'Random Ads', APP_TD );
		} elseif ( ! empty( $_GET['sort'] ) && $_GET['sort'] == 'popular' ) {
			$new_trail[] = __( 'Popular Ads', APP_TD );
		} else {
			$new_trail[] = __( 'Latest Ads', APP_TD );
		}

		if ( is_paged() ) {
			$new_trail[] = array_pop( $trail );
		}

		return $new_trail;
	}

}


/**
 * Author Archive view.
 */
class CP_Author_Archive extends APP_View {

	/**
	 * Check if this class should handle the current view.
	 *
	 * @return bool
	 */
	public function condition() {
		return is_author() && ! is_admin();
	}

	function parse_query( $wp_query ) {
		$wp_query->set( 'post_type', array( 'post', APP_POST_TYPE ) );
		add_filter( 'appthemes_pagenavi_args', array( $this, 'set_tab' ) );
	}

	function set_tab( $args ) {
		global $wp_query;
		if ( $wp_query->get( 'post_type' ) == 'post' ) {
			$args['add_fragment'] = '#block2';
		} else {
			$args['add_fragment'] = '#block1';
		}

		return $args;
	}

	function breadcrumbs( $trail ) {
		$new_trail = array( $trail[0] );

		$author = get_queried_object();
		$new_trail[] = sprintf( __( 'About %s', APP_TD ), $author->display_name );

		if ( is_paged() ) {
			$new_trail[] = array_pop( $trail );
		}

		return $new_trail;
	}

}


/**
 * Posts Tag Archive view.
 */
class CP_Posts_Tag_Archive extends APP_View {

	/**
	 * Check if this class should handle the current view.
	 *
	 * @return bool
	 */
	public function condition() {
		return is_tag();
	}


	function breadcrumbs( $trail ) {
		$new_trail = array( $trail[0] );

		$term = get_queried_object();
		$new_trail[] = sprintf( __( 'Posts tagged with "%s"', APP_TD ), $term->name );

		if ( is_paged() ) {
			$new_trail[] = array_pop( $trail );
		}

		return $new_trail;
	}

}


/**
 * Listings Tag Archive view.
 */
class CP_Ads_Tag_Archive extends APP_View {

	/**
	 * Check if this class should handle the current view.
	 *
	 * @return bool
	 */
	public function condition() {
		return is_tax( APP_TAX_TAG );
	}


	function breadcrumbs( $trail ) {
		$new_trail = array( $trail[0] );

		$term = get_queried_object();
		$new_trail[] = sprintf( __( 'Ads tagged with "%s"', APP_TD ), $term->name );

		if ( is_paged() ) {
			$new_trail[] = array_pop( $trail );
		}

		return $new_trail;
	}

}
