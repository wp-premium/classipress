<?php
/**
 * ClassiPress core theme functions.
 *
 * @package ClassiPress\Functions
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */


/**
 * Displays the login message in the header.
 *
 * @return void
 */
if ( ! function_exists( 'cp_login_head' ) ) {
	function cp_login_head() {

		if ( is_user_logged_in() ) :
			$current_user = wp_get_current_user();
			$logout_url = cp_logout_url();
			?>
			<?php _e( 'Welcome,', APP_TD ); ?> <strong><?php echo $current_user->display_name; ?></strong> [ <a href="<?php echo esc_url( CP_DASHBOARD_URL ); ?>"><?php _e( 'My Dashboard', APP_TD ); ?></a> | <a href="<?php echo esc_url( $logout_url ); ?>"><?php _e( 'Log out', APP_TD ); ?></a> ]&nbsp;
		<?php else : ?>

			<?php _e( 'Welcome,', APP_TD ); ?> <strong><?php _e( 'visitor!', APP_TD ); ?></strong> [

			<?php if ( get_option('users_can_register') ): ?>
				<a href="<?php echo esc_url( appthemes_get_registration_url() ); ?>"><?php _e( 'Register', APP_TD ); ?></a> |
			<?php endif; ?>

			<a href="<?php echo wp_login_url(); ?>"><?php _e( 'Login', APP_TD ); ?></a> ]&nbsp;

		<?php endif;

	}
}


/**
 * Returns user name depend of account type.
 *
 * @param object $user (optional)
 *
 * @return string
 */
function cp_get_user_name( $user = false ) {

	if ( ! $user && is_user_logged_in() ) {
		$user = wp_get_current_user();
	} else if ( is_numeric( $user ) ) {
		$user = get_userdata( $user );
	}

	if ( is_object( $user ) ) {
		return $user->display_name;
	} else {
		return false;
	}
}


/**
 * Returns logout url depend of login type.
 *
 * @patam string $url (optional)
 *
 * @return string
 */
function cp_logout_url( $url = '' ) {

	if ( ! $url ) {
		$url = home_url();
	}

	if ( is_user_logged_in() ) {
		return wp_logout_url( $url );
	} else {
		return false;
	}
}


/**
 * Corrects logout url in admin bar.
 *
 * @return void
 */
function cp_admin_bar_render() {
	global $wp_admin_bar;

	if ( is_user_logged_in() ) {
		$wp_admin_bar->remove_menu( 'logout' );
		$wp_admin_bar->add_menu( array(
			'parent' => 'user-actions',
			'id'     => 'logout',
			'title'  => __( 'Log out', APP_TD ),
			'href'   => cp_logout_url(),
		) );
	}

}
add_action( 'wp_before_admin_bar_render', 'cp_admin_bar_render' );


/**
 * Returns formatted price based on user settings, used for display prices.
 *
 * @param float $price
 *
 * @return string
 */
function cp_price_format( $price ) {
	global $cp_options;

	if ( is_numeric( $price ) ) {
		$decimals = ( $cp_options->hide_decimals || $price == 0 ) ? 0 : 2;
		$decimal_separator = $cp_options->decimal_separator;
		$thousands_separator = $cp_options->thousands_separator;

		$price = number_format( $price, $decimals, $decimal_separator, $thousands_separator );
	}

	return $price;
}


/**
 * Displays passed price based on user defined format and currency symbol.
 *
 * @param float $price
 * @param string $price_type (optional)
 * @param bool $echo (optional)
 *
 * @return string
 */
function cp_display_price( $price, $price_type = '', $echo = true ) {

	$price = cp_price_format( $price );
	$price = cp_pos_currency( $price, $price_type );

	if ( $echo ) {
		echo $price;
	} else {
		return $price;
	}
}


/**
 * Displays ad price and position the currency symbol.
 *
 * @param int $post_id
 * @param string $meta_field
 *
 * @return string
 */
if ( ! function_exists( 'cp_get_price' ) ) {
	function cp_get_price( $post_id, $meta_field ) {
		global $cp_options;

		if ( get_post_meta( $post_id, $meta_field, true ) ) {

			$price_out = get_post_meta( $post_id, $meta_field, true );
			$price_out = cp_price_format( $price_out );
			$price_out = cp_pos_currency( $price_out, 'ad' );

		} else {
			if ( $cp_options->force_zeroprice ) {
				$price_out = cp_pos_currency( 0, 'ad' );
			} else {
				$price_out = '&nbsp;';
			}
		}

		echo $price_out;
	}
}


/**
 * Position the currency symbol and return it with the price.
 *
 * @param float $price_out
 * @param string $price_type (optional)
 *
 * @return string
 */
function cp_pos_currency( $price_out, $price_type = '' ) {
	global $post, $cp_options;

	$price = $price_out;

	if ( $price_type == 'ad' ) {
		$curr_symbol = $cp_options->curr_symbol;
	} else {
		$curr_symbol = $price_type;
	}

	// if ad have custom currency, display it instead of default one
	if ( $price_type == 'ad' && isset( $post ) && is_object( $post ) ) {
		$custom_curr = get_post_meta( $post->ID, 'cp_currency', true );
		if ( ! empty( $custom_curr ) ) {
			$curr_symbol = $custom_curr;
		}
	}

	// possition the currency symbol
	if ( current_theme_supports( 'app-price-format' ) ) {
		$price_out = _appthemes_format_display_price( $price_out, $curr_symbol, $cp_options->currency_position );
	} else {
		$price_out = $price_out . '&nbsp;' . $curr_symbol;
	}

	return apply_filters( 'cp_currency_position', $price_out, $price, $curr_symbol, $price_type );
}

