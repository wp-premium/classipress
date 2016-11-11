<?php

/**
 * Base class for checkout views.
 */
class CP_Order extends APP_View {

	function init() {
		global $wp;

		$wp->add_query_var('checkout');
		$wp->add_query_var('bt_end');
	}

	function condition() {
		return (bool) get_query_var('checkout');
	}

	function parse_query( $wp_query ) {

		$checkout_type = get_query_var('checkout');

		// setup a new checkout after being redirected from a gateway page
		appthemes_setup_checkout( $checkout_type, $_SERVER['REQUEST_URI'] );

		$wp_query->set( 'checkout', get_query_var('checkout') );
	}

    function template_vars() {
    	global $cp_options;

		$order = get_order();

		if ( ! $order->get_gateway() ) {
			return;
		}

		$gateway = APP_Gateway_Registry::get_gateway( $order->get_gateway() );

        $template_vars = array(
			'gateway'    => $gateway->display_name('dropdown'),
        );

        if ( APP_Gateway_registry::is_gateway_enabled( 'bank-transfer' ) ) {
        	$template_vars['bt_message'] = $cp_options->gateways['bank-transfer']['message'];
        }

        return $template_vars;
    }

	function template_include( $template ) {

		$order = get_order();

		// point the progress bar to the final step if the order is complete.
		// by default, it shows the gateway process step (options/pay) when the order is processed by the gateway
		if ( $order && in_array( $order->get_status(), array( APPTHEMES_ORDER_PAID, APPTHEMES_ORDER_COMPLETED, APPTHEMES_ORDER_ACTIVATED ) ) ) {
			add_filter( 'appthemes_form_progress_current_step', array( $this, 'set_summary_step' ) );
		}

		return $template;
	}

	function set_summary_step( $step ) {
		return 'order-summary';
	}

}


/**
 * Form Step: Select Membership Package
 */
class CP_Membership_Form_Select extends APP_Checkout_Step {

	protected $errors;
	protected $posted_fields;

	public function __construct() {
		global $cp_options;

		if ( ! $cp_options->enable_membership_packs ) {
			return;
		}

		$this->errors = new WP_Error();

		parent::__construct( 'select-membership', array(
			'register_to' => array(
				'membership-purchase',
			)
		) );

	}

	/**
	 * Displays form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function display( $order, $checkout ) {

		the_post();

		if ( cp_payments_is_enabled( 'membership' ) ) {
			add_action( 'appthemes_notices', array( $this, 'display_extra_messages' ), 9 );

			appthemes_load_template( 'form-membership-packages.php', array(
				'action' => $checkout->get_checkout_type(),
				'packages' => cp_get_membership_packages(),
			) );
		} else {
			appthemes_load_template( 'form-membership-disabled.php' );
		}

	}

	/**
	 * Processing form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function process( $order, $checkout ) {

		if ( ! isset( $_POST['action'] ) || 'membership-purchase' !== $_POST['action'] ) {
			return;
		}

		check_admin_referer( $checkout->get_checkout_type() );

		$this->posted_fields = $this->clean_expected_fields();

		$this->errors = $this->validate_fields( $this->errors );

		if ( $this->errors->get_error_codes() ) {
			return false;
		}

		$checkout->add_data( 'package_id', $this->posted_fields['pack'] );

		// save order complete and cancel urls
		$checkout->add_data( 'complete_url', appthemes_get_step_url( 'order-summary' ) );
		$checkout->add_data( 'cancel_url', appthemes_get_step_url( 'gateway-select' ) );

		$this->finish_step();
	}

	/**
	 * Validates submitted fields.
	 *
	 * @param object $errors
	 *
	 * return object
	 */
	public function validate_fields( $errors ) {

		if ( empty( $this->posted_fields['pack'] ) ) {
			$errors->add( 'missed-pack', __( 'You need to choose membership package.', APP_TD ) );
		} else {
			$pack_id = appthemes_numbers_only( $this->posted_fields['pack'] );
			$membership = cp_get_membership_package( $pack_id );
			if ( ! $membership ) {
				$errors->add( 'invalid-pack-id', __( 'Choosen membership package does not exist.', APP_TD ) );
			}
		}

		return $errors;
	}

	/**
	 * Displays extra notices.
	 *
	 * return void
	 */
	public function display_extra_messages() {
		// display the custom message entered on admin settings page
		cp_display_message( 'membership_form_help' );

		$required_message = '';

		if ( isset( $_GET['membership'] ) && $_GET['membership'] == 'required' ) {
			if ( ! empty( $_GET['cat'] ) && $_GET['cat'] != 'all' ) {
				$category_id = appthemes_numbers_only( $_GET['cat'] );
				$category = get_term_by( 'term_id ', $category_id, APP_TAX_CAT );
				if ( $category ) {
					$term_link = html( 'a', array( 'href' => get_term_link( $category, APP_TAX_CAT ), 'title' => $category->name ), $category->name );
					$required_message = sprintf( __( 'Membership is currently required in order to post to category %s.', APP_TD ), $term_link );
				}
			} else {
				$required_message = __( 'Membership is currently required.', APP_TD );
			}
		}

		if ( $required_message ) {
			appthemes_display_notice( 'success', $required_message );
		}

	}

	/**
	 * Returns fields names that we expect.
	 *
	 * return array
	 */
	protected function expected_fields() {
		$fields = array(
			'pack',
		);

		$fields = apply_filters( 'cp_form_expected_fields', $fields, $this->step_id );

		return $fields;
	}

	/**
	 * Returns cleaned fields that we expect.
	 *
	 * return array
	 */
	protected function clean_expected_fields() {
		$posted = array();

		foreach( $this->expected_fields() as $field ) {
			$posted[ $field ] = isset( $_POST[ $field ] ) ? appthemes_clean( $_POST[ $field ] ) : '';
		}

		return $posted;
	}

}


/**
 * Form Step: Preview Membership Package
 */
class CP_Membership_Form_Preview extends APP_Checkout_Step {

	protected $errors;
	protected $package_id;

	public function __construct() {
		global $cp_options;

		if ( ! $cp_options->enable_membership_packs ) {
			return;
		}

		$this->errors = new WP_Error();

		parent::__construct( 'preview-membership', array(
			'register_to' => array(
				'membership-purchase' => array( 'after' => 'select-membership' ),
			)
		) );

	}

