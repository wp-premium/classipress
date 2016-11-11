<?php
/**
 * Test Order
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';
P2P_Storage::init();
P2P_Storage::install();

/**
 * @group payments
 */
class APP_OrderTest extends APP_UnitTestCase {

	protected $order;

	public static function setUpBeforeClass(){

		appthemes_setup_orders();

	}

	public function setUp(){
		parent::setUp();

		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$this->order = APP_Order_Factory::create();

	}

	public function test_retrieve_error(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		APP_Order_Factory::retrieve( 'not an id' );

	}

	public function test_create_order_defaults(){

		$this->assertEquals( 'Transaction', $this->order->get_description() );
		$this->assertEquals( APPTHEMES_ORDER_PENDING, $this->order->get_status() );
		$this->assertEquals( 0, $this->order->get_total() );
		$this->assertEmpty( $this->order->get_items() );
		$this->assertEmpty( $this->order->get_gateway() );

		return $this->order;

	}

	public function test_retrieve_order( ){

		$this->order_id = $this->order->get_id();

		$new_order = APP_Order_Factory::retrieve( $this->order_id );
		$this->assertEquals( $this->order, $new_order );

	}


	public function test_creating_order_recieves_default_currency(){

		// Create Mock Options Array
		add_theme_support( 'app-price-format', array(
			'currency_default' => 'GBP'
		) );

		$this->order = APP_Order_Factory::create();

		// Default currency should be the code in the options
		$this->assertEquals( 'GBP', $this->order->get_currency() );

	}

	function test_creating_order_recieves_default_description(){

		// Default description should be 'Transaction' (unless theme is localized)
		$this->assertEquals( 'Transaction', $this->order->get_description() );

	}

	function test_change_description_reflects(){

		// Setting a new description should cause it to be returned
		$this->order->set_description( 'Test Description' );
		$this->assertEquals( 'Test Description', $this->order->get_description() );

	}

	function test_change_description_non_string_errors(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_warning' );
		$this->order->set_description( array( 'not-a-string' ) );

	}

	function test_change_currency_reflects(){

		// Setting a new currency should cause it to be returned
		$this->order->set_currency( 'EUR' );
		$this->assertEquals( 'EUR', $this->order->get_currency() );

	}

	function test_change_currency_bad_value_errors(){

		// Fail on Bad Currency Code
		$return_value = $this->order->set_currency( 'not-valid' );
		$this->assertFalse( $return_value );

	}

	function test_change_currency_non_string_errors(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		$this->order->set_currency( array( 'not-a-string' ) );
	}

	function test_change_currency_error_does_not_reflect(){

		$this->order->set_currency( 'EUR' );
		$this->order->set_currency( 'not-valid' );

		// Failed calls should retain old value
		$this->assertEquals( 'EUR', $this->order->get_currency() );

	}

	function test_default_gateway_blank(){

		$this->assertEmpty( $this->order->get_gateway() );

	}

	function test_change_gateway_reflects(){

		$this->order->set_gateway( 'paypal' );
		$this->assertEquals( 'paypal', $this->order->get_gateway() );
		$this->assertEquals( 'paypal', get_post_meta( $this->order->get_id(), 'gateway', true ) );

	}

	function test_clear_gateway_reflects(){

		$this->order->set_gateway( 'paypal' );

		// Clearing a gateway should set it to blank
		$this->order->clear_gateway();
		$this->assertEmpty( $this->order->get_gateway() );

	}

	function test_change_gateway_bad_value_errors(){

		// Fail on Bad Gateway ID
		$return_value = $this->order->set_gateway( 'non-existant-gateway' );
		$this->assertFalse( $return_value );

	}

	function test_change_gateway_error_does_not_reflect(){

		$this->order->set_gateway( 'paypal' );
		$this->order->set_gateway( 'non-existant-gateway' );

		// Failed calls should retain old value
		$this->assertEquals( 'paypal', $this->order->get_gateway() );

	}

	function test_change_gateway_non_string_errors(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		$this->order->set_gateway( array( 'not-a-string' ) );

	}

	function test_add_item_reflects(){

		$status = $this->order->add_item( 'payment-test', 5, $this->order->get_id() );
		$this->assertTrue( $status );
		$this->assertCount( 1, $this->order->get_items() );

		$status = $this->order->add_item( 'new-type', 5 );
		$this->assertTrue( $status );
		$this->assertCount( 2, $this->order->get_items() );
	}

	function test_add_item_no_post_id_reflects(){

		$status = $this->order->add_item( 'test', 5 );
		$this->assertTrue( $status );

		$item = $this->order->get_item(0);
		$this->assertEquals( $this->order->get_id(), $item['post_id'] );

	}

	function test_add_item_numeric_index_no_error(){

		$status = $this->order->add_item( 123, 123 );
		$this->assertTrue( $status );

	}

