<?php
/**
 * Theme specific widgets.
 *
 * @package ClassiPress\Widgets
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */

add_action( 'widgets_init', 'cp_unregister_widgets', 11 );
add_action( 'widgets_init', 'cp_widgets_init' );

/**
 * Widget to show all ad categories.
 * @since 3.3
 */
class CP_Widget_Ad_Categories extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base' => 'widget-ad-categories',
			'name' => __( 'ClassiPress Ad Categories', APP_TD ),
			'defaults' => array(
				'title' => __( 'Ad Categories', APP_TD ),
				'number' => '0',
				'show_count' => '0',
			),
			'widget_ops' => array(
				'description' => __( 'Display the ad categories.', APP_TD ),
				'classname' => 'widget-ad-categories',
			),
			'control_options' => array(),
		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) ) {
 			$number = null;
 		}
		$show_count = ! empty( $instance['show_count'] ) ? '1' : '0';

		$cat_args = array(
			'orderby'            => 'name',
			'order'              => 'ASC',
			'hierarchical'       => 1,
			'show_count'         => $show_count,
			'use_desc_for_title' => 0,
			'hide_empty'         => 0,
			'depth'              => 1,
			'number'             => null,
			'title_li'           => '',
			'taxonomy'           => APP_TAX_CAT,
			'cp_number'          => $number,
		);

		echo '<div class="recordfromblog"><ul>';

		add_filter( 'get_terms', 'cp_filter_limit_number_of_categories', 10, 3 );

		wp_list_categories( $cat_args );

		remove_filter( 'get_terms', 'cp_filter_limit_number_of_categories', 10, 3 );

		echo '</ul></div>';

	}

	protected function form_fields() {
		$form_fields = array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type' => 'text',
				'name' => 'number',
				'desc' => __( 'Number of categories to show (0 for all):', APP_TD ),
				'extra' => array( 'size' => 3 ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'show_count',
				'desc' => __( 'Show ads counts', APP_TD ),
			),
		);

		return $form_fields;
	}

}


/**
 * Widget to show all ad sub-categories.
 * @since 3.5
 */
class CP_Widget_Ad_Sub_Categories extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base'  => 'widget-ad-sub-categories',
			'name'     => __( 'ClassiPress Ad Sub Categories', APP_TD ),
			'defaults' => array(
				'title'      => __( 'Sub-Categories', APP_TD ),
				'number'     => '0',
				'show_count' => '0',
			),
			'widget_ops' => array(
				'description' => __( 'Display sub-categories on a category page.', APP_TD ),
				'classname'   => 'widget-ad-categories',
			),
			'control_options' => array(),
		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	/**
	 * Only display widget on ad category pages.
	 *
	 * note: cannot be used with 'condition()' since it's too soon for the conditional tag.
	 */
	public function widget( $args, $instance ) {

		if ( ! is_tax( APP_TAX_CAT ) ) {
			return;
		}

		parent::widget( $args, $instance );
	}

	public function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) ) {
 			$number = null;
 		}
		$show_count = ! empty( $instance['show_count'] ) ? '1' : '0';
		$use_dropdown = ! empty( $instance['use_dropdown'] );

		// go get the taxonomy category id so we can filter with it
		// have to use slug instead of name otherwise it'll break with multi-word cats
		if ( ! isset( $filter ) ) {
			$filter = '';
		}

		$ad_cat_array = get_term_by( 'slug', get_query_var( APP_TAX_CAT ), APP_TAX_CAT, ARRAY_A, $filter );

		// show all subcategories if any
		$args = array(
			'hide_empty'       => false,
			'show_count'       => $show_count,
			'title_li'         => '',
			'echo'             => false,
			'taxonomy'         => APP_TAX_CAT,
			'depth'            => 1,
			'child_of'         => $ad_cat_array['term_id'],
			'number'           => $number,
			'class'            => 'postform cat-dropdownlist',
			'value_field'      => 'slug',
			'show_option_none' => $use_dropdown ? __( 'Any', APP_TD )  : false,
			'hierarchical'     => true,
			'hide_if_empty'    => true
		);

		$js_submit = '';

		if ( $use_dropdown ) {
			$subcats = wp_dropdown_categories( $args );

			ob_start();
?>
			<script type="text/javascript">
				<!--
				var dropdown = document.getElementById("cat");
				function onCatChange() {
					if ( dropdown.options[dropdown.selectedIndex].value !== '' ) {
						location.href = document.URL+'/'+dropdown.options[dropdown.selectedIndex].value;
					}
				}
				dropdown.onchange = onCatChange;
				-->
			</script>
<?php
			$js_submit = ob_get_clean();
		} else {
			$subcats = wp_list_categories( $args );
		}

		if ( ! empty( $subcats ) ) {
			echo html( 'ul', print_r( $subcats, true ) );
			echo $js_submit;
		} else {
			echo html( 'p', __( 'No sub-categories available', APP_TD ) );
		}

	}

	protected function form_fields() {
		$form_fields = array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type' => 'text',
				'name' => 'number',
				'desc' => __( 'Number of sub-categories to show (0 for all):', APP_TD ),
				'extra' => array( 'size' => 3 ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'use_dropdown',
				'desc' => __( 'Use Dropdown', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'show_count',
				'desc' => __( 'Show ads counts', APP_TD ),
			),
		);

		return $form_fields;
	}

	function form( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		$output = '';
		foreach ( $this->form_fields() as $field ) {
			$output .= html( 'p', $this->input( $field, $instance ) );
		}
		$output .= html( 'p', __( '<strong>Note:</strong> This widget is only displayed on category pages.', APP_TD ) );

		echo $output;
	}

}


