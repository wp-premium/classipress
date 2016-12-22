<?php
/**
 * Admin functions
 *
 * @package Framework\Functions
 */

/**
 * Outputs admin menu sprite CSS
 *
 * @param array $icons
 * @param string $sprite_url (optional)
 *
 * @return void
 */
function appthemes_menu_sprite_css( $icons, $sprite_url = '' ) {
	$sprite_url = ! empty( $sprite_url ) ? $sprite_url : get_template_directory_uri() . '/images/admin-menu.png';

	echo '<style type="text/css">';

	foreach ( $icons as $i => $selector ) {
		$sprite_x = ( 30 * $i ) + 4;

		echo <<<EOB

$selector div.wp-menu-image {
	background-image: url('$sprite_url');
	background-position: {$sprite_x}px -31px !important;
}

$selector div.wp-menu-image img {
	display: none;
}

$selector:hover div.wp-menu-image,
$selector.wp-has-current-submenu div.wp-menu-image {
	background-position: {$sprite_x}px 1px !important;
}
EOB;
	}

	echo '</style>';
}

/**
 * Install a widget into a sidebar
 *
 * @param string $widget_name Registered widget $id_base ( WP_Widget->id_base ).
 * @param string $sidebar Registered sidebar $id. See register_sidebar(), $id arg.
 * @param array $instance_settings Optional settings for this instance of the widget. Its an array of the keys => values of the widget form.
 * @param int $instance_key Optional Instance key, must be unique to this instance, if not, the function will detect and recursively increment until it is uniqnue.
 * @param string $position Optional Position in which to put the widget on the sidebar, usage: 'append' or 'prepend'. Default: append
 * @return bool False if 'sidebars_widgets' option was not updated and true if 'sidebars_widgets' option was updated.
 */
function appthemes_install_widget( $widget_name, $sidebar, $instance_settings = array(), $instance_key = 1, $position = 'append' ) {
	$sidebars_widgets = get_option( 'sidebars_widgets' );

	if ( ! array_key_exists( $sidebar, $sidebars_widgets ) ) {
		return;
	}

	$settings = get_option( 'widget_' . $widget_name , array() );

	if ( array_key_exists( $instance_key, $settings ) ) {
		$instance_key++;
		return appthemes_install_widget( $widget_name, $sidebar, $instance_settings, $instance_key, $position );
	}

	$settings[ $instance_key ] = $instance_settings;
	$settings['_multiwidget'] = 1;
	update_option( 'widget_' . $widget_name, $settings );

	if ( $position == 'append' ) {
		$sidebars_widgets[ $sidebar ][] = $widget_name . '-' . $instance_key;
	} elseif( $position == 'prepend' ) {
		$_sidebar_widgets = array();
		$_sidebar_widgets[] = $widget_name . '-' . $instance_key;
		foreach ( $sidebars_widgets[ $sidebar ] as $sidebar_widget ) {
			$_sidebar_widgets[] = $sidebar_widget;
		}
		$sidebars_widgets[ $sidebar ] = $_sidebar_widgets;
	}

	return update_option( 'sidebars_widgets', $sidebars_widgets );
}

/**
 * Installs widgets into sidebars
 *
 * @param array $sidebars_widgets An array of sidebar id's as the keys, with an array of widgets with array of their settings
 * Example:
 * $sidebars_widgets = array (
 *		'sidebar_1' => array (
 *				'widget_name' => array(
 *					'widget_setting_1' => 'setting_value',
 *					'widget_setting_2' => 'setting_value',
 *				),
 *		),
 *		'sidebar_2' => array (
 *				'widget_name' => array(
 *					'widget_setting_1' => 'setting_value',
 *					'widget_setting_2' => 'setting_value',
 *				),
 *		),
 * );
 *
 * @param bool $reset_sidebar_first Optional whether to clear out the sidebar before adding the array of widgets to the sidebar. Default: true.
 * @return void
 */
function appthemes_install_widgets( $sidebars_widgets, $reset_sidebar_first = true ) {
	foreach ( $sidebars_widgets as $sidebar => $widgets ) {

		$current_sidebars_widgets = get_option( 'sidebars_widgets' );

		if ( $reset_sidebar_first || empty( $current_sidebars_widgets[ $sidebar ] ) ) {
			$current_sidebars_widgets[ $sidebar ] = array();
			update_option( 'sidebars_widgets', $current_sidebars_widgets );
		}

		foreach ( $widgets as $widget => $settings ) {
			appthemes_install_widget( $widget, $sidebar, $settings );
		}
	}

}
