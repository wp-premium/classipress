<?php
/**
 * AppThemes common functions.
 *
 * @version 1.0
 * @author AppThemes
 *
 * DO NOT UPDATE WITHOUT UPDATING ALL OTHER THEMES!
 *
 * Add new functions to the /framework/ folder and move existing functions there as well, when you need to modify them.
 *
 */

add_action( 'wp_login', 'appthemes_last_login' );

/**
 * Get the page view counters and display on the page.
 */
function appthemes_get_stats($post_id) {
	global $posts, $app_abbr;

	$daily_views = get_post_meta($post_id, $app_abbr.'_daily_count', true);
	$total_views = get_post_meta($post_id, $app_abbr.'_total_count', true);

	if(!empty($total_views) && (!empty($daily_views)))
		echo number_format($total_views) . '&nbsp;' . __( 'total views', APP_TD ). ',&nbsp;' . number_format($daily_views) . '&nbsp;' . __( 'today', APP_TD );
	else
		_e( 'no views yet', APP_TD );
}


/**
 * Give us either the uploaded profile pic, a gravatar, or a placeholder.
 */
function appthemes_get_profile_pic( $author_id, $author_email, $avatar_size ) {
	echo get_avatar( $author_email, $avatar_size );
}


/**
 * Change the author url base permalink.
 */
function appthemes_author_permalink() {
	global $wp_rewrite, $app_abbr;

	$author_base = trim(get_option($app_abbr.'_author_url'));

	// don't waste resources if the author base hasn't been customized
	// MAKE SURE TO CHECK IF VAR IS EMPTY OTHERWISE THINGS WILL BREAK
	if($author_base <> 'author') {
		$wp_rewrite->author_base = $author_base;
		$wp_rewrite->flush_rules();
	}
}


/**
 *
 * Helper functions
 *
 */

/**
 * mb_string compatibility check.
 */
if ( ! function_exists('mb_strlen') ) {
	function mb_strlen($str) {
		return strlen($str);
	}
}


/**
 * Round to the nearest value used in pagination.
 */
function appthemes_round( $num, $tonearest ) {
	return floor($num/$tonearest)*$tonearest;
}


/**
 * For the price field to make only numbers, periods, and commas.
 */
function appthemes_clean_price($string, $returnType = false) {
	global $cp_options;

	if ( $cp_options->clean_price_field || $returnType ) {
		$string = preg_replace('/[^0-9.,]/', '', $string);
		$string = preg_replace('/,/', '.', $string);
		if ( preg_match('/[.]/', $string) ) {
			$parts = explode('.', $string);
			$last = array_pop($parts);
			if ( strlen($last) == 2 )
				$string = implode('', $parts) . '.' . $last;
			else
				$string = implode('', $parts) . $last;
		}
	}

	if ( $returnType == 'float' )
		$string = (float)$string;

	return apply_filters('appthemes_clean_price', $string);
}


/**
 * Error message output function.
 */
function appthemes_error_msg( $error_msg ) {
	$msg_string = '';
	foreach ( $error_msg as $value ) {
		if ( ! empty( $value ) )
			$msg_string = $msg_string . '<div class="error">' . $msg_string = $value.'</div><div class="pad5"></div>';
	}
	return $msg_string;
}


/**
 * Just places the search term into a js variable for use with jquery.
 * Not being used as of 3.0.5 b/c of js conflict with search results.
 */
function appthemes_highlight_search_term( $query ) {
	if ( is_search() && strlen( $query ) > 0 ) {
		echo '
			<script type="text/javascript">
				var search_query  = "' . $query . '";
			</script>
		';
	}

}


/**
 * Insert the first login date once the user has been created.
 */
function appthemes_first_login( $user_id ) {
	update_user_meta( $user_id, 'last_login', current_time( 'mysql' ) );
}


/**
 * Insert the last login date for each user.
 */