/**
 * Callback function to limit number of categories.
 *
 * @param array $terms
 * @param array $taxonomies
 * @param array $args
 *
 * @return array
 */
function cp_filter_limit_number_of_categories( $terms, $taxonomies, $args ) {
	if ( ! isset( $args['cp_number'] ) || is_null( $args['cp_number'] ) ) {
		return $terms;
	}

	$i = 0;
	$number = absint( $args['cp_number'] );
	foreach ( (array) $terms as $key => $term ) {
		if ( $i >= $number || $term->parent != 0 ) {
			unset( $terms[ $key ] );
			continue;
		}
		$i++;
	}
	return $terms;
}


/**
 * Displays search form for the search widget.
 *
 * return void
 */
function cp_ad_search_widget() {
	$args = cp_get_dropdown_categories_search_args( 'widget' );
?>

	<div class="recordfromblog">

		<form action="<?php echo home_url( '/' ); ?>" method="get" id="searchform" class="form_search">

			<input name="s" type="text" id="s" class="editbox_search" value="<?php the_search_query(); ?>" placeholder="<?php esc_attr_e( 'What are you looking for?', APP_TD ); ?>" />

			<?php wp_dropdown_categories( $args ); ?>
			<div class="pad5"></div>
			<input type="submit" class="btn_orange" value="<?php _e( 'Search', APP_TD ); ?>" title="<?php _e( 'Search', APP_TD ); ?>" id="go" name="sa" />
		</form>

	</div><!-- /recordfromblog -->

<?php
}


/**
 * Filter by City Widget.
 * Not used.
 */
function cp_ad_region_widget() {
	global $wpdb;
?>
	<div class="shadowblock_out">

		<div class="shadowblock">

			<h2 class="dotted"><?php _e( 'Filter by City', APP_TD ); ?></h2>

			<div class="recordfromblog">

				<ul>
					<?php

						//$all_custom_fields = get_post_custom($post->ID);

						// get all the custom field labels so we can match the field_name up against the post_meta keys
						$sql = "SELECT field_values FROM $wpdb->cp_ad_fields f WHERE f.field_name = 'cp_city'";

						//$results = $wpdb->get_results($sql);
						$results = $wpdb->get_row( $sql );


						if ( $results ) {
					?>

							<a href="?region=all"><?php _e( 'All', APP_TD ); ?></a> /
							<?php
								$options = explode( ',', $results->field_values );

								foreach ( $options as $option ) {
							?>
									<a href="?region=<?php echo $option; ?>"><?php echo $option; ?></a> /
							<?php
								}

						} else {

							_e( 'No cities found.', APP_TD );

						}
					?>
				</ul>

			</div><!-- /recordfromblog -->

		</div><!-- /shadowblock -->

	</div><!-- /shadowblock_out -->

<?php
}


/**
 * Widget 125 Ads
 */
class CP_Widget_125_Ads extends APP_Widget_125_Ads {

	public static $ads = '';

	public function __construct() {
		$args = array(
			'id_base' => 'cp_125_ads',
			'defaults' => array(
				'style_url' => false,
			),
		);

		parent::__construct( $args );

		self::$ads = $this->defaults['ads'];
	}

}


/**
 * Facebook like box widget
 */
class CP_Widget_Facebook extends APP_Widget_Facebook {

