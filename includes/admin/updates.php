<?php
/**
 * Updating functions.
 *
 * @package ClassiPress\Admin\Update
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */


/**
 * Functions to be called in install and upgrade scripts.
 *
 * @since 3.1.0
 */
if ( ! function_exists( 'cp_upgrade_all' ) ) :
	function cp_upgrade_all() {

		$current_db_version = get_option( 'cp_db_version' );

		if ( $current_db_version < 1280 ) {
			cp_update_advanced_search_db();
		}

		if ( $current_db_version < 1290 ) {
			cp_upgrade_317();
		}

		if ( $current_db_version < 1320 ) {
			cp_upgrade_320();
		}

		if ( $current_db_version < 1960 ) {
			cp_upgrade_330();
		}

		if ( $current_db_version < 2103 ) {
			cp_upgrade_332();
		}

		if ( $current_db_version < 2221 ) {
			cp_upgrade_340();
		}

		if ( $current_db_version < 2683 || isset( $_GET['backup_353'] ) ) {
			cp_upgrade_353();
		}

		update_option( 'cp_db_version', CP_DB_VERSION );

	}
endif;
add_action( 'appthemes_first_run', 'cp_upgrade_all' );


/**
 * Execute changes made in ClassiPress 3.1.0.
 * Geocoding migration script.
 *
 * @since 3.1.0
 */
