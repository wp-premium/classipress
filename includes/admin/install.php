<?php
/**
 * Installation functions.
 *
 * @package ClassiPress\Admin\Install
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */


function cp_install_theme() {

	// run the table install script
	cp_tables_install();

	// populate the database tables
	cp_populate_tables();

	// insert the default values
	cp_default_values();

	// create pages and assign templates
	cp_create_pages();

	// create a default ad and category
	cp_default_ad();

	// create the default menus
	cp_default_menus();

	// assign default widgets to sidebars
	cp_default_widgets();

	// flush the rewrite rules
	flush_rewrite_rules();

	// if fresh install, setup current database version, and do not process update
	if ( get_option( 'cp_db_version' ) == false ) {

		// set blog and ads pages
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', CP_Ads_Home::get_id() );
		update_option( 'page_for_posts', CP_Blog_Archive::get_id() );

		update_option( 'cp_db_version', CP_DB_VERSION );
	}

}
add_action( 'appthemes_first_run', 'cp_install_theme' );


// Create the theme database tables
function cp_tables_install() {
	global $wpdb;

	// create the ad forms table - store form data

		$sql = "
					id int(10) NOT NULL AUTO_INCREMENT,
					form_name varchar(255) NOT NULL,
					form_label varchar(255) NOT NULL,
					form_desc longtext DEFAULT NULL,
					form_cats longtext NOT NULL,
					form_status varchar(255) DEFAULT NULL,
					form_owner varchar(255) NOT NULL DEFAULT 'admin',
					form_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					form_modified datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					PRIMARY KEY  (id)";

	scb_install_table( 'cp_ad_forms', $sql );


	// create the ad meta table - store form fields meta data

		$sql = "
					meta_id int(10) NOT NULL AUTO_INCREMENT,
					form_id int(10) NOT NULL,
					field_id int(10) NOT NULL,
					field_req varchar(255) NOT NULL DEFAULT '0',
					field_pos int(10) NOT NULL DEFAULT '0',
					field_search int(10) NOT NULL DEFAULT '0',
					PRIMARY KEY  (meta_id)";

	scb_install_table( 'cp_ad_meta', $sql );


	// create the ad fields table - store form fields data

		$sql = "
					field_id int(10) NOT NULL AUTO_INCREMENT,
					field_name varchar(255) NOT NULL,
					field_label varchar(255) NOT NULL,
					field_desc longtext DEFAULT NULL,
					field_type varchar(255) NOT NULL,
					field_values longtext DEFAULT NULL,
					field_tooltip longtext DEFAULT NULL,
					field_search varchar(255) NOT NULL DEFAULT '0',
					field_perm int(11) NOT NULL DEFAULT '0',
					field_core int(11) NOT NULL DEFAULT '0',
					field_req int(11) NOT NULL DEFAULT '0',
					field_owner varchar(255) NOT NULL DEFAULT 'admin',
					field_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					field_modified datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					field_min_length int(11) NOT NULL DEFAULT '0',
					field_validation longtext DEFAULT NULL,
					PRIMARY KEY  (field_id)";

	scb_install_table( 'cp_ad_fields', $sql );


	// create the geocodes table - store geo location data

		$sql = "
					post_id bigint(20) unsigned NOT NULL,
					lat float( 10, 6 ) NOT NULL,
					lng float( 10, 6 ) NOT NULL,
					PRIMARY KEY  (post_id)";

	scb_install_table( 'cp_ad_geocodes', $sql );

}


