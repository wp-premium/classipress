<?php
/**
 * Scripts enqueue functions
 *
 * Note:
 * These functions register and enqueue the default scripts and styles for the Reports component
 * Each theme should manually hook into these functions as needed to avoid enqueing on every page
 *
 * @package Components\Reports
 */


/**
 * Registers and enqueues the default scripts
 *
 * @return void
 */
function appthemes_reports_enqueue_scripts() {
	$url = appthemes_reports_get_args( 'url' );

	wp_enqueue_script( 'app-reports', $url . '/scripts/reports.js', array( 'jquery' ), APP_REPORTS_VERSION, true );

	wp_localize_script( 'app-reports', 'app_reports', array(
		'ajax_url' => admin_url( 'admin-ajax.php', 'relative' ),
		'images_url' => $url . '/images/',
	) );

}


/**
 * Registers and enqueue the default styles
 *
 * @return void
 */
function appthemes_reports_enqueue_styles() {
	$url = appthemes_reports_get_args( 'url' );

	wp_enqueue_style( 'app-reports', $url . '/style.css', null, APP_REPORTS_VERSION );
}

