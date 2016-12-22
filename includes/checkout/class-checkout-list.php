<?php
/**
 * Relational Checkout List class
 *
 * @package Components\Checkouts
 */
class APP_Relational_Checkout_List extends APP_List{

	protected $items = array();
	public $raw_items = array();

	public function add( $id, $payload ){

		$this->raw_items[ $id ] = array(
			'id' => $id,
			'payload' => $payload,
		);
		$this->sort();
	}

	public function add_before( $ref_id, $id, $payload ){

		$this->raw_items[ $id ] = array(
			'id' => $id,
			'payload' => $payload,
			'before' => $ref_id,
		);
		$this->sort();
	}

	public function add_after( $ref_id, $id, $payload ){

		$this->raw_items[ $id ] = array(
			'id' => $id,
			'payload' => $payload,
			'after' => $ref_id,
		);
		$this->sort();
	}

	public function remove( $id ){

		unset( $this->raw_items[ $id ] );
		$this->sort();
	}

	protected function sort(){

		$this->items = array();
		$this->data_tree = $this->build_tree( $this->raw_items );

		$item = reset( $this->data_tree );
		do{
			$this->build_list( $item );
			$item = current( $this->data_tree );
		} while( $item );

	}

	private function build_tree( $items ){

		$flat_tree = array();
		foreach( $items as $key => $data ){
			$flat_tree[ $key ] = array( 'id' => $key, 'before' => array(), 'after' => array() );
		}

		foreach( $items as $id => $item ){

			if( ! empty( $item['before'] ) && isset( $flat_tree[ $item['before'] ] ) )
				$flat_tree[ $item['before'] ]['before'][] = $id;
			else if( ! empty( $item['after'] ) && isset( $flat_tree[ $item['after'] ] ) )
				$flat_tree[ $item['after'] ]['after'][] = $id;
			else if( isset( $item['before'] ) || isset( $item['after'] ) )
				unset( $flat_tree[ $id ] );

		}

		return $flat_tree;
	}

	private function build_list( $item ){

		foreach( $item['before'] as $child )
			$this->build_list( $this->data_tree[ $child ] );

		$this->items[ $item['id'] ] = $this->raw_items[ $item['id'] ];
		unset( $this->data_tree[ $item['id'] ] );

		foreach( $item['after'] as $child )
			$this->build_list( $this->data_tree[ $child ] );


	}

}