// Populate the database tables
function cp_populate_tables() {
	global $wpdb;

	/**
	* Insert default data into tables
	*
	* Flag values for the cp_ad_fields table
	* =======================================
	* Field permissions (field name - field_perm) are 0,1,2 and are as follows:
	* 0 = rename label, remove from form layout, reorder, change values, delete
	* 1 = rename label, reorder
	* 2 = rename label, remove from form layout, reorder, change values
	*
	* please don't ask about the logic of the order. :-)
	*
	* field_core can be 1 or 0. 1 means it's a core field and will be included
	* in the default form if no custom form has been created
	*
	* field_req in this table is only used for the default form meaning if no
	* custom form has been created, use these fields with 1 meaning mandatory field
	*
	*
	*/

	$field_sql = "SELECT field_id FROM $wpdb->cp_ad_fields WHERE field_name = %s LIMIT 1";

	// DO NOT CHANGE THE ORDER OF THE FIRST 9 RECORDS!
	// admin-options.php cp_add_core_fields() depends on these fields
	// add more records after the post_content row insert statement


	// Title field
	$wpdb->get_results( $wpdb->prepare($field_sql, 'post_title') );
	if ( $wpdb->num_rows == 0 ) {

		$wpdb->insert( $wpdb->cp_ad_fields, array(
			'field_name' => 'post_title',
			'field_label' => 'Title',
			'field_desc' => 'This is the name of the ad and is mandatory on all forms. It is a core ClassiPress field and cannot be deleted.',
			'field_type' => 'text box',
			'field_values' => '',
			'field_search' => '0',
			'field_perm' => '1',
			'field_core' => '1',
			'field_req' => '1',
			'field_owner' => 'ClassiPress',
			'field_created' => current_time('mysql'),
			'field_modified' => current_time('mysql'),
			'field_min_length' => '0'
		) );

	}

	// Price field
	$wpdb->get_results( $wpdb->prepare( $field_sql, 'cp_price' ) );
	if ( $wpdb->num_rows == 0 ) {

		$wpdb->insert( $wpdb->cp_ad_fields, array(
			'field_name' => 'cp_price',
			'field_label' => 'Price',
			'field_desc' => 'This is the price field for the ad. It is a core ClassiPress field and cannot be deleted.',
			'field_type' => 'text box',
			'field_values' => '',
			'field_search' => '0',
			'field_perm' => '2',
			'field_core' => '1',
			'field_req' => '1',
			'field_owner' => 'ClassiPress',
			'field_created' => current_time('mysql'),
			'field_modified' => current_time('mysql'),
			'field_min_length' => '0'
		) );

	}

	// Street field
	$wpdb->get_results( $wpdb->prepare( $field_sql, 'cp_street' ) );
	if ( $wpdb->num_rows == 0 ) {

		$wpdb->insert( $wpdb->cp_ad_fields, array(
			'field_name' => 'cp_street',
			'field_label' => 'Street',
			'field_desc' => 'This is the street address text field. It is a core ClassiPress field and cannot be deleted. (Needed on your forms for Google maps to work best.)',
			'field_type' => 'text box',
			'field_values' => '',
			'field_search' => '0',
			'field_perm' => '2',
			'field_core' => '1',
			'field_req' => '0',
			'field_owner' => 'ClassiPress',
			'field_created' => current_time('mysql'),
			'field_modified' => current_time('mysql'),
			'field_min_length' => '0'
		) );

	}

	// City field
	$wpdb->get_results( $wpdb->prepare( $field_sql, 'cp_city' ) );
	if ( $wpdb->num_rows == 0 ) {

		$wpdb->insert( $wpdb->cp_ad_fields, array(
			'field_name' => 'cp_city',
			'field_label' => 'City',
			'field_desc' => 'This is the city field for the ad listing. It is a core ClassiPress field and cannot be deleted. (Needed on your forms for Google maps to work best.)',
			'field_type' => 'text box',
			'field_values' => '',
			'field_search' => '1',
			'field_perm' => '2',
			'field_core' => '1',
			'field_req' => '0',
			'field_owner' => 'ClassiPress',
			'field_created' => current_time('mysql'),
			'field_modified' => current_time('mysql'),
			'field_min_length' => '0'
		) );

	}

	// State field
	$wpdb->get_results( $wpdb->prepare( $field_sql, 'cp_state' ) );
	if ( $wpdb->num_rows == 0 ) {

		$wpdb->insert( $wpdb->cp_ad_fields, array(
			'field_name' => 'cp_state',
			'field_label' => 'State',
			'field_desc' => 'This is the state/province drop-down select box for the ad. It is a core ClassiPress field and cannot be deleted. (Needed on your forms for Google maps to work best.)',
			'field_type' => 'drop-down',
			'field_values' => 'Alabama,Alaska,Arizona,Arkansas,California,Colorado,Connecticut,Delaware,District of Columbia,Florida,Georgia,Hawaii,Idaho,Illinois,Indiana,Iowa,Kansas,Kentucky,Louisiana,Maine,Maryland,Massachusetts,Michigan,Minnesota,Mississippi,Missouri,Montana,Nebraska,Nevada,New Hampshire,New Jersey,New Mexico,New York,North Carolina,North Dakota,Ohio,Oklahoma,Oregon,Pennsylvania,Rhode Island,South Carolina,South Dakota,Tennessee,Texas,Utah,Vermont,Virginia,Washington,West Virginia,Wisconsin,Wyoming',
			'field_search' => '1',
			'field_perm' => '2',
			'field_core' => '1',
			'field_req' => '1',
			'field_owner' => 'ClassiPress',
			'field_created' => current_time('mysql'),
			'field_modified' => current_time('mysql'),
			'field_min_length' => '0'
		) );

	}

	// Country field
	$wpdb->get_results( $wpdb->prepare( $field_sql, 'cp_country' ) );
	if ( $wpdb->num_rows == 0 ) {

		$wpdb->insert( $wpdb->cp_ad_fields, array(
			'field_name' => 'cp_country',
			'field_label' => 'Country',
			'field_desc' => 'This is the country drop-down select box for the ad. It is a core ClassiPress field and cannot be deleted.',
			'field_type' => 'drop-down',
			'field_values' => 'United States,United Kingdom,Afghanistan,Albania,Algeria,American Samoa,Angola,Anguilla,Antarctica,Antigua and Barbuda,Argentina,Armenia,Aruba,Ashmore and Cartier Island,Australia,Austria,Azerbaijan,Bahamas,Bahrain,Bangladesh,Barbados,Belarus,Belgium,Belize,Benin,Bermuda,Bhutan,Bolivia,Bosnia and Herzegovina,Botswana,Brazil,British Virgin Islands,Brunei,Bulgaria,Burkina Faso,Burma,Burundi,Cambodia,Cameroon,Canada,Cape Verde,Cayman Islands,Central African Republic,Chad,Chile,China,Christmas Island,Colombia,Comoros,Congo,Cook Islands,Costa Rica,Cote dIvoire,Croatia,Cuba,Cyprus,Czeck Republic,Denmark,Djibouti,Dominica,Dominican Republic,Ecuador,Egypt,El Salvador,Equatorial Guinea,Eritrea,Estonia,Ethiopia,Europa Island,Falkland Islands,Faroe Islands,Fiji,Finland,France,French Guiana,French Polynesia,French Southern and Antarctic Lands,Gabon,Gambia,Gaza Strip,Georgia,Germany,Ghana,Gibraltar,Glorioso Islands,Greece,Greenland,Grenada,Guadeloupe,Guam,Guatemala,Guernsey,Guinea,Guinea-Bissau,Guyana,Haiti,Heard Island and McDonald Islands,Honduras,Hong Kong,Howland Island,Hungary,Iceland,India,Indonesia,Iran,Iraq,Ireland,Ireland Northern,Isle of Man,Israel,Italy,Jamaica,Jan Mayen,Japan,Jarvis Island,Jersey,Johnston Atoll,Jordan,Juan de Nova Island,Kazakhstan,Kenya,Kiribati,Korea North,Korea South,Kuwait,Kyrgyzstan,Laos,Latvia,Lebanon,Lesotho,Liberia,Libya,Liechtenstein,Lithuania,Luxembourg,Macau,Macedonia,Madagascar,Malawi,Malaysia,Maldives,Mali,Malta,Marshall Islands,Martinique,Mauritania,Mauritius,Mayotte,Mexico,Micronesia,Midway Islands,Moldova,Monaco,Mongolia,Montserrat,Morocco,Mozambique,Namibia,Nauru,Nepal,Netherlands,Netherlands Antilles,New Caledonia,New Zealand,Nicaragua,Niger,Nigeria,Niue,Norfolk Island,Northern Mariana Islands,Norway,Oman,Pakistan,Palau,Panama,Papua New Guinea,Paraguay,Peru,Philippines,Pitcaim Islands,Poland,Portugal,Puerto Rico,Qatar,Reunion,Romania,Russia,Rwanda,Saint Helena,Saint Kitts and Nevis,Saint Lucia,Saint Pierre and Miquelon,Saint Vincent and the Grenadines,Samoa,San Marino,Sao Tome and Principe,Saudi Arabia,Scotland,Senegal,Seychelles,Sierra Leone,Singapore,Slovakia,Slovenia,Solomon Islands,Somalia,South Africa,South Georgia,Spain,Spratly Islands,Sri Lanka,Sudan,Suriname,Svalbard,Swaziland,Sweden,Switzerland,Syria,Taiwan,Tajikistan,Tanzania,Thailand,Tobago,Toga,Tokelau,Tonga,Trinidad,Tunisia,Turkey,Turkmenistan,Tuvalu,Uganda,Ukraine,United Arab Emirates,Uruguay,Uzbekistan,Vanuatu,Vatican City,Venezuela,Vietnam,Virgin Islands,Wales,Wallis and Futuna,West Bank,Western Sahara,Yemen,Yugoslavia,Zambia,Zimbabwe',
			'field_search' => '1',
			'field_perm' => '2',
			'field_core' => '1',
			'field_req' => '1',
			'field_owner' => 'ClassiPress',
			'field_created' => current_time('mysql'),
			'field_modified' => current_time('mysql'),
			'field_min_length' => '0'
		) );

	}

	// Zip/Postal Code field
	$wpdb->get_results( $wpdb->prepare( $field_sql, 'cp_zipcode' ) );
	if ( $wpdb->num_rows == 0 ) {

		$wpdb->insert( $wpdb->cp_ad_fields, array(
			'field_name' => 'cp_zipcode',
			'field_label' => 'Zip/Postal Code',
			'field_desc' => 'This is the zip/postal code text field. It is a core ClassiPress field and cannot be deleted. (Needed on your forms for Google maps to work best.)',
			'field_type' => 'text box',
			'field_values' => '',
			'field_search' => '0',
			'field_perm' => '2',
			'field_core' => '1',
			'field_req' => '0',
			'field_owner' => 'ClassiPress',
			'field_created' => current_time('mysql'),
			'field_modified' => current_time('mysql'),
			'field_min_length' => '0'
		) );

	}

	// Tags field
	$wpdb->get_results( $wpdb->prepare($field_sql, 'tags_input') );
	if ( $wpdb->num_rows == 0 ) {

		$wpdb->insert( $wpdb->cp_ad_fields, array(
			'field_name' => 'tags_input',
			'field_label' => 'Tags',
			'field_desc' => 'This is for inputting tags for the ad. It is a core ClassiPress field and cannot be deleted.',
			'field_type' => 'text box',
			'field_values' => '',
			'field_search' => '0',
			'field_perm' => '2',
			'field_core' => '1',
			'field_req' => '0',
			'field_owner' => 'ClassiPress',
			'field_created' => current_time('mysql'),
			'field_modified' => current_time('mysql'),
			'field_min_length' => '0'
		) );

	}

	// Description field
	$wpdb->get_results( $wpdb->prepare($field_sql, 'post_content') );
	if ( $wpdb->num_rows == 0 ) {

		$wpdb->insert( $wpdb->cp_ad_fields, array(
			'field_name' => 'post_content',
			'field_label' => 'Description',
			'field_desc' => 'This is the main description box for the ad. It is a core ClassiPress field and cannot be deleted.',
			'field_type' => 'text area',
			'field_values' => '',
			'field_search' => '0',
			'field_perm' => '1',
			'field_core' => '1',
			'field_req' => '1',
			'field_owner' => 'ClassiPress',
			'field_created' => current_time('mysql'),
			'field_modified' => current_time('mysql'),
			'field_min_length' => '0'
		) );

	}

	// Region field
	$wpdb->get_results( $wpdb->prepare( $field_sql, 'cp_region' ) );
	if ( $wpdb->num_rows == 0 ) {

		$wpdb->insert( $wpdb->cp_ad_fields, array(
			'field_name' => 'cp_region',
			'field_label' => 'Region',
			'field_desc' => 'This is the region drop-down select box for the ad.',
			'field_type' => 'drop-down',
			'field_values' => 'San Francisco Bay Area,Orange County,Central Valley,Northern CA,Southern CA',
			'field_search' => '1',
			'field_perm' => '2',
			'field_core' => '0',
			'field_req' => '0',
			'field_owner' => 'ClassiPress',
			'field_created' => current_time('mysql'),
			'field_modified' => current_time('mysql'),
			'field_min_length' => '0'
		) );

	}

	// Size field
	$wpdb->get_results( $wpdb->prepare( $field_sql, 'cp_size' ) );
	if ( $wpdb->num_rows == 0 ) {

		$wpdb->insert( $wpdb->cp_ad_fields, array(
			'field_name' => 'cp_size',
			'field_label' => 'Size',
			'field_desc' => 'This is an example of a custom drop-down field.',
			'field_type' => 'drop-down',
			'field_values' => 'XS,S,M,L,XL,XXL',
			'field_search' => '0',
			'field_perm' => '0',
			'field_core' => '0',
			'field_req' => '0',
			'field_owner' => 'ClassiPress',
			'field_created' => current_time('mysql'),
			'field_modified' => current_time('mysql'),
			'field_min_length' => '0'
		) );

	}

	// Feedback field
	$wpdb->get_results( $wpdb->prepare( $field_sql, 'cp_feedback' ) );
	if ( $wpdb->num_rows == 0 ) {

		$wpdb->insert( $wpdb->cp_ad_fields, array(
			'field_name' => 'cp_feedback',
			'field_label' => 'Feedback',
			'field_desc' => 'This is an example of a custom text area field.',
			'field_type' => 'text area',
			'field_values' => '',
			'field_search' => '0',
			'field_perm' => '0',
			'field_core' => '0',
			'field_req' => '0',
			'field_owner' => 'ClassiPress',
			'field_created' => current_time('mysql'),
			'field_modified' => current_time('mysql'),
			'field_min_length' => '0'
		) );

	}

	// Currency field
	$wpdb->get_results( $wpdb->prepare( $field_sql, 'cp_currency' ) );
	if ( $wpdb->num_rows == 0 ) {

		$wpdb->insert( $wpdb->cp_ad_fields, array(
			'field_name' => 'cp_currency',
			'field_label' => 'Currency',
			'field_desc' => 'This is the currency drop-down select box for the ad. Add it to the form below the price to allow users to choose the currency for the ad price.',
			'field_type' => 'drop-down',
			'field_values' => '$,€,£,¥',
			'field_search' => '0',
			'field_perm' => '0',
			'field_core' => '0',
			'field_req' => '0',
			'field_owner' => 'ClassiPress',
			'field_created' => current_time('mysql'),
			'field_modified' => current_time('mysql'),
			'field_min_length' => '0'
		) );

	}


	// Example Ad Pack
	$listing_packages = cp_get_listing_packages( array( 'post_status' => 'any' ) );
	if ( ! $listing_packages ) {
		$package_meta = array(
			'pack_name' => '30 days for only $5',
			'description' => 'This is the default price per ad package created by ClassiPress.',
			'price' => '5.00',
			'duration' => '30',
		);

		$package_id = wp_insert_post( array(
			'post_status' => 'publish',
			'post_type' => CP_PACKAGE_LISTING_PTYPE,
			'post_author' => 1,
			'post_name' => sanitize_title_with_dashes( $package_meta['pack_name'] ),
			'post_title' => $package_meta['pack_name'],
		) );

		foreach ( $package_meta as $meta_key => $meta_value ) {
			add_post_meta( $package_id, $meta_key, $meta_value, true );
		}
	}

	// Example Membership Pack
	$membership_packages = cp_get_membership_packages( array( 'post_status' => 'any' ) );
	if ( ! $membership_packages ) {
		$package_meta = array(
			'pack_name' => '30 days publishing for only $2',
			'description' => 'This is the default membership package created by ClassiPress.',
			'price' => '15.00',
			'price_modifier' => '2.00',
			'duration' => '30',
			'pack_type' => 'static',
			'pack_satisfies_required' => '1',
		);

		$package_id = wp_insert_post( array(
			'post_status' => 'publish',
			'post_type' => CP_PACKAGE_MEMBERSHIP_PTYPE,
			'post_author' => 1,
			'post_name' => sanitize_title_with_dashes( $package_meta['pack_name'] ),
			'post_title' => $package_meta['pack_name'],
		) );

		foreach ( $package_meta as $meta_key => $meta_value ) {
			add_post_meta( $package_id, $meta_key, $meta_value, true );
		}
	}

}


