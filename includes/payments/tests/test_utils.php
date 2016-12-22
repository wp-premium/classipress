<?php
/**
 * Test payments utils
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

/**
 * @group payments
 */
class APP_Payments_Utils_Test extends APP_UnitTestCase {

	public function test_order_creation(){

		$total = appthemes_prorate( 5, 5, 3 );
		$this->assertEquals( 3, $total );

		$total = appthemes_prorate( 10, 5, 3 );
		$this->assertEquals( 6, $total );

		$total = appthemes_prorate( 5.55, 5, 3 );
		$this->assertEquals( 3.33, $total );

	}

}