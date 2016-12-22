<?php
/**
 * Test Item registry
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

/**
 * @group payments
 */
class APP_Item_RegistryTest extends APP_UnitTestCase {

	public function test_register(){

		APP_Item_Registry::register( 'test', 'Title', array( 'key' => 'data' ) );

		$this->assertTrue( APP_Item_Registry::is_registered( 'test' ) );

		$this->assertEquals( 'Title', APP_Item_Registry::get_title( 'test' ) );
		$this->assertEquals( array( 'key' => 'data' ), APP_Item_Registry::get_meta('test') );
		$this->assertEquals( 'data', APP_Item_Registry::get_meta( 'test', 'key' ) );

	}

	public function test_register_error_bad_id(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		APP_Item_Registry::register( array( 'not-a-string' ), 'Title', array( 'key' => 'data' ) );

	}

	public function test_get_title_bad_id(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		APP_Item_Registry::get_title( array( 'not-a-string' ) );

	}

	public function test_get_meta_bad_id(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		APP_Item_Registry::get_meta( array( 'not-a-string' ) );

	}

	public function test_get_meta_bad_meta(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		APP_Item_Registry::get_meta( 'test', array( 'not-a-string') );

	}

	public function test_is_registered_bad_id(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		APP_Item_Registry::is_registered( array( 'not-a-string' ) );

	}
}
