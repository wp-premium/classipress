<?php
/**
 * Test Gateway
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

/**
 * @group payments
 */
class APP_GatewayTest extends APP_UnitTestCase {

	public function create_gateway(){
		return array( array( new APP_Mock_Gateway ) );
	}

	public function create_min_gateway(){
		return array( array( new APP_Mock_Min_Gateway ) );
	}

	public function create_empty_gateway(){
		return array( array( new APP_Mock_Empty_Gateway ) );
	}

	/**
	 * @dataProvider create_min_gateway
	 */
	public function test_default_strings( $gateway ){

		$this->assertEquals( 'mock-min-gateway', $gateway->identifier() );

		$this->assertEquals( 'mock-min-gateway', $gateway->display_name( 'dropdown' ) );
		$this->assertEquals( 'mock-min-gateway', $gateway->display_name( 'admin' ) );

	}

	/**
	 * @dataProvider create_gateway
	 */
	public function test_strings( $gateway ){

		$this->assertEquals( 'mock-gateway', $gateway->identifier() );

		$this->assertEquals( 'Mock Gateway Dropdown Text', $gateway->display_name( 'dropdown' ) );
		$this->assertEquals( 'Mock Gateway Admin Text', $gateway->display_name( 'admin' ) );

	}



}

class APP_Mock_Gateway extends APP_Gateway {

	public function __construct(){

		parent::__construct( 'mock-gateway', array(
			'dropdown' => 'Mock Gateway Dropdown Text',
			'admin' => 'Mock Gateway Admin Text',
			'recurring' => true,
		) );

	}

	private $process_history = array();
	public function process( $order, $options ){
		$this->process_history[] = $order;
	}
	public function process_recurring( $order, $options ){
		$this->process_history[] = $order;
	}
	public function process_called(){
		return (bool) $this->process_history;
	}

	public function form(){
		return array(
			'title' => 'Mock Gateway Form',
			'fields' => array(
				array(
					'title' => 'Mock Field 1',
					'name' => 'mock_field_1',
					'type' => 'text',
					'default' => 'mock_field_default',
				),
				array(
					'title' => 'Mock Field 2',
					'name' => 'test_field',
					'default' => 'test_value'
				)
			)
		);
	}

}

class APP_Mock_Min_Gateway extends APP_Gateway {

	private $process_history = array();

	public function __construct(){
		parent::__construct( 'mock-min-gateway' );
	}

	public function process( $order, $options ){
		$this->process_history[] = $order;
	}

	public function form(){
		return array(
			'mock-min-gateway-form'
		);
	}

	public function process_called(){
		return (bool) $this->process_history;
	}

}

class APP_Mock_Empty_Gateway extends APP_Gateway {

	public function __construct(){
	}

	public function process( $order, $options ){
	}

	public function form(){
	}

}