function appthemes_last_login( $login ) {
	$user = get_user_by( 'login', $login );
	update_user_meta( $user->ID, 'last_login', current_time( 'mysql' ) );
}


/**
 * Get the last login date for a user.
 */
function appthemes_get_last_login( $user_id ) {
	$last_login = get_user_meta( $user_id, 'last_login', true );
	return appthemes_display_date( $last_login );
}


/**
 * Format the user registration date used in the sidebar-user.php template.
 */
function appthemes_get_reg_date( $reg_date ) {
	return appthemes_display_date( $reg_date );
}


/**
 * Add or remove upload file types.
 */
function appthemes_custom_upload_mimes( $existing_mimes = array() ) {
	return $existing_mimes;
}


/**
 *
 * suggest terms on search results
 * based off the Search Suggest plugin by Joost de Valk.
 * This service has been deprecated since Feb 2011
 * @url http://developer.yahoo.com/search/web/V1/relatedSuggestion.html
 *
 */
function appthemes_search_suggest( $full = true ) {
	global $yahooappid, $s;

	require_once(ABSPATH . 'wp-includes/class-snoopy.php');
	$yahooappid = '3uiRXEzV34EzyTK7mz8RgdQABoMFswanQj_7q15.wFx_N4fv8_RPdxkD5cn89qc-';
	$query 	= "http://search.yahooapis.com/WebSearchService/V1/spellingSuggestion?appid=$yahooappid&query=".$s."&output=php";
	$wpurl 	= home_url('/');
	$snoopy = new Snoopy;

	$snoopy->fetch( $query );
	$resultset = unserialize( $snoopy->results );

	if ( isset( $resultset['ResultSet']['Result'] ) ) {
		if ( is_string( $resultset['ResultSet']['Result'] ) ) {
			$output = '<a href="'.$wpurl.'?s='.urlencode( $resultset['ResultSet']['Result'] ).'" rel="nofollow">'.$resultset['ResultSet']['Result'].'</a>';
		} else {
			foreach ( $resultset['ResultSet']['Result'] as $result ) {
				$output .= '<a href="'.$wpurl.'?s='.urlencode( $result ).'" rel="nofollow">'.$result.'</a>, ';
			}
		}
		if ( $full ) {
			echo __( 'Perhaps you meant', APP_TD ) . '<strong> ' . $output . '</strong>?';
		} else {
			return __( 'Perhaps you meant', APP_TD ) . '<strong> ' . $output . '</strong>?';
		}
	} else {
		return false;
	}
}


/**
 * Deletes all the theme database tables.
 */
function appthemes_delete_db_tables() {
	global $wpdb, $app_db_tables;

	echo '<div class="update-msg">';
	foreach ( $app_db_tables as $key => $value ) :

		$sql = "DROP TABLE IF EXISTS " . $wpdb->prefix . $value;
		$wpdb->query( $sql );

		printf( '<div class="delete-item">' . __( "Table '%s' has been deleted.", APP_TD ) . '</div>', $value );

	endforeach;
	echo '</div>';

}

/**
 * Deletes all the theme database options.
 */
function appthemes_delete_all_options() {
	global $wpdb, $app_abbr;

	$sql = "DELETE FROM " . $wpdb->options . " WHERE option_name LIKE '".$app_abbr."_%'";
	$wpdb->query( $sql );

	echo '<div class="update-msg">';
	echo '<div class="delete-item">' . __( 'All theme options have been deleted.', APP_TD ) . '</div>';
	echo '</div>';
}

/**
 * Replace all '<br/>' tags with just '\r\n'.
 */
function appthemes_br2nl($text) {
	return preg_replace( '#<br\s*/?>#i', "\r\n", $text );
}


/**
 *
 * Deprecated.
 *
 */


/**
 * tinyMCE text editor.
 *
 * @deprecated 3.3.3
 */
function appthemes_tinymce( $width = 540, $height = 400 ) {
	_deprecated_function( __FUNCTION__, '3.3.3', 'wp_editor()' );

	return;
}

