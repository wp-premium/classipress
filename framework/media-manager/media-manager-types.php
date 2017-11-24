<?php
/**
 * Meta objects which determine the "Media Manager" behavior in relation to
 * parent object's various meta types.
 *
 * @package Framework\Media-Manager
 * @author  AppThemes
 * @since   2.0
 */

interface APP_Media_Manager_Meta_Type {

	/**
	 * Retrieves the object meta type.
	 *
	 * @return string
	 */
	public function get_meta_type();

	/**
	 * Retrieves the object ID to be used in attachments query.
	 *
	 * @param int $object_id Object ID.
	 *
	 * @return int Parent ID.
	 */
	public function get_parent_id( $object_id );

	/**
	 * Retrieves metadata for a listing item.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $key     Optional. The meta key to retrieve. If no key is
	 *                        provided, fetches all metadata for the term.
	 * @param bool   $single  Whether to return a single value. If false, an
	 *                        array of all values matching the `$item_id`/`$key`
	 *                        pair will be returned. Default: false.
	 *
	 * @return mixed If `$single` is false, an array of metadata values.
	 *               If `$single` is true, a single metadata value.
	 */
	public function get_meta( $item_id, $key = '', $single = false );

	/**
	 * Update item meta field based on item ID.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with
	 * the same key and item ID.
	 *
	 * If the meta field for the item does not exist, it will be added.
	 *
	 * @param int    $item_id    Item ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if
	 *                           non-scalar.
	 * @param mixed  $prev_value Optional. Previous value to check before
	 *                           removing. Default empty.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful
	 *                  update, false on failure.
	 */
	public function update_meta( $item_id, $meta_key, $meta_value, $prev_value = '' );

	/**
	 * Remove metadata matching criteria from an item.
	 *
	 * You can match based on the key, or key and value. Removing based on key
	 * and value, will keep from removing duplicate metadata with the same key.
	 * It also allows removing all metadata matching key, if needed.
	 *
	 * @param int    $item_id    Item ID.
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value. Must be serializable
	 *                           if non-scalar. Default empty.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_meta( $item_id, $meta_key, $meta_value = '' );
}

class APP_Media_Manager_Post_Meta_Type implements APP_Media_Manager_Meta_Type {
	/**
	 * Retrieves the object meta type.
	 *
	 * @return string
	 */
	public function get_meta_type() {
		return 'post';
	}

	/**
	 * Retrieves the object ID to be used in attachments query.
	 *
	 * @param int $object_id Object ID.
	 *
	 * @return int Parent ID.
	 */
	public function get_parent_id( $object_id ) {
		return $object_id;
	}

	/**
	 * Retrieves metadata for a listing item.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $key     Optional. The meta key to retrieve. If no key is
	 *                        provided, fetches all metadata for the item.
	 * @param bool   $single  Whether to return a single value. If false, an
	 *                        array of all values matching the `$item_id`/`$key`
	 *                        pair will be returned. Default: false.
	 *
	 * @return mixed If `$single` is false, an array of metadata values.
	 *               If `$single` is true, a single metadata value.
	 */
	function get_meta( $item_id, $key = '', $single = false ) {
		return get_post_meta( $item_id, $key, $single );
	}

	/**
	 * Update item meta field based on item ID.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with
	 * the same key and item ID.
	 *
	 * If the meta field for the item does not exist, it will be added.
	 *
	 * @param int    $item_id    Item ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if
	 *                           non-scalar.
	 * @param mixed  $prev_value Optional. Previous value to check before
	 *                           removing. Default empty.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful update,
	 *                  false on failure.
	 */
	function update_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_post_meta( $item_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Remove metadata matching criteria from an item.
	 *
	 * You can match based on the key, or key and value. Removing based on key
	 * and value, will keep from removing duplicate metadata with the same key.
	 * It also allows removing all metadata matching key, if needed.
	 *
	 * @param int    $item_id    Item ID.
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value. Must be serializable
	 *                           if non-scalar. Default empty.
	 *
	 * @return bool True on success, false on failure.
	 */
	function delete_meta( $item_id, $meta_key, $meta_value = '' ) {
		return delete_post_meta( $item_id, $meta_key, $meta_value );
	}
}

class APP_Media_Manager_User_Meta_Type implements APP_Media_Manager_Meta_Type {
	/**
	 * Retrieves the object meta type.
	 *
	 * @return string
	 */
	public function get_meta_type() {
		return 'user';
	}

	/**
	 * Retrieves the object ID to be used in attachments query.
	 *
	 * @param int $object_id Object ID.
	 *
	 * @return int Parent ID.
	 */
	public function get_parent_id( $object_id ) {
		return 0;
	}

