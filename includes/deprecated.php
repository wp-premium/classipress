<?php
/**
 * Deprecated functions.
 *
 * @package ClassiPress\Deprecated
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */


/**
 * Constants.
 *
 * @deprecated 3.3
 */
$upload_dir = wp_upload_dir();
define( 'UPLOADS_FOLDER', trailingslashit( 'classipress' ) );
define( 'CP_UPLOAD_DIR', trailingslashit( $upload_dir['basedir'] ) . UPLOADS_FOLDER );

define( 'FAVICON', get_template_directory_uri() . '/images/favicon.ico' );
define( 'THE_POSITION', 3 );

define( 'CP_ADD_NEW_CONFIRM_URL', home_url( '/' ) );
define( 'CP_MEMBERSHIP_PURCHASE_CONFIRM_URL', home_url( '/' ) );


/**
 * Assemble the blog path.
 *
 * @deprecated 3.0.5
 *
 * @return string
 */
if ( ! function_exists( 'cp_detect_blog_path' ) ) {
	function cp_detect_blog_path() {
		_deprecated_function( __FUNCTION__, '3.0.5' );

		$blogcatid = get_option( 'cp_blog_cat' );

		if ( ! empty( $blogcatid ) ) {
			$blogpath = get_category_link( get_option( 'cp_blog_cat' ) );
		} else {
			$blogpath = cp_cat_base() . '/blog/';
		}

		return $blogpath;
	}
}


/**
 * Return category base. If not set, uses the default "category".
 *
 * @deprecated 3.0.5
 *
 * @return string
 */
if ( ! function_exists( 'cp_cat_base' ) ) {
	function cp_cat_base() {
		_deprecated_function( __FUNCTION__, '3.0.5' );

		if ( appthemes_clean( get_option( 'category_base' ) ) == '' ) {
			$cat_base = home_url( '/' ) . 'category';
		} else {
			$cat_base = home_url( '/' ) . get_option( 'category_base' );
		}

		return $cat_base;
	}
}


/**
 * Checks if blog post is in subcategory, used in CP 3.0.4 and earlier.
 *
 * @deprecated 3.0.5
 *
 * @param array $cats
 * @param object $post (optional)
 *
 * @return bool
 */
function cp_post_in_desc_cat( $cats, $post = null ) {
	_deprecated_function( __FUNCTION__, '3.0.5' );

	foreach ( (array) $cats as $cat ) {
		$descendants = get_term_children( (int) $cat, 'category' );
		if ( $descendants && in_category( $descendants, $post ) ) {
			return true;
		}
	}
	return false;
}


/**
 * Returns blog category id, used in CP 3.0.4 and earlier.
 *
 * @deprecated 3.0.5
 *
 * @return int
 */
function cp_get_blog_catid() {
	_deprecated_function( __FUNCTION__, '3.0.5' );

	$blogcatid = get_option( 'cp_blog_cat' );

	if ( empty( $blogcatid ) ) {
		$blogcatid = 1;
	}

	return $blogcatid;
}


/**
 * Returns comma separated list of blog category ids, used in CP 3.0.4 and earlier.
 *
 * @deprecated 3.0.5
 *
 * @return string
 */
function cp_get_blog_cat_ids() {
	_deprecated_function( __FUNCTION__, '3.0.5' );

	$catids = cp_get_blog_cat_ids_array();
	$allcats = trim( join( ',', $catids ) );

	return $allcats;
}


/**
 * Returns array of blog category ids, used in CP 3.0.4 and earlier.
 *
 * @deprecated 3.0.5
 *
 * @return array
 */
function cp_get_blog_cat_ids_array() {
	_deprecated_function( __FUNCTION__, '3.0.5' );

	$catid = cp_get_blog_catid();
	$descendants = get_term_children( (int) $catid, 'category' );

	$output = array();
	$output[] = $catid;

	foreach ( $descendants as $key => $value ) {
		$output[] = $value;
	}

	return $output;
}


/**
 * Was displaying 125x125px ads in sidebar.
 *
 * @deprecated 3.0.5
 * @see CP_Widget_125_Ads
 *
 * @return void
 */
function cp_ad_sponsors_widget() {
	_deprecated_function( __FUNCTION__, '3.0.5' );
}


