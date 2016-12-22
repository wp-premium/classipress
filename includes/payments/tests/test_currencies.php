<?php
/**
 * Test Currencies
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

/**
 * @group payments
 */
class APP_CurrenciesTest extends APP_UnitTestCase {

	/**
	 * Verify that initial currencies have been loaded
	 */
	public function test_default_currencies(){

		APP_Currencies::init();

		$this->assertNotEmpty( APP_Currencies::get_currencies() );

		$this->assertNotEmpty( APP_Currencies::get_currency( 'USD' ) );
		$this->assertEquals( 'US Dollars (&#36;)', APP_Currencies::get_currency_string( 'USD' ) );

	}

	/**
	 * Verify currencies added with no arguments are given proper defaults
	 */
	public function test_default_arguments() {

		$code = strtoupper( __FUNCTION__ );
		$status = APP_Currencies::add_currency( $code, array() );
		$this->assertTrue( $status );

		// Verify default currency behavior
		$this->assertEquals( $code, APP_Currencies::get_name( $code ) );
		$this->assertEquals( $code, APP_Currencies::get_symbol( $code ) );

		$currency = APP_Currencies::get_currency( $code );
		$this->assertNotEmpty( $currency );
		$expected = array(
			'code' => $code,
			'name' => $code,
			'symbol' => $code,
		);
		$this->assertEquals( $expected, $currency );
	}

	/**
	 * Verify currencies added with arguments retain those values
	 */
	public function test_input_arguments() {
		$code = strtoupper( __FUNCTION__ );
		$name = 'Test Name 123';
		$symbol = '###';
		$display = '{price}{symbol}';

		APP_Currencies::add_currency( $code, compact( 'name', 'symbol', 'display' )  );

		$this->assertEquals( $name, APP_Currencies::get_name( $code ) );
		$this->assertEquals( $symbol, APP_Currencies::get_symbol( $code ) );

		$currency_array = APP_Currencies::get_currency( $code );
		$this->assertNotEmpty( $currency_array );
		$this->assertEquals( compact( 'name', 'symbol', 'display', 'code' ), $currency_array );
	}

	/**
	 * Verifies that calling add_currency on an existing currency fails
	 */
	public function test_add_overwrite_protection(){

		$status = APP_Currencies::add_currency( 'USD', array(
			'name' => 'Not United States',
			'symbol' => '###',
			'display' => '{none}'
		) );

		$this->assertFalse( $status );
		$this->assertEquals( APP_Currencies::get_name( 'USD' ), 'US Dollars' );

	}

	/**
	 * Verifies that calling update_currency on an existing currency succeeds
	 */
	public function test_update_currency(){

		$code = strtoupper( __FUNCTION__ );

		$status = APP_Currencies::add_currency( $code, array() );
		$this->assertTrue( $status );

		$currency_args =  array(
			'name' => 'Updated',
			'symbol' => 'UUU',
			'display' => '{updated}',
			'code' => $code
		);

		APP_Currencies::update_currency( $code, $currency_args );
		$this->assertEquals( APP_Currencies::get_currency( $code ), $currency_args );
	}

	/**
	 * Verify that retrieving an unregistered currency returns false
	 */
	public function test_get_bad_currency(){

		$this->assertFalse( APP_Currencies::get_currency( 'not-a-real-currency' ) );

	}

	/**
	 * Verify currency strings return properly
	 */
	public function test_currency_string(){

		$string = APP_Currencies::get_currency_string( 'USD' );
		$this->assertEquals( 'US Dollars (&#36;)', $string );

		$string_array = APP_Currencies::get_currency_string_array();
		$this->assertInternalType( 'array', $string_array );
		$this->assertContains( $string, $string_array );

	}

	/**
	 * Verify that is_valid works correctly
	 */
	public function test_is_valid(){

		$this->assertTrue( APP_Currencies::is_valid( 'USD' ) );
		$this->assertFalse( APP_Currencies::is_valid( 'not-a-currency' ) );

	}

	public function test_price(){

		$price = APP_Currencies::get_price( 5, 'USD' );
		$this->assertEquals( '&#36;5', $price );

	}
}

/**
 * @group payments
 */
class APP_Current_Currencies_Test extends APP_UnitTestCase {

	private static $old_support;

	public static function setUpBeforeClass(){
		self::$old_support = get_theme_support( 'app-price-format' );
		remove_theme_support( 'app-price-format' );
	}

	public static function tearDownAfterClass(){
		add_theme_support( 'app-price-format', self::$old_support );
	}

	public function setUp(){
		parent::setUp();
		add_theme_support( 'app-price-format', array() );
	}

	/**
	 * Verifies that the default currency returns correctly
	 */
	public function test_currency_defaults(){

		$code = 'USD';
		$name = 'US Dollars';
		$symbol = '&#36;';

		$currency = APP_Currencies::get_current_currency();
		$this->assertNotEmpty( $currency );

		$this->assertEquals( compact( 'code', 'name', 'symbol' ), $currency );
		$this->assertEquals( $name, APP_Currencies::get_current_currency( 'name' ) );
		$this->assertEquals( $symbol, APP_Currencies::get_current_currency( 'symbol' ) );

		$this->assertEquals( $name, APP_Currencies::get_current_name() );
		$this->assertEquals( $symbol, APP_Currencies::get_current_symbol() );

	}


}

