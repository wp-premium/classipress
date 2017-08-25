<?php
/**
 * Action and filter hooks.
 *
 * @package ClassiPress\Actions
 * @author  AppThemes
 * @since   ClassiPress 3.1
 */

add_action( 'init', 'cp_social_connect_login' );
add_action( 'init', 'cp_disable_auto_embeds' );
add_action( 'init', 'cp_disable_wp_features' );

add_filter( 'excerpt_more', 'cp_ads_excerpt_more' );
add_filter( 'excerpt_length', 'cp_ads_excerpt_length' );

add_action( 'wp_ajax_cp_dynamic_content', '_cp_get_dynamic_content' );
add_action( 'wp_ajax_nopriv_cp_dynamic_content', '_cp_get_dynamic_content' );

add_action( 'after_setup_theme', '_cp_remove_default_notices', 9999 );
add_action( 'after_setup_theme', 'cp_custom_registration_email', 1000 );

add_action( 'appthemes_display_notice', 'cp_output_notices', 10, 2 );

add_action( 'wp_head', 'cp_cufon_styles' );
add_action( 'wp_head', 'cp_generator' );
add_action( 'wp_head', 'cp_pingback_header' );
add_action( 'wp_head', 'cp_alternate_rss' );

add_action( 'wp_footer', 'cp_google_analytics_code' );

add_action( 'appthemes_blog_loop_else', 'cp_blog_loop_else' );
add_action( 'appthemes_before_blog_post_title', 'cp_blog_comments_bubble' );
add_action( 'appthemes_after_blog_post_title', 'cp_blog_post_meta' );
add_action( 'appthemes_after_blog_post_content', 'cp_do_loop_stats' );
add_action( 'appthemes_after_blog_post_content', 'cp_blog_post_meta_footer' );
add_action( 'appthemes_after_blog_post_content', 'cp_add_reset_stats_link' );
add_action( 'appthemes_after_blog_endwhile', 'cp_do_pagination' );
add_action( 'appthemes_after_blog_loop', 'cp_single_ad_banner' );
add_action( 'appthemes_after_blog_loop', 'cp_category_search_ad_banner' );

add_action( 'before_delete_post', 'cp_delete_ad_meta' );
add_action( 'appthemes_before_post_title', 'cp_ad_loop_price' );
add_action( 'appthemes_after_post_title', 'cp_ad_loop_meta' );
add_action( 'appthemes_after_post_content', 'cp_do_loop_stats' );
add_action( 'appthemes_after_post_content', 'cp_blog_post_meta_footer' );
add_action( 'appthemes_after_post_content', 'cp_do_ad_ref_id' );
add_action( 'appthemes_after_post_content', 'cp_add_reset_stats_link' );
add_action( 'appthemes_after_post_content', 'cp_report_listing_button' );

add_action( 'appthemes_after_endwhile', 'cp_do_pagination' );
add_action( 'appthemes_loop_else', 'cp_ad_loop_else' );
add_action( 'appthemes_after_loop', 'cp_single_ad_banner' );
add_action( 'appthemes_after_loop', 'cp_category_search_ad_banner' );

add_action( 'wp', 'cp_cache_stats' );
add_action( 'wp', 'cp_cache_featured_images' );
add_filter( 'social_connect_redirect_to', 'cp_social_connect_redirect_to', 10, 1 );

add_action( 'register_form', 'cp_maybe_display_recaptcha' );
add_filter( 'show_password_fields_on_registration', 'cp_password_fields_support', 10, 1 );

add_action( 'wp_login', 'cp_redirect_to_home_page' );
add_action( 'app_login', 'cp_redirect_to_home_page' );

add_action( 'appthemes_advertise_content', 'cp_adbox_336x280' );
add_action( 'appthemes_advertise_header', 'cp_adbox_468x60' );

add_filter( 'close_comments_for_post_types', 'cp_close_comments_for_old_ads' );
add_filter( 'wp_dropdown_cats', 'cp_change_dropdown_indentation_on_mobile' );
add_filter( 'cp_formbuilder_cp_price', 'cp_limit_characters_in_price_field' );
add_action( 'user_register', 'cp_move_social_url_on_user_registration' );
add_action( 'template_redirect', 'cp_set_default_template_vars' );
add_filter( 'appthemes_favicon', 'cp_custom_favicon', 10, 1 );

