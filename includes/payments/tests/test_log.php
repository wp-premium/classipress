<?php
/**
 * Test logs
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

/**
 * @group payments
 */
class APP_Log_Test extends APP_UnitTestCase {

	public function test_post_log(){

		$id = wp_insert_post( array(
			'post_title' => 'test',
			'post_content' => 'test',
		) );

		$log = new APP_Post_log( $id );

		$log->log( 'test' );
		$this->assertCount( 1, $log->get_log() );

		$log->log( 'test-test' );
		$this->assertCount( 2, $log->get_log() );

		$log->clear_log();
		$this->assertCount( 0, $log->get_log() );

	}

	public function test_general_log(){

		$log = new APP_General_Log( 'test-general-log' );

		$log->log( 'test' );
		$this->assertCount( 1, $log->get_log() );

		$log->log( 'test-test' );
		$this->assertCount( 2, $log->get_log() );

		$log->clear_log();
		$this->assertCount( 0, $log->get_log() );

	}


}