/**
 * Returns the taxonomy terms list.
 *
 * @param int $id (optional)
 * @param string $taxonomy
 * @param string $before (optional)
 * @param string $sep (optional)
 * @param string $after (optional)
 *
 * @return string
 */
function cp_get_the_term_list( $id = 0, $taxonomy, $before = '', $sep = '', $after = '' ) {
	$terms = get_the_terms( $id, $taxonomy );

	if ( is_wp_error( $terms ) ) {
		return $terms;
	}

	if ( empty( $terms ) ) {
		return false;
	}

	foreach ( $terms as $term ) {
		$link = get_term_link( $term, $taxonomy );
		if ( is_wp_error( $link ) ) {
			return $link;
		}
		$term_links[] = $term->name . ', ';
	}

	$term_links = apply_filters( "term_links-$taxonomy", $term_links );

	return $before . join( $sep, $term_links ) . $after;
}


/**
 * Changes ad to draft if it's expired.
 *
 * @param int $post_id
 *
 * @return bool
 */
function cp_has_ad_expired( $post_id ) {
	$expire_time = strtotime( get_post_meta( $post_id, 'cp_sys_expire_date', true ) );

	// if current date is past the expires date, change post status to draft
	if ( current_time( 'timestamp' ) > $expire_time ) {
		$my_post = array();
		$my_post['ID'] = $post_id;
		$my_post['post_status'] = 'draft';
		wp_update_post( $my_post );

		return true;
	}

	return false;
}


/**
 * Checks if ad listing is expired.
 *
 * @param int $listing_id (optional)
 *
 * @return bool
 */
function cp_is_listing_expired( $listing_id = 0 ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();
	$listing = get_post( $listing_id );

	// listing awaiting moderation or payment
	if ( $listing->post_status == 'pending' ) {
		return false;
	}

	// check expiration date
	$expire_time = strtotime( get_post_meta( $listing_id, 'cp_sys_expire_date', true ) );
	if ( $expire_time > current_time( 'timestamp' ) ) {
		return false;
	}

	return true;
}


/**
 * Determines what the ad listing post status should be.
 *
 * @param array $advals
 *
 * @return string
 */
if ( ! function_exists( 'cp_set_post_status' ) ) {
	function cp_set_post_status( $advals ) {
		global $cp_options;

		if ( $cp_options->moderate_ads ) {
			return 'pending';
		}

		if ( cp_payments_is_enabled() ) {
			return 'pending';
		}

		return 'publish';
	}
}


/**
 * Prints an redirect button and javascript.
 *
 * @param string $url
 * @param string $message (optional)
 *
 * @return void
 */
function cp_js_redirect( $url, $message = '' ) {
	if ( empty( $message ) ) {
		$message = __( 'Continue', APP_TD );
	}

	echo html( 'a', array( 'href' => $url ), $message );
	echo html( 'script', 'location.href="' . $url . '"' );
}


/**
 * Shows how much time is left before the ad expires.
 *
 * @param string $date_time
 *
 * @return string
 */
function cp_timeleft( $date_time ) {
	if ( is_string( $date_time ) ) {
		$date_time = strtotime( $date_time );
	}

	$timeLeft = $date_time - current_time( 'timestamp' );

	$days_label = __( 'days', APP_TD );
	$day_label = __( 'day', APP_TD );
	$hours_label = __( 'hours', APP_TD );
	$hour_label = __( 'hour', APP_TD );
	$mins_label = __( 'mins', APP_TD );
	$min_label = __( 'min', APP_TD );
	$secs_label = __( 'secs', APP_TD );
	$r_label = __( 'remaining', APP_TD );
	$expired_label = __( 'This ad has expired', APP_TD );

	if ( $timeLeft > 0 ) {
		$days = floor( $timeLeft/60/60/24 );
		$hours = $timeLeft/60/60%24;
		$mins = $timeLeft/60%60;
		$secs = $timeLeft%60;

		if ( $days == 01 ) { $d_label = $day_label; } else { $d_label = $days_label; }
		if ( $hours == 01 ) { $h_label = $hour_label; } else { $h_label = $hours_label; }
		if ( $mins == 01 ) { $m_label = $min_label; } else { $m_label = $mins_label; }

		if ( $days ) {
			$theText = $days . " " . $d_label;
			if ( $hours ) { $theText .= ", " .$hours . " " . $h_label; }
		} elseif ( $hours ) {
			$theText = $hours . " " . $h_label;
			if ( $mins ) { $theText .= ", " .$mins . " " . $m_label; }
		} elseif ( $mins ) {
			$theText = $mins . " " . $m_label;
			if ( $secs ) { $theText .= ", " .$secs . " " . $secs_label; }
		} elseif ( $secs ) {
			$theText = $secs . " " . $secs_label;
		}
	} else {
		$theText = $expired_label;
	}

	return $theText;
}


/**
 * Displays breadcrumb on the top of pages.
 *
 * @return void
 */