add_filter( 'mce_buttons', 'cp_editor_modify_buttons', 10, 2 );


### Hook Callbacks

/**
 * Adds support for cufon font replacement.
 * @since 3.0.5
 *
 * @return void
 */
function cp_cufon_styles() {
	global $cp_options;

	if ( ! $cp_options->cufon_enable ) {
		return;
	}
?>
	<!--[if gte IE 9]> <script type="text/javascript"> Cufon.set('engine', 'canvas'); </script> <![endif]-->

	<!-- cufon font replacements -->
	<script type="text/javascript">
		// <![CDATA[
		<?php echo stripslashes( $cp_options->cufon_code ) . "\n"; ?>
		// ]]>
	</script>
	<!-- end cufon font replacements -->

<?php
}



/**
 * Adds the google analytics tracking code in the footer.
 * @since 3.0.5
 *
 * @return void
 */
function cp_google_analytics_code() {
	global $cp_options;

	if ( ! empty( $cp_options->google_analytics ) ) {
		echo stripslashes( $cp_options->google_analytics );
	}

}



/**
 * Adds the ad price field in the loop before the ad title.
 * @since 3.1.3
 *
 * @return void
 */
function cp_ad_loop_price() {
	global $post;

	if ( $post->post_type == 'page' || $post->post_type == 'post' ) {
		return;
	}
?>

	<div class="tags price-wrap">
		<span class="tag-head"><p class="post-price"><?php cp_get_price( $post->ID, 'cp_price' ); ?></p></span>
	</div>

<?php
}



/**
 * Adds the ad meta in the loop after the ad title.
 * @since 3.1
 *
 * @return void
 */
function cp_ad_loop_meta() {
	global $post, $cp_options;

	if ( is_singular( APP_POST_TYPE ) ) {
		return;
	}
?>
	<p class="post-meta">
		<span class="dashicons-before folder"><?php if ( $post->post_type == 'post' ) the_category( ', ' ); else echo get_the_term_list( $post->ID, APP_TAX_CAT, '', ', ', '' ); ?></span> <span class="dashicons-before owner"><?php if ( $cp_options->ad_gravatar_thumb ) appthemes_get_profile_pic( get_the_author_meta( 'ID' ), get_the_author_meta( 'user_email' ), 32 ); ?><?php the_author_posts_link(); ?></span> <span class="dashicons-before clock"><span><?php echo appthemes_date_posted( $post->post_date ); ?></span></span>
	</p>
<?php
}



/**
 * Adds the stats after the ad listing and blog post content.
 * @since 3.1
 *
 * @return void
 */
function cp_do_loop_stats() {
	global $post, $cp_options;

	if ( is_singular( array( 'post', APP_POST_TYPE ) ) ) {
		return;
	}

	if ( ! $cp_options->ad_stats_all || ! current_theme_supports( 'app-stats' ) ) {
		return;
	}
?>
	<p class="stats"><?php appthemes_stats_counter( $post->ID ); ?></p>
<?php
}


/**
 * Adds the ad reference ID after the ad listing content.
 * @since 3.1.3
 *
 * @return void
 */
function cp_do_ad_ref_id() {
	global $post;

	if ( ! is_singular( APP_POST_TYPE ) ) {
		return;
	}

?>
	<div class="note"><strong><?php _e( 'Listing ID:', APP_TD ); ?></strong> <?php if ( get_post_meta( $post->ID, 'cp_sys_ad_conf_id', true ) ) echo get_post_meta( $post->ID, 'cp_sys_ad_conf_id', true ); else _e( 'N/A', APP_TD ); ?></div>
	<div class="dotted"></div>
	<div class="pad5"></div>
<?php
}


/**
 * Adds the pagination after the ad listing and blog post content.
 * @since 3.1
 *
 * @return void
 */
function cp_do_pagination() {
	// don't do on pages, the home page, or single blog post
	if ( is_page() || is_singular( 'post' ) ) {
		return;
	}

	if ( function_exists( 'appthemes_pagination' ) ) {
		appthemes_pagination();
	}

}



/**
 * Adds the no ads found message.
 * @since 3.1
 *
 * @return void
 */