	/**
	 * Displays form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function display( $order, $checkout ) {

		the_post();

		$this->package_id = $checkout->get_data( 'package_id' );

		add_action( 'appthemes_notices', array( $this, 'display_extra_messages' ), 9 );

		appthemes_load_template( 'form-membership-preview.php', array(
			'action' => $checkout->get_checkout_type(),
			'renew' => $this->is_membership_renew(),
			'membership' => cp_get_membership_package( $this->package_id ),
			'active_membership' => $this->get_current_membership(),
		) );

	}

	/**
	 * Processing form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function process( $order, $checkout ) {

		// if no package data move step backward
		if ( ! $checkout->get_data( 'package_id' ) ) {
			$checkout->cancel_step();
			return;
		}

		if ( ! isset( $_POST['action'] ) || 'membership-purchase' !== $_POST['action'] ) {
			return;
		}

		check_admin_referer( $checkout->get_checkout_type() );

		$this->package_id = $checkout->get_data( 'package_id' );

		$this->errors = apply_filters( 'cp_membership_validate_fields', $this->errors );
		if ( cp_payments_is_enabled( 'membership' ) ) {
			$this->errors = apply_filters( 'appthemes_validate_purchase_fields', $this->errors );
		}

		$package = cp_get_membership_package( $this->package_id );
		if ( ! $package ) {
			$this->errors->add( 'invalid-pack-id', __( 'Choosen membership package does not exist.', APP_TD ) );
		}

		if ( $this->errors->get_error_codes() ) {
			return false;
		}

		// clear previous package items, can have only one
		$membership_packs = cp_get_membership_packages();
		foreach ( $membership_packs as $membership_pack ) {
			$order->remove_item( $membership_pack->post_name );
		}

		$order->add_item( $package->post_name, $package->price, 0, true );
		$order->set_description( $package->pack_name );
		do_action( 'appthemes_create_order', $order, CP_PACKAGE_MEMBERSHIP_PTYPE );

		$this->finish_step();
	}

	/**
	 * Returns current membership of user.
	 *
	 * return object
	 */
	public function get_current_membership() {
		global $current_user;

		return cp_get_user_membership_package( $current_user->ID );
	}

	/**
	 * Checks if it's a membership renew.
	 *
	 * return bool
	 */
	public function is_membership_renew() {
		global $current_user;

		$current_membership = $this->get_current_membership();

		if ( ! $current_membership ) {
			return false;
		}

		$package = cp_get_membership_package( $this->package_id );

		if ( $current_membership->ID != $package->ID ) {
			return false;
		}

		return true;
	}

	/**
	 * Displays extra notices.
	 *
	 * return void
	 */
	public function display_extra_messages() {
		global $current_user;

		$package = cp_get_membership_package( $this->package_id );
		$current_membership = $this->get_current_membership();

		if ( $current_membership && $current_membership->ID != $package->ID ) {
			$days_remining = appthemes_days_between_dates( $current_user->membership_expires );
			$message = sprintf(
				__( 'Your Current Membership (%1$s) will be canceled upon purchase. This membership still has %2$s days remaining.', APP_TD ),
				$current_membership->pack_name,
				$days_remining
			);
			appthemes_display_notice( 'error', $message );
		}

	}

}


/**
 * Listing Checkout Steps Helper Class
 */
class CP_Listing_Checkout_Step extends APP_Checkout_Step {

	/**
	 * Returns listing object for editing.
	 *
	 * return object
	 */
	public function get_listing_obj() {
		$listing_id = is_object( $this->checkout ) ? $this->checkout->get_data( 'listing_id' ) : false;

		if ( $listing_id ) {
			$listing = get_post( $listing_id );
		} else if ( isset( $_GET['listing_renew'] ) ) {
			$listing = get_post( $_GET['listing_renew'] );
		} else if ( isset( $_GET['listing_edit'] ) ) {
			$listing = get_post( $_GET['listing_edit'] );
		} else {
			$listing = appthemes_get_draft_post( APP_POST_TYPE );
		}

		return ( $listing ) ? cp_get_listing_obj( $listing->ID, $listing_id ) : false;
	}

	/**
	 * Returns select category url.
	 *
	 * return string
	 */
	public function get_select_category_url() {
		$step_url = appthemes_get_step_url( 'select-category' );
		$step_url = add_query_arg( array( 'action' => 'change' ), $step_url );

		return $step_url;
	}

