<?php
/**
 * Customizable Post Loops widget
 *
 * Displays posts of selected type with large number of options.
 * Each post wrapped with its own 'widget' wrapper, what allows apply widget styles separately.
 * Usefull for horizontal widgets areas, where each widget placed in own cell in row.
 *
 * Options:
 * - `title`          - Widget title
 *
 * Query posts parameters:
 * - `post_type`      - Select one of public post types (post, page, CPTs)
 * - `number`         - Number of posts to display
 * - `post__in`       - IDs of posts to display
 * - `sticky`         - Display only sticky posts (if post type supports)
 *
 * Template options (if `template` supports them):
 * - `template`       - Post content template name. Tries locate template with this name,
 *                      otherwise tries to load template with name 'content-recent-posts.php',
 *                      otherwise will load supplied own template with name 'content-recent-posts.php'
 * - `show_rating`    - Display Rating (requires "StarStruck" plugin)
 * - `show_date`      - Display post date
 * - `show_thumbnail` - Display post thumbnail
 * - `show_readmore`  - Display 'Read More' link
 *
 * Internal options:
 * - `images_url`     - Images folder URL with trailing slash
 * - `style_url`      - URL to widget styles file (not used by default)
 * - `script_url`     - URL to widget scripts file (not used by default)
 *
 *
 * @package Components\Widgets
 */
class APP_Widget_Recent_Posts extends APP_Widget{

	protected $classname = '';
	protected $args = array();
	protected static $i = 0;

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base' => 'appthemes_recent_posts',
			'name' => __( 'AppThemes Recent Posts', APP_TD ),
			'defaults' => array(
				'title'			 => __( 'Recent Posts', APP_TD ),
				'post_type'		 => 'post',
				'number'		 => 3,
				'post__in'		 => '',
				'sticky'		 => false,
				'show_rating'	 => false,
				'show_date'		 => false,
				'show_thumbnail' => false,
				'show_readmore'	 => false,
				'template'		 => '',
				// Internal custom options
				'style_url' => get_template_directory_uri() . '/includes/widgets/styles/widget-recent-posts.css',
				'images_url' => get_template_directory_uri() . '/includes/widgets/images/',
				// 'style_url' => '',
				// 'script_url' => '',

			),
			'widget_ops' => array(
				'description' => __( 'Show recent posts any public types.', APP_TD ),
				'classname' => 'widget-recent-posts'
			),
			'control_options' => array(),

		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		$this->classname = $widget_ops['classname'];

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );

		add_action( 'save_post', array( $this, 'flush_widget_cache') );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache') );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache') );
	}

	protected function form_fields() {

		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		if ( isset( $post_types['attachment'] ) )
			unset( $post_types['attachment'] );

		$choices = array();
		foreach ( $post_types as $key => $post_type ) {
			$choices[ $key ] = $post_type->labels->name;
		}

		return array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type'	 => 'select',
				'name'	 => 'post_type',
				'choices'=> $choices,
				'extra'	 => array( 'class' => 'widefat' ),
				'desc'	 => __( 'Select Post Type:', APP_TD )
			),
			array(
				'type' => 'text',
				'name' => 'number',
				'desc' => __( 'Number of posts to show:', APP_TD )
			),
			array(
				'type' => 'text',
				'name' => 'post__in',
				'desc' => __( 'Enter posts IDs delimited by comma:', APP_TD )
			),
			array(
				'type'		 => 'text',
				'name'		 => 'template',
				'desc'		 => __( 'Posts content template name:', APP_TD ),
			),
			array(
				'type'		 => 'checkbox',
				'name'		 => 'sticky',
				'desc'		 => __( 'Display only sticky posts (if post type supports):', APP_TD ),
			),
			array(
				'type'		 => 'checkbox',
				'name'		 => 'show_rating',
				'desc'		 => __( 'Display Rating (requires "StarStruck" plugin):', APP_TD ),
			),
			array(
				'type'		 => 'checkbox',
				'name'		 => 'show_readmore',
				'desc'		 => __( 'Display "Read More" button:', APP_TD ),
			),
			array(
				'type'		 => 'checkbox',
				'name'		 => 'show_date',
				'desc'		 => __( 'Display post date:', APP_TD ),
			),
			array(
				'type'		 => 'checkbox',
				'name'		 => 'show_thumbnail',
				'desc'		 => __( 'Display post thumbnail:', APP_TD ),
			),
		);
	}

	function widget( $args, $instance ) {

		$instance = wp_parse_args( $instance, $this->defaults );

		extract( $args );

		echo $before_widget;

		$title = apply_filters( 'widget_title', isset( $instance['title'] ) ? $instance['title'] : '', $instance, $this->id_base );

		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;

		$instance = array_merge( $this->defaults, $instance );
		$cache = wp_cache_get( $this->classname, 'widget');

		if ( !is_array($cache) )
			$cache = array();

		if ( isset( $cache[ $this->id ] ) ) {
			echo $cache[ $this->id ];
			return;
		}

		self::$i = 0;
		$this->args = $args;
		$this->args['widget_title'] = $title;

		$widget_query = $this->query_posts( $instance );

		$template_path = array(
			$instance[ 'template' ],
			'content-recent-posts.php',
		);

		$template_path = locate_template( $template_path );

		if ( ! $template_path )
			$template_path = dirname( __FILE__ ) . '/templates/content-recent-posts.php';

		appthemes_add_template_var( array( 'instance' => $instance ) );

		ob_start();

		if ( $widget_query->have_posts() ) {

			while ( $widget_query->have_posts() ) : $widget_query->the_post();

				$this->sub_widget();

				load_template( $template_path, false );

			endwhile;

		}

		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		$cache[ $this->id ] = ob_get_flush();
		wp_cache_set( $this->classname, $cache, 'widget' );

		echo $after_widget;
	}

	protected function query_posts( $instance ) {
		$q_args = array(
			'post_type'		 => $instance[ 'post_type' ],
			'posts_per_page' => $instance[ 'number' ],
			'no_found_rows'	 => true,
			'post_status'	 => 'publish',
		);

		$post__in = array_map( 'trim', explode( ',', $instance[ 'post__in' ] ) );

		if ( ! empty( $post__in[0] ) )
			$q_args['post__in'] = $post__in;

		if ( $instance['sticky'] )
			$q_args['post__in'] = get_option( 'sticky_posts' );
		else
			$q_args['ignore_sticky_posts'] = true;

		return new WP_Query( $q_args );
	}

	public function sub_widget() {

		self::$i++;
		$padding = ( ! empty( $this->args['widget_title'] ) ) ? '<div class="recent-padding"></div>' : '';

		if ( 1 !== self::$i ) {
			$before_sub_widget = str_replace( $this->id, $this->id . '-' . self::$i, $this->args['before_widget'] );
			echo $this->args['after_widget'] . $before_sub_widget . $padding;
		}
	}

	public function flush_widget_cache() {
		wp_cache_delete( $this->classname, 'widget' );
	}
}