function cp_ad_loop_else() {
?>
	<div class="shadowblock_out">

		<div class="shadowblock">

			<div class="pad10"></div>

			<p class="not-found"><?php _e( 'Sorry, no listings were found.', APP_TD ); ?></p>

			<div class="pad10"></div>

		</div><!-- /shadowblock -->

	</div><!-- /shadowblock_out -->
<?php
}


/**
 * Adds the post meta after the blog post title.
 * @since 3.1
 *
 * @return void
 */
function cp_blog_post_meta() {
	global $post;

	// don't do post-meta on pages
	if ( is_page() ) {
		return;
	}
?>
	<p class="meta dotted"><span class="dashicons-before user"><?php the_author_posts_link(); ?></span> <span class="dashicons-before folderb"><?php the_category( ', ' ); ?></span> <span class="dashicons-before clock"><span><?php echo appthemes_date_posted( $post->post_date ); ?></span></span></p>
<?php
}


/**
 * Adds the blog post meta footer content.
 * @since 3.1.3
 *
 * @return void
 */
function cp_blog_post_meta_footer() {
	global $post, $cp_options;

	if ( ! is_singular( array( 'post', APP_POST_TYPE ) ) ) {
		return;
	}
?>
	<div class="prdetails">
		<?php if ( is_singular( 'post' ) ) { ?>
			<p class="dashicons-before tags"><?php if ( get_the_tags() ) echo the_tags( '', '&nbsp;', '' ); else _e( 'No Tags', APP_TD ); ?></p>
		<?php } else { ?>
			<p class="dashicons-before tags"><?php if ( get_the_term_list( $post->ID, APP_TAX_TAG ) ) echo get_the_term_list( $post->ID, APP_TAX_TAG, '', '&nbsp;', '' ); else _e( 'No Tags', APP_TD ); ?></p>
		<?php } ?>
		<?php if ( $cp_options->ad_stats_all && current_theme_supports( 'app-stats' ) ) { ?><p class="dashicons-before stats"><?php appthemes_stats_counter( $post->ID ); ?></p> <?php } ?>
		<p class="dashicons-before print"><?php if ( function_exists( 'wp_email' ) ) email_link(); ?>&nbsp;&nbsp;<?php if ( function_exists( 'wp_print' ) ) print_link(); ?></p>
		<?php cp_edit_ad_link(); ?>
	</div>

<?php
}


/**
 * Adds the no blog posts found message.
 * @since 3.1
 *
 * @return void
 */
function cp_blog_loop_else() {
?>
	<div class="shadowblock_out">

		<div class="shadowblock">

			<div class="pad10"></div>

			<p><?php _e( 'Sorry, no posts could be found.', APP_TD ); ?></p>

			<div class="pad50"></div>

		</div><!-- /shadowblock -->

	</div><!-- /shadowblock_out -->
<?php
}


/**
 * Adds the comments bubble to blog posts.
 * @since 3.1.3
 *
 * @return void
 */
function cp_blog_comments_bubble() {

	if ( ! comments_open() ) {
		return;
	}

?>
	<div class="dashicons-before comment-bubble"><?php comments_popup_link( '0', '1', '%', 'bubble' ); ?></div>
<?php
}


/**
 * Adds the blog and ad listing single page banner ad.
 * @since 3.1.3
 *
 * @return void
 */
function cp_single_ad_banner() {
	global $post;

	if ( ! is_singular( array( 'post', APP_POST_TYPE ) ) ) {
		return;
	}

	appthemes_advertise_content();
}

/**
 * Adds the blog and ad listing category/search banner ad.
 *
 * @since 3.5
 *
 * @return void
 */
function cp_category_search_ad_banner() {
	global $post;

	if ( ! is_category() && ! is_tax( APP_TAX_CAT ) && ! is_search() ) {
		return;
	}

	appthemes_advertise_content();
}


/**
 * Collects stats if are enabled, limits db queries.
 * @since 3.1.8
 *
 * @return void
 */
function cp_cache_stats() {
	global $cp_options;

	if ( is_singular( array( APP_POST_TYPE, 'post' ) ) ) {
		return;
	}

	if ( ! $cp_options->ad_stats_all || ! current_theme_supports( 'app-stats' ) ) {
		return;
	}

	add_action( 'appthemes_before_loop', 'appthemes_collect_stats' );
	//add_action( 'appthemes_before_search_loop', 'appthemes_collect_stats' );
	add_action( 'appthemes_before_blog_loop', 'appthemes_collect_stats' );
}


