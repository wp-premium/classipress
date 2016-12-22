<?php
/**
 * Test Gateway processes
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

/**
 * @group payments
 */
class APP_Gateway_Processes_Test extends APP_UnitTestCase {

	public function test_gateway_creation(){

		appthemes_register_gateway( 'APP_Mock_Gateway' );
		$gateway = APP_Gateway_Registry::get_gateway( 'mock-gateway' );
		$this->assertNotEmpty( $gateway );

		// New gateways should not be enabled
		$this->assertFalse( APP_Gateway_Registry::is_gateway_enabled( 'mock-gateway' ) );

		$this->assertTrue( $this->_test_gateway_creation_internal( $gateway ) );
		return $gateway;
	}

	/**
	 * Tests the mock gateway functions properly
	 * @depends test_gateway_creation
	 */
	public function _test_gateway_creation_internal( $gateway ){

		$this->assertNotEmpty( $gateway->identifier() );
		$this->assertInternalType( 'string', $gateway->identifier() );

		$this->assertNotEmpty( $gateway->display_name() );
		$this->assertInternalType( 'string', $gateway->display_name() );

		$this->assertNotEmpty( $gateway->display_name( 'admin' ) );
		$this->assertInternalType( 'string', $gateway->display_name( 'admin' ) );

		$this->assertInternalType( 'array', $gateway->form() );
		$this->assertNotEmpty( $gateway->form() );

		$form = $gateway->form();

		$this->assertArrayHasKey( 'title', $form );
		$this->assertArrayHasKey( 'fields', $form );

		$this->assertInternalType( 'array', $form['fields'] );
		$this->assertNotEmpty( $form['fields'] );

		foreach( $form['fields'] as $field ){

			$this->assertInternalType( 'array', $field, 'A gateway form field is not an array' );
			$this->assertArrayHasKey( 'title', $field, 'A gateway form field does not have a name' );
			$this->assertArrayHasKey( 'name', $field, 'A gateway form field does not have a name' );

		}

		return true;

	}

	/**
	 * @depends test_gateway_creation
	 */
	public function test_anonymous_process_fail( $gateway ){

		$order = appthemes_new_order();
		$order->add_item( 'mock-process-test-item', 5, $order->get_id() );

		// Unenabled gateways should fail
		$status = appthemes_process_gateway( 'mock-gateway', $order );
		$this->assertFalse( $status );

		// Gateway should not have been called
		$this->assertFalse( $gateway->process_called() );

	}

	/**
	 * @depends test_gateway_creation
	 * @depends test_anonymous_process_fail
	 */
	public function test_anonymous_process_success( $gateway ){

		$order = appthemes_new_order();
		$order->add_item( 'mock-process-test-item', 5, $order->get_id() );

		$old_options = APP_Gateway_Registry::get_options();

		// Create new options object with gateway enabled
		$options = new stdClass;
		$options->gateways = array(
			'enabled' => array(
				'mock-gateway' => true
			)
		);
		APP_Gateway_Registry::register_options( $options );

		// Gateway should be enabled
		$this->assertTrue( APP_Gateway_Registry::is_gateway_enabled( 'mock-gateway' ) );

		// After enabled, it should pass
		$status = appthemes_process_gateway( 'mock-gateway', $order );
		$this->assertTrue( $status );

		// Processing should call the gateway
		$this->assertTrue( $gateway->process_called() );

		APP_Gateway_Registry::register_options( $old_options );
	}

	/**
	 * Verify that the defaults are returned for gateway
	 * @depends test_gateway_creation
	 */
	public function test_form_return( $gateway ){

		$form = $gateway->form();

		$values = APP_Gateway_Registry::get_gateway_options( $gateway->identifier() );
		$this->assertNotEmpty( $values );

		foreach( $form['fields'] as $field ){

			$this->assertEquals( $values[ $field['name'] ], $field['default'] );

		}
	}
}
