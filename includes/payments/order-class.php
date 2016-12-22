<?php
/**
 * Represents a purchase order made up of items.
 *
 * @package Components\Payments
 */
abstract class APP_Order {

	/**
	 * Order ID, defined by Wordpress when
	 * creating Order
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Parent order id for child order
	 * @var int
	 */
	protected $parent = 0;

	/**
	 * State of the order.
	 * See order-functions.php for possible states
	 * @var string
	 */
	protected $state = APPTHEMES_ORDER_PENDING;

	/**
	 * Order description
	 * @var  string
	 */
	protected $description = '';

	/**
	 * Information on the creator of the order
	 * @var array
	 */
	protected $creator = array(
		'user_id' => 0,
		'ip_address' => 0,
	);

	/**
	 * Information on the payment amount and method
	 * @var array
	 */
	protected $payment = array(
		'total' => 0,
		'currency' => 'USD',
		'gateway' => '',
		'recurring_period' => 0,
	);

	/**
	 * List of items in the currency order
	 * @var array
	 */
	protected $items = array();

	/**
	 * Additional information for the escrow order
	 * @var array
	 */
	protected $data = array();

	protected $log;

	const RECUR_PERIOD_TYPE_DAYS = 'D';
	const RECUR_PERIOD_TYPE_WEEKS = 'W';
	const RECUR_PERIOD_TYPE_MONTHS = 'M';
	const RECUR_PERIOD_TYPE_YEARS = 'Y';

	/**
	 * Sets up the order objects
	 * @param object $post       Post object returned from get_post()
	 * 								See (http://codex.wordpress.org/Function_Reference/get_post)
	 * @param string $gateway    Gateway indentifier for this order to use
	 * @param string $ip_address IP_Address used to create the order
	 * @param string $currency   Currency code for currency currently being used. Should
	 * 								be registered with APP_Currencies
	 * @param array $items       Array of items currently attached to the order
	 */
	public function __construct( $post, $items ) {

		$this->id = $post->ID;
		$this->parent = $post->post_parent;
		$this->description = $post->post_title;
		$this->state = $post->post_status;

		$this->data = get_post_meta( $this->id, 'data', true );
		$meta_fields = get_post_custom( $post->ID );

		$this->creator['user_id'] = $post->post_author;
		$this->creator['ip_address'] = $this->get_meta_field( 'ip_address', 0, $meta_fields );

		$this->payment['currency'] = $this->get_meta_field( 'currency', 'USD', $meta_fields );
		$this->payment['gateway'] = $this->get_meta_field( 'gateway', '', $meta_fields );
		$this->payment['recurring_period'] = $this->get_meta_field( 'recurring_period', 0, $meta_fields );
		$this->payment['recurring_period_type'] = $this->get_meta_field( 'recurring_period_type', 'D', $meta_fields );

		$this->log = new APP_Post_Log( $post->ID );

		$this->items = $items;
		$this->refresh_total();

	}

	public function get_info( $part = '' ){

		$basic = array(
			'id' => $this->id,
			'parent' => $this->parent,
			'description' => $this->description,
			'state' => $this->state
		);

		$fields = array_merge( $basic, $this->creator, $this->payment );

		if( empty( $part ) )
			return $fields;
		else
			return $fields[ $part ];

	}

	/**
	 * Returns the Order ID
	 * @return int Order ID
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Returns the Order description
	 * @return string The order description
	 */
	public function get_description() {
		return $this->description;
	}

	public function set_description( $description ){

		if( ! is_string( $description ) )
			trigger_error( 'Description must be a string.', E_USER_WARNING );

		$this->description = $description;
		$this->update_post( array(
			'post_title' => $description
		) );

	}

	/**
	 * Adds an item to the order.
	 *
	 * @param string $type            A string representing the type of item being added
	 * @param int $price              The price of the item
	 * @param int $post_id (optional) The post that this item affects
	 * @param bool $unique (optional) Is the item unique per order
	 *
	 * @return bool A boolean True if the item has been added, False otherwise
	 */
	public function add_item( $type, $price, $post_id = 0, $unique = false ) {

		if ( empty( $post_id ) ) {
			$post_id = $this->get_id();
		}

		if ( ! is_numeric( $post_id ) ) {
			trigger_error( 'Post ID must be an integer', E_USER_WARNING );
			return false;
		}

		if ( ! is_numeric( $price ) ) {
			trigger_error( 'Price must be numeric', E_USER_WARNING );
			return false;
		}

		if ( ! is_string( $type ) && ! is_int( $type ) ) {
			trigger_error( 'Item Type must be a string or integer', E_USER_WARNING );
			return false;
		}

		if ( $unique ) {
			$this->remove_item( $type );
		}

		$p2p_id = p2p_type( APPTHEMES_ORDER_CONNECTION )->connect( $this->id, $post_id );
		if ( ! is_int( $p2p_id ) ) {
			return false;
		}

		$this->items[] = array(
			'type' => $type,
			'price' => (float) $price,
			'post_id' => $post_id,
			'post' => get_post( $post_id ),
			'unique_id' => $p2p_id,
		);

		p2p_add_meta( $p2p_id, 'type', $type );
		p2p_add_meta( $p2p_id, 'price', (float) $price );

		$this->refresh_total();

		return true;
	}