	public function __construct() {
		$args = array(
			'id_base' => 'cp_facebook_like',
		);

		parent::__construct( $args );
	}

}


/**
 * Recent Blog Posts Widget.
 */
class CP_Widget_Blog_Posts extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base'  => 'cp_recent_posts',
			'name'     => __( 'ClassiPress Recent Blog Posts', APP_TD ),
			'defaults' => array(
				'title' => __( 'From the Blog', APP_TD ),
				'count' => 5,
			),
			'widget_ops' => array(
				'description' => __( 'Your most recent blog posts.', APP_TD ),
			),
			'control_options' => array(),
		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		if ( empty( $instance['count'] ) || ! $count = absint( $instance['count'] ) ) {
 			$count = 5;
 		}

		// include the main blog loop
		appthemes_load_template( 'includes/sidebar-blog-posts.php', array( 'count' => $count ) );

	}

	protected function form_fields() {
		$form_fields = array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type'  => 'text',
				'name'  => 'count',
				'desc'  => __( 'Number of posts to show:', APP_TD ),
				'extra' => array( 'size' => 3 ),
			),
		);

		return $form_fields;
	}

}


/**
 * Ads Search Widget.
 */
class CP_Widget_Search extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base'  => 'ad_search',
			'name'     => __( 'ClassiPress Ad Search Box', APP_TD ),
			'defaults' => array(
				'title' => __( 'Search Classified Ads', APP_TD ),
			),
			'widget_ops' => array(
				'description' => __( 'Your sidebar ad search box.', APP_TD ),
			),
			'control_options' => array(),
		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {

		cp_ad_search_widget();

	}

}


/**
 * Today's Popular Ads Widget.
 */
class CP_Widget_Top_Ads_Today extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base' => 'top_ads',
			'name'    => __( 'ClassiPress Top Ads Today', APP_TD ),
			'defaults' => array(
				'title'  => __( 'Popular Ads Today', APP_TD ),
				'number' => 10,
			),
			'widget_ops' => array(
				'description' => __( 'Display the top ads today.', APP_TD ),
				'classname'   => 'widget-top-ads-today',
			),
			'control_options' => array(),
		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) ) {
 			$number = 10;
 		}

		cp_todays_count_widget( APP_POST_TYPE, $number );

	}

	protected function form_fields() {
		$form_fields = array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type' => 'text',
				'name' => 'number',
				'desc' => __( 'Number of ads to show:', APP_TD ),
				'extra' => array( 'size' => 3 ),
			),
		);

		return $form_fields;
	}

}


/**
 * Overall Popular Ads Widget.
 */
class CP_Widget_Top_Ads_Overall extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base' => 'top_ads_overall',
			'name'    => __( 'ClassiPress Top Ads Overall', APP_TD ),
			'defaults' => array(
				'title'  => __( 'Popular Ads Overall', APP_TD ),
				'number' => 10,
			),
			'widget_ops' => array(
				'description' => __( 'Display the top ads overall.', APP_TD ),
				'classname'   => 'widget-top-ads-overall',
			),
			'control_options' => array(),
		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) ) {
 			$number = 10;
 		}

		cp_todays_overall_count_widget( APP_POST_TYPE, $number );

	}

	protected function form_fields() {
		$form_fields = array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type' => 'text',
				'name' => 'number',
				'desc' => __( 'Number of ads to show:', APP_TD ),
				'extra' => array( 'size' => 3 ),
			),
		);

		return $form_fields;
	}

}


/**
 * Ad tags and categories cloud widget.
 */
class CP_Widget_Ads_Tag_Cloud extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base' => 'ad_tag_cloud',
			'name'    => __( 'ClassiPress Ads Tag Cloud', APP_TD ),
			'defaults' => array(
				'title'    => __( 'Ad Tags', APP_TD ),
				'taxonomy' => APP_TAX_TAG,
				'number'   => 45,
			),
			'widget_ops' => array(
				'description' => __( 'Your most used ad tags in cloud format.', APP_TD ),
				'classname'   => 'widget_tag_cloud',
			),
			'control_options' => array(),
		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) ) {
 			$number = 45;
 		}

		$current_taxonomy = ( empty( $instance['taxonomy'] ) || ! taxonomy_exists( $instance['taxonomy'] ) ) ? APP_TAX_TAG : $instance['taxonomy'];

		echo '<div>';
		wp_tag_cloud( apply_filters( 'widget_tag_cloud_args', array( 'taxonomy' => $current_taxonomy, 'number' => $number ) ) );
		echo "</div>\n";

	}

	protected function form_fields() {
		$form_fields = array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type'   => 'select',
				'name'   => 'taxonomy',
				'desc'   => __( 'Taxonomy:', APP_TD ),
				'values' => $this->get_taxonomy_options(),
			),
			array(
				'type'  => 'text',
				'name'  => 'number',
				'desc'  => __( 'Number of items to show:', APP_TD ),
				'extra' => array( 'size' => 3 ),
			),
		);

		return $form_fields;
	}

	protected function get_taxonomy_options() {
		$options = array();
		$taxonomies = get_object_taxonomies( APP_POST_TYPE );

		foreach ( $taxonomies as $taxonomy ) {
			$tax_obj = get_taxonomy( $taxonomy );
			if ( ! $tax_obj->show_tagcloud || empty( $tax_obj->labels->name ) ) {
				continue;
			}
			$options[ $taxonomy ] = $tax_obj->labels->name;
		}

		return $options;
	}

}