/**
 * Collects featured images if are enabled, limits db queries.
 * @since 3.1.8
 *
 * @return void
 */
function cp_cache_featured_images() {
	global $cp_options;

	if ( $cp_options->ad_images && ! is_singular( array( APP_POST_TYPE, 'post' ) ) ) {
		add_action( 'appthemes_before_loop', 'cp_collect_featured_images' );
		add_action( 'appthemes_before_featured_loop', 'cp_collect_featured_images' );
		//add_action( 'appthemes_before_search_loop', 'cp_collect_featured_images' );
		add_action( 'appthemes_before_blog_loop', 'cp_collect_featured_images' );
	}
}


/**
 * Modifies Social Connect redirect to url.
 * @since 3.1.9
 *
 * @param string $redirect_to
 *
 * @return string
 */
function cp_social_connect_redirect_to( $redirect_to ) {
	if ( preg_match( '#/wp-(admin|login)?(.*?)$#i', $redirect_to ) ) {
		$redirect_to = home_url();
	}

	if ( current_theme_supports( 'app-login' ) ) {
		if ( APP_Login::get_url( 'redirect' ) == $redirect_to || appthemes_get_registration_url( 'redirect' ) == $redirect_to ) {
			$redirect_to = home_url();
		}
	}

	return $redirect_to;
}


/**
 * Process Social Connect request if App Login enabled.
 * @since 3.2
 *
 * @return void
 */
function cp_social_connect_login() {
	if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'social_connect' ) {
		if ( current_theme_supports( 'app-login' ) && function_exists( 'sc_social_connect_process_login' ) ) {
			sc_social_connect_process_login( false );
		}
	}
}


/**
 * Display reCatpcha if theme supports it.
 *
 * @since 3.5.2
 */
function cp_maybe_display_recaptcha() {
	global $cp_options;

	if ( ! $cp_options->captcha_enable ) {
		return;
	}

	appthemes_display_recaptcha();
}


/**
 * Controls password fields visibility.
 * @since 3.2
 *
 * @param bool $show_password
 *
 * @return bool
 */
function cp_password_fields_support( $show_password ) {
	global $cp_options;

	return (bool) $cp_options->allow_registration_password;
}


/**
 * Replaces default registration email.
 * @since 3.2
 *
 * @return void
 */
function cp_custom_registration_email() {
	remove_action( 'appthemes_after_registration', 'appthemes_new_user_notification', 10, 2 );
	add_action( 'appthemes_after_registration', 'cp_new_user_notification', 10, 2 );
}


/**
 * Redirects logged in users to homepage.
 * @since 3.2
 *
 * @return void
 */
function cp_redirect_to_home_page() {
	if ( ! isset( $_REQUEST['redirect_to'] ) ) {
		wp_redirect( home_url() );
		exit();
	}
}


/**
 * Displays 336 x 280 ad box on single page.
 * @since 3.3
 *
 * @return void
 */
function cp_adbox_336x280() {
	global $cp_options;

	if ( ! $cp_options->adcode_336x280_enable ) {
		return;
	}
?>
	<div class="shadowblock_out">
		<div class="shadowblock">
			<h2 class="dotted"><?php _e( 'Sponsored Links', APP_TD ); ?></h2>
<?php
			if ( ! empty( $cp_options->adcode_336x280 ) ) {
				echo stripslashes( $cp_options->adcode_336x280 );
			} elseif ( $cp_options->adcode_336x280_url ) {
				$img = html( 'img', array( 'src' => $cp_options->adcode_336x280_url, 'border' => '0', 'alt' => '' ) );
				echo html( 'a', array( 'href' => $cp_options->adcode_336x280_dest, 'target' => '_blank' ), $img );
			}
?>
		</div><!-- /shadowblock -->
	</div><!-- /shadowblock_out -->
<?php
}


/**
 * Displays 468 x 60 ad box in header.
 * @since 3.3
 *
 * @return void
 */
