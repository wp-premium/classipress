<?php
/**
 * Create new Fields Section on Edit Profile form.
 *
 * @package Framework\Metaboxes
 */
class APP_User_Meta_Box {

	/**
	 * Metabox ID.
	 * @var string
	 */
	private $id;

	/**
	 * Title.
	 * @var string
	 */
	private $title;

	/**
	 * Page templates.
	 * @var array
	 */
	private $templates;

	/**
	 * User ID.
	 * @var int
	 */
	protected $user_id;

	/**
	 * Priority.
	 * @var int
	 */
	protected $priority;

	/**
	 * Errors.
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Action hooks.
	 * @var array
	 */
	protected $actions = array( 'admin_enqueue_scripts' );

	/**
	 * Initiates Section registration on appropriate pages.
	 *
	 * @param string $id (optional) Section ID
	 * @param string $title (optional) Section Title
	 * @param array $args (optional) Section parameters:
	 * - `templates` - list of templates where current section allowed;
	 * - `priority`	- priority of display section;
	 *
	 * @return void
	 */
	public function __construct( $id = '', $title = '', $args = array() ) {

		$args = wp_parse_args( $args, array(
			'templates' => null,
			'priority'	=> 10,
		) );

		if ( isset( $_POST['user_section_' . $id ] ) && wp_verify_nonce( $_POST['user_section_' . $id ], 'user_section_update' ) ) {
			add_action( 'personal_options_update', array( $this, 'save' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save' ) );
			add_action( 'user_profile_update_errors', array( $this, 'update_errors' ) );
		}

		if ( is_admin() ) {
			add_action( 'load-profile.php', array( $this, 'register' ) );
			add_action( 'load-user-edit.php', array( $this, 'register' ) );

		// for front-end forms
		} elseif ( $args['templates'] ) {
			$this->templates = (array) $args['templates'];
			add_action( 'template_redirect', array( $this, 'frontend_register' ) );

		} else {
			return false;
		}

		$this->id = $id;
		$this->title = $title;
		$this->priority = $args['priority'];
	}

	/**
	 * Registers the metabox on the frontend.
	 * Checks current page template name whether to show current field section.
	 *
	 * @return void
	 */
	final public function frontend_register() {

		if ( ! is_page() ) {
			return;
		}

		$current_template = basename( get_page_template() );

		if ( in_array( $current_template, $this->templates ) ) {
			$this->register();
		}
	}

	/**
	 * Registers the metabox.
	 *
	 * @return void
	 */
	final public function register() {

		if ( ! $this->condition() ) {
			return;
		}

		add_action( 'show_user_profile', array( $this, 'display' ), $this->priority );
		add_action( 'edit_user_profile', array( $this, 'display' ), $this->priority );
		add_action( 'show_user_profile', array( $this, 'nonce' ), $this->priority );
		add_action( 'edit_user_profile', array( $this, 'nonce' ), $this->priority );

		foreach ( $this->actions as $action ) {
			if ( method_exists( $this, $action ) ) {
				add_action( $action, array( $this, $action ) );
			}
		}
	}

	/**
	 * Additional checks before registering the metabox.
	 *
	 * @return bool
	 */
	protected function condition() {
		return true;
	}

	/**
	 * Filter data before display.
	 *
	 * @param array $form_data
	 * @param object $user
	 *
	 * @return array
	 */
	public function before_display( $form_data, $user ) {
		return $form_data;
	}

	/**
	 * Displays the nonce hidden form field.
	 * Used to ensure we save fields of current section.
	 *
	 * @param object $user
	 *
	 * @return void
	 */
	public function nonce( $user ) {
		wp_nonce_field( 'user_section_update', 'user_section_' . $this->id );
	}

	/**
	 * Displays metabox content.
	 *
	 * @param object $user
	 *
	 * @return void
	 */
	public function display( $user ) {
		$this->user_id = $user->ID;
		$form_fields = $this->form_fields();

		if ( ! $form_fields ) {
			return;
		}

		$form_data = $this->before_display( $this->get_meta( $user->ID ), $user );

		if ( $this->title ) {
			echo html( 'h3', $this->title );
		}

		$this->before_form( $user );
		echo $this->table( $form_fields, $form_data );
		$this->after_form( $user );

	}

	/**
	 * Returns table.
	 *
	 * @param array $rows
	 * @param array $formdata
	 *
	 * @return string
	 */
	public function table( $rows, $formdata ) {
		$output = '';
		foreach ( $rows as $row ) {
			$output .= $this->table_row( $row, $formdata );
		}

		$output = scbForms::table_wrap( $output );

		return $output;
	}

	/**
	 * Returns table row.
	 *
	 * @param array $row
	 * @param array $formdata
	 *
	 * @return string
	 */
	public function table_row( $row, $formdata ) {
		$name = $row['name'];
		// Wrap description in span tag
		if ( isset( $row['desc'] ) ) {
			$row['desc'] = $this->wrap_desc( $row['desc'] );
		}
		// Get input html
		$input = scbForms::input( $row, $formdata );
		// Remove unnecessary label wrapper
		$input = str_replace( array( '<label>', '</label>' ), '', $input );
		// Wrap into table row
		return html( 'tr',
			html( 'th',
				html( 'label', array( 'for' => $name ), $row['title'] )
			),
			html( 'td', $input )
		);
	}

	/**
	 * Display some extra HTML before the form.
	 *
	 * @param object $user
	 *
	 * @return void
	 */
	public function before_form( $user ) { }

	/**
	 * Display some extra HTML after the form.
	 *
	 * @param object $user
	 *
	 * @return void
	 */
	public function after_form( $user ) { }

	/**
	 * Saves metabox form data.
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function save( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		$this->user_id = $user_id;

		$form_fields = $this->form_fields();
		$to_update = $this->before_save( scbForms::validate_post_data( $form_fields ), $user_id );
		$form_fields = $this->set_keys( $form_fields );
		$this->validate_fields_data( $to_update, $form_fields, $user_id );

		if ( ! empty( $this->errors ) ) {
			return false;
		}

		foreach ( $to_update as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}
	}

	/**
	 * Adds validation errors to WP_Error object to show them on the form.
	 *
	 * @param object $errors WP_Error object
	 *
	 * @return type
	 */
	public function update_errors( $errors ) {
		foreach ( $this->errors as $error ) {
			$errors->add( $error['key'] . '_error', '<strong>' . __( 'ERROR', APP_TD ) . '</strong>: ' . $error['title'] . ' - ' . $error['msg'] );
		}
	}

	/**
	 * Returns section fields array in scbForms style.
	 * Should be used in subclasses for passing form fields.
	 *
	 * @return array The list of form fields.
	 */
	public function form_fields() {
		return array();
	}

	/**
	 * Filter data before save.
	 *
	 * @param array $to_update
	 * @param int $user_id
	 *
	 * @return array
	 */
	protected function before_save( $to_update, $user_id ) {
		return $to_update;
	}

	/**
	 * Validate all posted fields and set up error messages if field data invalid.
	 *
	 * @param array $to_update Array of posted fields data ($key=>$value)
	 * @param array $form_fields Array of the fields parameters
	 * @param int $user_id User ID
	 *
	 * @return void
	 */
	protected function validate_fields_data( $to_update, $form_fields, $user_id ) {
		foreach ( $to_update as $key => $value ) {
			$error_msg = $this->validate_field( $key, $value, $form_fields[ $key ], $user_id );
			if ( ! $error_msg ) {
				continue;
			}

			$this->errors[] = array(
				'key'	=> $key,
				'title' => $form_fields[ $key ]['title'],
				'msg'	=> $error_msg,
			);
		}
	}

	/**
	 * Validate field data and return error message if field data invalid.
	 * Allows to add custom validation methods by override base method.
	 *
	 * @param string $field_name
	 * @param mixed $value
	 * @param array $props
	 * @param int $user_id
	 *
	 * @return boolean|string Returns false if field is valid, otherwise returns error message.
	 */
	protected function validate_field( $field_name, $value, $props, $user_id ) {
		return false;
	}

	/**
	 * Wraps description text to span html tag to save form markup.
	 *
	 * @param string $desc
	 *
	 * @return string
	 */
	protected function wrap_desc( $desc ) {
		return html( 'span', array( 'class' => 'description' ), $desc );
	}

	/**
	 * Returns an array of user meta.
	 *
	 * @param int $user_id
	 *
	 * @return array
	 */
	protected function get_meta( $user_id ) {
		return array_map( array( $this, 'single' ), get_user_meta( $user_id ) );
	}

	/**
	 * Makes form array associative.
	 *
	 * @param array $arr
	 *
	 * @return array
	 */
	protected function set_keys( $arr ) {
		$ret = array();
		foreach ( $arr as $value ) {
			$ret[ $value['name'] ] = $value;
		}
		return $ret;
	}

	/**
	 * Replaces the array item key to its value.
	 *
	 * @param array $val
	 *
	 * @return mixed
	 */
	protected function single( $val ) {
		return maybe_unserialize( $val[0] );
	}

}