	/**
	 * Removes an item or items from the order. Removes all items that match the criteria
	 * Use remove_item_by_index for super-specific removal.
	 *
	 * @param string $type (optional) A string representing the type of item to remove
	 * @param int $price (optional)   The price of the item being removed
	 * @param int $post_id (optional) The post that this item affects
	 *
	 * @return int|bool Quantity of items removed. Boolean False on failure
	 */
	public function remove_item( $type = '', $price = 0, $post_id = 0 ) {

		if ( ! empty( $post_id ) && ! is_numeric( $post_id ) ) {
			trigger_error( 'Post ID must be an integer', E_USER_WARNING );
			return false;
		}

		if ( ! empty( $price ) && ! is_numeric( $price ) ) {
			trigger_error( 'Price must be numeric', E_USER_WARNING );
			return false;
		}

		if ( ! empty( $type ) && ! is_string( $type ) && ! is_int( $type ) ) {
			trigger_error( 'Item Type must be a string or integer', E_USER_WARNING );
			return false;
		}

		$matches = array();
		foreach ( $this->items as $index => $item ) {

			if ( ! empty( $type ) && $item['type'] != $type ) {
				continue;
			}

			if ( ! empty( $price ) && $item['price'] != $price ) {
				continue;
			}

			if ( ! empty( $post_id ) && $item['post_id'] != $post_id ) {
				continue;
			}

			$matches[] = $item;
		}

		foreach ( $matches as $item ) {
			$this->remove_item_by_id( $item['unique_id'] );
		}


		$this->refresh_total();

		return count( $matches );
	}

