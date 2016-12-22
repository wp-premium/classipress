<?php
/**
 * Admin Packages Metaboxes.
 *
 * @package ClassiPress\Admin\Metaboxes\Packages
 * @author  AppThemes
 * @since   ClassiPress 3.4
 */


/**
 * Listing Package Details Metabox.
 */
class CP_Listing_Package_General_Metabox extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'listing-package-details', __( 'Ad Pack Details', APP_TD ), CP_PACKAGE_LISTING_PTYPE, 'normal', 'high' );
	}

	public function before_form( $post ) {
		?><style type="text/css">#notice{ display: none; }</style><?php
	}

	public function form_fields() {
		$form_fields = array(
			array(
				'title' => __( 'Name', APP_TD ),
				'type' => 'text',
				'name' => 'pack_name',
				'tip' => __( 'Create a name that best describes this ad package. (i.e. 30 days for only $5) This will be visible on your new ad listing submission page.', APP_TD ),
			),
			array(
				'title' => __( 'Description', APP_TD ),
				'type' => 'textarea',
				'name' => 'description',
				'sanitize' => 'wp_kses_post',
				'tip' => __( 'Enter a description of your ad package. It will not be visible on your site.', APP_TD ),
				'extra' => array( 'style' => 'width: 25em;' ),
			),
			array(
				'title' => __( 'Price', APP_TD ),
				'type' => 'text',
				'name' => 'price',
				'desc' => sprintf( __( 'Example: %s ' , APP_TD ), '1.00' ),
				'tip' => __( 'Enter a numeric value for this package (do not enter a currency symbol or commas).', APP_TD ),
				'extra' => array( 'style' => 'width: 70px;' ),
			),
			array(
				'title' => __( 'Duration', APP_TD ),
				'type' => 'text',
				'name' => 'duration',
				'sanitize' => 'absint',
				'desc' => sprintf( __( 'Example: %s ' , APP_TD ), '30' ),
				'tip' => __( 'Enter a numeric value to specify the number of days for this ad package.', APP_TD ),
				'extra' => array( 'size' => 3 ),
			),
		);

		return $form_fields;
	}

	public function validate_post_data( $data, $post_id ) {

		$errors = new WP_Error();

		if ( empty( $data['pack_name'] ) ) {
			$errors->add( 'pack_name', __( 'Package title cannot be empty', APP_TD ) );
		}

		if ( ! is_numeric( $data['price'] ) ) {
			$errors->add( 'price', __( 'Price must be numeric', APP_TD ) );
		}

		if ( ! is_numeric( $data['duration'] ) ) {
			$errors->add( 'duration', __( 'Duration must be numeric', APP_TD ) );
		}

		return $errors;
	}

	public function post_updated_messages( $messages ) {
		$messages[ CP_PACKAGE_LISTING_PTYPE ] = array(
		 	1 => __( 'Package updated.', APP_TD ),
		 	4 => __( 'Package updated.', APP_TD ),
		 	6 => __( 'Package created.', APP_TD ),
		 	7 => __( 'Package saved.', APP_TD ),
		 	9 => __( 'Package scheduled.', APP_TD ),
			10 => __( 'Package draft updated.'),
		);

		return $messages;
	}

	public function after_form( $post ) {
		echo html( 'input', array( 'id' => 'post_title', 'name' => 'post_title', 'type' => 'hidden' ) );
?>
<script type="text/javascript">
	jQuery(document).ready(function($){

		$( "#submitpost input[type=submit]" ).click( function(){
			$("#post_title").val( $("#pack_name").val() );
		} );

	});
</script>
	<?php

	}

}


/**
 * Membership Package Details Metabox.
 */
