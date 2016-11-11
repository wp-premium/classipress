<?php
/**
 * Gateway aimed at testing a themes integration with AppThemes Payments
 * Automatically installed if WP_Debug is enabled
 *
 * @package Components\Payments\Gateways
 */
class APP_TestGateway extends APP_Boomerang{

	/**
	 * Set up the gateway
	 */
	public function __construct() {

		parent::__construct( 'test_gateway', array(
			'dropdown' => 'Developer Gateway',
			'admin' => 'Developer Gateway',
			'recurring' => true,
		) );
		add_filter( 'appthemes_dev_gateway_fields', array( $this, 'display_order' ), 10, 2 );

	}

	/**
	 * This gateway contains no options
	 * See APP_Gateway::form()
	 */
	public function form() {
		return array();
	}

	/**
	 * Displays control panel for processing an order.
	 * @param  APP_Order $order   Order to be processed
	 * @param  array $options     Array of user options (not-used)
	 * @return void
	 */
	public function process( $order, $options ) {

		if( !$this->is_returning() ){
			$this->redirect( array(
				'action' => $this->get_return_url( $order )
			), array(), 'You are now being redirected.' );
			return;
		}

		?>
		<div class="section-head"><h2>Developer Payment Gateway</h2></div>
		<form id="create-listing" >
			<?php $this->view_transaction( $order, $options ); ?>
		</form>
		<?php
		include( dirname( __FILE__ ) . '/template/test-form.php' );

	}

	public function process_recurring( $order, $options ){
		$order->complete();
	}

	public function create_form( $order, $options ){}

	/**
	 * Displays information about the transaction
	 * @param  APP_Order $order   Order to be displayed
	 * @param  array $options     Array of user options (not-used)
	 * @return void
	 */
	public function view_transaction( $order, $options ){

		$sections = apply_filters( 'appthemes_dev_gateway_fields', array(), $order );

		$output = '';
		foreach( $sections as $title => $fields ){

			$output .= '<fieldset>';

			$output .= $this->display_section( $title );
			foreach( $fields as $field => $value ){
				$output .= $this->display_field( $field, $value );
			}

			$output .= '</fieldset>';
		}

		echo $output;
	}

	/**
	 * Adds general order sections to the fields displayed
	 * See view_transaction()
	 * @param  array $sections 	Sections already being displayed
	 * @param  APP_Order $order Order being processed
	 * @return array           	$sections with added sections
	 */
	public function display_order( $sections, $order ){

		$sections['General Information'] = array(
			'ID' => $order->get_id(),
		);

		$sections['Money &amp; Currency'] = array(
			'Currency' => APP_Currencies::get_name( $order->get_currency() ) . ' (' . $order->get_currency() . ')',
			'Total' => APP_Currencies::get_price( $order->get_total(), $order->get_currency() ),
		);

		return $sections;

	}

	/**
	 * Formats a section header
	 * @param  string $title Title of section
	 * @return string        Formatted section header html
	 */
	private function display_section( $title ){

		return '<div class="featured-head"><h3>' . $title . ' </h3></div>';

	}

	/**
	 * Formats a field
	 * @param  string $field Field name
	 * @param  string $value Field value
	 * @return string        Formatted field html
	 */
	private function display_field( $field, $value ){

		return '<div class="form-field"><label>' . $field . ': <strong>' . $value . '</strong></label></div>';

	}
}

appthemes_register_gateway( 'APP_TestGateway' );