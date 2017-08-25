<?php
/**
 * Core functions.
 *
 * @package ClassiPress\Core
 * @author  AppThemes
 * @since   ClassiPress 3.3
 */

add_action( 'init', 'cp_register_taxonomies', 8 );
add_action( 'init', 'cp_register_post_types', 9 );
add_action( 'init', 'cp_register_search_index_items', 10 );
add_action( 'init', '_cp_setup_build_search_index', 100 );
add_action( 'after_setup_theme', 'cp_register_menus' );
add_action( 'after_setup_theme', 'cp_register_sidebars' );


/**
 * Register custom post type for ads.
 *
 * @return void
 */
function cp_register_post_types() {
	global $cp_options;

	$labels = array(
		'name'                  => _x( 'Ads', 'post type general name', APP_TD ),
		'singular_name'         => _x( 'Ad', 'post type singular name', APP_TD ),
		'add_new'               => __( 'Add New', APP_TD ),
		'add_new_item'          => __( 'Create New Ad', APP_TD ),
		'edit_item'             => __( 'Edit Ad', APP_TD ),
		'new_item'              => __( 'New Ad', APP_TD ),
		'view_item'             => __( 'View Ad', APP_TD ),
		'view_items'            => __( 'View Ads', APP_TD ),
		'search_items'          => __( 'Search Ads', APP_TD ),
		'not_found'             => __( 'No ads found.', APP_TD ),
		'not_found_in_trash'    => __( 'No ads found in trash.', APP_TD ),
		'parent_item_colon'     => __( 'Parent Ad:', APP_TD ),
		'menu_name'             => _x( 'Ads', 'post type menu name', APP_TD ),
		'all_items'             => __( 'All Ads', APP_TD ),
		'archives'              => __( 'Ad Archives', APP_TD ),
		'attributes'            => __( 'Ad Attributes', APP_TD ),
		'insert_into_item'      => __( 'Insert into ad', APP_TD ),
		'uploaded_to_this_item' => __( 'Uploaded to this ad', APP_TD ),
		'featured_image'        => __( 'Featured Image', APP_TD ),
		'set_featured_image'    => __( 'Set featured image', APP_TD ),
		'remove_featured_image' => __( 'Remove featured image', APP_TD ),
		'use_featured_image'    => __( 'Use as featured image', APP_TD ),
		'filter_items_list'     => __( 'Filter ads list', APP_TD ),
		'items_list_navigation' => __( 'Ads list navigation', APP_TD ),
		'items_list'            => __( 'Ads list', APP_TD ),
	);

	$args = array(
		'labels'              => $labels,
		'description'         => __( 'This is where you can create new classified ads on your site.', APP_TD ),
		'public'              => true,
		'show_ui'             => true,
		'has_archive'         => true,
		'capability_type'     => 'post',
		'publicly_queryable'  => true,
		'exclude_from_search' => false,
		'menu_position'       => 8,
		'menu_icon'           => 'dashicons-tag',
		'hierarchical'        => false,
		'rewrite'             => array( 'slug' => $cp_options->post_type_permalink, 'with_front' => false, 'feeds' => true ),
		'query_var'           => true,
		'supports'            => array( 'title', 'editor', 'author', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'sticky' ),
	);

	register_post_type( APP_POST_TYPE, $args );
}


/**
 * Register taxonomies for ads
 *
 * @return void
 */