function cp_breadcrumb() {

	if ( is_front_page() && ! is_paged() ) {
		return;
	}

	echo '<div id="crumbs">';

	breadcrumb_trail( array(
		'separator' => '&raquo;',
		'show_browse' => false,
	) );

	echo '</div>';
}


/**
 * Returns most popular ads for use in loop.
 *
 * @return object|bool Boolean false on failure
 */
function cp_get_popular_ads() {
	global $cp_has_next_page;

	$popular = new CP_Popular_Posts_Query();

	if ( ! $popular->have_posts() ) {
		return false;
	}

	$cp_has_next_page = ( $popular->max_num_pages > 1 );

	return $popular;
}


/**
 * Query popular ads & posts.
 */
class CP_Popular_Posts_Query extends WP_Query {

	public $stats;
	public $stats_table;
	public $today_date;

	function __construct( $args = array(), $stats = 'total' ) {
		global $wpdb;

		$this->stats = $stats;
		$this->stats_table = ( $stats == 'today' ) ? $wpdb->cp_ad_pop_daily : $wpdb->cp_ad_pop_total;
		$this->today_date = date( 'Y-m-d', current_time( 'timestamp' ) );

		$defaults = array(
			'post_type' => APP_POST_TYPE,
			'post_status' => 'publish',
			'paged' => ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1,
			'suppress_filters' => false,
		);
		$args = wp_parse_args( $args, $defaults );

		$args = apply_filters( 'cp_popular_posts_args', $args );

		add_filter( 'posts_join', array( $this, 'posts_join' ) );
		add_filter( 'posts_where', array( $this, 'posts_where' ) );
		add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );

		parent::__construct( $args );

		// remove filters to don't affect any other queries
		remove_filter( 'posts_join', array( $this, 'posts_join' ) );
		remove_filter( 'posts_where', array( $this, 'posts_where' ) );
		remove_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
	}

	function posts_join( $sql ) {
		global $wpdb;
		return $sql . " INNER JOIN $this->stats_table ON ($wpdb->posts.ID = $this->stats_table.postnum) ";
	}

	function posts_where( $sql ) {
		global $wpdb;
		$sql = $sql . " AND $this->stats_table.postcount > 0 ";

		if ( $this->stats == 'today' ) {
			$sql .= " AND $this->stats_table.time = '$this->today_date' ";
		}

		if ( $this->get( 'date_start' ) ) {
			$sql .= " AND $wpdb->posts.post_date > '" . $this->get( 'date_start' ) . "' ";
		}

		return $sql;
	}

	function posts_orderby( $sql ) {
		return "$this->stats_table.postcount DESC";
	}

}


/**
 * Returns ads which are marked as featured for slider.
 *
 * @return object|bool Boolean false on failure
 */
function cp_get_featured_slider_ads() {

	$args = array(
		'post_type' => APP_POST_TYPE,
		'post_status' => 'publish',
		'post__in' => get_option( 'sticky_posts' ),
		'posts_per_page' => 15,
		'orderby' => 'rand',
		'no_found_rows' => true,
		'suppress_filters' => false,
	);

	$args = apply_filters( 'cp_featured_slider_args', $args );

	$featured = new WP_Query( $args );

	if ( ! $featured->have_posts() ) {
		return false;
	}

	return apply_filters( 'cp_featured_slider_ads', $featured );
}


/**
 * Returns user ads for his dashboard.
 *
 * @param array $args (optional)
 *
 * @return object|bool Boolean false on failure
 */
function cp_get_user_dashboard_listings( $args = array() ) {
	$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

	$defaults = array(
		'post_type'        => APP_POST_TYPE,
		'post_status'      => 'publish, pending, draft',
		'author'           => get_current_user_id(),
		'paged'            => $paged,
		'suppress_filters' => false,
	);
	$args = wp_parse_args( $args, $defaults );

	$args = apply_filters( 'cp_user_dashboard_listings_args', $args );

	$listings = new WP_Query( $args );

	if ( ! $listings->have_posts() ) {
		return false;
	}

	return apply_filters( 'cp_user_dashboard_listings', $listings );
}


/**
 * Returns user orders for his dashboard.
 *
 * @since 3.5
 *
 * @param array $args (optional)
 *
 * @return object|bool Boolean false on failure
 */
function cp_get_user_dashboard_orders( $args = array() ) {
	$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

	$defaults = array(
		'post_type'        => APPTHEMES_ORDER_PTYPE,
		'post_status'      => 'any',
		'author'           => get_current_user_id(),
		'paged'            => $paged,
		'suppress_filters' => false,
	);
	$args = wp_parse_args( $args, $defaults );

	if ( get_query_var('order_status') ) {
		$args['post_status'] = get_query_var('order_status');
	}

	$args = apply_filters( 'cp_user_dashboard_orders_args', $args );

	$orders = new WP_Query( $args );

	if ( ! $orders->have_posts() ) {
		return false;
	}

	return apply_filters( 'cp_user_dashboard_orders', $orders );
}


/**
 * Deletes ad listing together with associated attachments.
 *
 * @param int $post_id
 *
 * @return bool
 */
function cp_delete_ad_listing( $post_id ) {

	// delete post and it's revisions, comments, meta
	if ( wp_delete_post( $post_id, true ) ) {
		return true;
	} else {
		return false;
	}

}


