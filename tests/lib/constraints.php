<?php

class APP_Constraint_Post_Count extends PHPUnit_Framework_Constraint {

	function __construct( $expected ) {
		$this->expected = $expected;
	}

	function matches( $wp_query ) {
		return $this->expected == count( $wp_query->posts );
	}

	function toString() {
		return sprintf( 'has %d posts', $this->expected );
	}
}


class APP_Constraint_WP_Query extends PHPUnit_Framework_Constraint {

	public function __construct( $description, $test_cb ) {
		$this->desc = $description;
		$this->test = $test_cb;
	}

	function matches( $wp_query ) {
		foreach ( $wp_query->posts as $post ) {
			if ( ! call_user_func( $this->test, $post ) )
				return false;
		}

		return true;
	}

	function toString() {
		return $this->desc;
	}
}