	/**
	 * Retrieves metadata for a listing item.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $key     Optional. The meta key to retrieve. If no key is
	 *                        provided, fetches all metadata for the item.
	 * @param bool   $single  Whether to return a single value. If false, an
	 *                        array of all values matching the `$item_id`/`$key`
	 *                        pair will be returned. Default: false.
	 *
	 * @return mixed If `$single` is false, an array of metadata values.
	 *               If `$single` is true, a single metadata value.
	 */
	function get_meta( $item_id, $key = '', $single = false ) {
		return get_user_meta( $item_id, $key, $single );
	}

	/**
	 * Update item meta field based on item ID.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with
	 * the same key and item ID.
	 *
	 * If the meta field for the item does not exist, it will be added.
	 *
	 * @param int    $item_id    Item ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if
	 *                           non-scalar.
	 * @param mixed  $prev_value Optional. Previous value to check before
	 *                           removing. Default empty.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful update,
	 *                  false on failure.
	 */
	function update_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_user_meta( $item_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Remove metadata matching criteria from an item.
	 *
	 * You can match based on the key, or key and value. Removing based on key
	 * and value, will keep from removing duplicate metadata with the same key.
	 * It also allows removing all metadata matching key, if needed.
	 *
	 * @param int    $item_id    Item ID.
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value. Must be serializable
	 *                           if non-scalar. Default empty.
	 *
	 * @return bool True on success, false on failure.
	 */
	function delete_meta( $item_id, $meta_key, $meta_value = '' ) {
		return delete_user_meta( $item_id, $meta_key, $meta_value );
	}
}

class APP_Media_Manager_Term_Meta_Type implements APP_Media_Manager_Meta_Type {
	/**
	 * Retrieves the object meta type.
	 *
	 * @return string
	 */
	public function get_meta_type() {
		return 'term';
	}

	/**
	 * Retrieves the object ID to be used in attachments query.
	 *
	 * @param int $object_id Object ID.
	 *
	 * @return int Parent ID.
	 */
	public function get_parent_id( $object_id ) {
		return 0;
	}

	/**
	 * Retrieves metadata for a listing item.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $key     Optional. The meta key to retrieve. If no key is
	 *                        provided, fetches all metadata for the item.
	 * @param bool   $single  Whether to return a single value. If false, an
	 *                        array of all values matching the `$item_id`/`$key`
	 *                        pair will be returned. Default: false.
	 *
	 * @return mixed If `$single` is false, an array of metadata values.
	 *               If `$single` is true, a single metadata value.
	 */
	function get_meta( $item_id, $key = '', $single = false ) {
		return get_term_meta( $item_id, $key, $single );
	}

	/**
	 * Update item meta field based on item ID.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with
	 * the same key and item ID.
	 *
	 * If the meta field for the item does not exist, it will be added.
	 *
	 * @param int    $item_id    Item ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if
	 *                           non-scalar.
	 * @param mixed  $prev_value Optional. Previous value to check before
	 *                           removing. Default empty.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful update,
	 *                  false on failure.
	 */
	function update_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_term_meta( $item_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Remove metadata matching criteria from an item.
	 *
	 * You can match based on the key, or key and value. Removing based on key
	 * and value, will keep from removing duplicate metadata with the same key.
	 * It also allows removing all metadata matching key, if needed.
	 *
	 * @param int    $item_id    Item ID.
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value. Must be serializable
	 *                           if non-scalar. Default empty.
	 *
	 * @return bool True on success, false on failure.
	 */
	function delete_meta( $item_id, $meta_key, $meta_value = '' ) {
		return delete_term_meta( $item_id, $meta_key, $meta_value );
	}
}

class APP_Media_Manager_Meta_Type_Object_Factory {

	static protected $instances = array();

	/**
	 * Adds new instance for future use.
	 *
	 * This might be used to replace the standard meta type objects or add new.
	 *
	 * @param APP_Media_Manager_Meta_Type $object
	 */
	static public function add_instance( APP_Media_Manager_Meta_Type $object ) {
		self::$instances[ $object->get_meta_type() ] = $object;
	}

	/**
	 * Retrieves the instance of meta type object.
	 *
	 * @param string The meta type name.
	 *
	 * @return APP_Media_Manager_Meta_Type
	 */
	static public function get_instance( $type ) {
		if ( isset( self::$instances[ $type ] ) ) {
			return self::$instances[ $type ];
		}

		switch ( $type ) {
			case 'post':
				self::add_instance( new APP_Media_Manager_Post_Meta_Type() );
				break;
			case 'user':
				self::add_instance( new APP_Media_Manager_User_Meta_Type() );
				break;
			case 'term':
				self::add_instance( new APP_Media_Manager_Term_Meta_Type() );
				break;
		}

		if ( ! isset( self::$instances[ $type ] ) ) {
			return null;
		}

		return self::$instances[ $type ];
	}

}