class CP_Membership_Package_General_Metabox extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'membership-package-details', __( 'Membership Pack Details', APP_TD ), CP_PACKAGE_MEMBERSHIP_PTYPE, 'normal', 'high' );
	}

	public function before_form( $post ) {
		?><style type="text/css">#notice{ display: none; }</style><?php
	}

	public function form_fields() {
		$form_fields = array(
			array(
				'title' => __( 'Name', APP_TD ),
				'type' => 'text',
				'name' => 'pack_name',
				'tip' => __( 'Create a name that best describes this membership package. (i.e. 30 days unlimited posting for only $25) This will be visible on your membership purchase page.', APP_TD ),
			),
			array(
				'title' => __( 'Description', APP_TD ),
				'type' => 'textarea',
				'name' => 'description',
				'sanitize' => 'wp_kses_post',
				'tip' => __( 'Enter a description of your membership package.', APP_TD ),
				'extra' => array( 'style' => 'width: 25em;' ),
			),
			array(
				'title' => __( 'Package Type', APP_TD ),
				'type' => 'select',
				'name' => 'pack_type',
				'values' => array(
					'static' => __( 'Static Price', APP_TD ),
					'discount' => __( 'Discounted Price', APP_TD ),
					'percentage' => __( '% Discounted Price', APP_TD ),
				),
			),
			array(
				'title' => __( 'Price', APP_TD ),
				'type' => 'text',
				'name' => 'price',
				'desc' => sprintf( __( 'Example: %s ' , APP_TD ), '1.00' ),
				'tip' => __( 'The price this membership will cost your customer to purchase. Enter a numeric value (do not enter a currency symbol or commas).', APP_TD ),
				'extra' => array( 'style' => 'width: 70px;' ),
			),
			array(
				'title' => __( 'Duration', APP_TD ),
				'type' => 'text',
				'name' => 'duration',
				'sanitize' => 'absint',
				'desc' => sprintf( __( 'Example: %s ' , APP_TD ), '30' ),
				'tip' => __( 'The length of time in days this membership lasts.', APP_TD ),
				'extra' => array( 'size' => 3 ),
			),
			array(
				'title' => __( 'Price Modifier', APP_TD ),
				'type' => 'text',
				'name' => 'price_modifier',
				'desc' => __( 'Enter #.## for currency (i.e. 2.25 for $2.25), ### for percentage (i.e. 50 for 50%)' , APP_TD ),
				'tip' => __( 'The price modifier is how a membership affects the price of an ad. Enter a numeric value (do not enter a currency symbol or commas). This will modify the checkout price based on the selected package type.', APP_TD ),
				'extra' => array( 'size' => 3 ),
			),
			array(
				'title' => __( 'Satisfies Membership Req.', APP_TD ),
				'type' => 'checkbox',
				'name' => 'pack_satisfies_required',
				'desc' =>
					__( 'Yes' , APP_TD ) . '. ' .
					sprintf(
						__( 'If the &quot;<a href="%s">Are Membership Packs Required to Purchase Ads</a>&quot; option under the Membership tab is enabled, you should enable it.', APP_TD ),
						'admin.php?page=app-pricing&tab=membership'
					),
				'tip' => __( 'Disabling it means that this membership does not allow the customer to post to categories requiring membership. You would disable it if you wanted to separate memberships that are required to post versus memberships that simply affect the final price.', APP_TD ),
			),
		);

		return $form_fields;
	}

	public function validate_post_data( $data, $post_id ) {

		$errors = new WP_Error();

		if ( empty( $data['pack_name'] ) ) {
			$errors->add( 'pack_name', __( 'Package title cannot be empty', APP_TD ) );
		}

		if ( ! is_numeric( $data['price'] ) ) {
			$errors->add( 'price', __( 'Price must be numeric', APP_TD ) );
		}

		if ( ! is_numeric( $data['duration'] ) ) {
			$errors->add( 'duration', __( 'Duration must be numeric', APP_TD ) );
		}

		return $errors;
	}

	public function post_updated_messages( $messages ) {
		$messages[ CP_PACKAGE_MEMBERSHIP_PTYPE ] = array(
		 	1 => __( 'Package updated.', APP_TD ),
		 	4 => __( 'Package updated.', APP_TD ),
		 	6 => __( 'Package created.', APP_TD ),
		 	7 => __( 'Package saved.', APP_TD ),
		 	9 => __( 'Package scheduled.', APP_TD ),
			10 => __( 'Package draft updated.'),
		);

		return $messages;
	}

	public function after_form( $post ) {
		echo html( 'input', array( 'id' => 'post_title', 'name' => 'post_title', 'type' => 'hidden' ) );
?>
<script type="text/javascript">
	jQuery(document).ready(function($){

		$( "#submitpost input[type=submit]" ).click( function(){
			$("#post_title").val( $("#pack_name").val() );
		} );

	});
</script>
	<?php

	}

}

