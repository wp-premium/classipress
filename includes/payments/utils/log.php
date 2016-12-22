<?php
/**
 * Log utils
 *
 * @package Components\Payments\Utils
 */

/**
 * Log interface
 */
interface APP_Log{

	/**
	 * Logs a message
	 * @param string $message A message to log
	 * @param string $type The type of log message being recorded. Usually, 'major', 'minor' or 'info'
	 */
	public function log( $message, $type = 'info' );

	/**
	 * Outputs an array of log messages with time, message, and type information
	 * @return array
	 */
	public function get_log();

	/**
	 * Clears the log completely
	 */
	public function clear_log();

}

class APP_General_Log implements APP_Log{

	protected $log_name;
	public function __construct( $log_name ){
		$this->log_name = $log_name;
	}

	public function log( $message, $type = 'info' ){
		$entry = array(
			'time' => current_time( 'timestamp' ),
			'message' => $message,
			'type' => $type
		);

		$data = get_option( $this->get_option_name() );
		if( empty( $data ) ){
			add_option( $this->get_option_name(), array( $entry ), '', 'no' );
		}else{
			$data[] = $entry;
			update_option( $this->get_option_name(), $data );
		}

	}

	public function get_log(){

		$messages = get_option( $this->get_option_name() );
		if( $messages === false ){
			return array();
		}

		$output = array();
		foreach( $messages as $data ){

			if( !isset( $data['type'] ) ) $data['type'] = 'info';

			$output[] = array(
				'time' => date( 'Y-m-d H:i:s',  $data['time'] ),
				'message' => $data['message'],
				'type' => $data['type']
			);

		}

		return $output;
	}

	public function clear_log(){

		delete_option( $this->get_option_name() );

	}

	private function get_option_name(){
		return 'log_' . $this->log_name;
	}

}

class APP_Post_Log implements APP_Log{

	protected $post_id;
	public function __construct( $post_id ){
		$this->post_id = $post_id;
	}

	final public function log( $message, $type = 'info' ){

		add_post_meta( $this->post_id, '_log_messages', array(
			'time' => current_time( 'timestamp' ),
			'message' => $message,
			'type' => $type,
	        ) );

	}

	final public function get_log(){

		$messages = get_post_meta( $this->post_id, '_log_messages' );

		$output = array();
		foreach( $messages as $data ){

			if( !isset( $data['type'] ) ) $data['type'] = 'info';

			$output[] = array(
				'time' => date( 'Y-m-d H:i:s',  $data['time'] ),
				'message' => $data['message'],
				'type' => $data['type']
			);

		}

		return $output;
	}

	final public function clear_log(){

		delete_post_meta( $this->post_id, '_log_messages' );

	}

}