function cp_update_advanced_search_db() {
	global $wpdb;

	$output = '';
	$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s ORDER BY ID ASC", APP_POST_TYPE ) );
	if ( $post_ids ) {
		echo scb_admin_notice( __( 'Geocoding ad listing addresses to make the advanced search radius feature work. This process queries Google Maps to get longitude and latitude coordinates based on each ad listings address. Please be patient as this may take a few minutes to complete.', APP_TD ) );

		foreach ( $post_ids as $post_id ) {
			if ( ! cp_get_geocode( $post_id ) ) {
				$result = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key IN ('cp_street','cp_city','cp_state','cp_zipcode','cp_country')", $post_id ), OBJECT_K );
				$address = '';
				foreach ( $result as $cur ) {
					if ( ! empty( $cur->meta_key ) ) {
						$address .= "{$cur->meta_value}, ";
					}
				}
				$address = rtrim( $address, ', ' );
				if ( $address ) {
					$output .= sprintf( '<p>' . __( "Ad #%d - %s ", APP_TD ), $post_id, $address );
					$geocode = json_decode( wp_remote_retrieve_body( wp_remote_get( 'http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode( $address ) . '&sensor=false' ) ) );
					if ( 'OK' == $geocode->status ) {
						$output .= esc_html( "({$geocode->results[0]->geometry->location->lat}, {$geocode->results[0]->geometry->location->lng})" );
						cp_update_geocode( $post_id, '', $geocode->results[0]->geometry->location->lat, $geocode->results[0]->geometry->location->lng );
						$output .= ' &raquo; <font color="green">' . __( 'Geocoding complete.', APP_TD ) . '</font>';
					} else {
						$output .= ' &raquo; <font color="red">' . __( 'Geocoding failed - address not found.', APP_TD ) . '</font>';
					}
					$output .= '</p>';
				}
			}
		}

		$output .= '<br /><strong>' . __(' Geocoding table updated.', APP_TD ) . '</strong><br />';
		$output .= '<small>' . __( 'Please note: Ads that failed during this process will not show up during a radius search since the address was invalid.', APP_TD ) . '</small>';

		update_option( 'cp_db_version', '1280' );
		echo scb_admin_notice( $output );
	} // end if $post_ids

	update_option( 'cp_db_version', '1280' );

}


/**
 * Execute changes made in ClassiPress 3.1.7.
 * Convert checkbox fields
 *
 * @since 3.1.7
 */
function cp_upgrade_317() {
	global $wpdb;

	$sql = "SELECT field_name FROM $wpdb->cp_ad_fields WHERE field_type = 'checkbox' ";
	$results = $wpdb->get_results( $sql );

	if ( $results ) {
		foreach ( $results as $result ) {
			$sql_meta = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s GROUP BY post_id", $result->field_name );
			$results_meta = $wpdb->get_results( $sql_meta );

			if ( $results_meta ) {
				foreach( $results_meta as $meta ) {
					$post_meta = get_post_meta( $meta->post_id, $result->field_name, true );
					if ( ! empty( $post_meta ) ) {
						delete_post_meta( $meta->post_id, $result->field_name );
						delete_post_meta( $meta->post_id, $result->field_name . '_list' );
						$post_meta_vals = explode( ",", $post_meta );
						if ( is_array( $post_meta_vals ) ) {
							foreach ( $post_meta_vals as $checkbox_value ) {
								add_post_meta( $meta->post_id, $result->field_name, $checkbox_value );
							}
						}
					}
				}
			}
		}
	}

	update_option( 'cp_db_version', '1290' );

}


/**
 * Execute changes made in ClassiPress 3.2.
 *
 * @since 3.2
 */
function cp_upgrade_320() {
	global $wpdb;

	if ( get_option( 'cp_admin_security' ) == 'install_themes' ) {
		update_option( 'cp_admin_security', 'manage_options' );
	}

	// remove old table indexes
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	drop_index( $wpdb->cp_ad_pop_daily, 'id' );
	drop_index( $wpdb->cp_ad_pop_total, 'id' );

	update_option( 'cp_db_version', '1320' );

}


/**
 * Convert and remove legacy meta used in ClassiPress 2.9.3 and earlier.
 *
 * @since 3.3
 */
function cp_upgrade_legacy_meta_293() {
	global $wpdb;

	// legacy meta
	$meta_keys = array(
		'expires' => 'cp_sys_expire_date',
		'price' => 'cp_price',
		'phone' => 'cp_phone',
		'location' => 'cp_city',
		'cp_totalcost' => 'cp_sys_total_ad_cost',
		'cp_adURL' => 'cp_url',
		'email' => '',
		'name' => '',
	);
	$meta_query = "SELECT * FROM $wpdb->postmeta WHERE meta_key IN ('" . implode( "', '", array_keys( $meta_keys ) ) . "') ";
	$legacy_meta = $wpdb->get_results( $meta_query );

	if ( $legacy_meta ) {
		foreach ( $legacy_meta as $postmeta ) {

			// convert anonymous posters to users
			if ( $postmeta->meta_key == 'email' ) {
				$user = get_user_by( 'email', $postmeta->meta_value );
				$post = get_post( $postmeta->post_id );
				if ( $post ) {
					if ( ! $user ) {
						$user_login = ( get_post_meta( $postmeta->post_id, 'name', true ) ) ? sanitize_user( get_post_meta( $postmeta->post_id, 'name', true ) . '-' . rand( 10, 1000 ), true ) : sanitize_title( $postmeta->meta_value );
						if ( ! username_exists( $user_login ) ) {
							$user_id = wp_create_user( $user_login, wp_generate_password(), $postmeta->meta_value );
							if ( $user_id && is_integer( $user_id ) ) {
								$user = get_user_by( 'id', $user_id );
							}
						}
					}
					if ( $user && $user->ID != $post->post_author ) {
						wp_update_post( array( 'ID' => $post->ID, 'post_author' => $user->ID ) );
					}
				}
			}

			// convert and remove legacy meta
			foreach ( $meta_keys as $old_meta_key => $new_meta_key ) {
				if ( $postmeta->meta_key != $old_meta_key ) {
					continue;
				}

				// remove legacy meta if no replacement
				if ( empty( $new_meta_key ) ) {
					delete_post_meta( $postmeta->post_id, $old_meta_key );
				}

				$new_meta_value = get_post_meta( $postmeta->post_id, $new_meta_key, true );
				if ( ! empty( $new_meta_value ) ) {
					delete_post_meta( $postmeta->post_id, $old_meta_key );
				} else {
					$old_meta_value = get_post_meta( $postmeta->post_id, $old_meta_key, true );
					update_post_meta( $postmeta->post_id, $new_meta_key, $old_meta_value );
					delete_post_meta( $postmeta->post_id, $old_meta_key );
				}

			}
		}
	}

}


/**
 * Convert coupons to format of AppThemes Coupons plugin.
 *
 * @since 3.3
 */
function cp_upgrade_coupons_330() {
	global $wpdb;

	// legacy coupons
	$legacy_coupons = $wpdb->get_results( "SELECT * FROM $wpdb->cp_coupons " );

	if ( ! $legacy_coupons ) {
		return;
	}

	foreach ( $legacy_coupons as $coupon ) {
		// create new post for coupon
		$new_coupon_post = array(
			'post_title' => $coupon->coupon_code,
			'post_status' => ( ( $coupon->coupon_status == 'active' ) ? 'publish' : 'draft' ),
			'post_type' => 'discount_coupon',
			'comment_status' => 'closed',
			'ping_status' => 'closed',
		);

		$new_coupon_id = wp_insert_post( $new_coupon_post );

		if ( ! $new_coupon_id ) {
			continue;
		}

		// add meta fields for coupon
		$new_coupon_postmeta = array(
			'code' => $coupon->coupon_code,
			'amount' => $coupon->coupon_discount,
			'type' => ( ( $coupon->coupon_discount_type == '%' ) ? 'percent' : 'flat' ),
			'start_date' => date( 'm/d/Y', strtotime( $coupon->coupon_start_date ) ),
			'end_date' => date( 'm/d/Y', strtotime( $coupon->coupon_expire_date ) ),
			'use_limit' => $coupon->coupon_max_use_count,
			'user_use_limit' => $coupon->coupon_max_use_count,
			'use_count' => $coupon->coupon_use_count,
		);

		foreach ( $new_coupon_postmeta as $meta_key => $meta_value ) {
			add_post_meta( $new_coupon_id, $meta_key, $meta_value, true );
		}

		// remove legacy entry
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->cp_coupons WHERE coupon_id = %d", $coupon->coupon_id ) );
	}

}


/**
 * Convert transactions to format of new AppThemes Payments.
 *
 * @since 3.3
 */
function cp_upgrade_transactions_330() {
	global $wpdb;

	if ( ! current_theme_supports( 'app-payments' ) )
		return;

	// legacy orders
	$legacy_orders = $wpdb->get_results( "SELECT * FROM $wpdb->cp_order_info " );

	if ( ! $legacy_orders ) {
		return;
	}

	foreach ( $legacy_orders as $legacy_order ) {
		// create new post for order
		$new_order_post = array(
			'post_title' => __( 'Transaction', APP_TD ),
			'post_content' => __( 'Transaction Data', APP_TD ),
			'post_status' => ( ( $legacy_order->payment_status == 'Completed' ) ? APPTHEMES_ORDER_ACTIVATED : APPTHEMES_ORDER_PENDING ),
			'post_type' => APPTHEMES_ORDER_PTYPE,
			'post_date' => date( 'Y-m-d H:i:s', strtotime( $legacy_order->payment_date ) ),
			'post_author' => ( ( $legacy_order->user_id ) ? $legacy_order->user_id : 1 ),
		);

		$new_order_id = wp_insert_post( $new_order_post );

		if ( ! $new_order_id ) {
			continue;
		}

		// set correct slug
		wp_update_post( array( 'ID' => $new_order_id, 'post_name' => $new_order_id ) );

		$price = ( empty( $legacy_order->mc_gross ) || ! is_numeric( $legacy_order->mc_gross ) ) ? 0 : $legacy_order->mc_gross;
		// add meta fields for order
		$new_order_postmeta = array(
			'currency' => $legacy_order->mc_currency,
			'total_price' => $price,
			'gateway' => ( ( $legacy_order->payment_type == 'banktransfer' ) ? 'bank-transfer' : 'paypal' ),
			'transaction_id' => $legacy_order->txn_id,
			'bt-sentemail' => '1',
			'ip_address' => appthemes_get_ip(),
			'first_name' => $legacy_order->first_name,
			'last_name' => $legacy_order->last_name,
			'street' => $legacy_order->street,
			'city' => $legacy_order->city,
			'state' => $legacy_order->state,
			'postcode' => $legacy_order->zipcode,
			'country' => $legacy_order->residence_country,
		);

		foreach ( $new_order_postmeta as $meta_key => $meta_value ) {
			add_post_meta( $new_order_id, $meta_key, $meta_value, true );
		}

		$order = appthemes_get_order( $new_order_id );
		if ( ! $order ) {
			continue;
		}

		if ( ! empty( $legacy_order->ad_id ) && $legacy_order->ad_id > 0 ) {
			$order->add_item( CP_ITEM_LISTING, $price, $legacy_order->ad_id );
		} else {
			$order->add_item( CP_ITEM_MEMBERSHIP, $price );
		}

		// remove legacy entry
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->cp_order_info WHERE id = %d", $legacy_order->id ) );
	}

}


/**
 * Convert old settings to scbOptions format.
 *
 * @since 3.3
 */
function cp_upgrade_settings_330() {
	global $wpdb, $cp_options;

	$new_options = array();
	$options_to_delete = array();

	// fields to convert from select 'yes/no' to checkbox
	$select_fields = array(
		'allow_registration_password',
		'use_logo',
		'search_ex_pages',
		'search_ex_blog',
		'ad_edit',
		'allow_relist',
		'ad_inquiry_form',
		'allow_html',
		'ad_stats_all',
		'ad_gravatar_thumb',
		'post_prune',
		'ad_images',
		'ad_image_preview',
		'captcha_enable',
		'adcode_468x60_enable',
		'adcode_336x280_enable',
		'disable_stylesheet',
		'debug_mode',
		'google_jquery',
		'disable_wp_login',
		'remove_wp_generator',
		'remove_admin_bar',
		'display_website_time',
		'cufon_enable',
		'new_ad_email',
		'prune_ads_email',
		'new_ad_email_owner',
		'expired_ad_email_owner',
		'nu_admin_email',
		'membership_activated_email_owner',
		'membership_ending_reminder_email',
		'nu_custom_email',
		'charge_ads',
		'enable_featured',
		'clean_price_field',
		'force_zeroprice',
		'hide_decimals',
		'enable_membership_packs',
	);

	// fields to translate
	$fields_to_translate = array(
		'cp_curr_pay_type' => 'currency_code',
		'cp_curr_symbol_pos' => 'currency_position',
	);

	// legacy settings
	$legacy_options = $wpdb->get_results( "SELECT * FROM $wpdb->options WHERE option_name LIKE 'cp_%'" );

	if ( ! $legacy_options ) {
		return;
	}

	foreach ( $legacy_options as $option ) {
		$new_option_name = substr( $option->option_name, 3 );

		// grab price per category options into an array
		$is_cat_price = appthemes_str_starts_with( $new_option_name, 'cat_price_' );
		if ( $is_cat_price ) {
			$cat_id = substr( $new_option_name, 10 );
			$new_options['price_per_cat'][ $cat_id ] = $option->option_value;
			$options_to_delete[] = $option->option_name;
			continue;
		}

		// translate old payment settings to new one
		if ( array_key_exists( $option->option_name, $fields_to_translate ) ) {
			$new_options[ $fields_to_translate[ $option->option_name ] ] = $option->option_value;
			$options_to_delete[] = $option->option_name;
			continue;
		}

		// skip not used options and membership entries
		if ( is_null( $cp_options->$new_option_name ) || $new_option_name == 'options' ) {
			continue;
		}

		// convert select 'yes/no' to checkbox
		if ( in_array( $new_option_name, $select_fields ) ) {
			$option->option_value = ( $option->option_value == 'yes' ) ? 1 : 0;
		}

		$new_options[ $new_option_name ] = maybe_unserialize( $option->option_value );
		$options_to_delete[] = $option->option_name;
	}

	// migrate payment gateways settings
	$gateways = array(
		'enabled' => array(
			'paypal' => ( get_option( 'cp_enable_paypal' ) == 'yes' ) ? 1 : 0,
			'bank-transfer' => ( get_option( 'cp_enable_bank' ) == 'yes' ) ? 1 : 0,
		),
		'paypal' => array(
			'email_address' => get_option( 'cp_paypal_email' ),
			'ipn_enabled' => ( get_option( 'cp_enable_paypal_ipn' ) == 'yes' ) ? 1 : 0,
			'sandbox_enabled' => ( get_option( 'cp_paypal_sandbox' ) ) ? 1 : 0,
		),
		'bank-transfer' => array(
			'message' => get_option( 'cp_bank_instructions' ),
		),
	);
	$new_options['gateways'] = $gateways;
	$options_to_delete = array_merge( $options_to_delete, array( 'cp_enable_paypal', 'cp_enable_bank', 'cp_paypal_email', 'cp_enable_paypal_ipn', 'cp_paypal_sandbox', 'cp_bank_instructions' ) );

	// enable selectbox js for those, which updating
	$new_options['selectbox'] = 1;
	// save new options
	$new_options = array_merge( get_option( 'cp_options', array() ), $new_options );
	update_option( 'cp_options', $new_options );

	// delete old options
	foreach ( $options_to_delete as $option_name ) {
		delete_option( $option_name );
	}
}


/**
 * Execute changes made in ClassiPress 3.3.
 *
 * @since 3.3
 */
function cp_upgrade_330() {

	// convert and remove legacy meta
	cp_upgrade_legacy_meta_293();

	// convert all expire dates to format 'Y-m-d H:i:s'
	$args = array(
		'post_type' => APP_POST_TYPE,
		'post_status' => 'any',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'meta_query' => array(
			array(
				'key' => 'cp_sys_expire_date',
				'value' => '',
				'compare' => '!=',
			),
		),
	);
	$legacy = new WP_Query( $args );
	if ( isset( $legacy->posts ) && is_array( $legacy->posts ) ) {
		foreach ( $legacy->posts as $post_id ) {
			$expire_time = strtotime( get_post_meta( $post_id, 'cp_sys_expire_date', true ) );
			$expire_date = date( 'Y-m-d H:i:s', $expire_time );
			update_post_meta( $post_id, 'cp_sys_expire_date', $expire_date );
		}
	}

	// change default for search field width option
	if ( get_option( 'cp_search_field_width' ) == '450px' ) {
		update_option( 'cp_search_field_width', '' );
	}

	// convert coupons to format of AppThemes Coupons plugin
	cp_upgrade_coupons_330();

	// convert transactions to format of new AppThemes Payments
	cp_upgrade_transactions_330();

	// convert old settings to scbOptions format
	cp_upgrade_settings_330();

	// set blog and ads pages
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', CP_Ads_Home::get_id() );
	update_option( 'page_for_posts', CP_Blog_Archive::get_id() );

	// remove old blog page
	$args = array(
		'post_type' => 'page',
		'meta_key' => '_wp_page_template',
		'meta_value' => 'tpl-blog.php',
		'posts_per_page' => 1,
		'suppress_filters' => true,
	);
	$blog_page = new WP_Query( $args );

	if ( ! empty( $blog_page->posts ) ) {
		wp_delete_post( $blog_page->posts[0]->ID, true );
	}

	update_option( 'cp_db_version', '1960' );
}


/**
 * Execute changes made in ClassiPress 3.3.2.
 *
 * @since 3.3.2
 */
function cp_upgrade_332() {
	global $wpdb;

	// remove old table index
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	drop_index( $wpdb->cp_ad_geocodes, 'id' );

	// drop 'category' and 'id' columns from geocodes table
	$wpdb->query( "ALTER TABLE $wpdb->cp_ad_geocodes DROP COLUMN category" );
	$wpdb->query( "ALTER TABLE $wpdb->cp_ad_geocodes DROP COLUMN id" );

	update_option( 'cp_db_version', '2103' );

	// redirect to run dbDelta again, on first pass it fail to create index
	cp_js_redirect( admin_url( 'admin.php?page=app-settings&firstrun=1' ), __( 'Continue Upgrading', APP_TD ) );
	exit;
}


/**
 * Convert packages to custom post types.
 *
 * @since 3.4
 */
function cp_upgrade_packages_340() {
	global $wpdb;

	// legacy packages
	$legacy_packages = $wpdb->get_results( "SELECT * FROM $wpdb->cp_ad_packs " );

	if ( ! $legacy_packages ) {
		return;
	}

	$memberships_relations = array();

	foreach ( $legacy_packages as $package ) {

		// Ad Package
		if ( in_array( $package->pack_status, array( 'active', 'inactive' ) ) ) {

			$package_meta = array(
				'pack_name' => stripslashes( $package->pack_name ),
				'description' => stripslashes( $package->pack_desc ),
				'price' => $package->pack_price,
				'duration' => $package->pack_duration,
			);

			$package_id = wp_insert_post( array(
				'post_status' => ( $package->pack_status == 'active' ) ? 'publish' : 'draft',
				'post_type' => CP_PACKAGE_LISTING_PTYPE,
				'post_author' => 1,
				'post_name' => sanitize_title_with_dashes( $package_meta['pack_name'] ),
				'post_title' => $package_meta['pack_name'],
			) );

			if ( ! $package_id ) {
				continue;
			}

			foreach ( $package_meta as $meta_key => $meta_value ) {
				add_post_meta( $package_id, $meta_key, $meta_value, true );
			}

			// delete old package
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->cp_ad_packs WHERE pack_id = %d", $package->pack_id ) );

		// Membership Package
		} else if ( in_array( $package->pack_status, array( 'active_membership', 'inactive_membership' ) ) ) {

			$package_meta = array(
				'pack_name' => stripslashes( $package->pack_name ),
				'description' => stripslashes( $package->pack_desc ),
				'price' => $package->pack_membership_price,
				'price_modifier' => $package->pack_price,
				'duration' => $package->pack_duration,
				'pack_type' => str_ireplace( 'required_', '', $package->pack_type ),
			);

			if ( stristr( $package->pack_type, 'required' ) ) {
				$package_meta['pack_satisfies_required'] = '1';
			}

			$package_id = wp_insert_post( array(
				'post_status' => ( $package->pack_status == 'active_membership' ) ? 'publish' : 'draft',
				'post_type' => CP_PACKAGE_MEMBERSHIP_PTYPE,
				'post_author' => 1,
				'post_name' => sanitize_title_with_dashes( $package_meta['pack_name'] ),
				'post_title' => $package_meta['pack_name'],
			) );

			if ( ! $package_id ) {
				continue;
			}

			foreach ( $package_meta as $meta_key => $meta_value ) {
				add_post_meta( $package_id, $meta_key, $meta_value, true );
			}

			// store relation between old and new package
			$memberships_relations[ $package->pack_id ] = $package_id;

			// delete old package
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->cp_ad_packs WHERE pack_id = %d", $package->pack_id ) );
		}

	}

	// users which purchased legacy packages
	$users = $wpdb->get_results( "SELECT user_id, meta_value as pack_id FROM $wpdb->usermeta WHERE meta_key = 'active_membership_pack' AND meta_value != ''" );

	if ( ! $users ) {
		return;
	}

	foreach ( $users as $user ) {
		if ( isset( $memberships_relations[ $user->pack_id ] ) ) {
			update_user_meta( $user->user_id, 'active_membership_pack', $memberships_relations[ $user->pack_id ] );
		} else {
			update_user_meta( $user->user_id, 'active_membership_pack', '' );
		}
	}

}


/**
 * Execute changes made in ClassiPress 3.4.
 *
 * @since 3.4
 */
function cp_upgrade_340() {
	global $cp_options;

	// rename and change the moderation option from radio to checkbox
	$moderate = ( $cp_options->post_status == 'pending' );
	$cp_options->moderate_ads = $moderate;

	// convert packages to custom post types
	cp_upgrade_packages_340();

	// remove old pages
	$args = array(
		'post_type' => 'page',
		'meta_query' => array(
			array(
				'key'     => '_wp_page_template',
				'value'   => array( 'tpl-add-new.php', 'tpl-membership-purchase.php', 'tpl-edit-item.php' ),
				'compare' => 'IN',
			),
		),
		'posts_per_page' => -1,
		'suppress_filters' => true,
	);
	$old_pages = new WP_Query( $args );

	if ( $old_pages->have_posts() ) {
		foreach ( $old_pages->posts as $old_page ) {
			wp_delete_post( $old_page->ID, true );
		}
	}

	// migrate logo options to 'custom-header' theme support
	if ( ! $cp_options->use_logo ) {
		// logo wasn't used, uset the default one
		set_theme_mod( 'header_image', 'remove-header' );
		remove_theme_mod( 'header_image_data' );
	} else if ( $cp_options->logo && $importer = appthemes_get_instance( 'CP_Importer' ) ) {
		// create new attachment from old logo
		$attachment_id = $importer->process_attachment( $cp_options->logo, 0 );
		if ( ! is_wp_error( $attachment_id ) && $attachment_attr = wp_get_attachment_image_src( $attachment_id, 'full' ) ) {
			$data = array();
			$data['url'] = esc_url_raw( $attachment_attr[0] );

			$header_image_data = (object) array(
				'attachment_id' => $attachment_id,
				'url'           => $data['url'],
				'thumbnail_url' => $data['url'],
				'height'        => $attachment_attr[2],
				'width'         => $attachment_attr[1],
			);

			update_post_meta( $attachment_id, '_wp_attachment_is_custom_header', get_stylesheet() );
			set_theme_mod( 'header_image', $data['url'] );
			set_theme_mod( 'header_image_data', $header_image_data );
			set_theme_mod( 'header_textcolor', 'blank' );
		}
	}

	// collect orders ids to update data
	if ( ! $order_ids = get_option( 'cp_upgrade_340_orders' ) ) {
		// get all orders ids
		$args = array(
			'post_type' => APPTHEMES_ORDER_PTYPE,
			'post_status' => 'any',
			'posts_per_page' => -1,
			'fields' => 'ids',
		);
		$orders = new WP_Query( $args );

		if ( isset( $orders->posts ) && is_array( $orders->posts ) ) {
			update_option( 'cp_upgrade_340_orders', $orders->posts );
		} else {
			update_option( 'cp_upgrade_340_orders', 'done' );
		}
	}


	update_option( 'cp_db_version', '2221' );
}


/**
 * Update orders to include urls, checkout type, and hash.
 *
 * @since 3.4
 */
function cp_upgrade_340_orders() {
	$order_ids = get_option( 'cp_upgrade_340_orders' );

	if ( ! $order_ids || $order_ids == 'done' ) {
		return;
	}

	$i = 0;
	$left_orders = $order_ids;

	foreach ( $order_ids as $key => $order_id ) {
		$i++;

		// all orders updated, quit the loop
		if ( empty( $left_orders ) ) {
			break;
		}

		// save current progress, and continue on next page load (memory and execution time have limits)
		if ( $i > 50 ) {
			echo scb_admin_notice( sprintf( __( 'Orders Update Progress: %d orders left.', APP_TD ), count( $left_orders ) ) );

			update_option( 'cp_upgrade_340_orders', $left_orders );
			return;
		}

		unset( $left_orders[ $key ] );

		// updated order check
		if ( $checkout_hash = get_post_meta( $order_id, 'checkout_hash', true ) ) {
			continue;
		}

		// retrieve order object
		$order = appthemes_get_order( $order_id );
		if ( ! $order ) {
			continue;
		}

		// determine checkout type and url
		if ( $item = $order->get_item( CP_ITEM_LISTING ) ) {
			$listing_orders_args = array(
				'connected_type' => APPTHEMES_ORDER_CONNECTION,
				'connected_query' => array( 'post_status' => 'any' ),
				'connected_to' => $item['post_id'],
				'post_status' => 'any',
				'fields' => 'ids',
				'nopaging' => true,
			);
			$listing_orders = new WP_Query( $listing_orders_args );

			if ( empty( $listing_orders->posts ) || $order_id == min( $listing_orders->posts ) ) {
				$checkout_type = 'create-listing';
				$checkout_url = get_permalink( CP_Add_New::get_id() );
			} else {
				$checkout_type = 'renew-listing';
				$checkout_url = add_query_arg( 'listing_renew', $item['post_id'], get_permalink( CP_Renew_Listing::get_id() ) );
			}
		} else if ( $item = $order->get_item( CP_ITEM_MEMBERSHIP ) ) {
			$checkout_type = 'membership-purchase';
			$checkout_url = get_permalink( CP_Membership::get_id() );
		} else {
			// unknown/invalid order
			continue;
		}

		// generate new checkout hash
		$hash = substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 20 );

		// if url set, get the hash
		if ( $complete_url = get_post_meta( $order_id, 'complete_url', true ) ) {
			$parsed_url = parse_url( $complete_url );
			parse_str( $parsed_url['query'], $url_args );
			if ( ! empty( $url_args['hash'] ) ) {
				$hash = $url_args['hash'];
			}
		}

		$complete_url = add_query_arg( array( 'step' => 'order-summary', 'hash' => $hash ), $checkout_url );
		$cancel_url = add_query_arg( array( 'step' => 'gateway-select', 'hash' => $hash ), $checkout_url );

		update_post_meta( $order_id, 'complete_url', $complete_url );
		update_post_meta( $order_id, 'cancel_url', $cancel_url );
		update_post_meta( $order_id, 'checkout_type', $checkout_type );
		update_post_meta( $order_id, 'checkout_hash', $hash );

	}

	// mark this upgrage as completed
	update_option( 'cp_upgrade_340_orders', 'done' );
}
add_action( 'admin_notices', 'cp_upgrade_340_orders' );

function cp_upgrade_353() {

	// convert all expire dates to format 'Y-m-d H:i:s'
	$args = array(
		'post_type' => APP_POST_TYPE,
		'post_status' => 'any',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'meta_query' => array(
			array(
				'key' => '_media_upgraded',
				'value' => 1,
				'compare' => '=',
			),
		),
	);

	$legacy = new WP_Query( $args );
	$order = 'ASC';

	if ( isset( $_GET['backup_353'] ) ) {
		$order = ( $_GET['backup_353'] ) ? 'DESC' : 'ASC';
	}

	if ( isset( $legacy->posts ) && is_array( $legacy->posts ) ) {
		foreach ( $legacy->posts as $post_id ) {
			cp_upgrade_353_resort_atts( $post_id, $order );
		}
	}

}

function cp_upgrade_353_resort_atts( $post_id, $order = 'ASC' ) {

	$attachments = get_posts( array( 'post_parent' => $post_id, 'post_type' => 'attachment', 'nopaging' => true, 'fields' => 'ids', 'order' => $order ) );
	if ( count( $attachments ) ) {
		update_post_meta( $post_id, '_app_media', $attachments );
	}

}
