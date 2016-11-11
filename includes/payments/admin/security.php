<?php
/**
 * Admin security.
 *
 * @package Components\Payments\Admin\Security
 */

/**
 * Class to secure Orders in back-end from user access.
 *
 * Security might be setted up in two levels:
 *   1. All users with 'edit_posts' capability can view own Orders list page and
 *      appropriate menu items, but only users with 'edit_others_posts'
 *      capability can edit orders in back-end.
 *   2. Only users with 'edit_others_posts' cap can view and edit orders in
 *      back-end.
 */
class APP_Payments_Admin_Security {

	/**
	 * Setup admin security.
	 *
	 * @param boolean $readable Whether users with 'edit_posts' capability can
	 *                          view orders list page in back-end.
	 */
	public function __construct( $readable = true ) {

		add_action( 'load-post-new.php', array( $this, '_protect_new_order_form' ) );
		add_action( 'load-post.php', array( $this, '_protect_edit_order_form' ) );
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 99, 4 );

		if ( $readable ) {
			add_action( 'pre_get_posts', array( $this, '_orders_query_set_only_author' ) );
		} else {
			add_action( 'load-edit.php', array( $this, '_protect_orders_index_page' ) );
		}
	}

	/**
	 * Disables 'post new order' page.
	 */
	public function _protect_new_order_form() {
		global $typenow;

		if ( APPTHEMES_ORDER_PTYPE !== $typenow ) {
			return;
		}

		$url = esc_url_raw( add_query_arg( 'post_type', APPTHEMES_ORDER_PTYPE, 'edit.php' ) );
		wp_redirect( $url );
		exit;
	}

	/**
	 * Redirects user from Edit Order form to single Order page unless user has
	 * appropriate permissions.
	 *
	 * @global string $typenow Current post type.
	 */
	public function _protect_edit_order_form() {
		global $typenow;

		if ( APPTHEMES_ORDER_PTYPE !== $typenow || current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		if ( isset( $_GET['post'] ) ) {
			$item_id = (int) $_GET['post'];
		} elseif ( isset( $_POST['post_ID'] ) ) {
			$item_id = (int) $_POST['post_ID'];
		} else {
			$item_id = 0;
		}

		if ( $item_id ) {

			$url = esc_url_raw( get_permalink( $item_id ) );

			wp_redirect( $url );
			exit;
		}

	}

	/**
	 * Protects orders index page from user access, unless user has appropriate
	 * permissions.
	 */
	public function _protect_orders_index_page() {
		global $typenow;

		if ( APPTHEMES_ORDER_PTYPE === $typenow && ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', APP_TD ) );
			exit;
		}
	}

	/**
	 * Filter a user's capabilities depending on specific context and/or privilege.
	 *
	 * @param array  $caps    Returns the user's actual capabilities.
	 * @param string $cap     Capability name.
	 * @param int    $user_id The user ID.
	 * @param array  $args    Adds the context to the cap. Typically the object ID.
	 *
	 * @return array Mapped caps
	 */
	public function map_meta_cap ( $caps, $cap, $user_id, $args ) {

		if ( 'edit_post' !== $cap && 'delete_post' !== $cap ) {
			return $caps;
		}

		$post = get_post( $args[0] );
		if ( empty( $post ) || APPTHEMES_ORDER_PTYPE !== $post->post_type ) {
			return $caps;
		}

		$post_type = get_post_type_object( $post->post_type );
		$caps = array_merge( $caps, array( $post_type->cap->edit_others_posts ) );

		return $caps;
	}

	/**
	 * Fixes orders counts by status.
	 *
	 * @global wpdb $wpdb WordPress Database Access Abstraction Object.
	 *
	 * @param object $counts An object containing the current post_type's post
	 *                       counts by status.
	 * @param string $type   Current post type.
	 *
	 * @return object Fixed counts object.
	 */
	public function _fix_count_orders( $counts, $type ) {
		global $wpdb;

		$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s";
		$query .= $wpdb->prepare( ' AND post_author = %d', get_current_user_id() );
		$query .= ' GROUP BY post_status';

		$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );
		$counts = array_fill_keys( get_post_stati(), 0 );

		foreach ( $results as $row ) {
			$counts[ $row['post_status'] ] = $row['num_posts'];
		}

		return (object) $counts;
	}

	/**
	 * Filters out query to retrieve orders created by current user unless user has
	 * appropriate permissions.
	 *
	 * @global WP_User $current_user Current user.
	 * @param WP_Query $wp_query Current query.
	 */
	public function _orders_query_set_only_author( $wp_query ) {
		global $current_user;

		$current_type = get_query_var( 'post_type' ) ? get_query_var( 'post_type' ) : '';

		if ( is_admin() && ! current_user_can( 'edit_others_posts' ) && APPTHEMES_ORDER_PTYPE === $current_type ) {
			$wp_query->set( 'author', $current_user->ID );
			add_filter( 'wp_count_posts', array( $this, '_fix_count_orders' ), 10, 2 );
		}
	}
}
