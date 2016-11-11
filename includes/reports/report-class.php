<?php
/**
 * Reports component classes
 *
 * @package Components\Reports
 */


/**
 * Class for a report collection
 * Examples of report collections: user reports, post reports
 */
abstract class APP_Report_Collection {

	/**
	 * The reports collection
	 * @var array
	 */
	public $reports = array();

	/**
	 * Total reports
	 * @var int
	 */
	protected $total_reports = 0;

	/**
	 * The report collection meta
	 * @var array
	 */
	protected $meta = array(
		'updated' => '',
	);


	/**
	 * Sets up a report collection object
	 *
	 * @param array args (optional) WP_Query args to be used to fetch the report collection
	 *
	 * @return void
	 */
	public function __construct( $args = array() ) {
		$this->reports = $this->get_reports( $args );
		$this->total_reports = count( $this->reports );
	}


	/**
	 * Abstract method to retrieve a report collection
	 */
	abstract protected function get_reports( $args = array() );


	/**
	 * Updates the report collection metadata on the DB
	 */
	protected function save_meta() {}


	/**
	 * Retrieves the report collection metadata
	 *
	 * @return array The collection metadata
	 */
	public function get_meta() {
		return $this->meta;
	}


	/**
	 * Sets the metadata for the current report collection
	 *
	 * @param array $meta The metadata to be added to the collection
	 *
	 * @return void
	 */
	protected function set_meta( $meta ) {
		$this->meta = wp_parse_args( $meta, $this->meta );
	}


	/**
	 * Get the total reports from the report collection
	 *
	 * @return int Total reports for the collection
	 */
	public function get_total_reports() {
		return $this->total_reports;
	}


	/**
	 * Adds a new report to the report collection
	 *
	 * @param object $report The report to be added
	 *
	 * @return void
	 */
	public function add_report( $report ) {
		$this->reports[] = $report;

		$this->total_reports++;
	}


}


/**
 * Represents a user reports collection
 */
class APP_User_Reports extends APP_Report_Collection {

	/**
	 * WordPress user ID
	 * @var int
	 */
	protected $user_id = 0;


	/**
	 * Sets up a user report collection object
	 *
	 * @param int $user_id  The user ID
	 * @param array args	WP_Query args to be used to fetch the report collection
	 *
	 * @return void
	 */
	public function __construct( $user_id, $args = array() ) {

		$this->user_id = $user_id;

		$meta = get_comment_meta( $user_id, APP_REPORTS_U_DATA_KEY, true );
		$this->meta = wp_parse_args( $meta, $this->meta );

		parent::__construct( $args );
	}


	/**
	 * Retrieves the user ID related with the current report collection
	 *
	 * @return int The WordPress user ID
	 */
	public function get_user_id() {
		return $this->user_id;
	}


	/**
	 * Retrieve the user reports collection
	 *
	 * @param type $args (optional) WP_Query args to be used to fetch the report collection
	 *
	 * @return array The reports collection
	 */
	protected function get_reports( $args = array() ) {
		$defaults = array(
			'meta_key' => APP_REPORTS_C_RECIPIENT_KEY,
			'meta_value' => $this->user_id,
			//'post_status'	=> 'completed'
		);
		$args = wp_parse_args( $args, $defaults );

		return APP_Report_Factory::get_reports( $args );
	}


	/**
	 * Retrieves the total user reports
	 *
	 * @param type $cached (optional) If set to TRUE will fetch the meta value stored in the DB
	 *
	 * @return int The total user reports
	 */
	public function get_total_reports( $cached = false ) {
		if ( $cached ) {
			$total = get_user_meta( $this->user_id, APP_REPORTS_U_TOTAL_KEY, true );
		} else {
			$total = $this->total_reports;
		}

		return $total;
	}


	/**
	 * Saves the report collection user metadata in the DB
	 *
	 * @return void
	 */
	protected function save_meta() {

		// save all data in array
		update_user_meta( $this->user_id, APP_REPORTS_U_DATA_KEY, $this->meta );

		// also save total reports in separate meta for sorting queries
		update_user_meta( $this->user_id, APP_REPORTS_U_TOTAL_KEY, $this->total_reports );
	}

}


