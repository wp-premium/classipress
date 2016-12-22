<?php
/**
 * Social Connect Widget
 *
 * Shows a list of clickable social media icons.
 *
 * Options:
 * - `title`                    - Widget title
 * - `descriptions_as_tooltips` - Uses social networks descriptions as tooltips
 *
 *  Template options (if `template` supports them):
 * - `template`       - Social connect content template name. Tries locate template with this name,
 *                      otherwise tries to load template with name 'content-social-connect.php',
 *                      otherwise will load supplied own template with name 'content-social-connect.php'
 *
 * Dynamic list of social links (uses `APP_Social_Networks`)
 * - `social_[network_id]_inc` - Show social media button for this network
 * - `social_[network_id]_url` - URL for this network
 * - `social_[network_id]_desc`- Description/tooltip for this network
 *
 * Internal options:
 * - `images_url`     - Icon images folder URL with trailing slash (default is empty - uses foundicons svgs iconset by default)
 *                      Note: icons or images need to match the 'APP_Social_Networks' network id slugs
 * - `social_networks`- List of social networks slugs to include (default is empty - shows all social networks from `APP_Social_Networks` by default)
 * - `exclude_mode`	  - If set to true, excludes all social networks set in `social_networks`param (default is false)
 *
 * @package Components\Widgets
 *
 */
class APP_Widget_Social_Connect extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base' => 'appthemes_social_connect',
			'name' => __( 'AppThemes Social Connect', APP_TD ),
			'defaults' => array(
				'title' => __( 'Social Connect', APP_TD ),
				'template' => 'teste',
				// Internal custom options
				'images_url' => '',
				'social_networks' => '',
				'exclude_mode' => false,
			),
			'widget_ops' => array(
				'description' => __( 'A set of icons to link to many social networks.', APP_TD ),
			),
			'control_options' => array(),

		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		$title = $instance['title'];

		$template_path = array(
			$instance[ 'template' ],
			'content-social-connect.php',
		);

		$template_path = locate_template( $template_path );

		if ( ! $template_path ) {
			$template_path = dirname( __FILE__ ) . '/templates/content-social-connect.php';
		}

		require $template_path;
	}

	function form_fields() {

		$params = $this->defaults;

		$fields = array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type' => 'checkbox',
				'name' => 'use_tooltips',
				'desc' => __( 'Use descriptions as tooltips (hides descriptions)', APP_TD )
			),
		);

		$social_networks = APP_Social_Networks::get_support();

		if ( ! empty( $params['social_networks'] ) ) {

			if ( ! $params['exclude_mode'] ) {
				$social_networks = $params['social_networks'];
			} else {
				$social_networks = array_diff( $social_networks,  $params['social_networks'] );
			}

		}

		foreach( $social_networks as $network_id ) {

			$fields[] = array(
				'type' => 'checkbox',
				'name' => "social_{$network_id }_inc",
				'desc' => sprintf( __( 'Show %s button', APP_TD ), APP_Social_Networks::get_title( $network_id ) ),
			);
			$fields[] = array(
				'type' => 'text',
				'name' => "social_{$network_id }_url",
				'desc' => sprintf( __( '%s URL', APP_TD ), APP_Social_Networks::get_title( $network_id ) ),
			);
			$fields[] = array(
				'type' => 'text',
				'name' => "social_{$network_id }_desc",
				'desc' => sprintf( __( '%s Description', APP_TD ), APP_Social_Networks::get_title( $network_id ) ),
			);

		}

		return $fields;
	}

}