/**
 * Checks if a membership is required to post into given category ID.
 *
 * @param int $cat_id
 *
 * @return mixed
 */
function get_membership_requirement( $cat_id ) {
	global $cp_options;

	if ( $cp_options->required_membership_type == 'all' ) {
		// if all posts require "required" memberships
		return 'all';
	} else if ( $cp_options->required_membership_type == 'category' ) {
		// if post requirements are based on category specific requirements
		$required_categories = $cp_options->required_categories;
		if ( ! empty( $required_categories[ $cat_id ] ) ) {
			return $cat_id;
		}
	}

	// no requirements active
	return false;
}


/**
 * Checks and redirects to membership purchase page if not meet membership requirement.
 *
 * @return void
 */
function cp_redirect_membership() {
	global $current_user, $cp_options;

	$current_requirement = false;

	if ( ! $cp_options->enable_membership_packs ) {
		return;
	}

	if ( isset( $_POST['cat'] ) ) {
		$current_requirement = get_membership_requirement( $_POST['cat'] );
	}

	if ( $cp_options->required_membership_type == 'all' ) {
		$current_requirement = 'all';
	}

	if ( ! $current_requirement ) {
		return;
	}

	$current_membership = cp_get_user_membership_package( $current_user->ID );

	if ( ! $current_membership || ! $current_membership->pack_satisfies_required ) {
		$redirect_url = add_query_arg( array( 'membership' => 'required', 'cat' => $current_requirement ), CP_MEMBERSHIP_PURCHASE_URL );
		wp_redirect( $redirect_url );
		exit;
	}

}


/**
 * Updates geo location in db.
 *
 * @param int $post_id
 * @param string $cat
 * @param float $lat
 * @param float $lng
 *
 * @return bool
 */
function cp_update_geocode( $post_id, $cat, $lat, $lng ) {
	global $wpdb;

	if ( ! empty( $cat ) ) {
		_deprecated_argument( __FUNCTION__, '3.3.2' );
	}

	if ( ! $lat || ! $lng || ! $post_id ) {
		return false;
	}

	$post_id = absint( $post_id );

	if ( ! cp_get_geocode( $post_id ) ) {
		return cp_add_geocode( $post_id, '', $lat, $lng );
	}

	$lat = floatval( $lat );
	$lng = floatval( $lng );

	$wpdb->update(
		$wpdb->cp_ad_geocodes,
		array(
			'lat' => $lat,
			'lng' => $lng
		),
		array(
			'post_id' => $post_id,
		)
	);

	return true;
}


/**
 * Adds geo location to db.
 *
 * @param int $post_id
 * @param string $cat
 * @param float $lat
 * @param float $lng
 *
 * @return bool
 */
function cp_add_geocode( $post_id, $cat, $lat, $lng ) {
	global $wpdb;

	if ( ! empty( $cat ) ) {
		_deprecated_argument( __FUNCTION__, '3.3.2' );
	}

	$post_id = intval( $post_id );
	$lat = floatval( $lat );
	$lng = floatval( $lng );

	if ( cp_get_geocode( $post_id ) ) {
		return false;
	}

	$wpdb->insert( $wpdb->cp_ad_geocodes, array(
		'post_id' => $post_id,
		'lat' => $lat,
		'lng' => $lng
	) );

	return true;
}


/**
 * Deletes geo location from db.
 *
 * @param int $post_id
 * @param string $cat (optional)
 *
 * @return int
 */
function cp_delete_geocode( $post_id, $cat = '' ) {
	global $wpdb;

	if ( ! empty( $cat ) ) {
		_deprecated_argument( __FUNCTION__, '3.3.2' );
	}

	return $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->cp_ad_geocodes WHERE post_id = %d", $post_id ) );
}


/**
 * Returns geo location from db.
 *
 * @param int $post_id
 * @param string $cat (optional)
 *
 * @return array|bool Boolean false on failure
 */
function cp_get_geocode( $post_id, $cat = '' ) {
	global $wpdb;

	if ( ! empty( $cat ) ) {
		_deprecated_argument( __FUNCTION__, '3.3.2' );
	}

	$row = $wpdb->get_row( $wpdb->prepare( "SELECT lat, lng FROM $wpdb->cp_ad_geocodes WHERE post_id = %d LIMIT 1", $post_id ) );

	if ( is_object( $row ) ) {
		return array( 'lat' => $row->lat, 'lng' => $row->lng );
	} else {
		return false;
	}
}


/**
 * Retrieves geo location from google and update stored record.
 *
 * @param int $meta_id
 * @param int $post_id
 * @param string $meta_key
 * @param string $meta_value
 *
 * @return void
 */