	public function remove_item_by_id( $unique_id ){

		p2p_delete_connection( $unique_id );
		foreach( $this->items as $key => $item ){
			if( $item['unique_id'] == $unique_id ){
				unset( $this->items[ $key ] );
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the first item in an order, or another as specified
	 * @param  integer $index The index number of the item to return
	 * @return array          An associative array of information about the item
	 */
	public function get_item( $type = '', $index = 0 ) {

		if( is_integer( $type ) ){
			$index = $type;
			$type = '';
		}

		$items = $this->get_items( $type );

		if ( isset( $items[ $index ] ) )
			return $items[ $index ];
		else
			return false;
	}

	/**
	 * Returns an array of all the items in an order that match a given
	 * type, or all items in the order.
	 * @param  string $item_type (optional) Item Type to filter by
	 * @return array            An array of items matching the criteria
	 */
	public function get_items( $type = '' ) {

		if ( empty( $type ) )
			return $this->items;

		if( ! is_string( $type ) && ! is_int( $type ) )
			trigger_error( 'Item type must be a string or integer.', E_USER_WARNING );

		$results = array();
		foreach ( $this->items as $item ){
			if ( $item['type'] == $type )
				$results[] = $item;
		}

		return $results;
	}

	/**
	 * Returns the gateway that should be used to
	 * process this order
	 * @return string The Gateway Identifer. See APP_Gateway
	 */
	public function get_gateway() {
		return $this->payment['gateway'];
	}

	/**
	 * Sets the gateway this order should be processed with
	 * @param string $gateway_id The Gateway Identifier. See APP_gateway
	 */
	public function set_gateway( $gateway_id ) {

		if( ! is_string( $gateway_id ) )
			trigger_error( 'Gateway ID must be a string', E_USER_WARNING );

		if ( $gateway_object = APP_Gateway_Registry::get_gateway( $gateway_id ) ){
			$this->payment['gateway'] = $gateway_object->identifier();
			$this->update_meta( 'gateway', $this->payment['gateway'] );
			return true;
		}

		return false;
	}

	/**
	 * Clears this order from being assocaited with a gateway.
	 * Used to prompt the user to select a new gateway
	 */
	public function clear_gateway() {

		$this->payment['gateway'] = '';
		delete_post_meta( $this->id, 'gateway' );

	}

	/**
	 * Recalculates the total of the order.
	 * See get_total() for results
	 * @return void
	 */
	protected function refresh_total() {

		$this->payment['total'] = 0;
		foreach ( $this->items as $item )
			$this->payment['total'] += (float) $item['price'];

		if ( $this->payment['total'] < 0 )
			$this->payment['total'] = 0;

		$this->update_meta( 'total_price', $this->payment['total'] );

	}

	/**
	 * Returns the total price of the order
	 * @return int Total price of the order
	 */
	public function get_total() {
		return number_format( (float) $this->payment['total'], 2, '.', '' );
	}

	/**
	 * Sets the currency to be used in this order.
	 * Changing this does not affect any of the prices used in the order
	 * @param string $currency_code Currency code used to identify the
	 * 								currency. Must be registered with APP_Currencies
	 * @return boolean True if currency was changed, false on error
	 */
	public function set_currency( $currency_code ) {

		if( ! is_string( $currency_code ) )
			trigger_error( 'Currency code must be string', E_USER_WARNING );

		if ( APP_Currencies::is_valid( $currency_code) )
		    $this->payment['currency'] = $currency_code;
		else
		    return false;

		$this->update_meta( 'currency', $this->payment['currency'] );
		return true;
	}

	/**
	 * Returns the current currency's code. See APP_Currency
	 * @return string Current currency's code
	 */
	public function get_currency() {
		return $this->payment['currency'];
	}

	/**
	 * Returns the current state of the order
	 * @return string State of the order. See order-functions.php for valid statuses
	 */
	public function get_status() {
		return $this->state;
	}

	abstract public function get_display_status();

	/**
	 * Sets the order statues and sends out correct action hooks.
	 * New order status must be different than old status
	 * @param string $status Valid status for order. See order-functions.php
	 * 							for valid statuses
	 */
	protected function set_status( $status ) {

		if ( $this->state == $status )
			return;

		$this->state = $status;

		$this->update_post( array(
			"post_status" => $status
		) );

		$identifier = $this->get_status_identifier( $status );
		if ( ! $identifier ) {
			return false;
		}
		do_action( 'appthemes_transaction_' . $identifier, $this );
	}

	abstract protected function get_status_identifier( $status );

	/**
	 * Schedules the order to attempt be completed at a certain time
	 * @internal
	 */
	public function schedule_payment( $time ){
		$this->update_post( array(
			'post_date' => $time
		) );
	}

	public function set_author( $author_id ){

		$author_id = intval( $author_id );
		if( !is_numeric( $author_id ) ){
			trigger_error( 'Author ID must be an integer', E_USER_WARNING );
		}

		$this->creator['user_id'] = $author_id;
		$this->update_post( array(
			'post_author' => $author_id,
		) );

	}

	/**
	 * Returns the User ID of the creator of the order
	 * @return int User ID
	 */
	public function get_author() {
		return $this->creator['user_id'];
	}

	/**
	 * Returns the IP Address used to create the order
	 * @return string IP Address
	 */
	public function get_ip_address() {
		return $this->creator['ip_address'];
	}

	/**
	 * Returns the URL to redirect to for processing the order
	 * @return string URL
	 */
	public function get_return_url() {
		return self::get_url( $this->id );
	}

	/**
	 * Returns the URL to redirect to for selecting a new gateway
	 * @return string URL
	 */
	public function get_cancel_url() {
		return add_query_arg( "cancel", 1, $this->get_return_url() );
	}

	/**
	 * Returns the id of the parent post
	 * @return id the post parent
	 */
	public function get_parent(){
		return $this->parent;
	}

	/**
	 * Adds data to the Order.
	 *
	 * @param string $key The key to store the data
	 * @param string $value The value.
	 */
	public function add_data( $key, $value ){

		$this->data[ $key ] = $value;

		update_post_meta( $this->id, 'data', $this->data );
	}

	/**
	 * Retrieves a piece of data added to the order via add_data()
	 *
	 * @param string $key The key to retrieve the data from.
	 * @return mixed The value for the given key, or all data present on the order
	 */
	public function get_data( $key = '' ) {

		if ( empty( $key ) ) {
			return $this->data;
		} elseif( ! empty( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		}
		return false;

	}

	/**
	 * Updates the order's post data
	 * @param $args array Array of values to update. See wp_update_post.
	 */
	protected function update_post( $args ){

		$defaults = array(
			'ID' => $this->get_id()
		);

		wp_update_post( array_merge( $defaults, $args ) );

	}

	/**
	 * Updates the meta fields for the post
	 * @param $meta_key    string|array Can either be the meta field to be updated, or an associative array
	 * 					of meta keys and values to be updated
	 * @param $meta_value  string	    Value to set the meta value to. Ignored if meta_key is an array
	 * @param $reset_cache boolean      Whether or not to update the cache after updating. Used to limit
	 * 					larges amounts of updates
	 */
	protected function update_meta( $meta_key, $meta_value = ''){

		if( is_array( $meta_key ) ){
			foreach( $meta_key as $key => $value ){
				$this->update_meta( $key, $value, false );
			}
			return;
		}

		update_post_meta( $this->id, $meta_key, $meta_value );

	}

	/**
	 * Log a message concerning this order
	 *
	 * @return false if logging is turned off
	 */
	public function log( $message, $group = 'info' ){
		if( !$this->log )
			return false;

		$this->log->log( $message, $group );
	}

	/**
	 * Retrieves log messages
	 * @return false if logging is turned off
	 */
	public function get_log(){
		if( !$this->log )
			return false;
		return $this->log->get_log();
	}

	/**
	 * Returns the URL for an order. Useful for getting the URL
	 * without building the order.
	 * @param int $order_id Order ID
	 * @return string URL for the order
	 */
	static public function get_url( $order_id ){
		if( !is_numeric( $order_id ) )
			trigger_error( 'Invalid order id given. Must be an integer', E_USER_WARNING );
		return apply_filters( 'appthemes_order_return_url', get_permalink( $order_id ) );
	}

	private function get_meta_field( $field, $default, $fields ){
		if( isset( $fields[ $field ] ) )
			return $fields[ $field ][0];
		else
			return $default;

	}

	/**
	 * Returns true if the order recurrs
	 */
	public function is_recurring(){
		return false;
	}

	/**
	 * Returns true if the order recurrs.
	 */
	public function is_escrow(){
		return false;
	}

}

class APP_Instant_Order extends APP_Order {

	protected $statuses = array(
		APPTHEMES_ORDER_PENDING => 'pending',
		APPTHEMES_ORDER_FAILED => 'failed',
		APPTHEMES_ORDER_COMPLETED => 'completed',
		APPTHEMES_ORDER_ACTIVATED => 'activated'
	);

	/**
	* Returns a status's hook identifier
	* @return string
	*/
	protected function get_status_identifier( $status ){
		if ( ! isset( $this->statuses[ $status] ) ) {
			return false;
		}
		return $this->statuses[ $status ];
	}

	/**
	 * Returns a version of the current state for display.
	 * @return string Current state, localized for display
	 */
	public function get_display_status() {

		$statuses = array(
			APPTHEMES_ORDER_PENDING => __( 'Pending', APP_TD ),
			APPTHEMES_ORDER_FAILED => __( 'Failed', APP_TD ),
			APPTHEMES_ORDER_COMPLETED => __( 'Completed', APP_TD ),
			APPTHEMES_ORDER_ACTIVATED => __( 'Activated', APP_TD ),
		);

		$status = $this->get_status();
		return $statuses[ $status ];

	}

	/**
	 * Sets the order as pending. Causes action 'appthemes_transaction_pending'
	 * @return void
	 */
	public function pending() {
		$this->set_status( APPTHEMES_ORDER_PENDING );
		$this->log( 'Marked as Pending', 'minor' );
	}

	/**
	 * Sets the order as failed. Causes action 'appthemes_transaction_failed'
	 * @return void
	 */
	public function failed() {
		$this->set_status( APPTHEMES_ORDER_FAILED );
		$this->log( 'Marked as Failed', 'major' );
	}

	/**
	 * Sets the order as completed. Causes action 'appthemes_transaction_completed'
	 * @return void
	 */
	public function complete() {
		$this->set_status( APPTHEMES_ORDER_COMPLETED );
		$this->log( 'Marked as Completed', 'major' );
	}

	/**
	 * Sets the order as activated. Causes action 'appthemes_transaction_activated'
	 * @return void
	 */
	public function activate() {
		$this->set_status( APPTHEMES_ORDER_ACTIVATED );
		$this->log( 'Marked as Activated', 'major' );
	}

	/**
	 * Returns true if the order recurrs
	 */
	public function is_recurring(){
		return false;
	}

}