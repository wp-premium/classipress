<?php
/**
 * Dynamic Checkout class
 *
 * @package Components\Checkouts
 */
class APP_Dynamic_Checkout{

	/**
	 * The steps list object.
	 *
	 * @var APP_Relational_Checkout_List
	 */
	protected $steps;

	/**
	 * The current step ID.
	 *
	 * @var bool|string
	 */
	protected $current_step = false;

	/**
	 * The finished step flag.
	 *
	 * @var bool
	 */
	protected $step_finished = false;

	/**
	 * The cancelled step flag.
	 *
	 * @var bool
	 */
	protected $step_cancelled = false;

	/**
	 * The checkout hash.
	 *
	 * @var string
	 */
	protected $hash;

	/**
	 * The Order ID.
	 *
	 * @var int
	 */
	protected $order_id;

	/**
	 * The current checkout type
	 *
	 * @var string
	 */
	protected $checkout_type;

	/**
	 * Setups checkout.
	 *
	 * @param string $checkout_type Checkout type.
	 * @param string $hash          Optional. Checkout hash.
	 *
	 * @return void
	 */
	public function __construct( $checkout_type, $hash = '' ) {

		$this->hash = substr( $hash, 0, 20 );
		$this->checkout_type = substr( $checkout_type, 0, 25 );
		if ( empty( $this->hash ) ) {
			$this->hash = substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 20 );
			$this->set_expiration();
			$this->add_data( 'checkout_type', $this->checkout_type );
		}

