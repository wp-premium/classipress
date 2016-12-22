<?php
/**
 * Queue utils
 *
 * @package Components\Payments\Utils
 */

/**
 * Defines a process to retrieve a set of items and iterate over them on a set interval
 */
class APP_Queue{

	/**
	 * Name of process occuring
	 */
	protected $identifier;

	public function __construct( $identifier, $args = array() ){

		$this->identifier = $identifier;

		$this->args = wp_parse_args( $args, array(
			'interval' => 'daily',
			'limit' => 0
		) );

		add_action( 'init', array( $this, 'schedule_process' ) );
		add_action( $this->identifier, array( $this, 'process' ) );

	}

	/**
	 * Schedules the process to happen every $interval
	 * @return void
	 */
	public function schedule_process(){
		if( !wp_next_scheduled( $this->identifier ) ){
			wp_schedule_event( time(), $this->args['interval'], $this->identifier );
		}
	}

	public function process(){

		$items_processed = 0;

		$items = $this->get_items();
		if( $items instanceof WP_Query )
			$items = $items->posts;

		if( empty( $items ) )
			return $items_processed;

		foreach( array_slice( $items, 0, $this->args['limit'] )  as $item ){
			$this->process_item( $item );
			$items_processed += 1;
		}

		return $items_processed;
	}

}