function cp_register_taxonomies() {
	global $cp_options;

	$labels = array(
		'name'                       => _x( 'Ad Categories', 'taxonomy general name', APP_TD ),
		'singular_name'              => _x( 'Category', 'taxonomy singular name', APP_TD ),
		'search_items'               => __( 'Search Categories', APP_TD ),
		'popular_items'              => __( 'Popular Categories', APP_TD ),
		'all_items'                  => __( 'All Categories', APP_TD ),
		'parent_item'                => __( 'Parent Category', APP_TD ),
		'parent_item_colon'          => __( 'Parent Category:', APP_TD ),
		'edit_item'                  => __( 'Edit Category', APP_TD ),
		'view_item'                  => __( 'View Category', APP_TD ),
		'update_item'                => __( 'Update Category', APP_TD ),
		'add_new_item'               => __( 'Add New Category', APP_TD ),
		'new_item_name'              => __( 'New Category Name', APP_TD ),
		'separate_items_with_commas' => __( 'Separate ad categories with commas', APP_TD ),
		'add_or_remove_items'        => __( 'Add or remove ad categories', APP_TD ),
		'choose_from_most_used'      => __( 'Choose from the most used ad categories', APP_TD ),
		'not_found'                  => __( 'No ad categories found.', APP_TD ),
		'no_terms'                   => __( 'No ad categories', APP_TD ),
		'items_list_navigation'      => __( 'Ad categories list navigation', APP_TD ),
		'items_list'                 => __( 'Ad categories list', APP_TD ),
		'menu_name'                  => _x( 'Categories', 'taxonomy menu name', APP_TD ),
	);

	$args = array(
		'labels'       => $labels,
		'hierarchical' => true,
		'show_ui'      => true,
		'query_var'    => true,
		'rewrite'      => array( 'slug' => $cp_options->ad_cat_tax_permalink, 'with_front' => false, 'hierarchical' => true ),
		'update_count_callback' => '_update_post_term_count',
	);

	register_taxonomy( APP_TAX_CAT, APP_POST_TYPE, $args );


	$labels = array(
		'name'                       => _x( 'Ad Tags', 'taxonomy general name', APP_TD ),
		'singular_name'              => _x( 'Tag', 'taxonomy singular name', APP_TD ),
		'search_items'               => __( 'Search Tags', APP_TD ),
		'popular_items'              => __( 'Popular Tags', APP_TD ),
		'all_items'                  => __( 'All Tags', APP_TD ),
		'parent_item'                => __( 'Parent Tag', APP_TD ),
		'parent_item_colon'          => __( 'Parent Tag:', APP_TD ),
		'edit_item'                  => __( 'Edit Tag', APP_TD ),
		'view_item'                  => __( 'View Tag', APP_TD ),
		'update_item'                => __( 'Update Tag', APP_TD ),
		'add_new_item'               => __( 'Add New Tag', APP_TD ),
		'new_item_name'              => __( 'New Tag Name', APP_TD ),
		'separate_items_with_commas' => __( 'Separate ad tags with commas', APP_TD ),
		'add_or_remove_items'        => __( 'Add or remove ad tags', APP_TD ),
		'choose_from_most_used'      => __( 'Choose from the most common ad tags', APP_TD ),
		'not_found'                  => __( 'No ad tags found.', APP_TD ),
		'no_terms'                   => __( 'No ad tags', APP_TD ),
		'items_list_navigation'      => __( 'Ad tags list navigation', APP_TD ),
		'items_list'                 => __( 'Ad tags list', APP_TD ),
		'menu_name'                  => _x( 'Tags', 'taxonomy menu name', APP_TD ),
	);

	$args = array(
		'labels'       => $labels,
		'hierarchical' => false,
		'show_ui'      => true,
		'query_var'    => true,
		'rewrite'      => array( 'slug' => $cp_options->ad_tag_tax_permalink, 'with_front' => false ),
		'update_count_callback' => '_update_post_term_count',
	);

	register_taxonomy( APP_TAX_TAG, APP_POST_TYPE, $args );
}


/**
 * Register menus
 *
 * @return void
 */
function cp_register_menus() {
	register_nav_menu( 'primary', __( 'Primary Navigation', APP_TD ) );
	register_nav_menu( 'secondary', __( 'Footer Navigation', APP_TD ) );
	register_nav_menu( 'theme_dashboard', __( 'User Dashboard', APP_TD ) );
}



/**
 * Register sidebars
 *
 * @return void
 */
