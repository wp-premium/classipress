<?php
/**
 * Add and initiate the AppThemes hooks
 *
 * @uses add_action() calls to trigger the hooks.
 * @package Framework\Hooks
 *
 * DO NOT UPDATE WITHOUT UPDATING ALL OTHER THEMES!
 * This is a shared file so changes need to be propagated to insure sync
 */


/**
 * called after theme files are included but before theme is loaded
 *
 */
function appthemes_init() {
	do_action( 'appthemes_init' );
}


/**
 * called in header.php after the opening body tag
 *
 */
function appthemes_before() {
	do_action( 'appthemes_before' );
}


/**
 * called in footer.php before the closing body tag
 *
 */
function appthemes_after() {
	do_action( 'appthemes_after' );
}


/**
 * called in header.php before the theme header hook loads
 *
 */
function appthemes_before_header() {
	do_action( 'appthemes_before_header' );
}


/**
 * called in header.php and loads the theme header
 *
 */
function appthemes_header() {
	do_action( 'appthemes_header' );
}


/**
 * called in header.php after the theme header hook loads
 *
 */
function appthemes_after_header() {
	do_action( 'appthemes_after_header' );
}


/**
 * Page action hooks
 *
 */


/**
 * called in page.php before the loop executes
 *
 */
function appthemes_before_page_loop() {
	do_action( 'appthemes_before_page_loop' );
}


/**
 * called in page.php before the page post section
 *
 */
function appthemes_before_page() {
	do_action( 'appthemes_before_page' );
}


/**
 * called in page.php before the page post title tag
 *
 */
function appthemes_before_page_title() {
	do_action( 'appthemes_before_page_title' );
}


/**
 * called in page.php after the page post title tag
 *
 */
function appthemes_after_page_title() {
	do_action( 'appthemes_after_page_title' );
}


/**
 * called in page.php before the page post content
 *
 */
function appthemes_before_page_content() {
	do_action( 'appthemes_before_page_content' );
}


/**
 * called in page.php after the page post content
 *
 */
function appthemes_after_page_content() {
	do_action( 'appthemes_after_page_content' );
}


/**
 * called in page.php after the page post section
 *
 */
function appthemes_after_page() {
	do_action( 'appthemes_after_page' );
}


/**
 * called in page page.php after the loop endwhile
 *
 */
function appthemes_after_page_endwhile() {
	do_action( 'appthemes_after_page_endwhile' );
}


/**
 * called in page page.php after the loop else
 *
 */
function appthemes_page_loop_else() {
	do_action( 'appthemes_page_loop_else' );
}


/**
 * called in page page.php after the loop executes
 *
 */
function appthemes_after_page_loop() {
	do_action( 'appthemes_after_page_loop' );
}


/**
 * called in page comments-page.php before the comments list block
 *
 */
function appthemes_before_page_comments() {
	do_action( 'appthemes_before_page_comments' );
}


/**
 * called in page comments-page.php in the ol block
 *
 */
function appthemes_list_page_comments() {
	do_action( 'appthemes_list_page_comments' );
}


/**
 * called in page comments-page.php after the comments list block
 *
 */
function appthemes_after_page_comments() {
	do_action( 'appthemes_after_page_comments' );
}


/**
 * called in page comments.php before the pings list block
 *
 */
function appthemes_before_page_pings() {
	do_action( 'appthemes_before_page_pings' );
}


/**
 * called in page comments.php in the ol block
 *
 */
function appthemes_list_page_pings() {
	do_action( 'appthemes_list_page_pings' );
}


/**
 * called in page comments.php after the pings list block
 *
 */
function appthemes_after_page_pings() {
	do_action( 'appthemes_after_page_pings' );
}


/**
 * called in page comments-page.php before the comments respond block
 *
 */
function appthemes_before_page_respond() {
	do_action( 'appthemes_before_page_respond' );
}


/**
 * called in page comments-page.php after the comments respond block
 *
 */
function appthemes_after_page_respond() {
	do_action( 'appthemes_after_page_respond' );
}


/**
 * called in page comments-page.php before the comments form block
 *
 */
function appthemes_before_page_comments_form() {
	do_action( 'appthemes_before_page_comments_form' );
}


/**
 * called in page comments-page.php to include the comments form block
 *
 */
function appthemes_page_comments_form() {
	do_action( 'appthemes_page_comments_form' );
}


/**
 * called in page comments-page.php after the comments form block
 *
 */
function appthemes_after_page_comments_form() {
	do_action( 'appthemes_after_page_comments_form' );
}



/**
 * Blog action hooks
 *
 */


/**
 * called in loop.php before the loop executes
 *
 */
