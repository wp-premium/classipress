<?php
/**
 * Widget 125 Ads
 *
 * @package Components\Widgets
 */
class APP_Widget_125_Ads extends APP_Widget {

	public function __construct( $args = array() ) {

		$images_url = ( isset( $args['defaults'] ) && isset( $args['defaults']['images_url'] ) ) ? $args['defaults']['images_url'] : get_template_directory_uri() . '/includes/widgets/images/';

		$default_args = array(
			'id_base' => 'appthemes_125_ads',
			'name' => __( 'AppThemes 125x125 Ads', APP_TD ),
			'defaults' => array(
				'title' => __( 'Sponsored Ads', APP_TD ),
				'newin' => false,
				'ads' => "https://www.appthemes.com|" . $images_url . "ad125a.gif|Ad 1|nofollow\n"
						."https://www.appthemes.com|" . $images_url . "ad125b.gif|Ad 2|follow\n"
						."https://www.appthemes.com|" . $images_url . "ad125a.gif|Ad 3|nofollow\n"
						."https://www.appthemes.com|" . $images_url . "ad125b.gif|Ad 4|follow",
				// Internal custom options
				'style_url' => get_template_directory_uri() . '/includes/widgets/styles/widget-125-ads.css',
				// 'script_url' => '',
				'images_url' => $images_url,
			),
			'widget_ops' => array(
				'description' => __( 'Places an ad space in the sidebar for 125x125 ads', APP_TD ),
				'classname' => 'widget-125-ads'
			),
			'control_options' => array(
				'width' => 500,
				'height' => 350
			),

		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		// separate the ad line items into an array
		$ads = explode("\n", $instance['ads']);

		if ( sizeof( $ads ) <= 0 )
			return false;

		$newin = ( $instance['newin'] ) ? '_blank' : '_self';
		$alt = 1;
		$output = '';

		foreach ( $ads as $ad ) {

			if ( ! $ad || ! strstr( $ad, '|' ) )
				continue;

			$alt = $alt*-1;
			$this_ad = explode( '|', $ad );

			$li_class = ( $alt == 1 ) ? array( 'class' => 'alt' ) : array();

			$output .= html( 'li', $li_class,
				html( 'a', array( 'href' => $this_ad[0], 'rel' => $this_ad[3], 'target' => $newin ),
					html( 'img', array( 'src' => $this_ad[1], 'width' => 125, 'height' => 125, 'alt' => $this_ad[2] ) )
				)
			);
		}

		echo html( 'ul class="ads"', $output );
	}

	protected function form_fields() {
		return array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type' => 'textarea',
				'class'=> 'widefat',
				'extra'=> "style='width: 100%' rows='16'",
				'name' => 'ads',
				'desc' => __( 'Ads:', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'newin',
				'desc' => __( 'Open ads in a new window?:', APP_TD )
			),
		);
	}

	function form( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		$output = '';
		foreach ( $this->form_fields() as $field ) {

			if ( 'newin' == $field['name'] ) {
				break;
			}

			$output .= html( 'p', $this->input( $field, $instance ) );
		}

		$output .= html( 'p', __( 'Enter one ad entry per line in the following format:', APP_TD ) . " " . html( 'code', __( 'URL|Image URL|Image Alt Text|rel', APP_TD ) ) );
		$output .= html( 'p', __( '<strong>Note:</strong> You must hit your &quot;enter/return&quot; key after each ad entry otherwise the ads will not display properly.', APP_TD ) );

		$output .= html( 'p', $this->input( $field, $instance ) );

		echo $output;
	}

}