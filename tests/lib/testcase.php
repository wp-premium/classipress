<?php

require_once dirname( __FILE__ ) . '/constraints.php';

abstract class APP_UnitTestCase extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();

		do_action( 'appthemes_first_run' );

		$this->catcher = new APP_Mail_Catcher;
	}

	function tearDown() {
		parent::tearDown();

		// hooks aren't automatically reset between tests
		$this->catcher->detach();
	}

	function assertMailSentTo( $expected ) {
		$results = wp_list_pluck( $this->catcher->get_bounty(), 'to' );

		sort( $results );
		sort( $expected );

		$constraint = new PHPUnit_Framework_Constraint_IsEqual( $expected );

		self::assertThat( $results, $constraint );
	}

	function assertPostCount( $expected ) {
		self::assertThat( $GLOBALS['wp_query'], $this->postCount( $expected ) );
	}

	function assertArrayHasPairs( $expected, $actual ){

		foreach( $expected as $key => $value ){
			self::assertArrayHasKey( $key, $actual );
			self::assertEquals( $value, $actual[ $key ] );
		}

	}

	protected function postCount( $expected ) {
		return new APP_Constraint_Post_Count( $expected );
	}
}


class APP_Callback_Catcher {

	protected $hook;

	protected $history = array();

	function __construct( $hook ) {
		$this->hook = $hook;

		add_action( $hook, array( $this, '_catch' ) );
	}

	function detach() {
		remove_action( $this->hook, array( $this, '_catch' ) );
	}

	function _catch( $args ) {
		$this->history[] = func_get_args();
	}

	function was_called() {
		return ! empty( $this->history );
	}
}


class APP_Mail_Catcher extends APP_Callback_Catcher {

	function __construct() {
		parent::__construct( '_wp_mail_sent' );
	}

	function _catch( $args ) {
		$this->history[] = $args;
	}

	function get_bounty() {
		return $this->history;
	}
}