// Insert the default values
function cp_default_values() {

	// uncheck the crop thumbnail image checkbox
	delete_option( 'thumbnail_crop' );
	// set the WP image sizes
	update_option( 'thumbnail_size_w', 50 );
	update_option( 'thumbnail_size_h', 50 );
	update_option( 'medium_size_w', 200 );
	update_option( 'medium_size_h', 150 );
	update_option( 'large_size_w', 500 );
	update_option( 'large_size_h', 500 );
	if ( get_option( 'embed_size_w' ) == false ) {
		update_option( 'embed_size_w', 500 );
	}

	// set the default new WP user role only if it's currently subscriber
	if ( get_option( 'default_role' ) == 'subscriber' ) {
		update_option( 'default_role', 'contributor' );
	}

	// check the "membership" box to enable wordpress registration
	if ( get_option( 'users_can_register' ) == 0 ) {
		update_option( 'users_can_register', 1 );
	}

}


// Create the ClassiPress pages and assign the templates to them
function cp_create_pages() {

	// NOTE:
	// Creation of page templates currently handled by Framework class 'APP_View_Page',
	// 'install' method hooked into 'appthemes_first_run'

}


// Create the default ad
function cp_default_ad() {
	global $wpdb;

	$posts = get_posts( array( 'posts_per_page' => 1, 'post_type' => APP_POST_TYPE, 'no_found_rows' => true ) );

	if ( ! empty( $posts ) ) {
		return;
	}

	$cat = appthemes_maybe_insert_term( 'Misc', APP_TAX_CAT );

	$description = '<p>This is your first ClassiPress ad listing. It is a placeholder ad just so you can see how it works. Delete this before launching your new classified ads site.</p>Duis arcu turpis, varius nec sagittis id, ultricies ac arcu. Etiam sagittis rutrum nunc nec viverra. Etiam egestas congue mi vel sollicitudin.</p><p>Vivamus ac libero massa. Cras pellentesque volutpat dictum. Ut blandit dapibus augue, lobortis cursus mi blandit sed. Fusce vulputate hendrerit sapien id aliquet.</p>';

	$default_ad = array(
		'post_title' => 'My First Classified Ad',
		'post_name' => 'my-first-classified-ad',
		'post_content' => $description,
		'post_status' => 'publish',
		'post_type' => APP_POST_TYPE,
		'post_author' => 1,
	);

	// insert the default ad
	$post_id = wp_insert_post( $default_ad );

	//set the custom post type categories
	wp_set_post_terms( $post_id, $cat['term_id'], APP_TAX_CAT, false );

	//set the custom post type tags
	$new_tags = array( 'ad tag1', 'ad tag2', 'ad tag3' );
	wp_set_post_terms( $post_id, $new_tags, APP_TAX_TAG, false );


	// set some default meta values
	$ad_expire_date = appthemes_mysql_date( current_time( 'mysql' ), 30 );
	$advals['cp_sys_expire_date'] = $ad_expire_date;
	$advals['cp_sys_ad_duration'] = '30';
	$advals['cp_sys_ad_conf_id'] = '3624e0d2963459d2';
	$advals['cp_sys_userIP'] = '153.247.194.375';
	$advals['cp_daily_count'] = '0';
	$advals['cp_total_count'] = '0';
	$advals['cp_price'] = '250';
	$advals['cp_street'] = '153 Townsend St';
	$advals['cp_city'] = 'San Francisco';
	$advals['cp_state'] = 'California';
	$advals['cp_country'] = 'United States';
	$advals['cp_zipcode'] = '94107';
	$advals['cp_sys_total_ad_cost'] = '5.00';

	// now add the custom fields into WP post meta fields
	foreach ( $advals as $meta_key => $meta_value ) {
		add_post_meta( $post_id, $meta_key, $meta_value, true );
	}

	// set coordinates of new ad
	cp_update_geocode( $post_id, '', '37.779633', '-122.391762' );

}


