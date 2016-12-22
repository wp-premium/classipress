<?php
/**
 * Widget Breadcrumbs
 *
 * @package Components\Widgets
 */
class APP_Widget_Breadcrumbs extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base' => 'appthemes_breadcrumbs',
			'name' => __( 'AppThemes Breadcrumbs', APP_TD ),
			'defaults' => array(
				//'container'       => 'div',
				'separator'       => '&#47;',
				//'before'          => '',
				//'after'           => '',
				//'show_on_front'   => true,
				'network'         => false,
				//'show_edit_link'  => false,
				'show_title'      => false,
				'show_browse'     => false,
				//'echo'            => true,

				/* Post taxonomy (examples follow). */
				//'post_taxonomy' => array(
					// 'post'  => 'post_tag',
					// 'book'  => 'genre',
				//),

				/* Labels for text used (see Breadcrumb_Trail::default_labels). */
				//'labels' => array(),
			),
			'widget_ops' => array(
				'description' => __( 'Displays the Breadcrumbs', APP_TD ),
				'classname' => 'widget-breadcrumbs'
			),
			'control_options' => array(),

		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {
		$instance['network'] = (boolean) $instance['network'];
		$instance['show_title'] = (boolean) $instance['show_title'];
		$instance['show_browse'] = (boolean) $instance['show_browse'];
		breadcrumb_trail( $instance );
	}


	protected function form_fields() {
		return array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type' => 'text',
				'name' => 'separator',
				'desc' => __( 'Crumbs Separator:', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'network',
				'desc' => __( 'Network', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'show_title',
				'desc' => __( 'Show Title', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'show_browse',
				'desc' => __( 'Show Browse', APP_TD ),
			),
		);
	}
}