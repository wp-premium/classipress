<?php
/**
 * Open Graph Load
 *
 * @package OpenGraph
 */

add_action( 'after_setup_theme', '_appthemes_load_open_graph', 999 );

function _appthemes_load_open_graph() {
	if ( current_theme_supports( 'app-open-graph' ) ) {
		require_once dirname( __FILE__ ) . '/open-graph.php';

		list( $args ) = get_theme_support( 'app-open-graph' );
		new APP_Open_Graph( (array) $args );
	}
}