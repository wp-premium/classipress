<?php
/**
 * Test PayPal
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

class APP_PayPal_Test extends APP_UnitTestCase {

	public function setUp(){
		$this->bridge =  new APP_PayPal_Bridge;
		parent::setUp();
	}

	public function getOrder( $blank = false ){
		$order = appthemes_new_order();
		if( $blank )
			return $order;

		$order->add_item( 'paypal-test-item', 5, $order->get_id() );
		$order->set_gateway('paypal');
		return $order;
	}

	public function getResponse( $order = '' ){

		if( empty( $order ) )
			$order = $this->getOrder();

		$options = APP_Gateway_Registry::get_gateway_options('paypal');
		return array(
			'item_number' => $order->get_id(),
			'mc_currency' => $order->get_currency(),
			'mc_gross' => $order->get_total(),
			'business' => $options['email_address']
		);

	}

	public function test_complete_order(){

		$response = $this->getResponse();
		$order = appthemes_get_order( $response['item_number'] );

		$response['txn_type'] = 'web_accept';
		$response['payment_status'] = 'Completed';

		remove_all_actions( 'appthemes_transaction_completed' );

		$errors = $this->bridge->process_single( $response );
		$this->assertEmpty( $errors->get_error_codes(), "Asserting that no errors exist." );

		$order = appthemes_get_order( $response['item_number'] );
		$this->assertEquals( $order->get_status(), APPTHEMES_ORDER_COMPLETED );

	}

	public function test_complete_order_wrong_type(){

		$this->markTestIncomplete();

		$response = $this->getResponse();
		$order = appthemes_get_order( $response['item_number'] );

		$response['txn_type'] = 'masspay';
		$response['payment_status'] = 'Completed';

		remove_all_actions( 'appthemes_transaction_completed' );

		$errors = $this->bridge->process_single( $response );
		$this->assertNotEmpty( $errors->get_error_codes() );
		$this->assertArrayHasKey( 'wrong_type', $errors->get_error_codes() );

		return $this->assertEquals( APPTHEMES_ORDER_PENDING, $order->get_status() );

	}

	public function test_pending_order(){

		$this->markTestIncomplete();

		$response = $this->getResponse();
		$order = appthemes_get_order( $response['item_number'] );

		$response['txn_type'] = 'web_accept';
		$response['payment_status'] = 'Pending';

		remove_all_actions( 'appthemes_transaction_completed' );

		$errors = $this->bridge->handle_response( $response );
		$this->assertEmpty( $errors->get_error_codes() );

		$this->assertEquals( APPTHEMES_ORDER_PENDING, $order->get_status() );

	}

	public function test_bad_item_number(){

		// Set to order number that hasn't been reached yet
		$response = $this->getResponse();
		$response['item_number'] = $response['item_number'] + 1;

		$errors = $this->bridge->process_single( $response );
		$this->assertNotEmpty( $errors->get_error_codes() );
		$this->assertContains( 'bad_order', $errors->get_error_codes() );

		// Remove the order number before processing
		unset( $response['item_number'] );

		$errors = $this->bridge->process_single( $response );
		$this->assertNotEmpty( $errors->get_error_codes() );
		$this->assertContains( 'no_order', $errors->get_error_codes() );

	}

	public function test_bad_amount(){

		$response = $this->getResponse();
		$response['mc_gross'] = 999;

		$errors = $this->bridge->process_single( $response );
		$this->assertNotEmpty( $errors->get_error_codes() );
		$this->assertContains( 'bad_amount', $errors->get_error_codes() );

		unset( $response['mc_gross'] );

		$errors = $this->bridge->process_single( $response );
		$this->assertNotEmpty( $errors->get_error_code() );
		$this->assertContains( 'bad_amount', $errors->get_error_codes() );
	}

	public function test_bad_email(){

		$response = $this->getResponse();
		$response['business'] = 'example@example.com';

		$errors = $this->bridge->process_single( $response );
		$this->assertNotEmpty( $errors->get_error_code() );
		$this->assertContains( 'bad_email', $errors->get_error_codes() );

		unset( $response['business'] );

		$errors = $this->bridge->process_single( $response );
		$this->assertNotEmpty( $errors->get_error_code() );
		$this->assertContains( 'bad_email', $errors->get_error_codes() );

	}

	public function test_bad_currency(){

		$response = $this->getResponse();
		$response['mc_currency'] = 'ABC';

		$errors = $this->bridge->process_single( $response );
		$this->assertNotEmpty( $errors->get_error_code() );
		$this->assertContains( 'bad_currency', $errors->get_error_codes() );

		unset( $response['mc_currency'] );

		$errors = $this->bridge->process_single( $response );
		$this->assertNotEmpty( $errors->get_error_code() );
		$this->assertContains( 'bad_currency', $errors->get_error_codes() );

	}

	public function test_bad_gateway(){

		$order = $this->getOrder();
		$response = $this->getResponse( $order );

		$order->set_gateway( 'bank-transfer' );

		$errors = $this->bridge->process_single( $response );
		$this->assertNotEmpty( $errors->get_error_code() );
		$this->assertContains( 'bad_gateway', $errors->get_error_codes() );

		$order->clear_gateway();

		$errors = $this->bridge->process_single( $response );
		$this->assertNotEmpty( $errors->get_error_code() );
		$this->assertContains( 'bad_gateway', $errors->get_error_codes() );

	}

}
