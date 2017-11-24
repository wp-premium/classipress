<?php
/**
 * Theme functions file
 *
 * DO NOT MODIFY THIS FILE. Make a child theme instead: http://codex.wordpress.org/Child_Themes
 *
 * @package ClassiPress
 * @author  AppThemes
 * @since   ClassiPress 1.0
 */

// Constants
define( 'CP_VERSION', '3.6.1' );
define( 'CP_DB_VERSION', '2794' );

// Should reflect the WordPress version in the .testenv file.
define( 'CP_WP_COMPATIBLE_VERSION', '4.7.4' );

define( 'APP_POST_TYPE', 'ad_listing' );
define( 'APP_TAX_CAT', 'ad_cat' );
define( 'APP_TAX_TAG', 'ad_tag' );

define( 'CP_ITEM_LISTING', 'ad-listing' );
define( 'CP_ITEM_MEMBERSHIP', 'membership-pack' );

define( 'CP_PACKAGE_LISTING_PTYPE', 'package-listing' );
define( 'CP_PACKAGE_MEMBERSHIP_PTYPE', 'package-membership' );

define( 'APP_TD', 'classipress' );

if ( version_compare( $wp_version, CP_WP_COMPATIBLE_VERSION, '<' ) ) {
	add_action( 'admin_notices', 'cp_display_version_warning' );
}

global $cp_options;

// Legacy variables - some plugins rely on them
$app_theme = 'ClassiPress';
$app_abbr = 'cp';
$app_version = CP_VERSION;
$app_db_version = 2683;
$app_edition = 'Ultimate Edition';


// Framework
require_once( dirname( __FILE__ ) . '/framework/load.php' );
require_once( dirname( __FILE__ ) . '/theme-framework/load.php' );
require_once( APP_FRAMEWORK_DIR . '/admin/class-meta-box.php' );
require_once( APP_FRAMEWORK_DIR . '/includes/tables.php' );

APP_Mail_From::init();

// define the transients we use
$app_transients = array( 'cp_cat_menu' );

// define the db tables we use
$app_db_tables = array( 'cp_ad_fields', 'cp_ad_forms', 'cp_ad_geocodes', 'cp_ad_meta', 'cp_ad_pop_daily', 'cp_ad_pop_total' );

// Only register deprecated tables on older CP versions.
if ( get_option( 'cp_db_version' ) < 2221 ) {
	array_merge( $app_db_tables, array( 'cp_ad_packs', 'cp_coupons', 'cp_order_info' ) );
}

// register the db tables
foreach ( $app_db_tables as $app_db_table ) {
	scb_register_table( $app_db_table );
}
scb_register_table( 'app_pop_daily', 'cp_ad_pop_daily' );
scb_register_table( 'app_pop_total', 'cp_ad_pop_total' );


$load_files = array(
	'checkout/load.php',
	'payments/load.php',
	'reports/load.php',
	'widgets/load.php',
	'stats/load.php',
	'recaptcha/load.php',
	'open-graph/load.php',
	'search-index/load.php',
	'admin/addons-mp/load.php',
	'plupload/app-plupload.php',
	'options.php',
	'appthemes-functions.php',
	'actions.php',
	'categories.php',
	'comments.php',
	'core.php',
	'cron.php',
	'custom-forms.php',
	'deprecated.php',
	'enqueue.php',
	'emails.php',
	'functions.php',
	'hooks.php',
	'images.php',
	'packages.php',
	'payments.php',
	'profile.php',
	'search.php',
	'security.php',
	'stats.php',
	'views.php',
	'views-checkout.php',
	'widgets.php',
	'theme-support.php',
	'customizer.php',
	'utils.php',
	// Form Progress
	'checkout/form-progress/load.php',
);
appthemes_load_files( dirname( __FILE__ ) . '/includes/', $load_files );