/**
 * Widget displays featured ads.
 * @since 3.3
 */
class CP_Widget_Featured_Ads extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base'  => 'widget-featured-ads',
			'name'     => __( 'ClassiPress Featured Ads', APP_TD ),
			'defaults' => array(
				'title'  => __( 'Featured Ads', APP_TD ),
				'number' => 10,
			),
			'widget_ops' => array(
				'description' => __( 'Display the featured ads.', APP_TD ),
				'classname'   => 'widget-featured-ads',
			),
			'control_options' => array(),
		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) ) {
 			$number = 10;
 		}

		$ads_args = array(
			'post__in'       => get_option( 'sticky_posts' ),
			'post_type'      => APP_POST_TYPE,
			'posts_per_page' => $number,
			'orderby'        => 'rand',
			'no_found_rows'  => true,
		);

		$featured_ads = new WP_Query( $ads_args );
		$result = '';

		if ( $featured_ads->have_posts() ) {
			$result .= '<ul>';
			while ( $featured_ads->have_posts() ) {
				$featured_ads->the_post();
				$result .= '<li><a href="' . get_permalink( get_the_ID() ) . '">' . get_the_title() . '</a></li>';
			}
			$result .= '</ul>';
		}

		wp_reset_postdata();

		echo $result;

	}

	protected function form_fields() {
		$form_fields = array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type'  => 'text',
				'name'  => 'number',
				'desc'  => __( 'Number of ads to show:', APP_TD ),
				'extra' => array( 'size' => 3 ),
			),
		);

		return $form_fields;
	}

}


/**
 * Widget displays ads marked as sold.
 * @since 3.3
 */
class CP_Widget_Sold_Ads extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base'  => 'widget-sold-ads',
			'name'     => __( 'ClassiPress Sold Ads', APP_TD ),
			'defaults' => array(
				'title'  => __( 'Sold Ads', APP_TD ),
				'number' => 10,
			),
			'widget_ops' => array(
				'description' => __( 'Display the ads marked as sold.', APP_TD ),
				'classname'   => 'widget-sold-ads',
			),
			'control_options' => array(),
		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) ) {
 			$number = 10;
 		}

		$ads_args = array(
			'post_type'      => APP_POST_TYPE,
			'posts_per_page' => $number,
			'meta_query'     => array(
				array(
					'key'     => 'cp_ad_sold',
					'value'   => 'yes',
					'compare' => '=',
				),
			),
			'no_found_rows' => true,
		);

		$sold_ads = new WP_Query( $ads_args );
		$result = '';

		if ( $sold_ads->have_posts() ) {
			$result .= '<ul>';
			while ( $sold_ads->have_posts() ) {
				$sold_ads->the_post();
				$result .= '<li><a href="' . get_permalink( get_the_ID() ) . '">' . get_the_title() . '</a></li>';
			}
			$result .= '</ul>';
		}

		wp_reset_postdata();

		echo $result;

	}

	protected function form_fields() {
		$form_fields = array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type'  => 'text',
				'name'  => 'number',
				'desc'  => __( 'Number of ads to show:', APP_TD ),
				'extra' => array( 'size' => 3 ),
			),
		);

		return $form_fields;
	}

}


/**
 * Registers the custom sidebar widgets.
 *
 * @return void
 */
function cp_widgets_init() {

	// widgets registered via APP_Widget::_register_widget()

}


/**
 * Removes some of the default sidebar widgets.
 *
 * @return void
 */
function cp_unregister_widgets() {
	unregister_widget( 'WP_Widget_Search' );
	unregister_widget( 'P2P_Widget' );
}
