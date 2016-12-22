<?php
/**
 * Keeps track of items registered in the theme and
 * stores information about them.
 *
 * Mainly used in order summaries and reports.
 *
 * @package Components\Payments
 */
class APP_Item_Registry{

	/**
	 * Stores the item types currently registered
	 * @var array
	 */
	private static $types = array();

	/**
	 * Registers an item for later dispaly
	 * @param  string $id    Item type, see APP_Order::add_item()
	 * @param  string $title Title for display to users
	 * @param  array  $meta  Meta information kept for various uses
	 * @return void
	 */
	public static function register( $id, $title, $meta = array(), $priority = 10 ){

		if( ! is_string( $id ) && ! is_int( $id ) )
			trigger_error( 'Item ID must be a string or integer', E_USER_WARNING );

		self::$types[ $id ] = array(
			'title' => $title,
			'meta' => $meta,
			'priority' => $priority,
		);
	}

	/**
	 * Returns the title of the given item
	 * @param  string $id Item type registered in register()
	 * @return string     Item title registered in register()
	 */
	public static function get_title( $id ){

		if( ! is_string( $id ) && ! is_int( $id ) )
			trigger_error( 'Item ID must be a string or integer', E_USER_WARNING );

		return self::$types[ $id ]['title'];
	}

	/**
	 * Returns the array of meta information, or part
	 * of it if specified
	 * @param  string $id  The item type registered in register()
	 * @param  string $key (optional) The part of the array to return
	 * @return array|mixed If specified, the part of the meta array,
	 * 							or the entire meta array
	 */
	public static function get_meta( $id, $key = '' ){

		if( ! is_string( $id ) && ! is_int( $id ) )
			trigger_error( 'Item ID must be a string or integer', E_USER_WARNING );

		if( ! empty( $key ) && !is_string( $key ) && !is_int( $key ) )
			trigger_error( 'Item Meta key must be a string or integer', E_USER_WARNING );

		if( empty( $key ) )
			return self::$types[ $id ]['meta'];
		else if( isset( self::$types[ $id]['meta'][$key] ) )
			return self::$types[ $id ]['meta'][$key];
		else
			return false;

	}

	public static function get_priority( $id ){

		if( ! is_string( $id ) && ! is_int( $id ) )
			trigger_error( 'Item ID must be a string or integer', E_USER_WARNING );

		if( isset( self::$types[ $id ] ) )
			return self::$types[ $id ]['priority'];
		else
			return 0;

	}

	public static function is_registered( $id ){

		if( ! is_string( $id ) && ! is_int( $id ) )
			trigger_error( 'Item ID must be a string or integer', E_USER_WARNING );

		return isset( self::$types[ $id ] );
	}


}