function cp_do_update_geocode( $meta_id, $post_id, $meta_key, $meta_value ) {
	global $wpdb, $cp_options;

	if ( ! in_array( $meta_key, array( 'cp_city', 'cp_country', 'cp_state', 'cp_street', 'cp_zipcode' ) ) ) {
		return;
	}

	// remove old geocode result
	cp_delete_geocode( $post_id );

	$address = '';
	$fields = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key IN ('cp_street','cp_city','cp_state','cp_zipcode','cp_country')", $post_id ), OBJECT_K );
	foreach ( $fields as $field ) {
		if ( ! empty( $field->meta_value ) ) {
			$address .= $field->meta_value . ', ';
		}
	}

	$address = rtrim( $address, ', ' );
	if ( empty( $address ) ) {
		return;
	}


	$api_key = $cp_options->api_key;
	$region = $cp_options->gmaps_region;
	$address = urlencode( $address );
	$geocode_url = add_query_arg( array( 'address' => $address, 'region' => $region, 'key' => $api_key ), 'https://maps.googleapis.com/maps/api/geocode/json' );
	$geocode_response = wp_remote_get( $geocode_url );
	$geocode_body = wp_remote_retrieve_body( $geocode_response );
	$geocode = json_decode( $geocode_body );
	if ( 'OK' == $geocode->status ) {
		cp_update_geocode( $post_id, '', $geocode->results[0]->geometry->location->lat, $geocode->results[0]->geometry->location->lng );
	}

}
add_action( 'added_post_meta', 'cp_do_update_geocode', 10, 4 );
add_action( 'updated_post_meta', 'cp_do_update_geocode', 10, 4 );


/**
 * Displays edit ad link. Use only in loop.
 *
 * @return void
 */
function cp_edit_ad_link() {
	global $post, $current_user, $cp_options;

	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) ) {
		edit_post_link( __( 'Edit Post', APP_TD ), '<p class="dashicons-before edit">', '</p>', $post->ID );
	} elseif( $cp_options->ad_edit && $post->post_author == $current_user->ID ) {
		$edit_link = add_query_arg( 'listing_edit', $post->ID, CP_EDIT_URL );
		echo '<p class="dashicons-before edit"><a class="post-edit-link" href="' . $edit_link . '" title="' . __( 'Edit Ad', APP_TD ) . '">' . __( 'Edit Ad', APP_TD ) . '</a></p>';
	}

}


/**
 * Returns the content excerpt.
 *
 * @uses apply_filters() Calls 'cp_get_content_preview'
 *
 * @return string
 */
function cp_get_content_preview() {
	return apply_filters( 'cp_get_content_preview', get_the_excerpt(), 55 );
}


/**
 * Loads all page templates, setups cache, limits db queries.
 *
 * @return void
 */
function cp_load_all_page_templates() {
	$pages = get_posts( array(
		'post_type' => 'page',
		'meta_key' => '_wp_page_template',
		'posts_per_page' => -1,
		'no_found_rows' => true,
	) );

}


/**
 * Returns localized status if available.
 *
 * @param string $status
 *
 * @return string
 */
function cp_get_status_i18n( $status ) {
	$statuses = array(
		'active' => __( 'Active', APP_TD ),
		'active_membership' => __( 'Active', APP_TD ),
		'chargeback' => __( 'Chargeback', APP_TD ),
		'completed' => __( 'Completed', APP_TD ),
		'denied' => __( 'Denied', APP_TD ),
		'draft' => __( 'Draft', APP_TD ),
		'ended' => __( 'Ended', APP_TD ),
		'expired' => __( 'Expired', APP_TD ),
		'failed' => __( 'Failed', APP_TD ),
		'future' => __( 'Scheduled', APP_TD ),
		'inactive' => __( 'Inactive', APP_TD ),
		'inactive_membership' => __( 'Inactive', APP_TD ),
		'live' => __( 'Live', APP_TD ),
		'live_expired' => __( 'Live-Expired', APP_TD ),
		'offline' => __( 'Offline', APP_TD ),
		'pending' => __( 'Pending', APP_TD ),
		'pending_moderation' => __( 'Awaiting approval', APP_TD ),
		'pending_payment' => __( 'Awaiting payment', APP_TD ),
		'private' => __( 'Private', APP_TD ),
		'publish' => __( 'Published', APP_TD ),
		'refunded' => __( 'Refunded', APP_TD ),
		'reversed' => __( 'Reversed', APP_TD ),
		'trash' => __( 'Trash', APP_TD ),
		'unverified' => __( 'Unverified', APP_TD ),
		'verified' => __( 'Verified', APP_TD ),
		'voided' => __( 'Voided', APP_TD )
	);

	$status = strtolower( $status );

	if ( array_key_exists( $status, $statuses ) ) {
		return $statuses[ $status ];
	} else {
		return ucfirst( $status );
	}
}


/**
 * Returns ad listing status name.
 *
 * @param int $listing_id (optional)
 *
 * @return string
 */
function cp_get_listing_status_name( $listing_id = 0 ) {
	global $cp_options;

	$listing_id = $listing_id ? $listing_id : get_the_ID();
	$listing = get_post( $listing_id );

	if ( cp_is_listing_expired( $listing->ID ) ) {
		if ( $listing->post_status == 'publish' && ! $cp_options->post_prune ) {
			return 'live_expired';
		} else {
			return 'ended';
		}
	} else if ( $listing->post_status == 'draft' ) {
		return 'offline';
	} else if ( $listing->post_status == 'pending' ) {
		if ( cp_have_pending_payment( $listing->ID ) ) {
			return 'pending_payment';
		} else {
			return 'pending_moderation';
		}
	}

	return 'live';
}


/**
 * Returns user dashboard ad listing actions.
 *
 * @param int $listing_id (optional)
 *
 * @return string
 */
