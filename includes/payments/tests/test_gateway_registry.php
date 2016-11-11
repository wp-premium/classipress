<?php
/**
 * Test Gateway registry
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

/**
 * @group payments
 */
class APP_Gateway_RegistryTest extends APP_UnitTestCase {

	public function test_register(){

		APP_Gateway_Registry::register_gateway( 'APP_Mock_Gateway' );
		$this->assertTrue( APP_Gateway_Registry::is_gateway_registered( 'mock-gateway' ) );

		$gateway = APP_Gateway_Registry::get_gateway( 'mock-gateway' );
		$this->assertEquals( 'mock-gateway', $gateway->identifier() );

		$this->assertFalse( APP_Gateway_Registry::is_gateway_enabled( 'mock-gateway') );

		return $gateway;
	}

	public function test_register_error_bad_class(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		APP_Gateway_Registry::register_gateway( 'Non_Existant_Class' );

	}

	public function test_register_error_bad_value(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		APP_Gateway_Registry::register_gateway( array( 'not-a-string' ) );

	}

	public function test_get_gateway_bad_value(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		APP_Gateway_Registry::get_gateway( array( 'not-a-string' ) );

	}

	public function test_get_gateways(){

		$this->assertInternalType( 'array', APP_Gateway_Registry::get_gateways() );

	}

	/**
	 * @depends test_register
	 */
	public function test_gateway_defaults( $gateway ){

		$options = APP_Gateway_Registry::get_gateway_options( $gateway->identifier() );

		$this->assertNotEmpty( $options );
		$this->assertEquals( $options['test_field'], 'test_value' );

		$real_options = APP_Gateway_Registry::get_options();
		$real_options->gateways = array( 'enabled' => array(), $gateway->identifier() => array( 'test_field' => 'other_test_value' ) );

		$new_options = APP_Gateway_Registry::get_gateway_options( $gateway->identifier() );
		$this->assertNotEmpty( $new_options );
		$this->assertEquals( 'other_test_value', $new_options['test_field'] );

	}

}
