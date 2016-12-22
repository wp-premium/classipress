<?php
/**
 * Stats Load
 *
 * @package Stats
 */

add_action( 'after_setup_theme', '_appthemes_load_stats', 999 );

function _appthemes_load_stats() {
	if ( current_theme_supports( 'app-stats' ) ) {
		require_once dirname( __FILE__ ) . '/stats.php';

		APP_Post_Statistics::init();
	}
}