function cp_register_sidebars() {

	// Home Page
	register_sidebar( array(
		'name' => __( 'Main Sidebar', APP_TD ),
		'id' => 'sidebar_main',
		'description' => __( 'This is your main ClassiPress sidebar.', APP_TD ),
		'before_widget' => '<div class="shadowblock_out %2$s" id="%1$s"><div class="shadowblock">',
		'after_widget' => '</div><!-- /shadowblock --></div><!-- /shadowblock_out -->',
		'before_title' => '<h2 class="dotted">',
		'after_title' => '</h2>',
	) );

	// Page
	register_sidebar( array(
		'name' => __( 'Page Sidebar', APP_TD ),
		'id' => 'sidebar_page',
		'description' => __( 'This is your ClassiPress page sidebar.', APP_TD ),
		'before_widget' => '<div class="shadowblock_out %2$s" id="%1$s"><div class="shadowblock">',
		'after_widget' => '</div><!-- /shadowblock --></div><!-- /shadowblock_out -->',
		'before_title' => '<h2 class="dotted">',
		'after_title' => '</h2>',
	) );

	// Blog
	register_sidebar( array(
		'name' => __( 'Blog Sidebar', APP_TD ),
		'id' => 'sidebar_blog',
		'description' => __( 'This is your ClassiPress blog sidebar.', APP_TD ),
		'before_widget' => '<div class="shadowblock_out %2$s" id="%1$s"><div class="shadowblock">',
		'after_widget' => '</div><!-- /shadowblock --></div><!-- /shadowblock_out -->',
		'before_title' => '<h2 class="dotted">',
		'after_title' => '</h2>',
	) );

	// Ad
	register_sidebar( array(
		'name' => __( 'Ad Sidebar', APP_TD ),
		'id' => 'sidebar_listing',
		'description' => __( 'This is your ClassiPress single ad listing sidebar.', APP_TD ),
		'before_widget' => '<div class="shadowblock_out %2$s" id="%1$s"><div class="shadowblock">',
		'after_widget' => '</div><!-- /shadowblock --></div><!-- /shadowblock_out -->',
		'before_title' => '<h2 class="dotted">',
		'after_title' => '</h2>',
	) );

	// Author
	register_sidebar( array(
		'name' => __( 'Author Sidebar', APP_TD ),
		'id' => 'sidebar_author',
		'description' => __( 'This is your ClassiPress author sidebar.', APP_TD ),
		'before_widget' => '<div class="shadowblock_out %2$s" id="%1$s"><div class="shadowblock">',
		'after_widget' => '</div><!-- /shadowblock --></div><!-- /shadowblock_out -->',
		'before_title' => '<h2 class="dotted">',
		'after_title' => '</h2>',
	) );

	// User
	register_sidebar( array(
		'name' => __( 'User Sidebar', APP_TD ),
		'id' => 'sidebar_user',
		'description' => __( 'This is your ClassiPress user dashboard sidebar.', APP_TD ),
		'before_widget' => '<div class="shadowblock_out %2$s" id="%1$s"><div class="shadowblock">',
		'after_widget' => '</div><!-- /shadowblock --></div><!-- /shadowblock_out -->',
		'before_title' => '<h2 class="dotted">',
		'after_title' => '</h2>',
	) );

	// Footer
	register_sidebar( array(
		'name' => __( 'Footer', APP_TD ),
		'id' => 'sidebar_footer',
		'description' => __( 'This is your ClassiPress footer. You can have up to four items which will display in the footer from left to right.', APP_TD ),
		'before_widget' => '<div class="column %2$s" id="%1$s">',
		'after_widget' => '</div><!-- /column -->',
		'before_title' => '<h2 class="dotted">',
		'after_title' => '</h2>',
	) );

}


/**
 * Build Search Index for past items
 *
 * @return void
 */
function _cp_setup_build_search_index() {
	if ( ! current_theme_supports( 'app-search-index' ) ) {
		return;
	}

	appthemes_add_instance( 'APP_Build_Search_Index' );
}



/**
 * Register items to index, post types, taxonomies, and custom fields
 *
 * @return void
 */
function cp_register_search_index_items() {
	if ( ! current_theme_supports( 'app-search-index' ) || isset( $_GET['firstrun'] ) ) {
		return;
	}

	// Ad listings
	$listing_custom_fields = array_merge( cp_custom_search_fields(), array( 'cp_sys_ad_conf_id' ) );

	$listing_index_args = array(
		'meta_keys' => $listing_custom_fields,
		'taxonomies' => array( APP_TAX_CAT, APP_TAX_TAG ),
	);
	APP_Search_Index::register( APP_POST_TYPE, $listing_index_args );

	// Blog posts
	$post_index_args = array(
		'taxonomies' => array( 'category', 'post_tag' ),
	);
	APP_Search_Index::register( 'post', $post_index_args );

	// Pages
	APP_Search_Index::register( 'page' );
}



/**
 * Whether the Search Index is ready to use
 *
 * @return void
 */
function cp_search_index_enabled() {
	if ( ! current_theme_supports( 'app-search-index' ) ) {
		return false;
	}

	return apply_filters( 'cp_search_index_enabled', appthemes_get_search_index_status() );
}

