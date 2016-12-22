<?php
/**
 * Checkout Step
 *
 * @package Components\Checkouts
 */

/**
 * Checkout Step class
 */
class APP_Checkout_Step {

	/**
	 * Current checkout object
	 * @var APP_Dynamic_Checkout
	 */
	protected $checkout;

	/**
	 * Checkout step errors object
	 * @var WP_Error
	 */
	protected $errors;

	/**
	 * Creates new Checkout Step instance
	 *
	 * @param string $step_id Step ID.
	 * @param array  $args    Step arguments.
	 */
	public function __construct( $step_id, $args = array() ) {
		$this->setup( $step_id, $args );
	}

	/**
	 * Setup Checkout Step
	 *
	 * @param string $step_id Step ID.
	 * @param array  $args    Step arguments.
	 */
	final protected function setup( $step_id, $args = array() ) {

		$this->errors = new WP_Error();

		$actions = array(
			'appthemes_notices' => 'notices',
		);

		foreach ( $actions as $action => $method ) {
			if ( method_exists( $this, $method ) ) {
				add_action( $action, array( $this, $method ) );
			}
		}

		$this->step_id = $step_id;
		$this->args = wp_parse_args( $args, array(
			'process_callback' => array( $this, 'process' ),
			'display_callback' => array( $this, 'display' ),
			'priority' => 10,
			'register_to' => array(),
		) );

		if ( ! is_array( $this->args['register_to'] ) ) {
			$this->args['register_to'] = array( $this->args['register_to'] => '' );
		}

		foreach ( $this->args['register_to'] as $checkout_type => $positional_arguments ) {

			if ( is_int( $checkout_type ) ) {
				$this->args['register_to'][ $positional_arguments ] = array();
				unset( $this->args['register_to'][ $checkout_type ] );
			}
		}

		foreach ( $this->args['register_to'] as $checkout_type => $positonal_arguments ) {
			add_action( 'appthemes_register_checkout_steps_' . $checkout_type, array( $this, 'register' ), $this->args['priority'] );
		}
	}

	/**
	 * Registers Checkout Step
	 *
	 * @param APP_Dynamic_Checkout $checkout Current Checkout type instance.
	 */
	public function register( $checkout ) {

		$checkout_type = $checkout->get_checkout_type();
		$position      = array();

		if ( isset( $this->args['register_to'][ $checkout_type ] ) ) {
			$position = $this->args['register_to'][ $checkout_type ];
		}

		if ( empty( $position ) ) {
			$checkout->add_step( $this->step_id, $this->args['process_callback'], $this->args['display_callback'] );
		} else if ( ! empty( $position['before'] ) ) {
			$checkout->add_step_before( $position['before'], $this->step_id, $this->args['process_callback'], $this->args['display_callback'] );
		} else if ( ! empty( $position['after'] ) ) {
			$checkout->add_step_after( $position['after'], $this->step_id, $this->args['process_callback'], $this->args['display_callback'] );
		}

		$this->checkout = $checkout;
	}

	/**
	 * Finish Checkout Step
	 */
	public function finish_step() {
		$this->checkout->finish_step();
	}

	/**
	 * Cancels Checkout Step
	 */
	public function cancel_step() {
		$this->checkout->cancel_step();
	}

	/**
	 * Displays Checkout Step
	 *
	 * @param APP_Order            $order    Order object.
	 * @param APP_Dynamic_Checkout $checkout Checkout object.
	 */
	public function display( $order, $checkout ) {}

	/**
	 * Processes Checkout Step
	 *
	 * @param APP_Order            $order    Order object.
	 * @param APP_Dynamic_Checkout $checkout Checkout object.
	 */
	public function process( $order, $checkout ) {}

	/**
	 * Displays notices
	 */
	function notices() {

		if ( $this->errors->get_error_codes() ) {
			appthemes_display_notice( 'checkout-error', $this->errors );
		}

	}

}

