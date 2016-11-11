<?php
/**
 * Search Index Load
 *
 * @package Search-Index
 */

add_action( 'after_setup_theme', '_appthemes_load_search_index', 999 );

function _appthemes_load_search_index() {
	if ( current_theme_supports( 'app-search-index' ) ) {
		require_once dirname( __FILE__ ) . '/search-index.php';

		if ( is_admin() && appthemes_search_index_get_args( 'admin_page' ) ) {
			new APP_Search_Index_Admin();
		}
	}
}