<?php
/**
 * Test Order processes
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

/**
 * @group payments
 */
class APP_Order_Processes_Test extends APP_UnitTestCase {

	/**
	 * Test creating a draft order and upgrading it
	 */
	public function test_draft_order_creation(){

		$order = appthemes_new_order();
		$this->assertNotEmpty( $order );

		$draft_order = new APP_Draft_Order();

		$draft_order->set_description( 'Draft' );
		$draft_order->set_gateway( 'paypal' );
		$draft_order->set_currency( 'EUR' );

		$draft_order->add_item( 'test', 5, $order->get_id() );

		$this->assertEquals( 'Draft', $draft_order->get_description() );
		$this->assertEquals( 'paypal', $draft_order->get_gateway() );
		$this->assertEquals( 'EUR', $draft_order->get_currency() );
		$this->assertEquals( 5, $draft_order->get_total() );
		$this->assertCount( 1, $draft_order->get_items() );

		$new_order = appthemes_new_order( $draft_order );

		$this->assertEquals( 'Draft', $new_order->get_description() );
		$this->assertEquals( 'paypal', $new_order->get_gateway() );
		$this->assertEquals( 'EUR', $new_order->get_currency() );
		$this->assertEquals( 5, $new_order->get_total() );
		$this->assertCount( 1, $new_order->get_items() );

	}

	/**
	 * Verify that an order is retrieved correctly
	 */
	public function test_order_retrieval(){

		$order = appthemes_new_order();
		$this->assertNotEmpty( $order );

		$retrieved_order = appthemes_get_order( $order->get_id() );
		$this->assertEquals( $order, $retrieved_order );

	}

}
