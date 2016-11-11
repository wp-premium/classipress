<?php
// In case the full P2P plugin is activated
if ( ! class_exists( 'P2P_Storage', false ) ) {

	define( 'P2P_TEXTDOMAIN', APP_TD );

	require_once dirname( __FILE__ ) . '/p2p-core/autoload.php';

	P2P_Storage::init();

	P2P_Query_Post::init();
	P2P_Query_User::init();

	P2P_URL_Query::init();

	P2P_Widget::init();
	P2P_Shortcodes::init();

	add_action( 'appthemes_first_run', array( 'P2P_Storage', 'install' ), 9 );

}
