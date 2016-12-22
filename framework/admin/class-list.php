<?php
/**
 * Generic container for easily manipulating an ordered associative array
 *
 * @package Framework\List
 */
class APP_List {

	protected $items = array();

	function add( $id, $payload ) {
		// TODO: allow overwrite or have a replace() method ?
		$this->items[ $id ] = $payload;
	}

	function add_before( $ref_id, $id, $payload ) {
		$new_array = array();

		$found = false;
		foreach ( $this->items as $key => $value ) {
			if ( $key == $ref_id ) {
				$new_array[ $id ] = $payload;
				$found = true;
			}

			$new_array[ $key ] = $value;
		}

		if ( ! $found ) {
			$new_array[ $id ] = $payload;
		}

		$this->items = $new_array;
	}

	function add_after( $ref_id, $id, $payload ) {
		$new_array = array();

		$found = false;
		foreach ( $this->items as $key => $value ) {
			$new_array[ $key ] = $value;

			if ( $key == $ref_id ) {
				$new_array[ $id ] = $payload;
				$found = true;
			}
		}

		if ( ! $found ) {
			$new_array[ $id ] = $payload;
		}

		$this->items = $new_array;
	}

	function contains( $id ) {
		return isset( $this->items[ $id ] );
	}

	function is_empty() {
		return empty( $this->items );
	}

	function remove( $id ) {
		unset( $this->items[ $id ] );
	}

	function get( $id ) {
		return $this->items[ $id ];
	}

	function get_all() {
		return $this->items;
	}

	function get_first() {
		reset( $this->items );
		return current( $this->items );
	}

	function get_first_key() {
		reset( $this->items );
		return key( $this->items );
	}

	function get_last() {
		end( $this->items );
		return current( $this->items );
	}

	function get_last_key() {
		end( $this->items );
		return key( $this->items );
	}

	function get_by_index( $index ) {
		$values = array_values( $this->items );
		return $values[ $index ];
	}

	function get_by_index_key( $index ) {
		$keys = array_keys( $this->items );
		return $keys[ $index ];
	}

	function get_before( $ref_id ) {

		$last_item = false;
		foreach ( $this->items as $key => $value ) {
			if ( $key == $ref_id ) {
				return $last_item;
			}
			$last_item = $value;
		}

		return false;
	}

	function get_key_before( $ref_id ) {

		$last_item = false;
		foreach ( $this->items as $key => $value ) {
			if ( $key == $ref_id ) {
				return $last_item;
			}
			$last_item = $key;
		}

		return false;
	}

	function get_after( $ref_id ) {

		$found = false;
		foreach ( $this->items as $key => $value ) {
			if ( $key == $ref_id ) {
				$found = true;
			} else if ( $found == true ) {
				return $value;
			}
		}

		return false;
	}

	function get_key_after( $ref_id ) {

		$found = false;
		foreach ( $this->items as $key => $value ) {
			if ( $key == $ref_id ) {
				$found = true;
			} else if ( $found == true ) {
				return $key;
			}
		}

		return false;
	}

}

