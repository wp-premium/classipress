<?php
/**
 * Test templates API
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

/**
 * @group payments
 */
class APP_Payments_Templates_Test extends APP_UnitTestCase {


	private static $order;
	public function setUp(){
		parent::setUp();
		self::$order = appthemes_new_order();
	}

	public function test_get_order(){

		$order = get_order( self::$order->get_id() );
		$this->assertEquals( $order, self::$order );

	}

	public function test_get_the_order_id(){

		$id = get_the_order_id( self::$order->get_id() );
		$this->assertEquals( $id, self::$order->get_id() );

		// phpUnit won't recognize number only content for some reason...
		$this->expectOutputString( (string) $id  );
		the_order_id( self::$order->get_id() );

	}

	public function test_get_the_order_description(){

		$description = get_the_order_description( self::$order->get_id() );
		$this->assertEquals( $description, self::$order->get_description() );

		$this->expectOutputString( $description );
		the_order_description( self::$order->get_id() );

	}

	public function test_get_the_order_status(){

		$status = get_the_order_status( self::$order->get_id() );
		$this->assertEquals( $status, self::$order->get_display_status() );

		$this->expectOutputString( $status );
		the_order_status( self::$order->get_id() );

	}

	public function test_get_the_order_total(){

		$total = get_the_order_total( self::$order->get_id() );
		$this->assertEquals( $total, appthemes_get_price( self::$order->get_total(), self::$order->get_currency() ) );

		$this->expectOutputString( $total );
		the_order_total( self::$order->get_id() );

	}

	public function test_get_the_order_currency(){

		$currency = get_the_order_currency_name( self::$order->get_id() );
		$this->assertEquals( $currency, APP_Currencies::get_name( self::$order->get_currency() ) );

		$this->expectOutputString( $currency );
		the_order_currency( self::$order->get_id() );

	}

	public function test_get_the_order_return_url(){

		$return_url = get_the_order_return_url( self::$order->get_id() );
		$this->assertEquals( $return_url, self::$order->get_return_url() );

		$this->expectOutputString( $return_url );
		the_order_return_url( self::$order->get_id() );

	}

	public function test_get_the_order_cancel_url(){

		$cancel_url = get_the_order_cancel_url( self::$order->get_id() );
		$this->assertEquals( $cancel_url, self::$order->get_cancel_url() );

		$this->expectOutputString( $cancel_url );
		the_order_cancel_url( self::$order->get_id() );

	}
}
