<?php
/**
 * The base for creating and processing ajax actions like, delete post,
 * favorite, etc.
 *
 * @package Framework\Views\Ajax
 */
class APP_Ajax_View {

	/**
	 * @var array An array of references to all registered ajax views instances
	 */
	private static $_registered = array();

	/**
	 * @var string Unique Ajax action name, must be prefixed with 'appthemes_'
	 */
	protected $ajax_action;

	/**
	 * @var string POST variable name, which contains current state or action
	 */
	protected $action_var;

	public function __construct( $ajax_action = false, $action_var = false ) {

		if ( ! $ajax_action || ! $action_var ) {
			return;
		}

		if ( isset( self::$_registered[ $ajax_action ] ) ) {
			return;
		}

		$this->ajax_action = $ajax_action;
		$this->action_var  = $action_var;

		// add reference to instance for future external use
		self::$_registered[ $ajax_action ] =& $this;

		add_action( 'init', array( $this, 'init' ) );

	}

	/**
	 * Retrieves registered Ajax View instance by given ajax action name
	 *
	 * @param string $ajax_action Registered ajax action name
	 *
	 * @return APP_Ajax_View Ajax View instance
	 */
	final static function get_ajax_view( $ajax_action = false ) {

		if ( $ajax_action && isset( self::$_registered[ $ajax_action ] ) ) {
			return self::$_registered[ $ajax_action ];
		}

	}

	/**
	 * Registers hooks
	 */
	public function init() {

		add_action( 'wp_ajax_' . $this->ajax_action, array( $this, 'ajax_handle' ) );
		add_action( 'wp_ajax_nopriv_' . $this->ajax_action, array( $this, 'ajax_handle' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

	}

	/**
	 * Enqueue scripts
	 */
	public static function enqueue_scripts() {

		wp_enqueue_script(
			'app-action-button',
			APP_FRAMEWORK_URI . '/includes/ajax/action-button.js',
			array( 'jquery' ),
			'1.0'
		);

	}

	/**
	 * The main ajax request handler
	 *
	 * @return type
	 */
	public function ajax_handle() {

		if ( ! $this->condition() ) {
			return;
		}

		$item_id = (int) $_POST['item_id'];

		check_ajax_referer( $this->ajax_action . "-" . $item_id );

		$defaults = array(
			'redirect' => false,
			'status'   => 'success',
			'notice'   => '',
		);

		$data = wp_parse_args( $this->response_message( $defaults ), $defaults );

		$this->ajax_response( $data['status'], $data['notice'], $item_id, $data['redirect'] );
	}

	/**
	 * Generate response message
	 * @param type $data
	 * @return type
	 */
	protected function response_message( $data ) {
		return $data;
	}

	/**
	 * Check condition
	 * @return bool
	 */
	protected function condition() {
		return ( isset( $_POST[ $this->action_var ] ) && isset( $_POST['item_id'] ) && isset( $_POST['current_url'] ) );
	}

	/**
	 * Output response
	 *
	 * @param type $status
	 * @param type $notice
	 * @param type $item_id
	 * @param type $redirect_url
	 */
	public function ajax_response( $status, $notice, $item_id, $redirect_url = '' ) {

		ob_start();
		appthemes_display_notice( $status, $notice );
		$notice = ob_get_clean();

		// allows theme or plugins to replace generic renderer with custom one
		$renderer = apply_filters( $this->ajax_action . '_ajax_renderer', array( $this, 'render' ) );

		// the context of calling function, used to vary outputting html
		$context = ( isset( $_POST['context'] ) ) ? $_POST['context'] : 'default';
		// prevent outputting html here
		ob_start();
		$html   = call_user_func( $renderer, $item_id, $context, $this );
		$buffer = ob_get_clean();

		if ( ! empty( $buffer ) ) {
			$html = $buffer;
		}

		$result = array(
			'html'     => $html,
			'status'   => $status,
			'notice'   => $notice,
			'redirect' => $redirect_url,
		);

		die ( json_encode( $result ) );

	}

	/**
	 * This is where button processor changes current action/state to another
	 * one or several (in case of multiple buttons generation) or leave default.
	 *
	 * To be overriden by subclasses
	 *
	 * @param type $item_id
	 * @return type
	 */
	public function get_actions( $item_id ) {
		// no logic by default
		return array();
	}

	/**
	 * Retrieves standard set of arguments to be used for action button generation
	 *
	 * @param int|string $item_id  Given entry ID
	 * @param string     $action   Current action/state value
	 *
	 * @return array An array of button parameters
	 */
	public function get_button_args( $item_id, $action ) {

		$action_arg = 'data-' . $this->action_var;

		return array(
			'tag'              => 'a',
			'content'          => __( 'Submit', APP_TD ),
			'href'             => '#',
			'class'            => 'button',
			'rel'              => 'nofollow',
			'data-context'     => 'default',
			'data-item_id'     => $item_id,
			'data-_ajax_nonce' => wp_create_nonce( $this->ajax_action . "-" . $item_id ),
			$action_arg        => $action,
		);
	}

	/**
	 * Default Renderer
	 *
	 * @param int|string $item_id  Given entry ID

	 * @return string Generated button html
	 */
	public function render( $item_id ) {

		$html = '';

		foreach ( $this->get_actions( $item_id ) as $action ) {
			$args    = $this->get_button_args( $item_id, $action );
			$tag     = $args['tag'];
			$content = $args['content'];
			unset($args['tag']);
			unset($args['content']);

			$html .= html( $tag, $args, $content );
		}

		return $html;

	}

}

/**
 * Retrieves registered Ajax View instance by given ajax action name
 *
 * @param string $ajax_action Registered ajax action name
 *
 * @return APP_Ajax_View Ajax View instance
 */
function appthemes_get_ajax_view( $ajax_action = false ) {
	return APP_Ajax_View::get_ajax_view( $ajax_action );
}