		$this->steps = new APP_Relational_Checkout_List;
	}

	public function add_step( $id, $process, $display ){
		$this->steps->add( $id, array( $process, $display ) );
	}

	public function add_step_before( $ref_id, $id, $process, $display ){
		$this->steps->add_before( $ref_id, $id, array( $process, $display ) );
	}

	public function add_step_after( $ref_id, $id, $process, $display ){
		$this->steps->add_after( $ref_id, $id, array( $process, $display ) );
	}

	public function unregister_step( $id ){
		$this->steps->remove( $id );
	}

	public function display_step( $id ){
		return $this->call_step( $id, 'display' );
	}

	public function process_step( $id ){
		return $this->call_step( $id, 'process' );
	}

	/**
	 * Sets expiration for a checkout data.
	 *
	 * When expiration will set all further calls of add_data() method will use
	 * this expiration value, until new expiration value will be set.
	 *
	 * This allows to dynamically control the checkout expiration on a different
	 * steps. Since different steps have a different time limit on passing them.
	 *
	 * @param int $expiration Optional. Time until expiration in seconds.
	 *                         Default 86400 (1 day). 0 - no expiration.
	 */
	public function set_expiration( $expiration = DAY_IN_SECONDS ) {
		$data = $this->get_data();
		if ( ! $data ) {
			$data = array();
		}

		$data['expiration'] = (int) $expiration;

		set_transient( $this->get_transient_key(), $data, $expiration );

		// Do not autoload option and make transient unexpirable.
		if ( ! $expiration ) {
			delete_option( '_transient_timeout_' . $this->get_transient_key() );
		}
	}

	/**
	 * Completes the checkout after all steps finished.
	 *
	 * Checkout data will be preserved for a one day and then finally deleted.
	 */
	public function complete_checkout() {
		do_action( 'appthemes_checkout_completed', $this );
		// Keep completed checkout data for a one day.
		$this->set_expiration( DAY_IN_SECONDS );
	}

	protected function call_step( $id, $type = 'display' ){

		$id = apply_filters( 'appthemes_checkout_call_step', $id );
		$this->current_step = $id;

		if ( $this->steps->is_empty() ) {
			return false;
		}

		if ( ! $this->steps->contains( $id ) ) {
			return false;
		}

		$callbacks = $this->steps->get( $this->current_step );
		$callback = $callbacks['payload'][ ( $type == 'display' ) ? 1 : 0 ];

		$order_id = $this->get_data( 'order_id' );
		if ( ! $order_id ) {
			$order = new APP_Draft_Order;
		} else {
			$order = appthemes_get_order( $order_id );
		}

		if ( is_callable( $callback ) ) {
			call_user_func( $callback, $order, $this );
		} else if ( is_string( $callback ) ) {
			locate_template( $callback, true );
		} else {
			return false;
		}

		if ( $order instanceof APP_Draft_Order && $order->is_modified() ) {
			$new_order = APP_Order_Factory::duplicate( $order );
			if ( ! $new_order ) {
				return false;
			}
			$this->add_data( 'order_id', $new_order->get_id() );

			// save checkout type & hash in order
			update_post_meta( $new_order->get_id(), 'checkout_type', $this->checkout_type );
			update_post_meta( $new_order->get_id(), 'checkout_hash', $this->hash );

			// save complete and cancel urls in order
			if ( $complete_url = $this->get_data( 'complete_url' ) ) {
				update_post_meta( $new_order->get_id(), 'complete_url', $complete_url );
			}
			if ( $cancel_url = $this->get_data( 'cancel_url' ) ) {
				update_post_meta( $new_order->get_id(), 'cancel_url', $cancel_url );
			}
		}

		if ( $this->step_cancelled ) {
			$this->redirect( appthemes_get_step_url( $this->get_previous_step( $id ) ) );
			appthemes_exit( "cancel_step_{$this->current_step}" );
		}

		if ( $this->step_finished ) {
			$next_step = $this->get_next_step( $id );

			if ( $this->current_step === $next_step ) {
				$this->complete_checkout();
			} else {
				$this->redirect( appthemes_get_step_url( $next_step ) );
				appthemes_exit( "finish_step_{$this->current_step}" );
			}
		}

		$this->current_step = false;
		return true;
	}

	/**
	 * Returns current checkout step.
	 *
	 * @return string|bool
	 */
	public function get_current_step() {
		return $this->current_step;
	}

	/**
	 * Returns next checkout step.
	 *
	 * @param string $id (optional)
	 *
	 * @return string|bool
	 */
	public function get_next_step( $id = '' ) {

		if ( $this->steps->is_empty() ) {
			return false;
		}

		if ( empty( $id ) ) {
			return $this->steps->get_first_key();
		} else {
			return $this->steps->get_key_after( $id );
		}

	}

	/**
	 * Returns previous checkout step.
	 *
	 * @param string $id (optional)
	 *
	 * @return string|bool
	 */
	public function get_previous_step( $id = '' ) {

		if ( $this->steps->is_empty() ) {
			return false;
		}

		if ( empty( $id ) ) {
			return $this->steps->get_first_key();
		} else {
			return $this->steps->get_key_before( $id );
		}

	}

	/**
	 * Mark current step as finished.
	 *
	 * @return void
	 */
	public function finish_step() {
		$this->step_finished = true;
	}

	/**
	 * Mark current step as cancelled.
	 *
	 * @return void
	 */
	public function cancel_step() {
		$this->step_cancelled = true;
	}

	/**
	 * Stores given data in checkout.
	 *
	 * @param string $key   Data key.
	 * @param mixed  $value Data value.
	 * @return void
	 */
	public function add_data( $key, $value ) {

		$data = $this->get_data();
		if ( ! $data ) {
			$data = array();
		}

		$expiration = ( isset( $data['expiration'] ) ) ?  $data['expiration'] : DAY_IN_SECONDS;

		$data[ $key ] = $value;
		set_transient( $this->get_transient_key(), $data, $expiration );
	}

	/**
	 * Returns data from checkout for given key.
	 *
	 * @param string $key (optional)
	 *
	 * @return mixed
	 */
	public function get_data( $key = '' ) {

		$data = get_transient( $this->get_transient_key() );
		if ( false === $data ) {
			$data = $this->get_data_from_order();
		}

		if ( empty( $key ) ) {
			return $data;
		} else if ( isset( $data[ $key ] ) ) {
			return $data[ $key ];
		} else {
			return false;
		}
	}

	/**
	 * Returns a basic checkout data from order.
	 *
	 * @return array|bool
	 */
	protected function get_data_from_order() {
		$orders = new WP_Query( array(
			'author' => get_current_user_id(),
			'post_type' => APPTHEMES_ORDER_PTYPE,
			'post_status' => 'any',
			'posts_per_page' => 1,
			'suppress_filters' => true,
			'no_found_rows' => true,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'checkout_type',
					'value'   => $this->checkout_type,
					'compare' => '=',
				),
				array(
					'key'     => 'checkout_hash',
					'value'   => $this->hash,
					'type'    => '=',
				),
			),
		) );

		if ( empty( $orders->posts ) ) {
			return false;
		}

		$data = array(
			'order_id' => $orders->posts[0]->ID,
			'checkout_type' => $this->checkout_type,
		);

		return $data;
	}

	/**
	 * Checks if checkout is valid, and has been setup.
	 *
	 * @return bool
	 */
	public function verify_hash() {
		$checkout_type = $this->get_data( 'checkout_type' );
		if ( $this->checkout_type != $checkout_type ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Redirects user to given url.
	 *
	 * @param string $url
	 *
	 * @return void
	 */
	public function redirect( $url ) {
		if ( did_action( 'wp_head' ) ) {
			echo html( 'a', array( 'href' => esc_url( $url ) ), __( 'Continue', APP_TD ) );
			echo html( 'script', 'location.href="' . esc_url( $url ) . '"' );
		} else {
			$url = esc_url_raw( $url );
			wp_redirect( $url );
		}
	}

	/**
	 * Returns a transient key.
	 *
	 * @return string
	 */
	protected function get_transient_key() {
		$user_key = is_user_logged_in() ? get_current_user_id() : appthemes_get_ip();
		$user_key = str_replace( '.', '_', $user_key );
		return $this->checkout_type . '_' . $this->hash . '_' . $user_key;
	}

	/**
	 * Returns a checkout hash.
	 *
	 * @return string
	 */
	public function get_hash() {
		return $this->hash;
	}

	/**
	 * Returns a checkout type.
	 *
	 * @return string
	 */
	public function get_checkout_type() {
		return $this->checkout_type;
	}

	/**
	 * Returns an array of steps.
	 *
	 * @return array
	 */
	public function get_steps() {
		return $this->steps->get_all();
	}

	/**
	 * Returns a number of steps.
	 *
	 * @return int
	 */
	public function get_steps_count() {
		return count( $this->steps->get_all() );
	}

}

