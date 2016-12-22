<?php
/**
 * Test Number format
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

/**
 * @group payments
 */
class APP_Number_Format_Test extends APP_UnitTestCase {

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

	public function test_args(){

		$this->assertArrayHasPairs( array(
			'hide_decimals' => false,
			'currency_default' => 'USD',
			'currency_identifier' => 'symbol',
			'currency_position' => 'left',
			'thousands_separator' => ',',
			'decimal_separator' => '.',
		), appthemes_price_format_get_args() );


		add_theme_support( 'app-price-format', array(
			'currency_identifier' => 'code'
		) );

		$args = appthemes_price_format_get_args();
		$this->assertEquals( 'code', $args['currency_identifier'] );

		add_theme_support( 'app-price-format', array(
			'currency_identifier' => 'not-supported'
		) );

		$args = appthemes_price_format_get_args();
		$this->assertEquals( 'symbol', $args['currency_identifier'] );

		add_theme_support( 'app-price-format', array(
			'currency_default' => 'GBP'
		) );

		$this->assertArrayHasPairs( array(
			'currency_default' => 'GBP',
		), appthemes_price_format_get_args() );

	}

	public function test_defaults(){

		$string = appthemes_get_price( 1 );
		$this->assertEquals( '&#36;1.00', $string );

		$string = appthemes_get_price( 1000 );
		$this->assertEquals( '&#36;1,000.00', $string );

		$string = appthemes_get_price( 1000000 );
		$this->assertEquals( '&#36;1,000,000.00', $string );

		$string = appthemes_get_price( 10.00 );
		$this->assertEquals( '&#36;10.00', $string );

		$string = appthemes_get_price( 10.95 );
		$this->assertEquals( '&#36;10.95', $string );

	}

	public function test_currency(){

		add_theme_support( 'app-price-format', array(
			'currency_default' => 'EUR'
		) );

		$string = appthemes_get_price( 1 );
		$this->assertEquals( '&euro;1.00', $string );

		$string = appthemes_get_price( 1000 );
		$this->assertEquals( '&euro;1,000.00', $string );

		$string = appthemes_get_price( 1000000 );
		$this->assertEquals( '&euro;1,000,000.00', $string );

		$string = appthemes_get_price( 10.00 );
		$this->assertEquals( '&euro;10.00', $string );

		$string = appthemes_get_price( 10.95 );
		$this->assertEquals( '&euro;10.95', $string );

	}

	public function test_currency_override(){

		$string = appthemes_get_price( 1, 'EUR' );
		$this->assertEquals( '&euro;1.00', $string );

		$string = appthemes_get_price( 1000, 'EUR' );
		$this->assertEquals( '&euro;1,000.00', $string );

		$string = appthemes_get_price( 1000000, 'EUR' );
		$this->assertEquals( '&euro;1,000,000.00', $string );

		$string = appthemes_get_price( 10.00, 'EUR' );
		$this->assertEquals( '&euro;10.00', $string );

		$string = appthemes_get_price( 10.95, 'EUR' );
		$this->assertEquals( '&euro;10.95', $string );

	}

	// Disabled until separator is added back in
	public function disabled_decimal_separator(){

		add_theme_support( 'app-price-format', array(
			'decimal_separator' => '#'
		) );

		$string = appthemes_get_price( 1 );
		$this->assertEquals( '&#36;1#00', $string );

		$string = appthemes_get_price( 1000 );
		$this->assertEquals( '&#36;1,000#00', $string );

		$string = appthemes_get_price( 1000000 );
		$this->assertEquals( '&#36;1,000,000#00', $string );

		$string = appthemes_get_price( 10.00 );
		$this->assertEquals( '&#36;10#00', $string );

		$string = appthemes_get_price( 10.95 );
		$this->assertEquals( '&#36;10#95', $string );

	}

	// Disabled until separator is added back in
	public function disabled_thousands_separator(){

		add_theme_support( 'app-price-format', array(
			'thousands_separator' => '#'
		) );

		$string = appthemes_get_price( 1000 );
		$this->assertEquals( '&#36;1#000.00', $string );

		$string = appthemes_get_price( 1000000 );
		$this->assertEquals( '&#36;1#000#000.00', $string );

	}

	public function test_hide_decimal(){

		add_theme_support( 'app-price-format', array(
			'hide_decimals' => true,
		) );

		$string = appthemes_get_price( 1 );
		$this->assertEquals( '&#36;1', $string );

		$string = appthemes_get_price( 1000 );
		$this->assertEquals( '&#36;1,000', $string );

		$string = appthemes_get_price( 1000000 );
		$this->assertEquals( '&#36;1,000,000', $string );

		$string = appthemes_get_price( 10.00 );
		$this->assertEquals( '&#36;10', $string );

		$string = appthemes_get_price( 10.95 );
		$this->assertEquals( '&#36;11', $string );

	}


	public function test_format(){

		add_theme_support( 'app-price-format', array(
			'currency_identifier' => 'code',
			'currency_position' => 'right_space',
		) );

		$string = appthemes_get_price( 1 );
		$this->assertEquals( '1.00 USD', $string );

		$string = appthemes_get_price( 1000 );
		$this->assertEquals( '1,000.00 USD', $string );

		$string = appthemes_get_price( 1000000 );
		$this->assertEquals( '1,000,000.00 USD', $string );

		$string = appthemes_get_price( 10.00 );
		$this->assertEquals( '10.00 USD', $string );

		$string = appthemes_get_price( 10.95 );
		$this->assertEquals( '10.95 USD', $string );

	}


	public function test_output(){

		$string = appthemes_get_price( 1 );
		$this->expectOutputString( $string );

		appthemes_display_price( 1 );

	}
}

