<?php
/**
 * Taxonomy list widget
 *
 * @package Components\Widgets
 */
class APP_Widget_Taxonomy_List extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base' => 'appthemes_taxonomy_list',
			'name' => __( 'AppThemes Taxonomy List', APP_TD ),
			'defaults' => array(
				'title' => __( 'Taxonomy Terms', APP_TD ),
				// $defaults:
				'menu_cols' => 2,
				'menu_depth' => 3,
				'menu_sub_num' => 3,
				'cat_parent_count' => false,
				'cat_child_count' => false,
				'cat_hide_empty' => false,
				'cat_nocatstext' => false,
				'taxonomy' => 'category',

				// $terms_defaults:
				//'hide_empty' => false,
				//'hierarchical' => true,
				//'pad_counts' => true,
				//'show_count' => true,
				//'orderby' => 'name',
				//'order' => 'ASC',

				// Other:
				'archive_responsive' => false,
				'style_url' => get_template_directory_uri() . '/includes/widgets/styles/widget-taxonomy-list.css',
			),
			'widget_ops' => array(
				'description' => __( 'Displays the list of selected taxonomy terms', APP_TD ),
				'classname' => 'widget-taxonomy-list'
			),
			'control_options' => array(),

		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );
		$terms_defaults = array();

		if ( is_tax( $instance['taxonomy'] ) && true == $instance['archive_responsive'] ) {
			$terms_defaults['child_of'] = get_queried_object_id();
		}
		echo appthemes_categories_list( $instance, $terms_defaults );
	}


	protected function form_fields() {

		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

		$choices = array();
		foreach ( $taxonomies as $key => $taxonomy ) {
			$choices[ $key ] = $taxonomy->labels->name;
		}

		return array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type'	 => 'select',
				'name'	 => 'taxonomy',
				'choices'=> $choices,
				'extra'	 => array( 'class' => 'widefat' ),
				'desc'	 => __( 'Select Taxonomy:', APP_TD )
			),
			array(
				'type' => 'text',
				'name' => 'menu_cols',
				'desc' => __( 'Number of Columns:', APP_TD ),
			),
			array(
				'type' => 'text',
				'name' => 'menu_depth',
				'desc' => __( 'Menu Depth:', APP_TD ),
			),
			array(
				'type' => 'text',
				'name' => 'menu_sub_num',
				'desc' => __( 'Number of Sub Menus:', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'cat_parent_count',
				'desc' => __( 'Show Parent Count', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'cat_child_count',
				'desc' => __( 'Show Childs Count', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'cat_hide_empty',
				'desc' => __( 'Hide Empty', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'cat_nocatstext',
				'desc' => __( 'Hide "No categories" text', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'archive_responsive',
				'desc' => __( 'Show Only List of Childs on Taxonomy Acrhive', APP_TD ),
			),
		);
	}
}