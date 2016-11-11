<?php
/**
 * Deprecator
 *
 * @package Framework\Deprecator
 */

/**
 * Class handles deprecating action and filter hooks
 */
class APP_Hook_Deprecator {

	public static $hooks;

	static function deprecate( $old_hook, $new_hook, $version, $hook_type = 'action', $args = 1 ) {
		self::$hooks[ $new_hook ] = compact( 'old_hook', 'new_hook', 'version', 'hook_type', 'args' );
		add_filter( $new_hook, array( __CLASS__, 'do_hook' ), 999, $args );
	}

	static function do_hook() {
		global $wp_filter;

		$current_hook = current_filter();

		if ( ! isset( self::$hooks[ $current_hook ] ) ) {
			return;
		}

		$hook = self::$hooks[ $current_hook ];
		$hook_args = func_get_args();

		if ( 'filter' == $hook['hook_type'] ) {
			$value = $hook_args[0];
		}

		if ( has_filter( $hook['old_hook'] ) ) {

			self::_deprecated_hook( $current_hook );

			if ( 'filter' == $hook['hook_type'] ) {
				$type = 'apply_filters';
			} else {
				$type = 'do_action';
			}

			$value = call_user_func_array( $type, array_merge( array( $hook['old_hook'] ), array_slice( $hook_args, 0, $hook['args'] ) ) );

		}

		if ( 'filter' == $hook['hook_type'] ) {
			return $value;
		}
	}

	static function _deprecated_hook( $current_hook ) {
		$hook = self::$hooks[ $current_hook ];
		_deprecated_function( $hook['old_hook'] . ' ' . $hook['hook_type'], $hook['version'], $hook['new_hook'] . ' ' . $hook['hook_type'] );
	}
}


/**
 * Handles deprecating action and filter hooks
 */
function appthemes_deprecate_hook( $old_hook, $new_hook, $version, $hook_type = 'action', $args = 1 ) {
	APP_Hook_Deprecator::deprecate( $old_hook, $new_hook, $version, $hook_type, $args );
}