function cp_get_dashboard_listing_actions( $listing_id = 0 ) {
	global $cp_options;

	$actions = array();
	$listing_id = $listing_id ? $listing_id : get_the_ID();
	$listing = get_post( $listing_id );
	$listing_status = cp_get_listing_status_name( $listing_id );

	// edit button
	if ( $cp_options->ad_edit ) {
		$edit_attr = array(
			'title' => __( 'Edit Ad', APP_TD ),
			'href' => add_query_arg( array( 'listing_edit' => $listing->ID ), CP_EDIT_URL ),
		);

		if ( in_array( $listing_status, array( 'live', 'offline' ) ) ) {
			$actions['edit'] = $edit_attr;
		}
		if ( $cp_options->moderate_edited_ads && in_array( $listing_status, array( 'pending_moderation', 'pending_payment' ) ) ) {
			$actions['edit'] = $edit_attr;
		}
	}

	// delete button
	$actions['delete'] = array(
		'title' => __( 'Delete Ad', APP_TD ),
		'href' => add_query_arg( array( 'aid' => $listing->ID, 'action' => 'delete' ), CP_DASHBOARD_URL ),
		'onclick' => 'return confirmBeforeDeleteAd();',
	);

	// pause button
	if ( $listing_status == 'live' ) {
		$actions['pause'] = array(
			'title' => __( 'Pause Ad', APP_TD ),
			'href' => add_query_arg( array( 'aid' => $listing->ID, 'action' => 'pause' ), CP_DASHBOARD_URL ),
		);
	}

	// restart button
	if ( $listing_status == 'offline' ) {
		$actions['restart'] = array(
			'title' => __( 'Restart ad', APP_TD ),
			'href' => add_query_arg( array( 'aid' => $listing->ID, 'action' => 'restart' ), CP_DASHBOARD_URL ),
		);
	}

	// set/unset sold links
	if ( in_array( $listing_status, array( 'live', 'offline' ) ) ) {
		$sold = get_post_meta( $listing->ID, 'cp_ad_sold', true );
		if ( $sold != 'yes' ) {
			// set sold
			$actions['set_sold'] = array(
				'title' => __( 'Mark Sold', APP_TD ),
				'href' => add_query_arg( array( 'aid' => $listing->ID, 'action' => 'setSold' ), CP_DASHBOARD_URL ),
			);
		} else {
			// unset sold
			$actions['unset_sold'] = array(
				'title' => __( 'Unmark Sold', APP_TD ),
				'href' => add_query_arg( array( 'aid' => $listing->ID, 'action' => 'unsetSold' ), CP_DASHBOARD_URL ),
			);
		}
	}

	// relist link
	if ( $cp_options->allow_relist && in_array( $listing_status, array( 'ended', 'live_expired' ) ) ) {
		$actions['relist'] = array(
			'title' => __( 'Relist Ad', APP_TD ),
			'href' => add_query_arg( array( 'listing_renew' => $listing->ID ), get_permalink( CP_Renew_Listing::get_id() ) ),
		);
	}

	// payment links
	if ( $listing_status == 'pending_payment' ) {
		$order = appthemes_get_order_connected_to( $listing->ID );
		// pay order
		$actions['pay_order'] = array(
			'title' => __( 'Pay now', APP_TD ),
			'href' => appthemes_get_order_url( $order->get_id() ),
		);
		if ( $order->get_gateway() ) {
			// reset gateway
			$actions['reset_gateway'] = array(
				'title' => __( 'Reset Gateway', APP_TD ),
				'href' => get_the_order_cancel_url( $order->get_id() ),
			);
		}
	}

	return apply_filters( 'cp_dashboard_listing_actions', $actions, $listing );
}


/**
 * Displays user dashboard ad listing actions.
 *
 * @param int $listing_id (optional)
 *
 * @return string
 */
function cp_dashboard_listing_actions( $listing_id = 0 ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();

	$actions = cp_get_dashboard_listing_actions( $listing_id );
	$li = '';

	$iconized = array( 'edit', 'delete', 'pause', 'restart' );

	foreach ( $actions as $action => $attr ) {

		$description = $attr['title'];

		if ( in_array( $action, $iconized ) ) {
			$attr['class'] = $action . ' dashicons-before' ;
			$description = '';
		}

		$a = html( 'a', $attr, $description );
		$li .= html( 'li', array( 'class' => $action ), $a );
	}

	$ul = html( 'ul', array( 'id' => 'listing-actions-' . $listing_id, 'class' => 'listing-actions' ), $li );
	echo $ul;
}


/**
 * Helper function to display element classes and styles.
 *
 * @param array $tags
 *
 * @return void
 */
function cp_display_style( $tags ) {
	global $cp_options;

	$styles = array();

	foreach ( (array) $tags as $tag ) {
		switch ( $tag ) {
			case 'search_field_width':
				if ( ! empty( $cp_options->search_field_width ) ) {
					$styles[] = 'width:' . $cp_options->search_field_width . ';';
				}
				break;
			case 'ad_single_images':
				if ( ! $cp_options->ad_images ) {
					$styles[] = 'no-images';
				}
				break;
			case 'ad_images':
				$styles[] = ( $cp_options->ad_images ) ? 'post-right' : 'post-right-no-img';
				break;
			case 'ad_class':
				if ( ! empty( $cp_options->ad_right_class ) ) {
					$styles[] = $cp_options->ad_right_class;
				}
				break;
			case 'dir_cols':
				$styles[] = ( $cp_options->cat_dir_cols == 2 ) ? 'twoCol' : 'Col';
				break;
			case 'featured':
				if ( is_sticky() ) {
					$styles[] = 'featured';
				}
				break;
		}
	}

	$styles = apply_filters( 'cp_display_style', $styles, $tags );
	echo implode( ' ', $styles );
}


