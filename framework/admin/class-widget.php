<?php
/**
 * AppThemes Widget
 *
 * The base for common theme widgets.
 *
 * Intended for:
 * - Create multiple instanses of same widget class with differ parameters;
 * - Load specific widget scripts and styles only on pages, where widget active;
 * - Add compatibility methods between WP_Widget and scbForms
 *
 * @package Framework\Widgets
 */
class APP_Widget extends scbWidget {

	/**
	 * All registered scripts in all instances.
	 * Key is script handle, value is boolean:
	 * true if it should be printed, false otherwise
	 * @var array
	 */
	protected static $scripts = array();

	/**
	 * All registered styles in all instances.
	 * @var array
	 */
	protected static $styles = array();

	/**
	 * Instance related scripts to be enqueued in footer.
	 * @var array
	 */
	protected $footer_scripts = array();

	public function __construct( $id_base, $name, $widget_options = array(), $control_options = array(), $defaults = array() ) {

		$this->defaults = $defaults;
		parent::__construct( $id_base, $name, $widget_options, $control_options );

		if ( ! did_action( 'widgets_init' ) ) {
			add_action( 'widgets_init', array( &$this, '_register_widget' ), 101 );
		} else {
			$this->_register_widget();
		}

		if ( is_active_widget( false, false, $this->id_base ) ) {
			add_action( 'wp_enqueue_scripts', array( &$this, 'register_scripts' ) );
		}


		self::init();

	}

	static function init( $class = null, $file = '', $base = '' ) {
		// Adding actions, which will runs once for all instances
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 11 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'remove_scripts' ), 9 );
	}

	public function _register_widget() {
		global $wp_registered_widgets;

		$registered = array_keys( $wp_registered_widgets );
		$registered = array_map( '_get_widget_id_base', $registered );

		// don't register new widget if old widget with the same id is already registered
		if ( in_array( $this->id_base, $registered, true ) ) {
			return;
		}

		if ( ! $this->condition() ) {
			return;
		}

		$this->_register();
	}

	/**
	 * Additional checks before registering the widget.
	 *
	 * @return bool
	 */
	protected function condition() {
		return true;
	}

	/**
	 * Use this method to register scripts and styles with provided wrapper-methods
	 * register_script() and register_style().
	 *
	 * Files will automaticly enqueued through method enqueue_scripts() in condition,
	 * that widget is active.
	 *
	 * If you want load scripts only on page where widget shown - use parameter $in_footer = true
	 * in the register_script() method.
	 *
	 * If you don't want use internal wrapper-methods for loading files - use regular WP enqueue functions
	 * in this method.
	 *
	 * By default, loads one script and one style if appropriate urls were specified in 'defaults' parameter.
	 */
	public function register_scripts() {
		list( $args ) = get_theme_support( 'app-versions' );

		if ( isset( $this->defaults['style_url'] ) && ! empty( $this->defaults['style_url'] ) ) {
			$this->register_style( $this->id_base, $this->defaults['style_url'], array(), $args['current_version'] );
		}

		if ( isset( $this->defaults['script_url'] ) && ! empty( $this->defaults['script_url'] ) ) {
			$this->register_script( $this->id_base, $this->defaults['script_url'], array(), $args['current_version'], true );
		}
	}

	/**
	 * Wrapper for wp_register_script() function.
	 * If script enqueued in footer - instance has chance disable enqueue on pages where widget not prints.
	 * Use this chance.
	 */
	final protected function register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
		wp_register_script( $handle, $src, $deps, $ver, $in_footer );
		self::$scripts[ $handle ] = ! $in_footer;
		if ( $in_footer ) {
			$this->footer_scripts[] = $handle;
		}
	}

	/**
	 * Wrapper for wp_register_style() function.
	 */
	final protected function register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
		wp_register_style( $handle, $src, $deps, $ver, $media );
		self::$styles[ $handle ] = $handle;
	}

	/**
	 * Registers styles and scripts only if widget active
	 */
	public static function enqueue_scripts() {
		foreach ( self::$styles as $handle ) {
			wp_enqueue_style( $handle );
		}
		foreach ( array_keys( self::$scripts ) as $handle ) {
			wp_enqueue_script( $handle );
		}
	}

	/**
	 * Removes unused scripts.
	 * If script enqueued in footer and not used in printed widgets - script will dequeued.
	 */
	public static function remove_scripts() {
		foreach ( self::$scripts as $script => $keep ) {
			if ( ! $keep ) {
				wp_deregister_script( $script );
				wp_dequeue_script( $script );
			}
		}
	}

	/**
	 * Displays widget content.
	 *
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget.
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {
		parent::widget( $args, $instance );
		foreach ( $this->footer_scripts as $handle ) {
			self::$scripts[ $handle ] = true;
		}
	}

	/**
	 * Validates and updates widget settings.
	 *
	 * @param array $new_instance New settings for this instance.
	 * @param array $old_instance Old settings for this instance.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {

		$form_fields = $this->form_fields();

		$to_update = scbForms::validate_post_data( $form_fields, $new_instance, $old_instance );

		return $to_update;
	}

	/**
	 * Displays widget settings form.
	 *
	 * @param array $instance Current settings.
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		$output = '';
		foreach ( $this->form_fields() as $field ) {
			$output .= html( 'p', $this->input( $field, $instance ) );
		}
		echo $output;
	}

	protected function form_fields() {
		return array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			)
		);
	}

	/**
	 * Returns ID of sidebar into which widget is inserted.
	 *
	 * @return string|bool A sidebar ID. Boolean False otherwise.
	 */
	protected function get_sidebar_id() {
		global $sidebars_widgets;

		if ( $this->id === false ) {
			_doing_it_wrong( __METHOD__, __( 'Sidebar ID can not be checked before the widget ID is set.', APP_TD ), null );
			return false;
		}

		if ( empty( $sidebars_widgets ) || ! is_array( $sidebars_widgets ) ) {
			return false;
		}

		foreach ( $sidebars_widgets as $sidebar_id => $sidebar_widget_ids ) {
			if ( empty( $sidebar_widget_ids ) || ! is_array( $sidebar_widget_ids ) ) {
				continue;
			}

			if ( in_array( $this->id, $sidebar_widget_ids ) ) {
				return $sidebar_id;
			}
		}

		return false;
	}

	protected function _array_merge_recursive( $array1, $array2 ) {
		if ( ! is_array( $array1 ) || ! is_array( $array2 ) ) {
			return $array2;
		}

		foreach ( $array2 as $key2 => $value2 ) {
			$array1[ $key2 ] = $this->_array_merge_recursive( @$array1[ $key2 ], $value2 );
		}

		return $array1;
	}

}