/**
 * Represents a post reports collection
 */
class APP_Post_Reports extends APP_Report_Collection {

	/**
	 * WordPress post ID
	 * @var int
	 */
	protected $post_id = 0;


	/**
	 * Sets up a post report collection object
	 *
	 * @param int $post_id  The post ID
	 * @param array args	WP_Query args to be used to fetch the report collection
	 *
	 * @return void
	 */
	public function __construct( $post_id, $args = array() ) {

		$this->post_id = $post_id;

		$meta = get_comment_meta( $post_id, APP_REPORTS_P_DATA_KEY, true );
		$this->meta = wp_parse_args( $meta, $this->meta );

		parent::__construct( $args );
	}


	/**
	 * Retrieves the post ID related with the current report collection
	 *
	 * @return int The WordPress User ID
	 */
	public function get_post_ID() {
		return $this->post_id;
	}


	/**
	 * Retrieve the post reports collection
	 *
	 * @param type $args (optional) WP_Query args to be used to fetch the report collection
	 *
	 * @return array The reports collection
	 */
	protected function get_reports( $args = array() ) {
		$defaults = array(
			'post_id' => $this->post_id,
			//'post_status' => 'completed'
		);
		$args = wp_parse_args( $args, $defaults );

		return APP_Report_Factory::get_reports( $args );
	}


	/**
	 * Retrieves the total post reports
	 *
	 * @param type $cached (optional) If set to TRUE will fetch the meta value stored in the DB
	 *
	 * @return int The total post reports
	 */
	public function get_total_reports( $cached = false ) {
		if ( $cached ) {
			$total = get_user_meta( $this->post_id, APP_REPORTS_P_TOTAL_KEY, true );
		} else {
			$total = $this->total_reports;
		}

		return $total;
	}


	/**
	 * Saves the report collection post metadata in the DB
	 *
	 * @return void
	 */
	protected function save_meta() {

		// save all data in array
		update_post_meta( $this->post_id, APP_REPORTS_P_DATA_KEY, $this->meta );

		// also save total reports in separate meta for sorting queries
		update_post_meta( $this->post_id, APP_REPORTS_P_TOTAL_KEY, $this->total_reports );
	}

}


/**
 * Represents a single report derived from a WordPress comment object
 */
class APP_Single_Report {

	/**
	 * Comment ID, defined by Wordpress when creating the Comment
	 * @var int
	 */
	protected $id = 0;

	/**
	 * The report type: user | post
	 * @var int
	 */
	protected $report_type = '';

	/**
	 * The ID for the user or post being reported
	 * @var int
	 */
	protected $recipient_id = 0;

	/**
	 * Extra metadata stored for each report
	 * @var array
	 */
	protected $meta = array(
		'updated' => '',
	);

	/**
	 * WordPress comment object
	 * @var object
	 */
	protected $comment = '';


	/**
	 * Sets up a report object
	 *
	 * @param object comment	The comment object that the report will inherit
	 *
	 * @return void
	 */
	function __construct( $comment ) {

		$this->id = $comment->comment_ID;
		$this->comment = $comment;

		$this->report_type = get_comment_meta( $this->id, APP_REPORTS_C_RECIPIENT_TYPE_KEY, true );
		$this->recipient_id = get_comment_meta( $this->id, APP_REPORTS_C_RECIPIENT_KEY, true );

		$meta = get_comment_meta( $this->id, APP_REPORTS_C_DATA_KEY, true );
		$this->meta = wp_parse_args( $meta, $this->meta );
	}


	### GETTERs


	/**
	 * Magic method to retrieve data from inaccessible properties
	 *
	 * @param property $name The property to get the value from
	 *
	 * @return mixed|null The property value or null if not found
	 */
	public function __get( $name ) {
		if ( array_key_exists( $name, $this->get_data() ) ) {
			return $this->get_data( $name );
		}
		return null;
	}


	/**
	 * Retrieves the report ID
	 *
	 * @return int The report ID
	 */
	function get_id() {
		return $this->get_data( 'id' );
	}