/**
 * Helper function to display messages.
 *
 * @param string $tag
 *
 * @return void
 */
function cp_display_message( $tag ) {
	global $cp_options;

	switch ( $tag ) {
		case 'terms_of_use':
			$message = $cp_options->ads_tou_msg;
			break;
		case 'welcome':
			$message = $cp_options->ads_welcome_msg;
			break;
		case 'ads_form_help':
			$message = $cp_options->ads_form_msg;
			break;
		case 'membership_form_help':
			$message = $cp_options->membership_form_msg;
			break;
		default:
			$message = '';
			break;
	}

	$message = apply_filters( 'the_content', $message );

	echo apply_filters( 'cp_display_message', $message, $tag );
}


/**
 * Displays website current time and timezone in footer.
 * @since 3.3
 *
 * @return void
 */
function cp_website_current_time() {
	global $cp_options;

	if ( ! $cp_options->display_website_time ) {
		return;
	}

	$timezone = get_option( 'gmt_offset' );
	$time = date_i18n( get_option( 'time_format' ) );
	$message = sprintf( __( 'All times are GMT %1$s. The time now is %2$s.', APP_TD ), $timezone, $time );
	$message = html( 'p', $message );
	echo html( 'div', array( 'class' => 'website-time' ), $message );
}


/**
 * Generates unique ID for ads and memberships
 * @since 3.3.1
 *
 * @param string $type
 *
 * @return string
 */
function cp_generate_id( $type = 'ad' ) {
	$id = uniqid( rand( 10, 1000 ), false );

	if ( $type == 'ad' ) {
		if ( cp_get_listing_by_ref( $id ) ) {
			return cp_generate_id();
		}
	}

	return $id;
}


/**
 * Retrieves listing data by given reference ID.
 * @since 3.3.1
 *
 * @param string $reference_id An listing reference ID
 *
 * @return object|bool A listing object, boolean False otherwise
 */
function cp_get_listing_by_ref( $reference_id ) {

	if ( empty( $reference_id ) || ! is_string( $reference_id ) ) {
		return false;
	}

	$reference_id = appthemes_numbers_letters_only( $reference_id );

	$listing_q = new WP_Query( array(
		'post_type' => APP_POST_TYPE,
		'post_status' => 'any',
		'meta_key' => 'cp_sys_ad_conf_id',
		'meta_value' => $reference_id,
		'posts_per_page' => 1,
		'suppress_filters' => true,
		'no_found_rows' => true,
	) );

	if ( empty( $listing_q->posts ) ) {
		return false;
	}

	return $listing_q->posts[0];
}


/**
 * Outputs the HTML for a single instance of the editor.
 * @since 3.3.3
 *
 * @param string $content The content of the editor.
 * @param array $args The textarea attributes.
 *
 * @return void
 */
function cp_editor( $content, $args = array() ) {

	$args = wp_parse_args( $args, array( 'class' => '', 'id' => 'post_content' ) );

	ob_start();
	wp_editor( ' ', $args['id'], cp_get_editor_settings() );
	$editor = ob_get_clean();

	$args['class'] .= ' wp-editor-area';
	$textarea_field = html( 'textarea', $args, apply_filters( 'the_editor_content', $content ) );

	// Note: Replace splitted into 2 steps as some strings might be trigered as regex
	$editor_tmp = preg_replace( '#<textarea[^>]*>(.*?)</textarea>#is', 'textarea_field', $editor );
	echo str_replace( 'textarea_field', $textarea_field, $editor_tmp );
}


/**
 * Returns an array of settings for WP Editor used on the frontend.
 * @since 3.3.3
 *
 * @return array An array of WP Editor settings.
 */
function cp_get_editor_settings() {
	$settings = array(
		'wpautop' => true,
		'media_buttons' => false,
		'teeny' => false,
		'dfw' => true,
		'tinymce' => array(
			'setup' => 'function(ed){ ed.onChange.add(function(ed, l){ed.save(); jQuery("#"+ed.id).valid();})}'
		),
		'quicktags' => array(
			'buttons' => 'strong,em,ul,ol,li,link,close',
		),
	);

	return $settings;
}


/**
 * Returns listing categories.
 *
 * @param int $listing_id (optional)
 *
 * @return array
 */
function cp_get_listing_categories( $listing_id = 0 ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();

	$_terms = get_the_terms( $listing_id, APP_TAX_CAT );

	if ( ! $_terms || is_wp_error( $_terms ) ) {
		return array();
	}

	// WordPress does not always key with the term_id, but thats what we want for the key.
	$terms = array();
	foreach ( $_terms as $_term ) {
		$terms[ $_term->term_id ] = $_term;
	}

	return $terms;
}


/**
 * Retrieves Listing object.
 *
 * @param int $listing_id The listing ID.
 * @param int $checkout_listing Optional. Is this a valid checkout listing or an auto draft.
 *
 * @return object
 */
