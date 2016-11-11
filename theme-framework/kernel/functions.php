<?php
/**
 * Theme Framework API
 *
 * @package ThemeFramework\Functions
 */

/**
 * Loads the appropriate .mo file from theme directory first,
 * and if not found then from the Language directory.
 */
function appthemes_load_textdomain() {

	load_theme_textdomain( APP_TD, get_template_directory() );
}

/**
 * Checks if a file is located in template directory.
 *
 * @param string $file A path to file
 *
 * @return bool True if file is located in template directory
 */
function appthemes_in_template_directory( $file = false ) {
	$theme_dir = realpath( get_template_directory() );

	if ( ! $file ) {
		$file = dirname( __FILE__ );
	}

	return (bool) ( strpos( $file, $theme_dir ) !== false );
}

/**
 * Sets the favicon to the default location.
 */
function appthemes_favicon() {
	$uri = apply_filters( 'appthemes_favicon', appthemes_locate_template_uri( 'images/favicon.ico' ) );

	if ( ! $uri ) {
		return;
	}

?>
<link rel="shortcut icon" href="<?php echo esc_url( $uri ); ?>" />
<?php
}

/**
 * Generates a better title tag than wp_title().
 */
function appthemes_title_tag( $title ) {
	global $page, $paged;

	$parts = array();

	if ( ! empty( $title ) ) {
		$parts[] = $title;
	}

	if ( is_home() || is_front_page() ) {
		$blog_title = get_bloginfo( 'name' );

		$site_description = get_bloginfo( 'description', 'display' );
		if ( $site_description && ! is_paged() ) {
			$blog_title .= ' - ' . $site_description;
		}

		$parts[] = $blog_title;
	}

	if ( ! is_404() && ( $paged >= 2 || $page >= 2 ) ) {
		$parts[] = sprintf( __( 'Page %s', APP_TD ), max( $paged, $page ) );
	}

	$parts = apply_filters( 'appthemes_title_parts', $parts );

	return implode( " - ", $parts );
}

/**
 * Includes custom post types into main feed, hook to 'request' filter
 *
 * @param array $query_vars
 *
 * @return array
 */
function appthemes_modify_feed_content( $query_vars ) {

	if ( ! current_theme_supports( 'app-feed' ) ) {
		return $query_vars;
	}

	list( $options ) = get_theme_support( 'app-feed' );

	if ( isset( $query_vars['feed'] ) && ! isset( $query_vars['post_type'] ) ) {
		$query_vars['post_type'] = array( 'post', $options['post_type'] );
	}

	return $query_vars;
}