	function test_get_item_returns_correct_values(){

		$this->order->add_item( 'payment-test', 5, $this->order->get_id() );

		$item = $this->order->get_item();
		$this->assertNotEmpty( $item );

		$this->assertArrayHasKey( 'price', $item );
		$this->assertEquals( 5, $item['price'] );

		$this->assertArrayHasKey( 'post_id', $item );
		$this->assertEquals( $this->order->get_id(), $item['post_id'] );

	}

	function test_get_item_returns_correct_index(){

		$this->order->add_item( 'new-type-1', 5, $this->order->get_id() );
		$this->order->add_item( 'new-type-2', 5, $this->order->get_id() );

		$new_item = $this->order->get_item(1);
		$this->assertEquals( 'new-type-2', $new_item['type'] );

	}

	function test_get_items_returns_all_items(){

		$this->order->add_item( 'payment-test', 5, $this->order->get_id() );
		$this->order->add_item( 'payment-test', 5, $this->order->get_id() );
		$this->order->add_item( 'payment-test1', 5, $this->order->get_id() );

		// Adding additional items should increase the count
		$this->assertCount( 3, $this->order->get_items() );

	}

	function test_get_items_filters_by_type(){

		$this->order->add_item( 'type-1', 1, $this->order->get_id() );
		$this->order->add_item( 'type-2', 2, $this->order->get_id() );
		$this->order->add_item( 'type-2', 3, $this->order->get_id() );
		$this->order->add_item( 'type-3', 4, $this->order->get_id() );
		$this->order->add_item( 'type-3', 5, $this->order->get_id() );
		$this->order->add_item( 'type-3', 6, $this->order->get_id() );

		// APP_Order::get_items should filter items properly
		$this->assertCount( 2, $this->order->get_items( 'type-2') );
	}

	function test_get_items_bad_index_errors(){

		$this->order->add_item( 'test-item', 1, $this->order->get_id() );

		// Getting a non-existant item should return false
		$this->assertFalse( $this->order->get_item( 10 ) );

	}

	function test_add_item_unique_deletes_others(){

		$this->order->add_item( 'payment-test', 5, $this->order->get_id() );
		$this->order->add_item( 'payment-test', 5, $this->order->get_id() );

		$this->order->add_item( 'payment-test', 15, $this->order->get_id(), true );

		$this->assertCount( 1, $this->order->get_items() );
		$this->assertEquals( 15, $this->order->get_total() );

	}

	function test_add_item_unqiue_only_deletes_same_type(){

		$this->order->add_item( 'payment-test1', 5, $this->order->get_id() );
		$this->order->add_item( 'payment-test2', 10, $this->order->get_id() );

		$this->order->add_item( 'payment-test2', 15, $this->order->get_id(), true );

		$this->assertCount( 2, $this->order->get_items() );
		$this->assertEquals( 20, $this->order->get_total() );
	}

	function test_remove_item_by_type_reflects(){

		$this->order->add_item( 'payment-test', 5, $this->order->get_id() );

		$this->order->remove_item( 'payment-test' );

		$this->assertCount( 0, $this->order->get_items() );
		$this->assertEquals( 0, $this->order->get_total() );

	}

	function test_remove_item_multiple_by_type_reflects(){

		$this->order->add_item( 'payment-test', 5 );
		$this->order->add_item( 'payment-test', 4 );
		$this->order->add_item( 'payment-test', 3 );
		$this->order->add_item( 'payment-test', 2 );
		$this->order->add_item( 'payment-test', 1 );

		$this->order->remove_item( 'payment-test' );

		$this->assertCount( 0, $this->order->get_items() );

	}

	function test_remove_item_by_price_reflects(){

		$this->order->add_item( 'payment-test', 5 );
		$this->order->add_item( 'payment-test', 4 );
		$this->order->add_item( 'payment-test1', 3 );
		$this->order->add_item( 'payment-test2', 2 );
		$this->order->add_item( 'payment-test3', 1 );

		$this->order->remove_item( '', 5 );

		$this->assertCount( 4, $this->order->get_items() );

	}

	function test_remove_item_by_id_reflects(){

		$new_order = appthemes_new_order();
		$this->order->add_item( 'payment-test', 5 );
		$this->order->add_item( 'payment-test', 4 );
		$this->order->add_item( 'payment-test1', 3, $new_order->get_id() );
		$this->order->add_item( 'payment-test2', 2, $new_order->get_id() );
		$this->order->add_item( 'payment-test3', 1, $new_order->get_id() );

		$this->order->remove_item( '', '', $new_order->get_id() );

		$this->assertCount( 2, $this->order->get_items() );

	}