function cp_get_listing_obj( $listing_id = 0, $checkout_listing = true ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();

	$listing = get_post( $listing_id );

	if ( ! $listing ) {
		return false;
	}

	if ( $checkout_listing ) {
		$categories = wp_get_post_terms( $listing->ID, APP_TAX_CAT );
	}
	$listing->category = ( ! empty( $categories ) ) ? $categories[0]->term_id : false;

	$form_id = cp_get_form_id( $listing->category );
	$form_fields = cp_get_custom_form_fields( $form_id );
	foreach ( $form_fields as $field ) {
		if ( in_array( $field->field_name, array( 'post_title', 'post_content' ) ) ) {
			continue;
		} else if ( $field->field_name == 'tags_input' ) {
			$listing->{$field->field_name} = rtrim( trim( cp_get_the_term_list( $listing->ID, APP_TAX_TAG ) ), ',' );
		} else {
			$is_single = ( $field->field_type != 'checkbox' );
			$listing->{$field->field_name} = get_post_meta( $listing->ID, $field->field_name, $is_single );
		}
	}

	// Clear the default 'Auto Draft' title
	if ( $listing->post_title == __( 'Auto Draft' ) ) {
		$listing->post_title = '';
	}

	return $listing;
}


/**
 * Outputs a listing of popular ads.
 *
 * @since 3.5
 */
function cp_output_popular_ads_listing() {
	global $cp_has_next_page;

	remove_action( 'appthemes_after_endwhile', 'cp_do_pagination' );

	$post_type_url = add_query_arg( array( 'paged' => 2 ), get_post_type_archive_link( APP_POST_TYPE ) );

	get_template_part( 'loop', 'popular' );

	if ( $cp_has_next_page ) :
		$popular_url = add_query_arg( array( 'sort' => 'popular' ), $post_type_url );

		cp_the_view_more_ads_link( $popular_url, 'popular' );
	endif;

	wp_reset_query();
}

/**
 * Outputs a listing of random ads.
 *
 * @since 3.5
 */
function cp_output_random_ads_listing() {
	global $wp_query;

	remove_action( 'appthemes_after_endwhile', 'cp_do_pagination' );

	$post_type_url = add_query_arg( array( 'paged' => 2 ), get_post_type_archive_link( APP_POST_TYPE ) );

	// show all random ads but make sure the sticky featured ads don't show up first
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

	query_posts( array( 'post_type' => APP_POST_TYPE, 'ignore_sticky_posts' => 1, 'post_status' => 'publish', 'paged' => $paged, 'orderby' => 'rand' ) );

	$total_pages = max( 1, absint( $wp_query->max_num_pages ) );

	get_template_part( 'loop', 'ad_listing' );

	if ( $total_pages > 1 ) {
		$random_url = add_query_arg( array( 'sort' => 'random' ), $post_type_url );

		cp_the_view_more_ads_link( $random_url, 'random' );
	}
}

/**
 * Outputs a listing of latest ads.
 *
 * @since 3.5.5
 */
function cp_output_latest_ads_listing() {
	global $wp_query;

	remove_action( 'appthemes_after_endwhile', 'cp_do_pagination' );
	$post_type_url = add_query_arg( array( 'paged' => 2 ), get_post_type_archive_link( APP_POST_TYPE ) );

	// show all ads but make sure the sticky featured ads don't show up first
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

	query_posts( array( 'post_type' => APP_POST_TYPE, 'ignore_sticky_posts' => 1, 'paged' => $paged ) );

	$total_pages = max( 1, absint( $wp_query->max_num_pages ) );

	get_template_part( 'loop', 'ad_listing' );

	if ( $total_pages > 1 ) {
		cp_the_view_more_ads_link( $post_type_url );
	}
}

/**
 * Retrieves an array of registered Ads Listing tabs and their parameters.
 *
 * @since 3.5.5
 *
 * @return array
 */
function cp_get_ads_listing_tabs() {

	$defaults = array(
		'latest' => array(
			'title'    => __( 'New Listings', APP_TD ),
			'callback' => 'cp_output_latest_ads_listing',
		),
		'popular' => array(
			'title' => __( 'Popular', APP_TD ),
			'callback' => 'cp_output_popular_ads_listing',
		),
		'random' => array(
			'title' => __( 'Random', APP_TD ),
			'callback' => 'cp_output_random_ads_listing',
		),
	);

	$tabs = apply_filters( 'cp_ads_listing_tabs', $defaults );

	return $tabs;
}

/**
 * Outputs the 'View More Ads' markup.
 *
 * @param string $url The 'View More Ads' URL.
 * @param string $content Optional. The content context for the link (random, popular, etc).
 * @param sting $text Optional. The 'View More Ads' text replacement.
 */
function cp_the_view_more_ads_link( $url, $content = 'default', $text = '' ) {

	$text = $text ? $text : __( 'View More Ads', APP_TD );

	ob_start();

	?><div class="paging"><a href="<?php echo esc_url( $url ); ?>"> <?php echo $text; ?> </a></div><?php

	echo apply_filters( 'cp_the_view_more_ads_link', ob_get_clean(), $url, $content, $text );
}


/**
 * Retrieves the '.min' suffix for CSS/JS files on a live site considering SCRIPT_DEBUG constant.
 *
 * @sinec 3.5
 */
function cp_get_enqueue_suffix() {
	return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
}


//setup function to stop function from failing if sm debug bar is not installed
//this allows for optional use of sm debug bar plugin
if ( ! function_exists( 'dbug' ) ) { function dbug( $args ) {} }