	/**
	 * Checks if field is valid and have no errors assigned.
	 *
	 * @param string $field_name
	 *
	 * return bool
	 */
	public function is_field_valid( $field_name ) {

		if ( $this->errors->get_error_message( 'missed-' . $field_name ) ) {
			return false;
		}

		if ( $this->errors->get_error_message( 'invalid-' . $field_name ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Assigns a plan to the Order
	 *
	 * @since 3.5
	 */
	function add_plan_to_order( $order, $checkout, $plan_id = 0, $membership_id = 0, $post_id = 0 ){
		global $cp_options;

		// clear previous plan if available
		$this->clear_order_plan( $order );

		// apply plan pricing if available
		if ( $plan_id ) {

			$plan = get_post( $plan_id );
			$plan_data = cp_get_plan_data( $plan_id );

			$plan = $plan->post_name;
			$price = $plan_data['price'];

			if ( $order->get_items( $plan ) ) {
				return;
			}

		// apply single listing pricings depending on the pricing scheme
		} else {

			$plan = $cp_options->price_scheme;
			$price = $this->posted_fields['cp_sys_ad_listing_fee'];

			if ( $order->get_items( $plan ) ) {
				return;
			}

		}

		// apply the membership price if available
		if ( $membership_id ) {
			$member_plan = get_post( $membership_id );
			$member_price = cp_calculate_membership_package_benefit( $membership_id, $this->posted_fields['cp_sys_total_ad_cost_no_benefit'] );

			$order->add_item( $member_plan->post_name, $member_price, $post_id );

			$price = 0;
		}

		// add the plan price
		$order->add_item( $plan, $price, $post_id );
	}

	/**
	 * Assigns a list of addons to the Order.
	 *
	 * @since 3.5
	 */
	function add_addons_to_order( $order, $post_id, $membership_id = 0 ) {
		global $cp_options, $current_user;

		// clear previous addons if available
		$this->clear_order_addons( $order, 'featured' );

		// note: currently there are no "real" payment module addons

		if ( is_sticky( $post_id ) ) {
			$price = $cp_options->sys_feat_price;

			if ( $membership_id ) {

				// ignore the 'featured' price if the active membership pricing scheme applies to the full price
				$membership = cp_get_user_membership_package( $current_user->ID );
				if ( $membership->pack_type != 'static' ) {
					$price = 0;
				}
			}

			$order->add_item( 'featured-listing', $price, $post_id );
		}

	}

	/**
	 * Sets the Order description that will later be displayed on the payment gateway page.
	 *
	 * @since 3.5
	 */
	function set_order_description( $order, $checkout, $post_id = 0 ) {
		$order_summary = '';

		if ( $post_id ) {
			$order_summary .= get_the_title( $post_id ) . ' :: ';
		}
		$order_summary .= $this->get_order_summary_content( $order, $checkout );

		$order->set_description( $order_summary );
	}

	/**
	 * @since 3.5
	 */
	function get_order_summary_content( $order, $checkout ) {

		$order_items = $relist = '';

		$items = $order->get_items();

		$order_plan = cp_get_order_plan_data( $order );

		$plan_type = $order_plan['type'];

		foreach( $items as $item ) {

			if ( ! APP_Item_Registry::is_registered( $item['type'] ) ) {
				$item_title = __( 'Unknown', APP_TD );
			} else {
				$item_title = APP_Item_Registry::get_title( $item['type'] );
			}

			if ( $item['type'] == $plan_type ) {
				$item_title .= $relist;
			}

			$item_html = ( $order_items ? ' / ' . $item_title : $item_title );

			$order_items .= $item_html;
		}

		if ( ! $order_items  ) {
			$order_items = '-';
		}

		return $order_items;
	}

	/**
	 * Clears all addons from an Order to avoid duplicate items when a user updates the addons selection.
	 *
	 * @since 3.5
	 */
	protected function clear_order_addons( $order, $type = '' ) {

		foreach( cp_get_addons( $type ) as $addon ) {
			$order->remove_item( $addon['type'] );
		}

	}

	/**
	 * Clears all plans from an Order to avoid duplicate items when a user updates the selection.
	 *
	 * @since 3.5
	 */
	protected function clear_order_plan( $order, $plan_types = '' ) {

		// remove pricing plan 'addons'
		$this->clear_order_addons( $order, 'pricing' );

		foreach( cp_get_plans( $plan_types ) as $plan ) {
			$order->remove_item( $plan['post']->post_name );
		}

	}

}


/**
 * Form Step: Select Listing Category
 */
class CP_Listing_Form_Select_Category extends CP_Listing_Checkout_Step {

	protected $errors;
	protected $posted_fields;

	public function __construct() {
		global $cp_options;

		$register_to = array( 'create-listing' );
		if ( $cp_options->allow_relist ) {
			$register_to[] = 'renew-listing';
		}

		$this->errors = new WP_Error();

		parent::__construct( 'select-category', array(
			'register_to' => $register_to
		) );

	}

	/**
	 * Displays form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function display( $order, $checkout ) {
		global $cp_options;

		the_post();

		add_action( 'appthemes_notices', array( $this, 'display_extra_messages' ), 9 );

		$listing = $this->get_listing_obj();
		$checkout->add_data( 'listing_id', $listing->ID );

		if ( ! $listing->category || ( isset( $_GET['action'] ) && $_GET['action'] == 'change' ) ) {
			appthemes_load_template( 'form-listing-category-select.php', array(
				'action' => $checkout->get_checkout_type(),
				'listing' => $listing,
			) );
		} else {
			$category = get_term_by( 'id', $listing->category, APP_TAX_CAT );

			appthemes_load_template( 'form-listing-category-preview.php', array(
				'action' => $checkout->get_checkout_type(),
				'listing' => $listing,
				'category' => $category,
				'category_fee' => $this->get_category_fee( $listing->category ),
				'select_category_url' => $this->get_select_category_url(),
			) );
		}

	}

	/**
	 * Processing form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function process( $order, $checkout ) {

		if ( ! isset( $_POST['action'] ) || ! in_array( $_POST['action'], array( 'create-listing', 'renew-listing' ) ) ) {
			return;
		}

		check_admin_referer( $checkout->get_checkout_type() );

		$this->posted_fields = $this->clean_expected_fields();

		$this->errors = $this->validate_fields( $this->errors );

		if ( $this->errors->get_error_codes() ) {
			return false;
		}

		$listing = $this->get_listing_obj();

		// set listing category
		wp_set_post_terms( $listing->ID, $this->posted_fields['cat'], APP_TAX_CAT, false );

		$checkout->add_data( 'category_id', $this->posted_fields['cat'] );
		$checkout->add_data( 'form_id', cp_get_form_id( $this->posted_fields['cat'] ) );

		// save order complete and cancel urls
		$checkout->add_data( 'complete_url', appthemes_get_step_url( 'order-summary' ) );
		$checkout->add_data( 'cancel_url', appthemes_get_step_url( 'gateway-select' ) );

		$this->finish_step();
	}

	/**
	 * Validates submitted fields.
	 *
	 * @param object $errors
	 *
	 * return object
	 */
	public function validate_fields( $errors ) {
		global $cp_options;

		if ( empty( $this->posted_fields['cat'] ) || $this->posted_fields['cat'] == '-1' ) {
			$errors->add( 'missed-category', __( 'You need to select a category.', APP_TD ) );
		} else {
			$category = get_term_by( 'id', $this->posted_fields['cat'], APP_TAX_CAT );
			if ( ! $category ) {
				$errors->add( 'invalid-category', __( 'Selected category is invalid.', APP_TD ) );
			} else if ( empty( $category->parent ) ) {
				if ( $cp_options->ad_parent_posting == 'no' ) {
					$errors->add( 'invalid-category', __( 'Posting to parent categories is not allowed.', APP_TD ) );
				} else if ( $cp_options->ad_parent_posting == 'whenEmpty' && $subcategories = get_term_children( $category->term_id, APP_TAX_CAT ) ) {
					$errors->add( 'invalid-category', __( 'Posting to parent categories that have subcategories is not allowed.', APP_TD ) );
				}
			}
		}

		return $errors;
	}

	/**
	 * Displays extra notices.
	 *
	 * return void
	 */
	public function display_extra_messages() {
		// display the custom message entered on admin settings page
		cp_display_message( 'ads_form_help' );

	}

	/**
	 * Returns fields names that we expect.
	 *
	 * return array
	 */
	protected function expected_fields() {
		$fields = array(
			'cat',
		);

		$fields = apply_filters( 'cp_form_expected_fields', $fields, $this->step_id );

		return $fields;
	}

	/**
	 * Returns cleaned fields that we expect.
	 *
	 * return array
	 */
	protected function clean_expected_fields() {
		$posted = array();

		foreach ( $this->expected_fields() as $field ) {
			$posted[ $field ] = isset( $_POST[ $field ] ) ? appthemes_clean( $_POST[ $field ] ) : '';

			if ( $field == 'cat' ) {
				$posted[ $field ] = appthemes_numbers_only( $posted[ $field ] );
			}
		}

		return $posted;
	}

	/**
	 * Returns listing category fee.
	 *
	 * @param int $category_id
	 *
	 * return string
	 */
	protected function get_category_fee( $category_id ) {
		global $cp_options;

		if ( $cp_options->price_scheme == 'category' && cp_payments_is_enabled() ) {
			$prices = $cp_options->price_per_cat;
			$category_fee = ( isset( $prices[ $category_id ] ) ) ? (float) $prices[ $category_id ] : 0;
			$category_fee = ' - ' . appthemes_get_price( $category_fee );
		} else {
			$category_fee = '';
		}

		return $category_fee;
	}

}


/**
 * Form Step: Edit Listing Details
 */
class CP_Listing_Form_Edit extends CP_Listing_Checkout_Step {

	protected $errors;
	protected $category_id;
	protected $form_id;
	protected $form_fields;
	protected $posted_fields;

	public function __construct() {
		global $cp_options;

		if ( ! $cp_options->ad_edit ) {
			return;
		}

		$this->errors = new WP_Error();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		parent::__construct( 'listing-edit', array(
			'register_to' => array(
				'edit-listing',
			)
		) );

	}

	/**
	 * Displays form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function display( $order, $checkout ) {

		the_post();

		$listing = $this->get_listing_obj();
		$categories = wp_get_post_terms( $listing->ID, APP_TAX_CAT );

		$this->category_id = ( ! empty( $categories ) ) ? $categories[0]->term_id : false;
		$this->form_id = cp_get_form_id( $this->category_id );
		$this->form_fields = cp_get_custom_form_fields( $this->form_id );

		$checkout->add_data( 'listing_id', $listing->ID );
		$checkout->add_data( 'category_id', $this->category_id );
		$checkout->add_data( 'form_id', $this->form_id );

		appthemes_load_template( 'form-listing-edit.php', array(
			'action' => $checkout->get_checkout_type(),
			'listing' => $listing,
			'category_id' => $this->category_id,
			'form_id' => $this->form_id,
			'form_fields' => $this->form_fields,
		) );

	}

	/**
	 * Processing form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function process( $order, $checkout ) {
		global $cp_options;

		if ( ! isset( $_POST['action'] ) || 'edit-listing' !== $_POST['action'] ) {
			return;
		}

		check_admin_referer( $checkout->get_checkout_type() );

		$this->category_id = $checkout->get_data( 'category_id' );
		$this->form_id = $checkout->get_data( 'form_id' );
		$this->form_fields = cp_get_custom_form_fields( $this->form_id );

		$this->posted_fields = $this->clean_expected_fields();

		$this->errors = $this->validate_fields( $this->errors );
		$this->errors = apply_filters( 'cp_listing_validate_fields', $this->errors );

		$this->update_listing( $order, $checkout );

		// set listing as pending if it require moderation
		if ( $cp_options->moderate_edited_ads ) {
			$listing = $this->get_listing_obj();
			$listing_args = array(
				'ID' => $listing->ID,
				'post_status' => 'pending',
			);
			$listing_id = wp_update_post( $listing_args );
		}

		if ( $this->errors->get_error_codes() ) {
			return false;
		}

		// add notice about successful update
		$link = html( 'a', array( 'href' => esc_url( CP_DASHBOARD_URL ), 'class' => 'no-padding' ), __( 'Return to dashboard.', APP_TD ) );
		if ( $cp_options->moderate_edited_ads ) {
			appthemes_add_notice( 'updated', sprintf( __( 'Your ad has been successfully updated and awaiting approval. %s', APP_TD ), $link ), 'success' );
		} else {
			appthemes_add_notice( 'updated', sprintf( __( 'Your ad has been successfully updated. %s', APP_TD ), $link ), 'success' );
		}

		$checkout->add_data( 'posted_fields', $this->posted_fields );
		$this->finish_step();
	}

	/**
	 * Updating listing.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function update_listing( $order, $checkout ) {

		$this->update_attachments( $order, $checkout );

		$listing = $this->get_listing_obj();
		$listing_args = array();

		// remove meta which are displayed without checking for presence in custom forms
		$fixed_meta_keys = array( 'cp_price', 'cp_currency' );
		foreach ( $fixed_meta_keys as $fixed_meta_key ) {
			delete_post_meta( $listing->ID, $fixed_meta_key );
		}

		// save custom form fields
		foreach ( $this->form_fields as $field ) {
			// do not save this field, it has been marked as invalid during validation
			if ( ! $this->is_field_valid( $field->field_name ) ) {
				continue;
			}

			$field_value = $this->posted_fields[ $field->field_name ];

			// save meta custom fields (have 'cp_' prefix)
			if ( appthemes_str_starts_with( $field->field_name, 'cp_' ) ) {
				if ( $field->field_type == 'checkbox' ) {
					delete_post_meta( $listing->ID, $field->field_name );
					if ( is_array( $field_value ) ) {
						foreach ( $field_value as $checkbox_value ) {
							if ( ! is_array( $checkbox_value ) ) {
								add_post_meta( $listing->ID, $field->field_name, $checkbox_value );
							}
						}
					}
				} else {
					if ( ! is_array( $field_value ) ) {
						update_post_meta( $listing->ID, $field->field_name, $field_value );
					}
				}
			}

			// look for listing title and content
			if ( in_array( $field->field_name, array( 'post_title', 'post_content' ) ) ) {
				$listing_args[ $field->field_name ] = $field_value;
			}

		}

		// update tags
		$tags = ! empty( $this->posted_fields['tags_input'] ) ? explode( ',', $this->posted_fields['tags_input'] ) : '';
		wp_set_object_terms( $listing->ID, $tags, APP_TAX_TAG );

		// update listing
		if ( ! empty( $listing_args ) ) {
			$listing_args['ID'] = $listing->ID;
			$listing_id = wp_update_post( $listing_args );
		}

		do_action( 'cp_update_listing', $listing->ID, $order, $checkout );
	}

	/**
	 * Updating attachments.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function update_attachments( $order, $checkout ) {
		global $cp_options;

		// needed for image uploading and deleting to work
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		$listing = $this->get_listing_obj();

		// associate app-plupload images
		if ( ! empty( $this->posted_fields['app_attach_id'] ) && is_array( $this->posted_fields['app_attach_id'] ) ) {
			$attachments = $this->posted_fields['app_attach_id'];
			$titles = ( ! empty( $this->posted_fields['app_attach_title'] ) && is_array( $this->posted_fields['app_attach_title'] ) ) ? $this->posted_fields['app_attach_title'] : array();
			// associate the already uploaded images to listing and update titles
			appthemes_plupload_associate_images( $listing->ID, $attachments, $titles, false );
		}

		// delete any images checked
		if ( ! empty( $this->posted_fields['image'] ) ) {
			cp_delete_image();
		}

		// update the image alt text
		if ( ! empty( $this->posted_fields['attachments'] ) ) {
			cp_update_alt_text();
		}

		// upload images and associate to listing
		if ( ! empty( $_FILES['image'] ) && $this->is_field_valid( 'files_image' ) ) {
			$images_count = cp_count_ad_images( $listing->ID );
			$max_images = $cp_options->num_images;
			if ( $max_images > $images_count ) {
				$images_data = cp_process_new_image();
				if ( ! empty( $images_data['attachment'] ) ) {
					cp_associate_images( $listing->ID, $images_data['attachment'] );
				}
			}
		}

		// After all update Media Manager data.
		$attach_ids = get_posts( array( 'post_parent' => $listing->ID, 'post_type' => 'attachment', 'nopaging' => true, 'fields' => 'ids', 'order' => 'ASC' ) );

		if ( count( $attach_ids ) ) {
			update_post_meta( $listing->ID, '_app_media', $attach_ids );

			foreach ( $attach_ids as $attach_id ) {
				update_post_meta( $attach_id, '_app_attachment_type', 'file' );
			}
		}

	}

	/**
	 * Validates submitted fields.
	 *
	 * @param object $errors
	 *
	 * return object
	 */
	public function validate_fields( $errors ) {
		global $cp_options;

		// validate images
		if ( ! empty( $_FILES['image'] ) ) {
			$image_errors = cp_validate_image();
			if ( $image_errors ) {
				$i = 0;
				foreach ( $image_errors as $image_error ) {
					$errors->add( 'invalid-files_image', $image_error );
					$i++;
				}
			}
		}

		// check if images are required and user uploaded some
		if ( $cp_options->ad_images && $cp_options->require_images ) {
			if ( empty( $_FILES['image']['tmp_name'][0] ) && empty( $this->posted_fields['attachments'] ) && empty( $this->posted_fields['app_attach_id'] ) ) {
				$errors->add( 'missed-image', __( 'Please upload at least 1 image.', APP_TD ) );
			}
		}

		// check custom form fields
		foreach ( $this->form_fields as $field ) {
			$field_value = $this->posted_fields[ $field->field_name ];

			if ( $field->field_req && empty( $field_value ) && $field_value != '0' ) {

				$errors->add( 'missed-' . $field->field_name, sprintf( __( 'Error: The "%s" field is empty.', APP_TD ), translate( $field->field_label, APP_TD ) ) );

			} else if ( $field->field_req && $field->field_min_length && ( mb_strlen( $field_value ) < $field->field_min_length ) ) {

				$errors->add( 'invalid-' . $field->field_name, sprintf( __( 'Error: The "%1$s" field should be at least %2$d characters long.', APP_TD ), translate( $field->field_label, APP_TD ), $field->field_min_length ) );

			} else if ( ! empty( $field_value ) && in_array( $field->field_type, array( 'checkbox', 'radio', 'drop-down' ) ) ) {

				$options = cp_explode( ',', $field->field_values );
				// check if the posted value is one of the provided by form
				if ( array_diff( (array)$field_value, $options ) ) {
					$errors->add( 'invalid-' . $field->field_name, sprintf( __( 'Error: The "%s" field is invalid.', APP_TD ), translate( $field->field_label, APP_TD ) ) );
				}

			}
		}

		return $errors;
	}

	/**
	 * Enqueue scripts.
	 *
	 * return void
	 */
	public function enqueue_scripts() {
		global $cp_options;

		$step = _appthemes_get_step_from_query();
		if ( $step !== 'listing-edit' ) {
			return;
		}

		if ( $cp_options->ad_images && appthemes_plupload_is_enabled() ) {
			$listing = $this->get_listing_obj();
			appthemes_plupload_enqueue_scripts( $listing->ID );
		}
	}

	/**
	 * Returns fields names that we expect.
	 *
	 * return array
	 */
	protected function expected_fields() {
		$fields = array(
			'attachments',
			'app_attach_id',
			'app_attach_title',
			'image',
			'cp_price',
			'cp_currency',
			'post_title',
			'post_content',
			'tags_input',
		);
		$fields = array_merge( $fields, $this->get_custom_form_fields_keys() );

		$fields = apply_filters( 'cp_form_expected_fields', $fields, $this->step_id );

		return $fields;
	}

	/**
	 * Returns cleaned fields that we expect.
	 *
	 * return array
	 */
	protected function clean_expected_fields() {
		global $cp_options;

		$posted = array();

		foreach ( $this->expected_fields() as $field ) {
			$posted[ $field ] = isset( $_POST[ $field ] ) ? $_POST[ $field ] : '';

			if ( ! is_array( $posted[ $field ] ) ) {
				$posted[ $field ] = appthemes_clean( $posted[ $field ] );
				if ( appthemes_str_starts_with( $field, 'cp_' ) ) {
					$posted[ $field ] = wp_kses_post( $posted[ $field ] );
					// Strip shortcodes
					if ( ! current_user_can( 'edit_others_posts' ) && ! is_admin() ) {
						$posted[ $field ] = strip_shortcodes( $posted[ $field ] );
					}
				}
			} else {
				$posted[ $field ] = array_map( 'appthemes_clean', $posted[ $field ] );
				if ( appthemes_str_starts_with( $field, 'cp_' ) ) {
					$posted[ $field ] = array_map( 'wp_kses_post', $posted[ $field ] );
				}
			}

			if ( $field == 'cp_price' ) {
				$posted[ $field ] = appthemes_clean_price( $posted[ $field ] );
			}

			if ( $field == 'tags_input' ) {
				$posted[ $field ] = appthemes_clean_tags( $posted[ $field ] );
				$posted[ $field ] = wp_kses_post( $posted[ $field ] );
			}

			if ( $field == 'post_content' ) {
				// check to see if html is allowed
				if ( ! $cp_options->allow_html ) {
					$posted[ $field ] = appthemes_filter( $posted[ $field ] );
				} else {
					$posted[ $field ] = wp_kses_post( $posted[ $field ] );
				}

				// Strip shortcodes
				if ( ! current_user_can( 'edit_others_posts' ) && ! is_admin() ) {
					$posted[ $field ] = strip_shortcodes( $posted[ $field ] );
				}
			}

			if ( $field == 'post_title' ) {
				$posted[ $field ] = appthemes_filter( $posted[ $field ] );
			}

		}

		return $posted;
	}

	/**
	 * Returns custom form fields keys.
	 *
	 * return array
	 */
	protected function get_custom_form_fields_keys() {
		$keys = array();

		foreach ( $this->form_fields as $field ) {
			$keys[] = $field->field_name;
		}

		return $keys;
	}

}


/**
 * Form Step: Edit/Fill Listing Details
 */
class CP_Listing_Form_Details extends CP_Listing_Form_Edit {

	protected $errors;
	protected $category_id;
	protected $form_id;
	protected $form_fields;
	protected $posted_fields;

	public function __construct() {
		global $cp_options;

		$register_to = array( 'create-listing' );
		if ( $cp_options->allow_relist ) {
			$register_to[] = 'renew-listing';
		}

		$this->errors = new WP_Error();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		$this->setup( 'listing-details', array(
			'register_to' => $register_to
		) );

	}

	/**
	 * Displays form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function display( $order, $checkout ) {

		the_post();

		add_action( 'appthemes_notices', array( $this, 'display_extra_messages' ), 9 );

		$this->category_id = $checkout->get_data( 'category_id' );
		$this->form_id = $checkout->get_data( 'form_id' );
		$this->form_fields = cp_get_custom_form_fields( $this->form_id );

		$listing = $this->get_listing_obj();
		$category = get_term_by( 'id', $this->category_id, APP_TAX_CAT );

		appthemes_load_template( 'form-listing-details.php', array(
			'action' => $checkout->get_checkout_type(),
			'listing' => $listing,
			'category' => $category,
			'select_category_url' => $this->get_select_category_url(),
			'form_id' => $this->form_id,
		) );

	}

	/**
	 * Processing form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function process( $order, $checkout ) {

		// if no category data move step backward
		if ( ! $checkout->get_data( 'category_id' ) ) {
			$checkout->cancel_step();
			return;
		}

		if ( ! isset( $_POST['action'] ) || ! in_array( $_POST['action'], array( 'create-listing', 'renew-listing' ) ) ) {
			return;
		}

		check_admin_referer( $checkout->get_checkout_type() );

		$this->category_id = $checkout->get_data( 'category_id' );
		$this->form_id = $checkout->get_data( 'form_id' );
		$this->form_fields = cp_get_custom_form_fields( $this->form_id );

		$this->posted_fields = $this->clean_expected_fields();

		$this->errors = $this->validate_fields( $this->errors );
		$this->errors = apply_filters( 'cp_listing_validate_fields', $this->errors );

		$this->update_listing( $order, $checkout );

		if ( $this->errors->get_error_codes() ) {
			return false;
		}

		$this->set_internal_data();

		$checkout->add_data( 'posted_fields', $this->posted_fields );
		$this->finish_step();
	}

	/**
	 * Updating listing.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function update_listing( $order, $checkout ) {

		parent::update_listing( $order, $checkout );

		$listing = $this->get_listing_obj();

		// (un)mark listing as featured
		if ( ! empty( $this->posted_fields['featured_ad'] ) ) {
			stick_post( $listing->ID );
		} else {
			unstick_post( $listing->ID );
		}

	}

	/**
	 * Sets listing internal data.
	 *
	 * return void
	 */
	protected function set_internal_data() {
		global $cp_options, $current_user;

		$listing = $this->get_listing_obj();

		// add items needed for function that displays listing preview
		$this->posted_fields['fid'] = $this->form_id;
		$this->posted_fields['cat'] = $this->category_id;

		// set listing unique id
		if ( $unique_id = get_post_meta( $listing->ID, 'cp_sys_ad_conf_id', true ) ) {
			$this->posted_fields['cp_sys_ad_conf_id'] = $unique_id;
		} else {
			$this->posted_fields['cp_sys_ad_conf_id'] = cp_generate_id();
		}

		// set user IP
		$this->posted_fields['cp_sys_userIP'] = appthemes_get_ip();

		// set listing duration
		if ( $this->posted_fields['ad_pack_id'] ) {
			$this->posted_fields['cp_sys_ad_duration'] = cp_get_ad_pack_length( $this->posted_fields['ad_pack_id'] );
		} else {
			$this->posted_fields['cp_sys_ad_duration'] = $cp_options->prun_period;
		}



		$listing_price_currency = ! empty( $this->posted_fields['cp_currency'] ) ? $this->posted_fields['cp_currency'] : $cp_options->curr_symbol;
		$coupon = false; // legacy coupon value

		if ( cp_payments_is_enabled() ) {
			// see if the featured ad checkbox has been checked
			if ( ! empty( $this->posted_fields['featured_ad'] ) ) {
				$this->posted_fields['featured_ad'] = 1;
				// save featured ad price
				$this->posted_fields['cp_sys_feat_price'] = $cp_options->sys_feat_price;
			}

			// calculate the ad listing fee and put into a variable
			$this->posted_fields['cp_sys_ad_listing_fee'] = cp_ad_listing_fee( $this->category_id, $this->posted_fields['ad_pack_id'], $this->posted_fields['cp_price'], $listing_price_currency );

			$featured_price = isset( $this->posted_fields['cp_sys_feat_price'] ) ? $this->posted_fields['cp_sys_feat_price'] : 0;

			$this->posted_fields['cp_sys_total_ad_cost'] = cp_calc_ad_cost( $this->category_id, $this->posted_fields['ad_pack_id'], $featured_price, $this->posted_fields['cp_price'], $coupon, $listing_price_currency );
			$this->posted_fields['cp_sys_total_ad_cost_no_benefit'] = $this->posted_fields['cp_sys_total_ad_cost'];

			// apply membership benefit
			if ( $cp_options->enable_membership_packs && $membership = cp_get_user_membership_package( $current_user->ID ) ) {
					$this->posted_fields['membership_pack'] = $membership->ID;
					// update the total cost based on the membership pack ID and current total cost
					$this->posted_fields['cp_sys_total_ad_cost'] = cp_calculate_membership_package_benefit( $membership->ID, $this->posted_fields['cp_sys_total_ad_cost'] );
					// add featured cost to static pack type
					if ( $featured_price && $membership->pack_type == 'static' ) {
						$this->posted_fields['cp_sys_total_ad_cost'] += $featured_price;
					}
			}
		}

		// prevent from minus prices if bigger discount applied
		if ( ! isset( $this->posted_fields['cp_sys_total_ad_cost'] ) ) {
			$this->posted_fields['cp_sys_total_ad_cost'] = 0;
		}

		$this->posted_fields['cp_sys_total_ad_cost'] = apply_filters( 'cp_sys_total_ad_cost', $this->posted_fields['cp_sys_total_ad_cost'], $this->posted_fields );

		// prevent from minus prices if bigger discount applied
		if ( $this->posted_fields['cp_sys_total_ad_cost'] < 0 ) {
			$this->posted_fields['cp_sys_total_ad_cost'] = 0;
		}
	}

	/**
	 * Validates submitted fields.
	 *
	 * @param object $errors
	 *
	 * return object
	 */
	public function validate_fields( $errors ) {
		global $cp_options;

		$errors = parent::validate_fields( $errors );

		// check if ad pack is specified for fixed price option, and valid
		if ( $cp_options->price_scheme == 'single' && cp_payments_is_enabled() && empty( $this->posted_fields['ad_pack_id'] ) ) {
			$errors->add( 'missed-ad_pack_id', __( 'You need to choose ad package.', APP_TD ) );
		} else if ( ! empty( $this->posted_fields['ad_pack_id'] ) ) {
			$package = cp_get_listing_package( $this->posted_fields['ad_pack_id'] );
			if ( ! $package ) {
				$errors->add( 'invalid-ad_pack_id', __( 'Choosen ad package does not exist.', APP_TD ) );
			}
		}

		return $errors;
	}

	/**
	 * Displays extra notices.
	 *
	 * return void
	 */
	public function display_extra_messages() {
		// display the custom message entered on admin settings page
		cp_display_message( 'ads_form_help' );

	}

	/**
	 * Enqueue scripts.
	 *
	 * return void
	 */
	public function enqueue_scripts() {
		global $cp_options;

		$step = _appthemes_get_step_from_query();
		if ( $step !== 'listing-details' ) {
			return;
		}

		if ( $cp_options->ad_images && appthemes_plupload_is_enabled() ) {
			$listing = $this->get_listing_obj();
			appthemes_plupload_enqueue_scripts( $listing->ID );
		}
	}

	/**
	 * Returns fields names that we expect.
	 *
	 * return array
	 */
	protected function expected_fields() {
		$fields = array(
			'ad_pack_id',
			'featured_ad',
		);
		$fields = array_merge( $fields, parent::expected_fields() );

		return $fields;
	}

	/**
	 * Returns cleaned fields that we expect.
	 *
	 * return array
	 */
	protected function clean_expected_fields() {

		$posted = parent::clean_expected_fields();

		foreach ( $this->expected_fields() as $field ) {
			if ( $field == 'ad_pack_id' ) {
				$posted[ $field ] = isset( $_POST[ $field ] ) ? $_POST[ $field ] : '';
				$posted[ $field ] = appthemes_numbers_only( $posted[ $field ] );
			}
		}

		return $posted;
	}

}


/**
 * Form Step: Preview Listing Details
 */
class CP_Listing_Form_Preview extends CP_Listing_Checkout_Step {

	protected $errors;
	protected $category_id;
	protected $form_id;
	protected $form_fields;
	protected $posted_fields;

	public function __construct() {
		global $cp_options;

		$register_to = array( 'create-listing' );
		if ( $cp_options->allow_relist ) {
			$register_to[] = 'renew-listing';
		}

		$this->errors = new WP_Error();

		parent::__construct( 'listing-preview', array(
			'register_to' => $register_to
		) );

	}

	/**
	 * Displays form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function display( $order, $checkout ) {
		global $cp_options;

		the_post();

		$this->category_id = $checkout->get_data( 'category_id' );
		$this->form_id = $checkout->get_data( 'form_id' );

		$this->posted_fields = $checkout->get_data( 'posted_fields' );

		$listing = $this->get_listing_obj();
		$category = get_term_by( 'id', $this->category_id, APP_TAX_CAT );

		appthemes_load_template( 'form-listing-preview.php', array(
			'action'        => $checkout->get_checkout_type(),
			'listing'       => $listing,
			'category'      => $category,
			'form_id'       => $this->form_id,
			'posted_fields' => $this->posted_fields,
		) );

	}

	/**
	 * Processing form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function process( $order, $checkout ) {
		global $cp_options;

		// if no listing internal ID move step backward
		$posted_fields = $checkout->get_data( 'posted_fields' );
		if ( ! $posted_fields['cp_sys_ad_conf_id'] ) {
			$checkout->cancel_step();
			return;
		}

		if ( ! isset( $_POST['action'] ) || ! in_array( $_POST['action'], array( 'create-listing', 'renew-listing' ) ) ) {
			return;
		}

		check_admin_referer( $checkout->get_checkout_type() );

		$this->posted_fields = $checkout->get_data( 'posted_fields' );

		if ( cp_payments_is_enabled() ) {
			$this->errors = apply_filters( 'appthemes_validate_purchase_fields', $this->errors );
		}

		if ( $this->errors->get_error_codes() ) {
			return false;
		}

		$listing = $this->get_listing_obj();

		$this->update_listing( $order, $checkout );

		if ( cp_payments_is_enabled() ) {
			$plan_id = isset( $this->posted_fields['ad_pack_id'] ) ? $this->posted_fields['ad_pack_id'] : 0;
			$membership_id = isset( $this->posted_fields['membership_pack'] ) ? $this->posted_fields['membership_pack'] : 0;

			$this->add_plan_to_order( $order, $checkout, $plan_id, $membership_id, $listing->ID );
			$this->add_addons_to_order( $order, $listing->ID, $membership_id );

			$this->set_order_description( $order, $checkout, $listing->ID );

			do_action( 'appthemes_create_order', $order, APP_POST_TYPE );
		} else if ( 'pending' == get_post_status( $listing->ID ) ) {
			// send ad owner an email
			cp_owner_new_ad_email( $listing->ID );
		}

		// send new ad notification email to admin
		if ( $cp_options->new_ad_email ) {
			cp_new_ad_email( $listing->ID );
		}

		$this->finish_step();
	}

	/**
	 * Updating listing.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function update_listing( $order, $checkout ) {
		global $cp_options;

		$listing = $this->get_listing_obj();

		// save internal data
		foreach ( $this->posted_fields as $field_key => $field_value ) {
			if ( appthemes_str_starts_with( $field_key, 'cp_sys_' ) ) {
				update_post_meta( $listing->ID, $field_key, wp_kses_post( $field_value ) );
			}
		}

		// update listing status
		$listing_args = array(
			'ID' => $listing->ID,
			'post_status' => cp_set_post_status( $this->posted_fields ),
			'post_author' => get_current_user_id(),
			'post_date' => current_time( 'mysql' ),
			'post_date_gmt' => current_time( 'mysql', 1 ),
		);
		$listing_id = wp_update_post( $listing_args );

		do_action( 'cp_update_listing', $listing->ID, $order, $checkout );
	}

}


/**
 * Form Step: Free Listing Submission Summary
 */
class CP_Listing_Form_Submit_Free extends CP_Listing_Checkout_Step {

	public function __construct() {
		global $cp_options;

		if ( $cp_options->charge_ads ) {
			return;
		}

		$register_to = array( 'create-listing' );
		if ( $cp_options->allow_relist ) {
			$register_to[] = 'renew-listing';
		}

		parent::__construct( 'listing-submit-free', array(
			'register_to' => $register_to
		) );

	}

	/**
	 * Displays form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function display( $order, $checkout ) {

		the_post();

		$listing = $this->get_listing_obj();

		appthemes_load_template( 'form-listing-submit-free.php', array(
			'action' => $checkout->get_checkout_type(),
			'listing' => $listing,
		) );

	}

	/**
	 * Processing form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function process( $order, $checkout ) {
		global $cp_options;

		$listing = $this->get_listing_obj();

		// set listing expire date
		$length = get_post_meta( $listing->ID, 'cp_sys_ad_duration', true );
		if ( empty( $length ) ) {
			$length = $cp_options->prun_period;
		}

		$expire_date = appthemes_mysql_date( current_time( 'mysql' ), $length );
		update_post_meta( $listing->ID, 'cp_sys_expire_date', $expire_date );

	}
}


/**
 * Order Checkout Steps Helper Class
 */
class CP_Order_Checkout_Step extends APP_Checkout_Step {

	/**
	 * Returns an array of checkouts that support payments.
	 *
	 * return array
	 */
	public function get_checkouts() {
		global $cp_options;

		$register_to = array();
		if ( $cp_options->enable_membership_packs ) {
			$register_to[] = 'membership-purchase';
		}

		if ( $cp_options->charge_ads ) {
			$register_to[] = 'create-listing';
			if ( $cp_options->allow_relist ) {
				$register_to[] = 'renew-listing';
			}
		}

		return apply_filters( 'cp_order_checkout_step_register_to', $register_to );
	}

	/**
	 * Checks if user can access checkout step, if not, cancel or finish current step.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return bool
	 */
	public function can_access_step( $order, $checkout ) {

		// if order not created move step backward
		if ( ! $order->get_id() ) {
			$checkout->cancel_step();
			return false;
		}

		return true;
	}

}


/**
 * Form Step: Select Payment Gateway
 */
class CP_Gateway_Select extends CP_Order_Checkout_Step {

	public function __construct() {

		if ( $register_to = $this->get_checkouts() ) {
			parent::__construct( 'gateway-select', array(
				'register_to' => $register_to
			) );
		}

	}

	/**
	 * Displays form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function display( $order, $checkout ) {
		appthemes_add_template_var( array( 'app_order' => $order ) );
		appthemes_load_template( 'order-select.php' );
	}

	/**
	 * Processing form.
	 *
	 * @param APP_Order $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function process( $order, $checkout ) {

		if ( ! $this->can_access_step( $order, $checkout ) ) {
			return;
		}

		if ( $order->get_total() == 0 ) {
			$order->complete();
			$this->finish_step();
		}

		if ( ! empty( $_POST['payment_gateway'] ) ) {
			$gateway = $_POST['payment_gateway'];
		} elseif ( 1 === count( APP_Gateway_Registry::get_active_gateways() ) ) {
			$gateways = array_keys( APP_Gateway_Registry::get_active_gateways() );
			$gateway  = $gateways[0];
		} else {
			$gateway = '';
		}

		if ( $gateway ) {
			$is_valid = $order->set_gateway( $gateway );
			if ( ! $is_valid ) {
				return;
			}

			$this->finish_step();
		}

	}

}


/**
 * Form Step: Process Payment Gateway
 */
class CP_Gateway_Process extends CP_Order_Checkout_Step {

	public function __construct() {

		if ( $register_to = $this->get_checkouts() ) {
			parent::__construct( 'gateway-process', array(
				'register_to' => $register_to
			) );

			add_filter( 'appthemes_order_return_url', array( $this, 'filter_return_url' ) );
		}

	}

	/**
	 * Displays form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function display( $order, $checkout ) {
		appthemes_add_template_var( array( 'app_order' => $order ) );
		appthemes_load_template( 'order-checkout.php', array( 'gateway' => $gateway->display_name('dropdown') ) );
	}

	/**
	 * Processing form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function process( $order, $checkout ) {

		if ( ! $this->can_access_step( $order, $checkout ) ) {
			return;
		}

		// update order complete and cancel urls
		update_post_meta( $order->get_id(), 'complete_url', appthemes_get_step_url( 'order-summary' ) );
		update_post_meta( $order->get_id(), 'cancel_url', appthemes_get_step_url( 'gateway-select' ) );

		wp_redirect( $order->get_return_url() );
		exit;
	}

	/**
	 * Modifies the Order return URL by adding the current step ID.
	 */
	public function filter_return_url( $url ) {
		$checkout = appthemes_get_checkout();

		if ( $checkout ) {
			$url = esc_url_raw( add_query_arg( array( 'step' => $this->step_id, 'checkout' => $checkout->get_checkout_type() ), $url ) );
		}
		return $url;
	}

}


/**
 * Form Step: Order Summary
 */
class CP_Order_Summary extends CP_Order_Checkout_Step {

	public function __construct() {

		if ( $register_to = $this->get_checkouts() ) {
			parent::__construct( 'order-summary', array(
				'register_to' => $register_to
			) );
		}

	}

	/**
	 * Displays form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function display( $order, $checkout ) {

		appthemes_add_template_var( array( 'app_order' => $order ) );
		appthemes_load_template( 'order-summary.php' );

	}

	/**
	 * Processing form.
	 *
	 * @param object $order
	 * @param object $checkout
	 *
	 * return void
	 */
	public function process( $order, $checkout ) {

		if ( ! $this->can_access_step( $order, $checkout ) ) {
			return;
		}

	}

}

