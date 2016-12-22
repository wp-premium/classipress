<?php
/**
 * CSV Ads Importer.
 *
 * @package ClassiPress\Admin\Importer
 * @author  AppThemes
 * @since   ClassiPress 3.3
 */


class CP_Importer extends APP_Importer {

	function setup() {
		parent::setup();

		$this->args['parent'] = 'edit.php?post_type='.$this->post_type;
		$this->args['admin_action_priority'] = 11;
		add_filter( 'appthemes_importer_import_row_data', array( $this, 'prevent_duplicate' ), 10, 1 );
		add_action( 'appthemes_after_import_upload_form', array( $this, 'example_csv_files' ) );
	}

	/**
	 * Prevents duplicate entries while importing.
	 */
	function prevent_duplicate( $data ) {
		if ( ! empty( $data['post_meta']['cp_sys_ad_conf_id'] ) ) {
			if ( cp_get_listing_by_ref( $data['post_meta']['cp_sys_ad_conf_id'] ) ) {
				return false;
			}
		}

		return $data;
	}

	/**
	 * Inserts links to example CSV files into Importer page.
	 */
	function example_csv_files() {
		$link1 = html( 'a', array( 'href' => get_template_directory_uri() . '/examples/ads.csv', 'title' => __( 'Download CSV file', APP_TD ) ), __( 'Ads', APP_TD ) );
		$link2 = html( 'a', array( 'href' => get_template_directory_uri() . '/examples/ads-with-attachments.csv', 'title' => __( 'Download CSV file', APP_TD ) ), __( 'Ads with attachments', APP_TD ) );

		echo html( 'p', sprintf( __( 'Download example CSV files: %1$s, %2$s', APP_TD ), $link1, $link2 ) );
	}

}


/**
 * Setups CSV importer.
 *
 * @return void
 */
function cp_csv_importer() {
	$fields = array(
		'title'       => 'post_title',
		'description' => 'post_content',
		'status'      => 'post_status',
		'author'      => 'post_author',
		'date'        => 'post_date',
		'slug'        => 'post_name'
	);

	$args = array(
		'taxonomies' => array( APP_TAX_CAT, APP_TAX_TAG ),

		'custom_fields' => array(
			'id'          => 'cp_sys_ad_conf_id',
			'expire_date' => 'cp_sys_expire_date',
			'duration'    => 'cp_sys_ad_duration',
			'total_cost'  => 'cp_sys_total_ad_cost',
			'price'       => 'cp_price',
			'street'      => 'cp_street',
			'city'        => 'cp_city',
			'zipcode'     => 'cp_zipcode',
			'state'       => 'cp_state',
			'country'     => 'cp_country'
		),

		'attachments' => true

	);

	$args = apply_filters( 'cp_csv_importer_args', $args );

	appthemes_add_instance( array( 'CP_Importer' => array( APP_POST_TYPE, $fields, $args ) ) );
}
add_action( 'wp_loaded', 'cp_csv_importer' );
