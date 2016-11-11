<?php
/**
 * Order connections tables
 *
 * @package Components\Payments\Admin
 */

/**
 * Shows the orders the current post is connected with
 */
class APP_Connected_Post_Orders{

	private static $post_types = array();
	public static function init(){

		add_action( 'add_meta_boxes', array( __CLASS__, 'register' ) );
		add_action( 'load-post.php', array( __CLASS__, 'add_screen_option' ) );
		add_filter( 'set-screen-option', array( __CLASS__, 'save_screen_option' ), 10, 3 );

	}

	public static function register(){

		if(  in_array( get_current_screen()->post_type, self::$post_types ) )
			add_action( 'edit_form_advanced', array( __CLASS__, 'display' ), 11 );

	}

	public static function display(){
		global $post_ID;

		$user = get_current_user_id();
		$orders_shown = get_user_meta( $user, 'appthemes_connected_orders_per_post_page', true );

		$orders = _appthemes_orders_get_connected( $post_ID )->posts;
		$visible_orders = array_slice( $orders, 0, $orders_shown );
		if( count( $visible_orders ) == 0)
			return;

		echo <<<EOF
<style type="text/css">
	.connected-orders{
		margin-bottom: 20px;
	}
</style>
EOF;

		$table = new APP_Connected_Orders_Table( $visible_orders );
		$table->show();
	}

	public static function add_post_type( $type ){

		if( is_array( $type ) ){
			foreach( $type as $value )
				self::add_post_type( $value );

			return;
		}

		if( ! in_array( $type, self::$post_types ) )
			self::$post_types[] = $type;

	}

	public static function add_screen_option(){

		if( ! in_array( get_current_screen()->post_type, self::$post_types ) )
			return;

		add_screen_option( 'per_page', array(
			'label' => __( 'Connected Orders', APP_TD ),
			'default' => 10,
			'option' => 'appthemes_connected_orders_per_post_page'
		) );

	}

	public static function save_screen_option( $status, $option, $value ){

		if( 'appthemes_connected_orders_per_post_page' == $option )
			return $value;

	}

}

class APP_Connected_User_Orders{

	public static function init(){
		add_action( 'show_user_profile', array( __CLASS__, 'display' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'display' ) );
		add_action( 'load-user-edit.php', array( __CLASS__, 'add_screen_option' ) );
		add_filter( 'set-screen-option', array( __CLASS__, 'save_screen_option' ), 10, 3 );
	}

	public static function display( $profileuser ){

		$query = new WP_Query( array(
			'author' => $profileuser->ID,
			'post_type' => APPTHEMES_ORDER_PTYPE,
			'showposts' => 10
		) );

		$user = get_current_user_id();
		$orders_shown = get_user_meta( $user, 'appthemes_connected_orders_per_user_page', true );

		$visible_orders = array_slice( $query->posts, 0, $orders_shown );
		if( count( $visible_orders ) == 0)
			return;

		$table = new APP_Connected_Orders_Table( $visible_orders );

		echo html( 'h3', array(), __( 'Payments', APP_TD ) );
		$table->show();

	}

	public static function add_screen_option(){

		add_screen_option( 'per_page', array(
			'label' => __( 'Connected Orders', APP_TD ),
			'default' => 10,
			'option' => 'appthemes_connected_orders_per_user_page'
		) );

	}

	public static function save_screen_option( $status, $option, $value ){

		if( 'appthemes_connected_orders_per_user_page' == $option )
			return $value;

	}
}

class APP_Connected_Orders_Table extends APP_Table{

	public function __construct( $posts ){
		$this->posts = $posts;
	}

	public function show(){
		echo $this->table( $this->posts, array( 'class' => 'widefat connected-orders' ) );
	}

	public function header( $data ){

		$cells = $this->cells( array(
			__( 'Last 10 Orders', APP_TD ),
			__( 'Price', APP_TD ),
			__( 'Status', APP_TD ),
			__( 'Date', APP_TD ),
		), 'th' );
		return html( 'tr', array(), $cells );
	}

	public function footer( $data ){
		return $this->header( $data );
	}

	protected function row( $item ){

		if( get_post_type( $item ) != APPTHEMES_ORDER_PTYPE )
			return;

		$title_link = '<a href="' . get_edit_post_link( $item->ID ) . '">' . get_the_order_id( $item->ID )  . '</a>';

		$cells = $this->cells( array(
			$title_link,
			get_the_order_total( $item->ID ),
			get_the_order_status( $item->ID ),
			$item->post_date
		) );

		return html( 'tr', array(), $cells );

	}

}
