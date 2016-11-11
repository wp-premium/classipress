<?php
/**
 * Test Reciept Order
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

/**
 * @group payments
 */
class APP_Order_ReceiptTest extends APP_UnitTestCase {

	protected $order;

	public function test_retrieve_error(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		APP_Order_Receipt::retrieve( 'not an id' );

	}

	public function test_retrieve_order( ){

		$order = APP_Order_Factory::create();
		$receipt = APP_Order_Receipt::retrieve( $order->get_id() );

		// Receipt should contain the same values as the original
		$this->assertEquals( $order->get_id(), $receipt->get_id() );
		$this->assertEquals( $order->get_description(), $receipt->get_description() );
		$this->assertEquals( $order->get_author(), $receipt->get_author() );
		$this->assertEquals( $order->get_ip_address(), $receipt->get_ip_address() );
		$this->assertEquals( $order->get_return_url(), $receipt->get_return_url() );
		$this->assertEquals( $order->get_cancel_url(), $receipt->get_cancel_url() );
		$this->assertEquals( $order->get_total(), $receipt->get_total() );
		$this->assertEquals( $order->get_currency(), $receipt->get_currency() );

		// Customized Data should also retain their value
		$order = APP_Order_Factory::create();
		$order->set_description( 'New Description' );
		$order->add_item( 'test', 100, $order->get_id() );

		$receipt = APP_Order_Receipt::retrieve( $order->get_id() );

		$this->assertEquals( $order->get_id(), $receipt->get_id() );
		$this->assertEquals( $order->get_description(), $receipt->get_description() );
		$this->assertEquals( $order->get_author(), $receipt->get_author() );
		$this->assertEquals( $order->get_ip_address(), $receipt->get_ip_address() );
		$this->assertEquals( $order->get_return_url(), $receipt->get_return_url() );
		$this->assertEquals( $order->get_cancel_url(), $receipt->get_cancel_url() );
		$this->assertEquals( $order->get_total(), $receipt->get_total() );
		$this->assertEquals( $order->get_currency(), $receipt->get_currency() );
	}

}