/**
 * Categories list.
 *
 * @deprecated 3.1.9
 * @deprecated Use cp_create_categories_list()
 * @see cp_create_categories_list()
 *
 * @param int $cols (optional)
 * @param int $subs (optional)
 *
 * @return string
 */
if ( ! function_exists( 'cp_cat_menu_drop_down' ) ) {
	function cp_cat_menu_drop_down( $cols = 3, $subs = 0 ) {
		_deprecated_function( __FUNCTION__, '3.1.9', 'cp_create_categories_list()' );

		return cp_create_categories_list( 'dir' );
	}
}


/**
 * Directory home page category display.
 *
 * @deprecated 3.0.5.2
 * @deprecated Use cp_create_categories_list()
 * @see cp_create_categories_list()
 *
 * @param int $cols
 *
 * @return string
 */
if ( ! function_exists( 'cp_directory_cat_columns' ) ) {
	function cp_directory_cat_columns( $cols ) {
		_deprecated_function( __FUNCTION__, '3.0.5.2', 'cp_create_categories_list()' );

		return cp_create_categories_list( 'dir' );
	}
}


/**
 * Create geocodes database table.
 *
 * @deprecated 3.2
 * @deprecated Use 'appthemes_first_run' hook
 * @see appthemes_first_run' hook
 *
 * @return bool
 */
if ( ! function_exists( 'cp_create_geocode_table' ) ) {
	function cp_create_geocode_table() {
		_deprecated_function( __FUNCTION__, '3.2', 'appthemes_first_run' );

		return false;
	}
}


/**
 * Get the ad price and position the currency symbol.
 * Meta field 'price' used on CP 2.9.3 and earlier
 *
 * @deprecated 3.2
 * @deprecated Use cp_get_price()
 * @see cp_get_price()
 *
 * @param int $post_id
 *
 * @return string
 */
function cp_get_price_legacy( $post_id ) {
	_deprecated_function( __FUNCTION__, '3.2', 'cp_get_price' );

	return cp_get_price( $post_id, 'price' );
}


/**
 * Builds the edit ad form on the tpl-edit-item.php page template.
 *
 * @deprecated 3.2.1
 * @deprecated Use cp_formbuilder()
 * @see cp_formbuilder()
 *
 * @param array $fields
 * @param object $post
 *
 * @return void
 */
if ( ! function_exists( 'cp_edit_ad_formbuilder' ) ) {
	function cp_edit_ad_formbuilder( $fields, $post ) {
		_deprecated_function( __FUNCTION__, '3.2.1', 'cp_formbuilder' );

		cp_formbuilder( $fields, $post );
	}
}


/**
 * Called before ad update to hook into the confirmation page.
 *
 * @deprecated 3.3
 *
 * @return void
 */