$load_classes = array(
	'CP_Blog_Archive',
	'CP_Posts_Tag_Archive',
	'CP_Post_Single',
	'CP_Author_Archive',
	'CP_Ads_Tag_Archive',
	'CP_Ads_Archive',
	'CP_Ads_Home',
	'CP_Ads_Categories',
	'CP_Add_New',
	'CP_Renew_Listing',
	'CP_Ad_Single',
	'CP_Edit_Item',
	'CP_Membership',
	'CP_User_Dashboard',
	'CP_User_Dashboard_Orders',
	'CP_User_Profile',
	// Checkout
	'CP_Order',
	'CP_Membership_Form_Select',
	'CP_Membership_Form_Preview',
	'CP_Listing_Form_Select_Category',
	'CP_Listing_Form_Edit',
	'CP_Listing_Form_Details',
	'CP_Listing_Form_Preview',
	'CP_Listing_Form_Submit_Free',
	'CP_Gateway_Select',
	'CP_Gateway_Process',
	'CP_Order_Summary',
	// Widgets
	'CP_Widget_125_Ads',
	'CP_Widget_Ad_Categories',
	'CP_Widget_Ad_Sub_Categories',
	'CP_Widget_Ads_Tag_Cloud',
	'CP_Widget_Blog_Posts',
	'CP_Widget_Facebook',
	'CP_Widget_Featured_Ads',
	'CP_Widget_Search',
	'CP_Widget_Sold_Ads',
	'CP_Widget_Top_Ads_Today',
	'CP_Widget_Top_Ads_Overall',
);
appthemes_add_instance( $load_classes );


// Admin only
if ( is_admin() ) {
	require_once( APP_FRAMEWORK_DIR . '/admin/importer.php' );

	$load_files = array(
		'admin.php',
		'dashboard.php',
		'enqueue.php',
		'install.php',
		'importer.php',
		'listing-single.php',
		'listing-list.php',
		'options.php',
		'package-single.php',
		'package-list.php',
		'settings.php',
		'system-info.php',
		'updates.php',
	);
	appthemes_load_files( dirname( __FILE__ ) . '/includes/admin/', $load_files );

	$load_classes = array(
		'CP_Theme_Dashboard',
		'CP_Theme_Settings_General' => $cp_options,
		'CP_Theme_Settings_Emails' => $cp_options,
		'CP_Theme_Settings_Pricing' => $cp_options,
		'CP_Theme_System_Info',
		'CP_Listing_Package_General_Metabox',
		'CP_Membership_Package_General_Metabox',
		'CP_Listing_Attachments_Metabox',
		'CP_Listing_Media' => array( '_app_media', __( 'Attachments', APP_TD ), APP_POST_TYPE, 'normal', 'low' ),
		'CP_Listing_Author_Metabox',
		'CP_Listing_Info_Metabox',
		'CP_Listing_Custom_Forms_Metabox',
		'CP_Listing_Pricing_Metabox',
	);
	appthemes_add_instance( $load_classes );

	// integrate custom permalinks in WP permalinks page
	$settings = appthemes_get_instance('CP_Theme_Settings_General');
	add_action( 'admin_init', array( $settings, 'init_integrated_options' ), 10 );
}


// Frontend only
if ( ! is_admin() ) {
	cp_load_all_page_templates();
}


// Constants
define( 'CP_DASHBOARD_URL', get_permalink( CP_User_Dashboard::get_id() ) );
define( 'CP_DASHBOARD_ORDERS_URL', get_permalink( CP_User_Dashboard_Orders::get_id() ) );
define( 'CP_PROFILE_URL', get_permalink( CP_User_Profile::get_id() ) );
define( 'CP_EDIT_URL', get_permalink( CP_Edit_Item::get_id() ) );
define( 'CP_ADD_NEW_URL', get_permalink( CP_Add_New::get_id() ) );
define( 'CP_MEMBERSHIP_PURCHASE_URL', get_permalink( CP_Membership::get_id() ) );


// Set the content width based on the theme's design and stylesheet.
// Used to set the width of images and content. Should be equal to the width the theme
// is designed for, generally via the style.css stylesheet.
if ( ! isset( $content_width ) ) {
	$content_width = 500;
}

function cp_display_version_warning(){
	global $wp_version;

	$message = sprintf( __( 'ClassiPress version %1$s is not compatible with WordPress version %2$s. Correct work is not guaranteed. Please upgrade the WordPress at least to version %3$s or downgrade the ClassiPress theme.', APP_TD ), CP_VERSION, $wp_version, CP_WP_COMPATIBLE_VERSION );
	echo '<div class="error fade"><p>' . $message .'</p></div>';
}

appthemes_init();
