<?php
/**
 * Base class for Payment Gateways
 *
 * @deprecated Use APP_Payment_Gateway instead
 * @package Components\Payments\Gateways
 */
abstract class APP_Gateway {

	/**
	 * Unique identifier for this gateway
	 * @var string
	 */
	private $identifier;

	/**
	 * Display names used for this Gateway
	 * @var array
	 */
	private $display;

	/**
	 * Creates the Gateway class with the required information to display it
	 *
	 * @param string  $display_name The display name
	 * @param string  $identifier   The unique indentifier used to indentify your payment type
	 */
	public function __construct( $identifier, $args = array() ) {

		if( ! is_string( $identifier ) )
			trigger_error( 'Identifier must be a string', E_USER_WARNING );

		if( ! is_array( $args ) && ! is_string( $args ) )
			trigger_error( 'Arguments must be an array or url encoded string.', E_USER_WARNING );

		$defaults = array(
			'dropdown' => $identifier,
			'admin' => $identifier,
			'recurring' => false
		);

		$args = wp_parse_args( $args, $defaults );

		$this->display = array(
			'dropdown' => $args['dropdown'],
			'admin' => $args['admin'],
		);

		$this->identifier = $identifier;
		$this->recurring = (bool) $args['recurring'];
	}

	/**
	 * Provides the display name for this Gateway
	 *
	 * @return string
	 */
	public final function display_name( $type = 'dropdown' ) {

		if( in_array( $type, array( 'dropdown', 'admin' ) ) )
			return $this->display[$type];
		else
			return $this->display['dropdown'];
	}

	/**
	 * Provides the unique identifier for this Gateway
	 *
	 * @return string
	 */
	public final function identifier() {
		return $this->identifier;
	}

	/**
	 * Returns if the current gateway is able to process
	 * recurring payments
	 * @return bool
	 */
	public function is_recurring(){
		return $this->recurring;
	}

	/**
	 * Checks if the current gateway supports a specific service
	 * @return bool
	 */
	public function supports( $service = 'instant' ){
		switch ( $service ) {
			case 'escrow':
				return false;
				break;
			case 'recurring':
				return $this->is_recurring();
				break;
			case 'instant':
				return true;
				break;

		}
	}

}

abstract class APP_Payment_Gateway implements APP_Payment_Processor, APP_Instant_Payment_Processor {

	/**
 	 * Unique identifier for this gateway
	 * @var string
	 */
	private $identifier;

	/**
	 * Display names used for this Gateway
	 * @var array
	 */
	private $display;

	/**
	 * Creates the Gateway class with the required information to display it
	 *
	 * @param string  $display_name The display name
	 * @param string  $identifier   The unique indentifier used to indentify your payment type
	 */
	public function __construct( $identifier, $args = array() ) {

		if( ! is_string( $identifier ) )
			trigger_error( 'Identifier must be a string', E_USER_WARNING );

		if( ! is_array( $args ) && ! is_string( $args ) )
			trigger_error( 'Arguments must be an array or url encoded string.', E_USER_WARNING );

		$defaults = array(
			'dropdown' => $identifier,
			'admin' => $identifier,
		);

		$args = wp_parse_args( $args, $defaults );

		$this->display = array(
			'dropdown' => $args['dropdown'],
			'admin' => $args['admin'],
		);

		$this->identifier = $identifier;
	}

	/**
	 * Provides the display name for this Gateway
	 *
	 * @return string
	 */
	public final function display_name( $type = 'dropdown' ) {

		if( in_array( $type, array( 'dropdown', 'admin' ) ) )
			return $this->display[$type];
		else
			return $this->display['dropdown'];
	}

	/**
	 * Provides the unique identifier for this Gateway
	 *
	 * @return string
	 */
	public final function identifier() {
		return $this->identifier;
	}

	/**
	 * Returns if the current gateway is able to process
	 * recurring payments
	 * @return bool
	 */
	public function is_recurring(){
		return $this->supports( 'recurring' );
	}

	/**
	 * Returns if the current gateway is able to process
	 * escrow payments
	 * @return bool
	 */
	public function is_escrow(){
		return $this->supports( 'escrow' );
	}

	/**
	 * Checks if the current gateway supports a specific service
	 * @return bool
	 */
	public function supports( $service = 'instant' ){
		switch ( $service ) {
			case 'instant':
				return ( $this instanceof APP_Instant_Payment_Processor );
				break;
			case 'recurring':
				return ( $this instanceof APP_Recurring_Payment_Processor );
				break;
			case 'escrow':
				return ( $this instanceof APP_Escrow_Payment_Processor );
				break;
			default:
				return false;
				break;
		}
	}

}

interface APP_Payment_Processor {

	/**
	 * Returns an array representing the form to output for admin configuration
	 * @return array scbForms style form array
	 */
	function form();

	/**
	 * Provides the unique identifier for this Gateway
	 *
	 * @return string
	 */
	function identifier();

	/**
	 * Provides the display name for this Gateway
	 *
	 * @return string
	 */
	function display_name( $type = 'dropdown' );

	/**
	 * Returns if the gateway supports the given service
	 * @return bool
	 */
	function supports( $service = 'instant' );

}

interface APP_Instant_Payment_Processor {

	/**
	 * Processes an order payment
	 * @param  APP_Order $order  The order to be processed
	 * @param  array $options    An array of user-entered options
	 *                           corresponding to the values provided in form()
	 * @return void
	 */
	function process( $order, array $options );

}

interface APP_Recurring_Payment_Processor {

	/**
	 * Process a recurring order
	 * @param APP_Order $order The order to be processed
	 * @param array $options An array of user-entered options
	 * 				corresponding to the values provided in form()
	 * @return void
	 */
	function process_recurring( $order, array $options );

}

interface APP_Escrow_Payment_Processor {

	/**
	 * Processes the initial setup of the escrow payment
	 * @param APP_Order $order The order to be processed
	 * @param array $options An array of user-entered options
	 * 				corresponding to the values provided in form()
	 * @return void
	*/
	function process_escrow( APP_Escrow_Order $order, array $options );

	/**
	* Completes the transaction towards the seller
	* @param APP_Order $order The order to be processed
	* @param array $options An array of user-entered options
	* 				corresponding to the values provided in form()
	* @return void
	*/
	function complete_escrow( APP_Escrow_Order $order, array $options );

	/**
	* Refunds the transaction towards the buyer
	* @param APP_Order $order The order to be processed
	* @param array $options An array of user-entered options
	* 				corresponding to the values provided in form()
	* @return void
	*/
	function fail_escrow( APP_Escrow_Order $order, array $options );

	/**
	 * Outputs/processes a form for user's escrow account data
	 */
	function user_form();
}