function appthemes_before_blog_loop() {
	do_action( 'appthemes_before_blog_loop' );
}


/**
 * called in loop.php before the blog post section
 *
 */
function appthemes_before_blog_post() {
	do_action( 'appthemes_before_blog_post' );
}


/**
 * called in loop.php before the blog post title tag
 *
 */
function appthemes_before_blog_post_title() {
	do_action( 'appthemes_before_blog_post_title' );
}


/**
 * called in loop.php after the blog post title tag
 *
 */
function appthemes_after_blog_post_title() {
	do_action( 'appthemes_after_blog_post_title' );
}


/**
 * called in loop.php before the blog post content
 *
 */
function appthemes_before_blog_post_content() {
	do_action( 'appthemes_before_blog_post_content' );
}


/**
 * called in loop.php after the blog post content
 *
 */
function appthemes_after_blog_post_content() {
	do_action( 'appthemes_after_blog_post_content' );
}


/**
 * called in loop.php after the blog post section
 *
 */
function appthemes_after_blog_post() {
	do_action( 'appthemes_after_blog_post' );
}


/**
 * called in blog loop.php after the loop endwhile
 *
 */
function appthemes_after_blog_endwhile() {
	do_action( 'appthemes_after_blog_endwhile' );
}


/**
 * called in blog loop.php after the loop else
 *
 */
function appthemes_blog_loop_else() {
	do_action( 'appthemes_blog_loop_else' );
}


/**
 * called in blog loop.php after the loop executes
 *
 */
function appthemes_after_blog_loop() {
	do_action( 'appthemes_after_blog_loop' );
}


/**
 * called in blog comments-blog.php before the comments list block
 *
 */
function appthemes_before_blog_comments() {
	do_action( 'appthemes_before_blog_comments' );
}


/**
 * called in blog comments.php in the ol block
 *
 */
function appthemes_list_blog_comments() {
	do_action( 'appthemes_list_blog_comments' );
}


/**
 * called in blog comments-blog.php after the comments list block
 *
 */
function appthemes_after_blog_comments() {
	do_action( 'appthemes_after_blog_comments' );
}


/**
 * called in blog comments.php before the pings list block
 *
 */
function appthemes_before_blog_pings() {
	do_action( 'appthemes_before_blog_pings' );
}


/**
 * called in blog comments.php in the ol block
 *
 */
function appthemes_list_blog_pings() {
	do_action( 'appthemes_list_blog_pings' );
}


/**
 * called in blog comments.php after the pings list block
 *
 */
function appthemes_after_blog_pings() {
	do_action( 'appthemes_after_blog_pings' );
}


/**
 * called in blog comments-blog.php before the comments respond block
 *
 */
function appthemes_before_blog_respond() {
	do_action( 'appthemes_before_blog_respond' );
}


/**
 * called in blog comments-blog.php after the comments respond block
 *
 */
function appthemes_after_blog_respond() {
	do_action( 'appthemes_after_blog_respond' );
}


/**
 * called in blog comments-blog.php before the comments form block
 *
 */
function appthemes_before_blog_comments_form() {
	do_action( 'appthemes_before_blog_comments_form' );
}


/**
 * called in blog comments-blog.php to include the comments form block
 *
 */
function appthemes_blog_comments_form() {
	do_action( 'appthemes_blog_comments_form' );
}


/**
 * called in blog comments-blog.php after the comments form block
 *
 */
function appthemes_after_blog_comments_form() {
	do_action( 'appthemes_after_blog_comments_form' );
}



/**
 * Custom post type action hooks
 *
 */


/**
 * called in loop-[custom-post-type].php before the loop executes
 *
 */
function appthemes_before_loop( $type = '' ) {
	if ( $type ) {
		$type .= '_';
	}

	do_action( 'appthemes_before_' . $type . 'loop' );
}


/**
 * called in loop-[custom-post-type].php before the post section
 *
 */
function appthemes_before_post( $type = 'post' ) {
	do_action( 'appthemes_before_' . $type );
}


/**
 * called in loop-[custom-post-type].php before the post title
 *
 */
function appthemes_before_post_title( $type = 'post' ) {
	do_action( 'appthemes_before_' . $type . '_title' );
}


/**
 * called in loop-[custom-post-type].php after the post title
 *
 */
function appthemes_after_post_title( $type = 'post' ) {
	do_action( 'appthemes_after_' . $type . '_title' );
}


/**
 * called in loop-[custom-post-type].php before the post content
 *
 */
function appthemes_before_post_content( $type = 'post' ) {
	if ( $type ) {
		$type .= '_';
	}

	do_action( 'appthemes_before_' . $type . 'content' );
}