function cp_adbox_468x60() {
	global $cp_options;

	if ( ! $cp_options->adcode_468x60_enable ) {
		return;
	}

	if ( ! empty( $cp_options->adcode_468x60 ) ) {
		echo stripslashes( $cp_options->adcode_468x60 );
	} else {
		if ( ! $cp_options->adcode_468x60_url ) {
			$img = html( 'img', array( 'src' => get_template_directory_uri() . '/images/468x60-banner.jpg', 'width' => '468', 'height' => '60', 'border' => '0', 'alt' => 'Premium WordPress Themes - AppThemes' ) );
			echo html( 'a', array( 'href' => 'https://www.appthemes.com', 'target' => '_blank' ), $img );
		} else {
			$img = html( 'img', array( 'src' => $cp_options->adcode_468x60_url, 'border' => '0', 'alt' => '' ) );
			echo html( 'a', array( 'href' => $cp_options->adcode_468x60_dest, 'target' => '_blank' ), $img );
		}
	}

}


/**
 * Disables WordPress 'auto-embeds' option.
 * @since 3.3
 *
 * @return void
 */
function cp_disable_auto_embeds() {
	global $cp_options;

	if ( ! $cp_options->disable_embeds ) {
		return;
	}

	remove_filter( 'the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
}


/**
 * Inserts link for admin to reset stats of an ad or post.
 * @since 3.3
 *
 * @return void
 */
function cp_add_reset_stats_link() {
	global $cp_options;

	if ( ! is_singular( array( APP_POST_TYPE, 'post' ) ) || ! $cp_options->ad_stats_all ) {
		return;
	}

	appthemes_reset_stats_link();
}


/**
 * Pings 'update services' while publish ad listing.
 * @since 3.3
 */
add_action( 'publish_' . APP_POST_TYPE, '_publish_post_hook', 5, 1 );


/**
 * Closes comments for old ads.
 * @see WordPress->Settings->Discussion
 * @since 3.3
 *
 * @param array $post_types
 *
 * @return array
 */
function cp_close_comments_for_old_ads( $post_types ) {
	$post_types[] = APP_POST_TYPE;

	return $post_types;
}


/**
 * Changes drop down indentation on mobile devices.
 * @since 3.3.1
 *
 * @param string $dropdown
 *
 * @return string
 */
function cp_change_dropdown_indentation_on_mobile( $dropdown ) {
	if ( wp_is_mobile() ) {
		$dropdown = preg_replace( '/&nbsp;&nbsp;&nbsp;/', ' - ', $dropdown );
	}

	return $dropdown;
}


/**
 * Limits characters in the price field.
 * @since 3.3.1
 *
 * @param array $args
 *
 * @return array
 */
function cp_limit_characters_in_price_field( $args ) {
	$args['maxlength'] = 15;

	return $args;
}


/**
 * Moves social URLs into custom fields on user registration.
 * @since 3.3.2
 *
 * @param int $user_id
 *
 * @return void
 */
function cp_move_social_url_on_user_registration( $user_id ) {

	$user_info = get_userdata( $user_id );

	if ( empty( $user_info->user_url ) ) {
		return;
	}

	if ( preg_match( '#facebook.com#i', $user_info->user_url ) ) {
		wp_update_user( array ( 'ID' => $user_id, 'user_url' => '' ) );
		update_user_meta( $user_id, 'facebook_id', $user_info->user_url );
	}
}


/**
 * Make the options object instantly available in templates.
 * @since 3.3.2
 *
 * @return void
 */
function cp_set_default_template_vars() {
	global $cp_options;

	appthemes_add_template_var( 'cp_options', $cp_options );
}


/**
 * Adds custom favicon if specified in settings.
 * @since 3.3.2
 *
 * @param string $favicon
 *
 * @return string
 */
function cp_custom_favicon( $favicon ) {
	global $cp_options;

	if ( ! empty( $cp_options->favicon_url ) ) {
		$favicon = $cp_options->favicon_url;
	}

	return $favicon;
}


/**
 * Adds version number in the header for troubleshooting.
 * @since 3.3.2
 *
 * @return void
 */
function cp_generator() {
	echo "\n\t" . '<meta name="generator" content="ClassiPress ' . CP_VERSION . '" />' . "\n";
}


/**
 * Add a pingback url auto-discovery header for singularly identifiable articles.
 *
 * @since 3.6.0
 */
function cp_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">' . "\n", get_bloginfo( 'pingback_url' ) );
	}
}


/**
 * Add an alternate rss feed url if Feedburner is provided. Otherwise use default.
 *
 * @since 3.6.0
 */
function cp_alternate_rss() {
	printf( '<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="%s">' . "\n", appthemes_get_feed_url() );
}


