<?php
/**
 * Test Draft Order
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

/**
 * @group payments
 */
class APP_Draft_OrderTest extends APP_UnitTestCase {

	public static function setUpBeforeClass(){

		appthemes_setup_orders();

	}

	public function setUp(){

		parent::setUp();
		add_theme_support( 'app-payments', array(
			'currency_default' => 'USD'
		) );
	}

	/**
	 * Test that new orders get the correct defaults
	 */
	public function test_create_order_defaults(){

		$order = new APP_Draft_Order();

		$this->assertEquals( 'Transaction', $order->get_description() );
		$this->assertEquals( APPTHEMES_ORDER_PENDING, $order->get_status() );
		$this->assertEquals( 0, $order->get_total() );
		$this->assertEmpty( $order->get_items() );
		$this->assertEmpty( $order->get_gateway() );

		return $order;

	}

	/**
	 * Test that new draft orders inherit the default currency
	 */
	public function test_create_order_default_currency(){

		add_theme_support( 'app-price-format', array(
			'currency_default' => 'GBP'
		) );

		$order = new APP_Draft_Order();
		$this->assertEquals( 'GBP', $order->get_currency() );

	}

	/**
	 * Test that changing the description updates the return of get_description() properly
	 */
	function test_change_description(){

		$order = new APP_Draft_Order();

		// Default description should be 'Transaction' (unless theme is localized)
		$this->assertEquals( 'Transaction', $order->get_description() );

		// Setting a new description should cause it to be returned
		$order->set_description( 'Test Description' );
		$this->assertEquals( 'Test Description', $order->get_description() );

	}

	/**
	 * Test that changing the currency gives the desired result
	 */
	function test_change_currency(){

		$order = new APP_Draft_Order();

		$order->set_currency( 'EUR' );
		$this->assertEquals( 'EUR', $order->get_currency() );

	}

	/**
	 * Test that changing to currency to bad value fails,
	 * and doesn't change the order's currency
	 */
	function test_change_currency_fail(){

		$order = new APP_Draft_Order();
		$default_currency = $order->get_currency();

		$return_value = $order->set_currency( '543' );
		$this->assertFalse( $return_value );

		$this->assertEquals( $default_currency, $order->get_currency() );
	}

	/**
	 * Test that changing a gateway gives the desired affects
	 */
	function test_change_gateway(){

		$order = new APP_Draft_Order();

		// Default gateway should be empty
		$this->assertEmpty( $order->get_gateway() );

		// Setting a new gateway should cause it to be returned
		$order->set_gateway( 'paypal' );
		$this->assertEquals( 'paypal', $order->get_gateway() );

		// Fail on Bad Gateway ID
		$return_value = $order->set_gateway( 'non-existant-gateway' );
		$this->assertFalse( $return_value );

		// Failed calls should retain old value
		$this->assertEquals( 'paypal', $order->get_gateway() );

		// Clearing a gateway should set it to blank
		$order->clear_gateway();
		$this->assertEmpty( $order->get_gateway() );

		return true;
	}

	/**
	 * Test that adding an item gives the desired effect
	 */
	function test_add_item_basic(){

		$order = new APP_Draft_Order();
		$order->add_item( 'payment-test', 5, $order->get_id() );

		// Adding an item should only increase the item count by 1
		$this->assertCount( 1, $order->get_items() );

		// Adding an item should cause it to be returned
		$this->assertCount( 1, $order->get_items( 'payment-test') );

		$item = $order->get_item();
		$this->assertNotEmpty( $item );

		// The correct values should be contained in the item array
		$this->assertArrayHasKey( 'price', $item );
		$this->assertEquals( 5, $item['price'] );

		$this->assertArrayHasKey( 'post_id', $item );
		$this->assertEquals( $order->get_id(), $item['post_id'] );

		$order->add_item( 'payment-test', 5, $order->get_id() );
		$order->add_item( 'payment-test', 5, $order->get_id() );
		$order->add_item( 'payment-test1', 5, $order->get_id() );

		// Adding additional items should increase the count
		$this->assertCount( 4, $order->get_items() );

		// APP_Order::get_items should filter items properly
		$this->assertCount( 3, $order->get_items( 'payment-test') );

		// Getting a non-existant item should return false
		$this->assertFalse( $order->get_item( 10 ) );

	}

	/**
	 * Checks that the total returns properly
	 */
	function test_get_total_basic(){

		$order = new APP_Draft_Order();

		// By default, order's totals should be 0
		$this->assertEquals( 0, $order->get_total() );

		// Total should reflect added items prices
		$order->add_item( 'payment-test', 5, $order->get_id() );
		$this->assertCount( 1, $order->get_items() );
		$this->assertEquals( 5, $order->get_total() );

		// Adding another item should increase the price
		$order->add_item( 'payment-test', 5, $order->get_id() );
		$this->assertCount( 2, $order->get_items() );
		$this->assertEquals( 10, $order->get_total() );

		// Adding another item type should increase the price
		$order->add_item( 'payment-test1', 5, $order->get_id() );
		$this->assertCount( 3, $order->get_items() );
		$this->assertEquals( 15, $order->get_total() );

		// Adding a float value as a price should correctly calculate the total with cents
		$order->add_item( 'payment-test1', 5.99, $order->get_id() );
		$this->assertCount( 4, $order->get_items() );
		$this->assertEquals( 20.99, $order->get_total() );

	}

	/**
	 * Tests that the proper status is returned
	 */
	function test_get_status(){

		$order = new APP_Draft_Order();

		$this->assertEquals( APPTHEMES_ORDER_PENDING, $order->get_status() );
		$this->assertEquals( 'Pending', $order->get_display_status() );

		$order->failed();

		$this->assertEquals( APPTHEMES_ORDER_FAILED, $order->get_status() );
		$this->assertEquals( 'Failed', $order->get_display_status() );

		$order->complete();

		$this->assertEquals( APPTHEMES_ORDER_COMPLETED, $order->get_status() );
		$this->assertEquals( 'Completed', $order->get_display_status() );

		$order->activate();

		$this->assertEquals( APPTHEMES_ORDER_ACTIVATED, $order->get_status() );
		$this->assertEquals( 'Activated', $order->get_display_status() );

	}

	public function test_upgrade_order(){

		$author = $this->factory->user->create( array( 'role' => 'contributor' ) );
		wp_set_current_user( $author );

		$order = new APP_Draft_Order();

		$order->set_description( 'Draft' );
		$order->set_gateway( 'paypal' );
		$order->set_currency( 'EUR' );

		// We need an existing order that can be connected to
		$real_order = APP_Order_Factory::create();
		$status = $order->add_item( 'test', 5, $real_order->get_id() );

		$new_real_order = APP_Draft_Order::upgrade( $order );

		$this->assertEquals( $author, $order->get_author() );
		$this->assertEquals( $order->get_author(), $new_real_order->get_author() );
		$this->assertEquals( 'Draft', $new_real_order->get_description() );
		$this->assertEquals( 'paypal', $new_real_order->get_gateway() );
		$this->assertEquals( 'EUR', $new_real_order->get_currency() );
		$this->assertCount( 1, $new_real_order->get_items() );
		$this->assertEquals( 5, $new_real_order->get_total() );

	}

}