/**
 * called in loop-[custom-post-type].php after the post content
 *
 */
function appthemes_after_post_content( $type = 'post' ) {
	if ( $type ) {
		$type .= '_';
	}

	do_action( 'appthemes_after_' . $type . 'content' );
}


/**
 * called in loop-[custom-post-type].php after the post section
 *
 */
function appthemes_after_post( $type = 'post' ) {
	do_action( 'appthemes_after_' . $type );
}


/**
 * called in loop-[custom-post-type].php after the loop endwhile
 *
 */
function appthemes_after_endwhile( $type = '' ) {
	if ( $type ) {
		$type .= '_';
	}

	do_action( 'appthemes_after_' . $type . 'endwhile' );
}


/**
 * called in loop-[custom-post-type].php after the loop else
 *
 */
function appthemes_loop_else( $type = '' ) {
	if ( $type ) {
		$type .= '_';
	}

	do_action( 'appthemes_' . $type . 'loop_else' );
}


/**
 * called in loop-[custom-post-type].php after the loop executes
 *
 */
function appthemes_after_loop( $type = '' ) {
	if ( $type ) {
		$type .= '_';
	}

	do_action( 'appthemes_after_' . $type . 'loop' );
}


/**
 * called in comments-[custom-post-type].php before the comments list block
 *
 */
function appthemes_before_comments() {
	do_action( 'appthemes_before_comments' );
}


/**
 * called in comments-[custom-post-type].php in the ol block
 *
 */
function appthemes_list_comments() {
	do_action( 'appthemes_list_comments' );
}


/**
 * called in comments-[custom-post-type].php after the comments list block
 *
 */
function appthemes_after_comments() {
	do_action( 'appthemes_after_comments' );
}


/**
 * called in comments-[custom-post-type].php before the pings list block
 *
 */
function appthemes_before_pings() {
	do_action( 'appthemes_before_pings' );
}


/**
 * called in comments-[custom-post-type].php in the ol block
 *
 */
function appthemes_list_pings() {
	do_action( 'appthemes_list_pings' );
}


/**
 * called in comments-[custom-post-type].php after the pings list block
 *
 */
function appthemes_after_pings() {
	do_action( 'appthemes_after_pings' );
}


/**
 * called in comments-[custom-post-type].php before the comments respond block
 *
 */
function appthemes_before_respond() {
	do_action( 'appthemes_before_respond' );
}


/**
 * called in comments-[custom-post-type].php after the comments respond block
 *
 */
function appthemes_after_respond() {
	do_action( 'appthemes_after_respond' );
}


/**
 * called in comments-[custom-post-type].php before the comments form block
 *
 */
function appthemes_before_comments_form() {
	do_action( 'appthemes_before_comments_form' );
}


/**
 * called in comments-[custom-post-type].php to include the comments form block
 *
 */
function appthemes_comments_form() {
	do_action( 'appthemes_comments_form' );
}


/**
 * called in comments-[custom-post-type].php after the comments form block
 *
 */
function appthemes_after_comments_form() {
	do_action( 'appthemes_after_comments_form' );
}



/**
 * sidebar hooks
 *
 */


/**
 * called in the sidebar template files before the widget section
 *
 */
function appthemes_before_sidebar_widgets( $location = '' ) {
	do_action( 'appthemes_before_sidebar_widgets', $location );
}


/**
 * called in the sidebar template files after the widget section
 *
 */
function appthemes_after_sidebar_widgets( $location = '' ) {
	do_action( 'appthemes_after_sidebar_widgets', $location );
}


/**
 * footer hooks
 *
 */


/**
 * called in the footer.php before the footer section
 *
 */
function appthemes_before_footer() {
	do_action( 'appthemes_before_footer' );
}


/**
 * invokes the footer section called in footer.php
 *
 */
function appthemes_footer() {
	do_action( 'appthemes_footer' );
}


/**
 * called in the footer.php after the footer section
 *
 */
function appthemes_after_footer() {
	do_action( 'appthemes_after_footer' );
}


/**
 * advertise hooks
 *
 */


/**
 * invokes the header advertise section
 *
 */
function appthemes_advertise_header() {
	do_action( 'appthemes_advertise_header' );
}


/**
 * invokes the content advertise section
 *
 */
function appthemes_advertise_content() {
	do_action( 'appthemes_advertise_content' );
}


/**
 * Invokes notices on templates
 */
function appthemes_notices() {
	do_action( 'appthemes_notices' );
}

/**
 * Can be used in class method to extend method functionality with custom actions
 *
 * @param string $class Class name
 * @param string $method Method name
 */
function appthemes_class_method( $class, $method ) {
	do_action( strtolower( $class . '_' . $method ) );
}