/**
 * Disables some WordPress features.
 * @since 3.3.2
 *
 * @return void
 */
function cp_disable_wp_features() {
	global $cp_options;

	// remove the WordPress version meta tag
	if ( $cp_options->remove_wp_generator ) {
		remove_action( 'wp_head', 'wp_generator' );
	}

	// remove the new 3.1 admin header toolbar visible on the website if logged in
	if ( $cp_options->remove_admin_bar ) {
		add_filter( 'show_admin_bar', '__return_false' );
	}

}


/**
 * Modify available buttons in html editor.
 * @since 3.3.3
 *
 * @param array $buttons
 * @param string $editor_id
 *
 * @return array
 */
function cp_editor_modify_buttons( $buttons, $editor_id ) {
	if ( is_admin() || ! is_array( $buttons ) ) {
		return $buttons;
	}

	$remove = array( 'wp_more', 'spellchecker' );

	return array_diff( $buttons, $remove );
}


/**
 * Displays report listing form.
 * @since 3.4
 *
 * @return void
 */
function cp_report_listing_button() {
	global $post;

	if ( ! is_singular( array( APP_POST_TYPE ) ) ) {
		return;
	}

	$form = appthemes_get_reports_form( $post->ID, 'post' );
	if ( ! $form ) {
		return;
	}

	$content = '<p class="edit">';
	$content .= '<a href="#" class="dashicons-before reports_form_link">' . __( 'Report problem', APP_TD ) . '</a>';
	$content .= '</p>';
	$content .= '<div class="report-form">' . $form . '</div>';

	echo $content;
}


/**
 * Deletes all meta and attachments related with the ad.
 *
 * @since 3.5
 *
 * @param int $post_id
 *
 * @return void
 */
function cp_delete_ad_meta( $post_id ) {
	global $wpdb;

	if ( APP_POST_TYPE != get_post_type( $post_id ) ) {
		return;
	}

	$attachments_query = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type='attachment'", $post_id );
	$attachments = $wpdb->get_results( $attachments_query );

	// delete all associated attachments
	if ( $attachments ) {
		foreach ( $attachments as $attachment ) {
			wp_delete_attachment( $attachment->ID, true );
		}
	}

	// delete geo location
	cp_delete_geocode( $post_id );
}


/**
 * Overrides the excerpt read more text.
 *
 * @since 3.5
 *
 * @param string $text The more text
 *
 * @return string The overriden more text.
 */
function cp_ads_excerpt_more( $text ) {
	global $post;

	if ( APP_POST_TYPE != $post->post_type ) {
		return $text;
	}

	return ' ' . html( 'a class="moretag" href="'. get_permalink( $post->ID ) . '"', __( '[&hellip;]', APP_TD ) );
}


/**
 * Overrides the excerpt length.
 *
 * @since 3.5
 *
 * @param int $length The excerpt length
 *
 * @return int The overriden excerpt length.
 */
function cp_ads_excerpt_length( $length ) {
	global $post;

	if ( APP_POST_TYPE != $post->post_type ) {
		return $length;
	}

	return 25;
}


/*
 * Loads and outputs content from a given template.
 */
function _cp_get_dynamic_content() {

	check_ajax_referer( 'cp-nonce', 'security' );

	$valid = cp_get_ads_listing_tabs();

	// indeed search results
	if ( ! empty( $_POST['content'] )
		&& isset( $valid[ $_POST['content'] ] )
		&& function_exists( $valid[ $_POST['content'] ]['callback'] ) ) {

		ob_start();

		call_user_func( $valid[ $_POST['content'] ]['callback'] );

		echo ob_get_clean();
	}

	die();

}


/**
 * Remove default notices markup.
 *
 * @since 3.5
 */
function _cp_remove_default_notices() {
	remove_action( 'appthemes_display_notice', array( 'APP_Notices', 'outputter' ), 10 );
}


/**
 * Prints notices.
 *
 * @since 3.5
 *
 * @param string $class CSS class of notice block.
 * @param array $msgs Messages to be displayed.
 * @return void
 */
function cp_output_notices( $class, $msgs ) {
?>
	<div class="notice <?php echo esc_attr( $class ); ?>">
		<?php foreach ( $msgs as $msg ) { ?>
			<div class="dashicons-before"><?php echo $msg; ?></div>
		<?php } ?>
	</div>
<?php
}