function cp_add_new_confirm_before_update() {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Called after ad update to hook into the confirmation page.
 *
 * @deprecated 3.3
 *
 * @return void
 */
function cp_add_new_confirm_after_update() {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Called to process the payment.
 *
 * @deprecated 3.3
 *
 * @param array $order_vals
 *
 * @return void
 */
function cp_action_gateway( $order_vals ) {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Called to hook into the payment list.
 *
 * @deprecated 3.3
 *
 * @param int $post_id
 *
 * @return void
 */
function cp_action_payment_button( $post_id ) {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Called to hook into the payment dropdown.
 *
 * @deprecated 3.3
 *
 * @return void
 */
function cp_action_payment_method() {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Called to hook into the admin gateway options.
 *
 * @deprecated 3.3
 *
 * @return void
 */
function cp_action_gateway_values() {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Called to hook into db transaction process.
 *
 * @deprecated 3.3
 *
 * @return void
 */
function cp_process_transaction_entry() {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Was sending new membership notification email to buyer when purchased by bank transfer.
 *
 * @deprecated 3.3
 *
 * @param string $order_id
 *
 * @return void
 */
function cp_bank_owner_new_membership_email( $order_id ) {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Was sending new ad notification email to buyer when purchased by bank transfer.
 *
 * @deprecated 3.3
 *
 * @param int $post_id
 *
 * @return void
 */
function cp_bank_owner_new_ad_email( $post_id ) {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Was sending new membership notification email to admin.
 *
 * @deprecated 3.3
 *
 * @param string $order_id
 *
 * @return void
 */
function cp_new_membership_email( $order_id ) {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Was calculating total membership cost.
 *
 * @deprecated 3.3
 *
 * @param int $pack_id
 * @param string $coupon_code
 *
 * @return float
 */
function cp_calc_membership_cost( $pack_id, $coupon_code ) {
	_deprecated_function( __FUNCTION__, '3.3' );

	$package = cp_get_membership_package( $pack_id );
	if ( $package ) {
		return $package->price_modifier;
	}

	return 0;
}


/**
 * Was returning all the order values for hidden payment fields.
 *
 * @deprecated 3.3
 *
 * @param array $order_vals
 *
 * @return array
 */
function cp_get_order_vals( $order_vals ) {
	_deprecated_function( __FUNCTION__, '3.3' );

	return $order_vals;
}


/**
 * Was returning all the order pack values for hidden payment fields.
 *
 * @deprecated 3.3
 *
 * @param array $order_vals
 *
 * @return array
 */
function cp_get_order_pack_vals( $order_vals ) {
	_deprecated_function( __FUNCTION__, '3.3' );

	return $order_vals;
}


/**
 * Was checking coupon code and returning coupon object, bool false if not found.
 *
 * @deprecated 3.3
 *
 * @param string $coupon_code
 *
 * @return bool
 */
function cp_check_coupon_discount( $coupon_code ) {
	_deprecated_function( __FUNCTION__, '3.3' );

	return false;
}


/**
 * Was returning coupons list that match criteria, bool false if nothing found.
 *
 * @deprecated 3.3
 *
 * @param string $coupon_code (optional)
 *
 * @return bool
 */
function cp_get_coupons( $coupon_code = '' ) {
	_deprecated_function( __FUNCTION__, '3.3' );

	return false;
}


/**
 * Was incrementing coupon used times value.
 *
 * @deprecated 3.3
 *
 * @param string $coupon_code
 *
 * @return void
 */
function cp_use_coupon( $coupon_code ) {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Prints price with positioned currency.
 *
 * @deprecated 3.3
 * @deprecated Use cp_pos_currency()
 * @see cp_pos_currency()
 *
 * @param float $price
 * @param string $price_type (optional)
 *
 * @return void
 */
function cp_pos_price( $price, $price_type = '' ) {
	_deprecated_function( __FUNCTION__, '3.3', 'cp_pos_currency' );
	$price = cp_pos_currency( $price, $price_type );
	echo $price;
}


/**
 * Was localizing admin scripts.
 *
 * @deprecated 3.3
 *
 * @return void
 */
function cp_theme_scripts_admin() {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Was creating admin dashboard page.
 *
 * @deprecated 3.3
 * @see CP_Theme_Dashboard
 *
 * @return void
 */
function cp_dashboard() {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Was creating admin general settings page.
 *
 * @deprecated 3.3
 * @see CP_Theme_Settings_General
 *
 * @return void
 */
function cp_settings() {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Was creating admin emails settings page.
 *
 * @deprecated 3.3
 * @see CP_Theme_Settings_Emails
 *
 * @return void
 */
function cp_emails() {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Was creating admin pricing settings page.
 *
 * @deprecated 3.3
 * @see CP_Theme_Settings_Pricing
 *
 * @return void
 */
function cp_pricing() {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Was updating admin settings.
 *
 * @deprecated 3.3
 *
 * @return void
 */
function cp_update_options() {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Was printing scripts for category selection on add-new page.
 *
 * @deprecated 3.3
 *
 * @return void
 */
function cp_ajax_addnew_js_header() {
	_deprecated_function( __FUNCTION__, '3.3' );
}


/**
 * Was creating admin system info page.
 *
 * @deprecated 3.3.1
 * @see CP_Theme_System_Info
 *
 * @return void
 */
function cp_system_info() {
	_deprecated_function( __FUNCTION__, '3.3.1' );
}


/**
 * Was printing dropdown menu with categories in Add New page.
 *
 * @deprecated 3.3.1
 * @deprecated Use cp_addnew_dropdown_child_categories()
 * @see cp_addnew_dropdown_child_categories()
 *
 * @return void
 */
if ( ! function_exists( 'cp_getChildrenCategories' ) ) {
	function cp_getChildrenCategories() {
		_deprecated_function( __FUNCTION__, '3.3.1', 'cp_addnew_dropdown_child_categories' );

		cp_addnew_dropdown_child_categories();
	}
}


/**
 * Sends custom new user notification.
 *
 * @deprecated 3.3.1
 * @deprecated Use cp_new_user_notification()
 * @see cp_new_user_notification()
 *
 * @param int $user_id
 * @param string $plaintext_pass (optional)
 *
 * @return void
 */
function app_new_user_notification( $user_id, $plaintext_pass = '' ) {
	_deprecated_function( __FUNCTION__, '3.3.1', 'cp_new_user_notification' );
	cp_new_user_notification( $user_id, $plaintext_pass );
}


/**
 * RSS blog feed for the dashboard page.
 *
 * @deprecated 3.3.2
 *
 * @return void
 */
function appthemes_dashboard_appthemes() {
	_deprecated_function( __FUNCTION__, '3.3.2' );
	$rss_feed = 'http://feeds2.feedburner.com/appthemes';
	wp_widget_rss_output( $rss_feed, array( 'items' => 10, 'show_author' => 0, 'show_date' => 1, 'show_summary' => 1 ) );
}


/**
 * RSS twitter feed for the dashboard page.
 *
 * @deprecated 3.3.2
 *
 * @return void
 */
function appthemes_dashboard_twitter() {
	_deprecated_function( __FUNCTION__, '3.3.2' );
}


/**
 * RSS forum feed for the dashboard page.
 *
 * @deprecated 3.3.2
 *
 * @return void
 */
function appthemes_dashboard_forum() {
	_deprecated_function( __FUNCTION__, '3.3.2' );
	$rss_feed = 'http://forums.appthemes.com/external.php?type=RSS2';
	wp_widget_rss_output( $rss_feed, array( 'items' => 5, 'show_author' => 0, 'show_date' => 1, 'show_summary' => 1 ) );
}


/**
 * Takes a membership pack and returns the proper benefit explanation
 *
 * @deprecated 3.4
 * @deprecated Use cp_calculate_membership_package_benefit() and cp_get_membership_package_benefit_text()
 * @see cp_calculate_membership_package_benefit() and cp_get_membership_package_benefit_text()
 *
 * @param int $pack_id
 * @param float $price (optional)
 *
 * @return mixed
 */
function get_pack_benefit( $pack_id, $price = false ) {

	if ( $price ) {
		_deprecated_function( __FUNCTION__, '3.4', 'cp_calculate_membership_package_benefit' );
		return cp_calculate_membership_package_benefit( $pack_id );
	} else {
		_deprecated_function( __FUNCTION__, '3.4', 'cp_get_membership_package_benefit_text' );
		return cp_get_membership_package_benefit_text( $pack_id );
	}

}


/**
 * Returns membership package.
 *
 * @deprecated 3.4
 * @deprecated Use cp_get_membership_package()
 * @see cp_get_membership_package()
 *
 * @param int $pack_id
 * @param string $type (optional)
 * @param string $return (optional)
 *
 * @return mixed
 */
function get_pack( $pack_id, $type = '', $return = '' ) {
	_deprecated_function( __FUNCTION__, '3.4', 'cp_get_membership_package' );

	$package = cp_get_membership_package( $pack_id );

	if ( ! empty( $return ) && ! empty( $package ) ) {
		$package = (array) $package;

		if ( $return == 'array' ) {
			return $package;
		} else {
			return $package[ $return ];
		}
	}

	return $package;
}


/**
 * Was looking for a package ID in given string.
 *
 * @deprecated 3.4
 *
 * @param int $active_pack
 * @param string $type (optional)
 *
 * @return mixed
 */
function get_pack_id( $active_pack, $type = '' ) {
	_deprecated_function( __FUNCTION__, '3.4' );

	return $active_pack;
}


/**
 * Was displaying admin packages pages.
 *
 * @deprecated 3.4
 *
 * @return void
 */
function cp_ad_packs() {
	_deprecated_function( __FUNCTION__, '3.4' );
}


/**
 * Was displaying admin ad listing images metabox.
 *
 * @deprecated 3.4
 * @see CP_Listing_Attachments_Metabox
 *
 * @return void
 */
function cp_custom_images_meta_box() {
	_deprecated_function( __FUNCTION__, '3.4' );
}


/**
 * Was displaying form for admin ad listing images metabox.
 *
 * @deprecated 3.4
 * @see CP_Listing_Attachments_Metabox
 *
 * @param int $post_id
 *
 * @return void
 */
function cp_edit_ad_images( $post_id ) {
	_deprecated_function( __FUNCTION__, '3.4' );
}


/**
 * Was displaying admin ad listing custom fields metabox.
 *
 * @deprecated 3.4
 * @see CP_Listing_Custom_Forms_Metabox
 *
 * @return void
 */
function cp_custom_fields_meta_box() {
	_deprecated_function( __FUNCTION__, '3.4' );
}


/**
 * Was displaying form for admin ad listing custom fields metabox.
 *
 * @deprecated 3.4
 * @see CP_Listing_Custom_Forms_Metabox
 *
 * @param array $fields
 * @param int $post_id
 *
 * @return void
 */
function cp_edit_ad_fields( $fields, $post_id ) {
	_deprecated_function( __FUNCTION__, '3.4' );
}


/**
 * Was saving admin ad listing custom fields values.
 *
 * @deprecated 3.4
 * @see CP_Listing_Custom_Forms_Metabox
 *
 * @param int $post_id
 *
 * @return void
 */
function cp_save_meta_box( $post_id ) {
	_deprecated_function( __FUNCTION__, '3.4' );
}


/**
 * Was processing membership order.
 *
 * @deprecated 3.4
 * @deprecated Use cp_update_user_membership()
 * @see cp_update_user_membership()
 *
 * @param object $user
 * @param array $order
 *
 * @return bool
 */
function appthemes_process_membership_order( $user, $order ) {
	_deprecated_function( __FUNCTION__, '3.4', 'cp_update_user_membership' );

	return false;
}


/**
 * Was returning a private order ID.
 *
 * @deprecated 3.4
 * @see APP_Order
 *
 * @param string $active_pack
 *
 * @return bool
 */
function get_order_id( $active_pack ) {
	_deprecated_function( __FUNCTION__, '3.4' );

	return false;
}


/**
 * Was returning a user ID from the order.
 *
 * @deprecated 3.4
 * @see APP_Order
 *
 * @param string $active_pack
 *
 * @return bool
 */
function get_order_userid( $active_pack ) {
	_deprecated_function( __FUNCTION__, '3.4' );

	return false;
}


/**
 * Was returning a user pending orders.
 *
 * @deprecated 3.4
 * @see APP_Order
 *
 * @param int $user_id (optional)
 * @param string $oid (optional)
 *
 * @return bool
 */
function get_user_orders( $user_id = '', $oid = '' ) {
	_deprecated_function( __FUNCTION__, '3.4' );

	return false;
}


/**
 * Was creating new ad listing based on passed values.
 *
 * @deprecated 3.4
 * @see CP_Listing_Form_Details, CP_Listing_Form_Preview
 *
 * @param array $advals
 * @param int $renew_id (optional)
 *
 * @return bool
 */
function cp_add_new_listing( $advals, $renew_id = false ) {
	_deprecated_function( __FUNCTION__, '3.4' );

	return false;
}


/**
 * Was called in cp_add_new_listing() to hook into inserting new ad process.
 *
 * @deprecated 3.4
 * @see 'cp_update_listing' action hook
 *
 * @param int $post_id
 *
 * @return void
 */
function cp_action_add_new_listing( $post_id ) {
	_deprecated_function( __FUNCTION__, '3.4' );

	do_action( 'cp_action_add_new_listing', $post_id );
}


/**
 * Was saving edited ad listing.
 *
 * @deprecated 3.4
 * @see CP_Listing_Form_Edit
 *
 * @return bool
 */
function cp_update_listing() {
	_deprecated_function( __FUNCTION__, '3.4' );

	return false;
}


/**
 * Was called in cp_update_listing() to hook into updating ad process.
 *
 * @deprecated 3.4
 * @see 'cp_update_listing' action hook
 *
 * @param int $post_id
 *
 * @return void
 */
function cp_action_update_listing( $post_id ) {
	_deprecated_function( __FUNCTION__, '3.4' );

	do_action( 'cp_action_update_listing', $post_id );
}


/**
 * Was allowing free ads to be relisted for the same duration.
 *
 * @deprecated 3.4
 * @see CP_Listing_Form_Details, CP_Listing_Form_Preview
 *
 * @param int $listing_id
 *
 * @return bool
 */
if ( ! function_exists( 'cp_renew_ad_listing' ) ) :
	function cp_renew_ad_listing( $listing_id ) {
		_deprecated_function( __FUNCTION__, '3.4' );

		return false;
	}
endif;


/**
 * Checks if a user is logged in, if not redirect them to the login page.
 *
 * @deprecated 3.4
 * @deprecated Use appthemes_auth_redirect_login()
 * @see appthemes_auth_redirect_login()
 *
 * @return void
 */
function auth_redirect_login() {
	_deprecated_function( __FUNCTION__, '3.4', 'appthemes_auth_redirect_login' );

	appthemes_auth_redirect_login();
}


/**
 * Was intended to exclude posts and pages from search results. Function never used.
 *
 * @deprecated 3.4
 *
 * @param object $query
 *
 * @return object
 */
function appthemes_exclude_search_types( $query ) {
	_deprecated_function( __FUNCTION__, '3.4' );

	if ( $query->is_search ) {
		$query->set( 'post_type', APP_POST_TYPE );
	}

	return $query;
}


/**
 * Was adding default expiration date if it was omitted.
 *
 * @deprecated 3.4
 *
 * @param int $post_id
 *
 * @return void
 */
function cp_check_expire_date( $post_id ) {
	_deprecated_function( __FUNCTION__, '3.4' );
}


/**
 * Was intended to display related posts based on tags. Function never used.
 *
 * @deprecated 3.4
 *
 * @param int $post_id
 * @param int $image_width
 * @param int $image_height
 *
 * @return bool
 */
function cp_related_posts( $post_id, $image_width, $image_height ) {
	_deprecated_function( __FUNCTION__, '3.4' );

	return false;
}


/**
 * Was intended to resize youtube videos. Function never used.
 *
 * @deprecated 3.4
 *
 * @param string $content
 *
 * @return string
 */
function cp_resize_youtube( $content ) {
	_deprecated_function( __FUNCTION__, '3.4' );

	return $content;
}


/**
 * Checks if current post object is a page.
 *
 * @deprecated 3.4
 *
 * @return bool
 */
function cp_is_type_page() {
	global $post;

	_deprecated_function( __FUNCTION__, '3.4' );

	return ( $post->post_type == 'page' );
}


/**
 * Displays the first medium image associated to the ad.
 *
 * @deprecated 3.4
 *
 * @param int $post_id (optional)
 * @param string $size (optional)
 * @param int $num (optional)
 *
 * @return void
 */
if ( ! function_exists( 'cp_get_image' ) ) {
	function cp_get_image( $post_id = '', $size = 'medium', $num = 1 ) {
		global $cp_options;

		_deprecated_function( __FUNCTION__, '3.4' );

		$img_check = '';
		$images = get_posts( array( 'post_type' => 'attachment', 'numberposts' => $num, 'post_status' => null, 'post_parent' => $post_id, 'order' => 'ASC', 'orderby' => 'ID', 'no_found_rows' => true ) );
		if ( $images ) {
			foreach ( $images as $image ) {
				$img_check = wp_get_attachment_image( $image->ID, $size, $icon = false );
			}
		} else {
			// show the placeholder image
			if ( $cp_options->ad_images ) {
				$img_check = '<img class="attachment-medium" alt="" title="" src="' . appthemes_locate_template_uri( 'images/no-thumb-75.jpg' ) . '" />';
			}
		}
		echo $img_check;
	}
}


/**
 * Returns the image associated to the ad used in the loop-ad for hover previewing.
 *
 * @deprecated 3.4
 *
 * @param int $post_id (optional)
 * @param string $size (optional)
 * @param string $class (optional)
 * @param int $num (optional)
 *
 * @return string
 */
if ( ! function_exists( 'cp_get_image_url_raw' ) ) {
	function cp_get_image_url_raw( $post_id = '', $size = 'medium', $class = '', $num = 1 ) {
		global $cp_options;

		_deprecated_function( __FUNCTION__, '3.4' );

		$img_url_raw = '';
		$images = get_posts( array( 'post_type' => 'attachment', 'numberposts' => $num, 'post_status' => null, 'post_parent' => $post_id, 'order' => 'ASC', 'orderby' => 'ID', 'no_found_rows' => true ) );
		if ( $images ) {
			foreach ( $images as $image ) {
				$iarray = wp_get_attachment_image_src( $image->ID, $size, $icon = false );
				$img_url_raw = $iarray[0];
			}
		} else {
			if ( $cp_options->ad_images ) {
				$img_url_raw = appthemes_locate_template_uri( 'images/no-thumb.jpg' );
			}
		}

		return $img_url_raw;
	}
}


/**
 * Displays the image associated to the ad used on the home page.
 *
 * @deprecated 3.4
 *
 * @param int $post_id (optional)
 * @param string $size (optional)
 * @param string $class (optional)
 * @param int $num (optional)
 *
 * @return void
 */
if ( ! function_exists( 'cp_get_image_url_feat' ) ) {
	function cp_get_image_url_feat( $post_id = '', $size = 'medium', $class = '', $num = 1 ) {
		global $cp_options;

		_deprecated_function( __FUNCTION__, '3.4' );

		$img_check = '';
		$images = get_posts( array( 'post_type' => 'attachment', 'numberposts' => $num, 'post_status' => null, 'post_parent' => $post_id, 'order' => 'ASC', 'orderby' => 'ID', 'no_found_rows' => true ) );
		if ( $images ) {
			foreach ( $images as $image ) {
				$alt = get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
				$iarray = wp_get_attachment_image_src( $image->ID, $size, $icon = false );
				$img_check = '<img class="'. $class .'" src="'. $iarray[0] .'" width="'. $iarray[1] .'" height="'. $iarray[2] .'" alt="'. $alt .'" title="'. $alt .'" />';
			}
		} else {
			if ( $cp_options->ad_images ) {
				$img_check = '<img class="preview" alt="" title="" src="' . appthemes_locate_template_uri( 'images/no-thumb-sm.jpg' ) . '" />';
			}
		}
		echo $img_check;
	}
}


/**
 * Displays the first raw image url.
 *
 * @deprecated 3.4
 *
 * @param int $post_id
 * @param int $num (optional)
 * @param string $order (optional)
 * @param string $orderby (optional)
 * @param string $mime (optional)
 *
 * @return void
 */
function cp_get_image_url_old( $post_id, $num = 1, $order = 'ASC', $orderby = 'menu_order', $mime = 'image' ) {
	_deprecated_function( __FUNCTION__, '3.4' );

	$single_url = '';
	$images = get_posts( array( 'post_type' => 'attachment', 'numberposts' => $num, 'post_status' => null, 'order' => $order, 'orderby' => $orderby, 'post_mime_type' => $mime, 'post_parent' => $post_id, 'no_found_rows' => true ) );
	if ( $images ) {
		foreach ( $images as $image ) {
			$single_url = wp_get_attachment_url( $image->ID, false );
		}
	}
	echo $single_url;
}


/**
 * Sets the thumbnail pic on the WP admin post.
 *
 * @deprecated 3.4
 *
 * @param int $post_id
 * @param int $thumbnail_id
 *
 * @return void
 */
function cp_set_ad_thumbnail( $post_id, $thumbnail_id ) {
	_deprecated_function( __FUNCTION__, '3.4' );

	$thumbnail_html = wp_get_attachment_image( $thumbnail_id, 'thumbnail' );
	if ( ! empty( $thumbnail_html ) ) {
		update_post_meta( $post_id, '_thumbnail_id', $thumbnail_id );
		die( _wp_post_thumbnail_html( $thumbnail_id ) );
	}
}


/**
 * Deletes the thumbnail pic on the WP admin post.
 *
 * @deprecated 3.4
 *
 * @param int $post_id
 *
 * @return void
 */
function cp_delete_ad_thumbnail( $post_id ) {
	_deprecated_function( __FUNCTION__, '3.4' );

	delete_post_meta( $post_id, '_thumbnail_id' );
	die( _wp_post_thumbnail_html() );
}


/**
 * Sends new post report email to admin.
 *
 * @deprecated 3.4
 * @see 'app-reports' theme support
 *
 * @param int $post_id
 *
 * @return void
 */
function app_report_post( $post_id ) {
	_deprecated_function( __FUNCTION__, '3.4' );
}


/**
 * Displays the recaptcha.
 *
 * @deprecated 3.5.2
 */
function appthemes_recaptcha() {
	_deprecated_function( __FUNCTION__, '3.5.2', 'appthemes_display_recaptcha()' );

	appthemes_display_recaptcha();
}