	function test_remove_item_by_all_reflects(){

		$this->order->add_item( 'payment-test', 5 );
		$this->order->add_item( 'payment-test', 4 );
		$this->order->add_item( 'payment-test1', 3 );
		$this->order->add_item( 'payment-test2', 2 );
		$this->order->add_item( 'payment-test3', 1 );

		$this->order->remove_item();

		$this->assertCount( 0, $this->order->get_items() );

	}

	function test_add_item_bad_type(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		$this->order->add_item( array( 'not-a-string' ), 100, 100 );

	}

	function test_add_item_bad_price(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		$this->order->add_item( 'test', 'not-a-number', 100 );

	}

	function test_add_item_bad_post(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		$this->order->add_item( 'test', 100, 'not-a-number' );

	}

	function test_get_item_bad_type(){

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
		$this->order->get_items( new stdClass() );

	}

	function test_get_total_default_zero(){

		$this->assertEquals( 0, $this->order->get_total() );

	}

	function test_get_total_reflects_added_items(){

		$this->order->add_item( 'payment-test', 5, $this->order->get_id() );
		$this->assertEquals( 5, $this->order->get_total() );

	}


	function test_get_total_reflects_multiple_added_items(){

		$this->order->add_item( 'payment-test1', 5 );
		$this->order->add_item( 'payment-test2', 5 );

		$this->assertEquals( 10, $this->order->get_total() );

	}

	function test_get_total_reflects_decimals(){

		$this->order->add_item( 'payment-test', 6.99 );
		$this->order->add_item( 'payment-test', 5.99 );

		$this->assertEquals( 12.98, $this->order->get_total() );

	}

	function test_get_status(){

		$this->assertEquals( APPTHEMES_ORDER_PENDING, $this->order->get_status() );
		$this->assertEquals( 'Pending', $this->order->get_display_status() );

	}

	function test_get_author(){

		$this->order->set_author( 99 );

		$this->refresh_order();
		$this->assertEquals( 99, $this->order->get_author() );

	}

	function test_order_copy(){

		$this->assertNotEmpty( $this->order->get_author() );

		$new_order = APP_Order_Factory::duplicate( $this->order );
		$check_fields = array( 'description', 'status', 'author', 'gateway', 'currency', 'total', 'ip_address' );

		$this->compareFields( $this->order, $new_order, $check_fields );

		$this->order->set_currency( 'GBP' );
		$this->order->add_item( 'payment-test', 5.99, $this->order->get_id() );
		$this->order->set_gateway( 'paypal' );
		$this->order->set_description( 'asdfasdfasfd' );

		$modified_order = APP_Order_Factory::duplicate( $this->order );
		$this->compareFields( $this->order, $modified_order, $check_fields );
	}

	function compareFields( $first, $second, $fields ){

		if( 'all' == $fields ){
			$fields = array( 'id', 'description', 'status', 'author', 'gateway', 'currency', 'total', 'ip_address',
				'items' );
		}

		foreach( $fields as $field ){
			$this->compareField( $first, $second, $field );
		}
	}

	function compareField( $first, $second, $field ){

		switch( $field ){

			case 'id':
				$this->assertEquals( $first->get_id(), $second->get_id() );
				break;
			case 'description':
				$this->assertEquals( $first->get_description(), $second->get_description() );
				break;
			case 'status':
				$this->assertEquals( $first->get_status(), $second->get_status() );
				break;
			case 'author':
				$this->assertEquals( $first->get_author(), $second->get_author() );
				break;
			case 'gateway':
				$this->assertEquals( $first->get_gateway(), $second->get_gateway() );
				break;
			case 'currency':
				$this->assertEquals( $first->get_currency(), $second->get_currency() );
				break;
			case 'total':
				$this->assertEquals( $first->get_total(), $second->get_total() );
				break;
			case 'ip_address':
				$this->assertEquals( $first->get_ip_address(), $second->get_ip_address() );
				break;
			case 'items':
				$this->assertEquals( $first->get_items(), $second->get_items() );
				break;
		}

	}

	// Disable until further expirmentation with events is figured out
	function disabled_complete_order(){

		$pending = new APP_Callback_Catcher( 'appthemes_transaction_pending' );
		$completed = new APP_Callback_Catcher( 'appthemes_transaction_completed' );
		$activated = new APP_Callback_Catcher( 'appthemes_transaction_activated' );
		$this->order->complete();

		$this->assertTrue( $completed->was_called() );

	}

	function disabled_activate_order(){

		$pending = new APP_Callback_Catcher( 'appthemes_transaction_pending' );
		$completed = new APP_Callback_Catcher( 'appthemes_transaction_completed' );
		$activated = new APP_Callback_Catcher( 'appthemes_transaction_activated' );
		$this->order->activate();

		$this->assertTrue( $activated->was_called() );

	}

	protected function refresh_order(){
		$id = $this->order->get_id();
		$this->order = appthemes_get_order( $id, true );
	}

}