// Create the default menus
function cp_default_menus() {
	$menus = array(
		'primary' => __( 'Header', APP_TD ),
		'secondary' => __( 'Footer', APP_TD ),
	);

	foreach( $menus as $location => $name ) {

		if ( has_nav_menu( $location ) ) {
			continue;
		}

		$menu_id = wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			continue;
		}

		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title' => __( 'Home', APP_TD ),
			'menu-item-url' => home_url( '/' ),
			'menu-item-status' => 'publish'
		) );

		$page_ids = array(
			CP_Ads_Categories::get_id(),
			CP_Blog_Archive::get_id(),
		);

		foreach ( $page_ids as $page_id ) {
			$page = get_post( $page_id );

			if ( ! $page ) {
				continue;
			}

			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-type' => 'post_type',
				'menu-item-object' => 'page',
				'menu-item-object-id' => $page_id,
				'menu-item-title' => $page->post_title,
				'menu-item-url' => get_permalink( $page ),
				'menu-item-status' => 'publish'
			) );
		}

		$locations = get_theme_mod( 'nav_menu_locations' );
		$locations[ $location ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}
}


function cp_default_widgets() {
	list( $args ) = get_theme_support( 'app-versions' );

	if ( ! get_option( $args['option_key'] ) && $args['current_version'] == get_transient( APP_UPDATE_TRANSIENT ) ) {

		$sidebars_widgets = array(
			// Homepage
			'sidebar_main' => array(
				'cp_125_ads' => array(
					'title' => __( 'Sponsored Ads', APP_TD ),
					'ads' => CP_Widget_125_Ads::$ads,
				),
				'cp_facebook_like' => array(
					'title' => __( 'Facebook Friends', APP_TD ),
					'fid' => '137589686255438',
					'connections' => 10,
					'width' => 305,
					'height' => 290,
				),
				'ad_tag_cloud' => array(
					'title' => __( 'Tags', APP_TD ),
					'taxonomy' => 'ad_tag',
					'number' => 45,
				),
				'cp_recent_posts' => array(
					'title' => __( 'From the Blog', APP_TD ),
					'count' => 5,
				),
			),
			// Ad
			'sidebar_listing' => array(
				'top_ads_overall' => array(
					'title' => __( 'Popular Ads Overall', APP_TD ),
					'number' => 10,
				),
				'widget-sold-ads' => array(
					'title' => __( 'Sold Ads', APP_TD ),
					'number' => 10,
				),
			),
			// Page
			'sidebar_page' => array(
				'top_ads_overall' => array(
					'title' => __( 'Popular Ads Overall', APP_TD ),
					'number' => 10,
				),
			),
			// Blog
			'sidebar_blog' => array(
				'categories' => array(
					'title' => __( 'Blog Categories', APP_TD ),
					'count' => 1,
				),
				'tag_cloud' => array(
					'title' => __( 'Tags', APP_TD ),
					'taxonomy' => 'post_tag',
				),
			),
			// Author
			'sidebar_author' => array(
				'widget-ad-categories' => array(
					'title' => __( 'Ad Categories', APP_TD ),
					'number' => 0,
				),
			),
			// Footer
			'sidebar_footer' => array(
				'text' => array(
					'title' => __( 'About Us', APP_TD ),
					'text' => 'This is just a text box widget so you can type whatever you want and it will automatically appear here. Pretty cool, huh?',
				),
				'top_ads_overall' => array(
					'title' => __( 'Most Popular', APP_TD ),
					'number' => 10,
				),
				'recent-posts' => array(
					'title' => __( 'Recent Posts', APP_TD ),
					'number' => 10,
				),
				'meta' => array(
					'title' => __( 'Meta', APP_TD ),
				),
			),
		);

		appthemes_install_widgets( $sidebars_widgets );

	}

}