	/**
	 * Retrieves the report type: user | post
	 *
	 * @return string The report type
	 */
	function get_type() {
		return $this->get_data( 'type' );
	}


	/**
	 * Retrieves the report recipient ID
	 *
	 * @return int The report recipient ID
	 */
	function get_recipient_id() {
		return $this->get_data( 'recipient' );
	}


	/**
	 * Retrieves the report post id
	 *
	 * @return int The report post id
	 */
	function get_post_ID() {
		return $this->get_data( 'comment_post_ID' );
	}


	/**
	 * Retrieves the reporter user id
	 *
	 * @return int The reporter user id
	 */
	function get_author_ID() {
		return $this->get_data( 'user_id' );
	}


	/**
	 * Retrieves the report content
	 *
	 * @return string The report content
	 */
	function get_content() {
		return $this->get_data( 'comment_content' );
	}


	/**
	 * Retrieves the report comment
	 *
	 * @return int The report comment
	 */
	function get_date() {
		return $this->get_data( 'comment_date' );
	}


	/**
	 * Retrieves the report inherited comment object
	 *
	 * @return object The comment objet
	 */
	function get_comment() {
		return $this->comment;
	}


	/**
	 * Retrieves specific or all report metadata
	 *
	 * @param string $key (optional) The meta key to retrieve values from
	 *
	 * @return mixed Returns a meta data array or a single value
	 */
	function get_meta( $key = '' ) {
		if ( $key ) {
			if ( empty( $this->meta[ $key ] ) ) {
				return;
			}

			$meta = $this->meta[ $key ];
		} else {
			$meta = $this->meta;
		}
		return $meta;
	}


	/**
	 * Retrieves specific or all comment report data
	 *
	 * @param string $part (optional) Field part to retrieve
	 *
	 * @return mixed A data single value or a data list
	 */
	private function get_data( $part = '' ) {

		$basic = array(
			'id' => $this->id,
			'type' => $this->report_type,
			'recipient' => $this->recipient_id,
		);
		$fields = array_merge( $basic, (array) $this->comment, (array) $this->meta );

		if ( empty( $part ) ) {
			return $fields;
		} elseif ( isset( $fields[ $part ] ) ) {
			return $fields[ $part ];
		}
	}


	### SETTERs


	/**
	 * Sets the report type
	 *
	 * @param string The report type: user | post
	 *
	 * @return void
	 */
	public function set_type( $type ) {
		$this->report_type = $type;
		update_comment_meta( $this->id, APP_REPORTS_C_RECIPIENT_TYPE_KEY, $type );
	}


	/**
	 * Sets the report recipient ID
	 *
	 * @param int The report recipient ID
	 *
	 * @return void
	 */
	public function set_recipient( $id ) {
		$this->recipient_id = $id;
		update_comment_meta( $this->id, APP_REPORTS_C_RECIPIENT_KEY, $id );
	}


	/**
	 * Sets the report meta
	 *
	 * @param array The meta key/value pairs
	 * @param type $single (optional)	If set to TRUE stores the key/value pair as a single meta key/value
	 *
	 * @return void
	 */
	public function set_meta( $meta, $single = false ) {
		$this->meta = wp_parse_args( $meta, $this->meta );

		if ( $single ) {
			update_comment_meta( $this->id, key( $meta ), $meta[ key( $meta ) ] );
		} else {
			update_comment_meta( $this->id, APP_REPORTS_C_DATA_KEY, $this->meta );
		}
	}


	/**
	 * Save the report metadata on the DB
	 *
	 * @return void
	 */
	protected function save_meta() {
		// save report meta details
		update_comment_meta( $this->id, APP_REPORTS_C_DATA_KEY, $this->get_meta() );
	}


	/**
	 * Approves report
	 *
	 * @param bool $status (optional)
	 *
	 * @return void
	 */
	public function approve( $status = true ) {
		$args = array(
			'comment_ID' => $this->get_id(),
			'comment_approved' => (int) $status,
		);
		wp_update_comment( $args );
	}


	### OTHER


	/**
	 * Returns the report approvement status
	 *
	 * @return bool	The bool state for the report approvement status
	 */
	function is_approved() {
		return $this->get_data( 'comment_approved' );
	}

}

