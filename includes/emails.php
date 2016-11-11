<?php
/**
 * Email functions.
 *
 * @package ClassiPress\Emails
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */

add_action( 'appthemes_transaction_completed', 'cp_send_buyer_receipt' );
add_action( 'appthemes_transaction_completed', 'cp_send_admin_receipt' );
add_action( 'appthemes_transaction_failed', 'cp_send_admin_failed_transaction' );
add_action( 'publish_to_pending', 'cp_notify_admin_moderated_listing', 10, 1 );


### Hook Callbacks

/**
 * Sends new ad notification email to admin.
 *
 * @param int $post_id
 *
 * @return void
 */
function cp_new_ad_email( $post_id ) {

	// get the post
	$post = get_post( $post_id );

	$title = $post->post_title;
	$category = appthemes_get_custom_taxonomy( $post_id, APP_TAX_CAT, 'name' );
	$author = stripslashes( cp_get_user_name( $post->post_author ) );
	$url = get_permalink( $post_id );
	$edit_url = get_edit_post_link( $post_id, '' );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject = sprintf( __( '[%s] New Ad Submission', APP_TD ), $blogname );

	$message  = html( 'p', __( 'Dear Admin,', APP_TD ) ) . PHP_EOL;
	$message .= html( 'p', sprintf( __( 'The following ad listing has just been submitted on your %s website.', APP_TD ), $blogname ) ) . PHP_EOL;

	$message_details  = __( 'Ad Details', APP_TD ) . '<br />';
	$message_details .= __( '-----------------', APP_TD ) . '<br />';
	$message_details .= sprintf( __( 'Title: %s', APP_TD ), $title ) . '<br />';
	$message_details .= sprintf( __( 'Category: %s', APP_TD ), $category ) . '<br />';
	$message_details .= sprintf( __( 'Author: %s', APP_TD ), $author ) . '<br />';
	$message_details .= __( '-----------------', APP_TD ) . '<br />';

	$message .= html( 'p', $message_details ) . PHP_EOL;
	$message .= html( 'p', sprintf( __( 'Preview Ad: %s', APP_TD ), html_link( $url ) ) ) . PHP_EOL;
	$message .= html( 'p', sprintf( __( 'Edit Ad: %s', APP_TD ), html_link( $edit_url ) ) ) . PHP_EOL;
	$message .= html( 'p', __( 'Regards,', APP_TD ) . '<br />' . __( 'ClassiPress', APP_TD ) ) . PHP_EOL;

	$email = array( 'to' => get_option( 'admin_email' ), 'subject' => $subject, 'message' => $message );
	$email = apply_filters( 'cp_email_admin_new_ad', $email, $post_id );

	appthemes_send_email( $email['to'], $email['subject'], $email['message'] );
}


/**
 * Sends new ad notification email to ad owner.
 *
 * @param int $post_id
 *
 * @return void
 */
function cp_owner_new_ad_email( $post_id ) {

	// get the post
	$post = get_post( $post_id );

	$title = $post->post_title;
	$category = appthemes_get_custom_taxonomy( $post_id, APP_TAX_CAT, 'name' );
	$author = stripslashes( cp_get_user_name( $post->post_author ) );
	$author_email = get_the_author_meta( 'user_email', $post->post_author );
	$post_status = cp_get_status_i18n( $post->post_status );

	$site_url = home_url( '/' );
	$dashboard_url = trailingslashit( CP_DASHBOARD_URL );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject = sprintf( __( 'Your Ad Submission on %s', APP_TD ), $blogname );

	$message  = html( 'p', sprintf( __( 'Hi %s,', APP_TD ), $author ) ) . PHP_EOL;
	$message .= html( 'p',
		__( 'Thank you for your recent submission!', APP_TD ) . '<br />' .
		__( 'Your ad listing has been submitted for review and will not appear live on our site until it has been approved.', APP_TD ) . '<br />' .
		sprintf( __( 'Below you will find a summary of your ad listing on the %s website.', APP_TD ), $blogname )
	) . PHP_EOL;

	$message_details  = __( 'Ad Details', APP_TD ) . '<br />';
	$message_details .= __( '-----------------', APP_TD ) . '<br />';
	$message_details .= sprintf( __( 'Title: %s', APP_TD ), $title ) . '<br />';
	$message_details .= sprintf( __( 'Category: %s', APP_TD ), $category ) . '<br />';
	$message_details .= sprintf( __( 'Status: %s', APP_TD ), $post_status ) . '<br />';
	$message_details .= __( '-----------------', APP_TD ) . '<br />';

	$message .= html( 'p', $message_details ) . PHP_EOL;
	$message .= html( 'p', __( 'You may check the status of your ad(s) at anytime by logging into your dashboard.', APP_TD ) . '<br />' . html_link( $dashboard_url ) ) . PHP_EOL;
	$message .= html( 'p', __( 'Regards,', APP_TD ) . '<br />' . sprintf( __( 'Your %s Team', APP_TD ), $blogname ) ) . PHP_EOL;
	$message .= html( 'p', html_link( $site_url ) ) . PHP_EOL;

	$email = array( 'to' => $author_email, 'subject' => $subject, 'message' => $message );
	$email = apply_filters( 'cp_email_user_new_ad', $email, $post_id );

	appthemes_send_email( $email['to'], $email['subject'], $email['message'] );
}


/**
 * Sends email to ad owner when an ad is approved or expires.
 *
 * @param string $new_status
 * @param string $old_status
 * @param object $post
 *
 * @return void
 */
function cp_notify_ad_owner_email( $new_status, $old_status, $post ) {
	global $current_user, $cp_options;

	if ( $post->post_type != APP_POST_TYPE ) {
		return;
	}

	$title = $post->post_title;
	$category = appthemes_get_custom_taxonomy( $post->ID, APP_TAX_CAT, 'name' );
	$author = stripslashes( cp_get_user_name( $post->post_author ) );
	$author_email = stripslashes( get_the_author_meta( 'user_email', $post->post_author ) );
	$post_status = cp_get_status_i18n( $post->post_status );

	$site_url = home_url( '/' );
	$dashboard_url = trailingslashit( CP_DASHBOARD_URL );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	// make sure the admin wants to send emails
	$send_approved_email = $cp_options->new_ad_email_owner;
	$send_expired_email = $cp_options->expired_ad_email_owner;

	// if the ad has been approved send email to ad owner only if owner is not equal to approver (if not in backend)
	// admin approving own ads or ad owner pausing and reactivating ad on his dashboard don't need to send email
	if ( $old_status == 'pending' && $new_status == 'publish' && ( ! is_admin() || ( is_admin() && $current_user->ID != $post->post_author ) ) && $send_approved_email ) {

		$subject = __( 'Your ad has been approved', APP_TD );

		$message  = html( 'p', sprintf( __( 'Hi %s,', APP_TD ), $author ) ) . PHP_EOL;
		$message .= html( 'p', sprintf( __( 'Your ad listing, "%s" has been approved and is now live on our site.', APP_TD ), $title ) ) . PHP_EOL;

		$message .= html( 'p', __( 'You can view your ad by clicking on the following link:', APP_TD ) . '<br />' . html_link( get_permalink( $post->ID ) ) ) . PHP_EOL;

		$message .= html( 'p', __( 'Regards,', APP_TD ) . '<br />' . sprintf( __( 'Your %s Team', APP_TD ), $blogname ) ) . PHP_EOL;
		$message .= html( 'p', html_link( $site_url ) ) . PHP_EOL;

		$email = array( 'to' => $author_email, 'subject' => $subject, 'message' => $message );
		$email = apply_filters( 'cp_email_user_ad_approved', $email, $post );

		appthemes_send_email( $email['to'], $email['subject'], $email['message'] );

	// if the ad has expired, send an email to the ad owner only if owner is not equal to approver
	} elseif ( $old_status == 'publish' && $new_status == 'draft' && $current_user->ID != $post->post_author && $send_expired_email ) {

		$subject = __( 'Your ad has expired', APP_TD );

		$message  = html( 'p', sprintf( __( 'Hi %s,', APP_TD ), $author ) ) . PHP_EOL;
		$message .= html( 'p', sprintf( __( 'Your ad listing, "%s" has expired.', APP_TD ), $title ) ) . PHP_EOL;

		if ( $cp_options->allow_relist ) {
			$message .= html( 'p', __( 'If you would like to relist your ad, please visit your dashboard and click the "relist" link.', APP_TD ) . '<br />' . html_link( $dashboard_url ) ) . PHP_EOL;
		}

		$message .= html( 'p', __( 'Regards,', APP_TD ) . '<br />' . sprintf( __( 'Your %s Team', APP_TD ), $blogname ) ) . PHP_EOL;
		$message .= html( 'p', html_link( $site_url ) ) . PHP_EOL;

		$email = array( 'to' => $author_email, 'subject' => $subject, 'message' => $message );
		$email = apply_filters( 'cp_email_user_ad_expired', $email, $post );

		appthemes_send_email( $email['to'], $email['subject'], $email['message'] );

	}
}
add_filter( 'transition_post_status', 'cp_notify_ad_owner_email', 10, 3 );


/**
 * Sends email to ad author from contact form.
 *
 * @param int $post_id
 *
 * @return object
 */
function cp_contact_ad_owner_email( $post_id ) {
	global $cp_options;

	$errors = new WP_Error();

	if ( ! wp_verify_nonce( $_POST['_cp_contact_nonce'], 'form_contact' ) ) {
		$errors->add( 'invalid_nonce', __( 'ERROR: Nonce field is invalid.', APP_TD ) );
		return $errors;
	}

	if ( $cp_options->captcha_enable ) {
		$errors = cp_recaptcha_verify( $errors );

		if ( $errors->get_error_codes() ) {
			return $errors;
		}
	}

	// check for required post data
	$expected = array( 'from_name', 'from_email', 'subject', 'message' );
	foreach ( $expected as $field_name ) {
		if ( empty( $_POST[ $field_name ] ) ) {
			$errors->add( 'empty_field', __( 'ERROR: All fields are required.', APP_TD ) );
			return $errors;
		}
	}

	// verify email
	if ( ! is_email( $_POST['from_email'] ) ) {
		$errors->add( 'invalid_email', __( 'ERROR: Incorrect email address.', APP_TD ) );
	}

	// verify post
	$post = get_post( $post_id );
	if ( ! $post ) {
		$errors->add( 'invalid_post', __( 'ERROR: Ad does not exist.', APP_TD ) );
	}

	if ( $errors->get_error_code() ) {
		return $errors;
	}

	$author_email = get_the_author_meta( 'user_email', $post->post_author );

	$from_name = appthemes_filter( appthemes_clean( $_POST['from_name'] ) );
	$from_email = appthemes_clean( $_POST['from_email'] );
	$subject = appthemes_filter( appthemes_clean( $_POST['subject'] ) );
	$posted_message = appthemes_filter( appthemes_clean( $_POST['message'] ) );

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$site_url = home_url( '/' );
	$permalink = get_permalink( $post_id );

	$message  = html( 'p', sprintf( __( 'Someone is interested in your ad listing: %s', APP_TD ), html_link( $permalink ) ) ) . PHP_EOL;
	$message .= html( 'p', '"' . wordwrap( nl2br( $posted_message ), 70 ) . '"' ) . PHP_EOL;
	$message .= html( 'p',
		sprintf( __( 'Name: %s', APP_TD ), $from_name ) . '<br />' .
		sprintf( __( 'E-mail: %s', APP_TD ), $from_email )
	) . PHP_EOL;
	$message .= html( 'p',
		__( '-----------------', APP_TD ) . '<br />' .
		sprintf( __( 'This message was sent from %s', APP_TD ), $blogname ) . '<br />' .
		html_link( $site_url )
	) . PHP_EOL;
	$message .= html( 'p', sprintf( __( 'Sent from IP Address: %s', APP_TD ), appthemes_get_ip() ) ) . PHP_EOL;

	$email = array( 'to' => $author_email, 'subject' => $subject, 'message' => $message, 'from' => $from_email, 'from_name' => $from_name );
	$email = apply_filters( 'cp_email_user_ad_contact', $email, $post_id );

	APP_Mail_From::apply_once( array( 'email' => $email['from'], 'name' => $email['from_name'], 'reply' => true ) );
	appthemes_send_email( $email['to'], $email['subject'], $email['message'] );

	return $errors;
}


/**
 * Sends new user notification.
 *
 * @param int $user_id
 * @param string $plaintext_pass (optional)
 *
 * @return void
 */
function cp_new_user_notification( $user_id, $plaintext_pass = '' ) {
	global $cp_options;

	$user = get_user_by( 'id', $user_id );

	$user_login = $user->user_login;
	$user_email = $user->user_email;

	// variables that can be used by admin to dynamically fill in email content
	$find = array( '/%username%/i', '/%password%/i', '/%blogname%/i', '/%siteurl%/i', '/%loginurl%/i', '/%useremail%/i' );
	if ( $cp_options->nu_email_type == 'text/plain' ) {
		$replace = array( $user_login, $plaintext_pass, get_bloginfo( 'name' ), home_url( '/' ), wp_login_url(), $user_email );
	} else {
		$replace = array( $user_login, $plaintext_pass, get_bloginfo( 'name' ), html_link( home_url( '/' ) ), html_link( wp_login_url() ), $user_email );
	}

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	// send the site admin an email everytime a new user registers
	if ( $cp_options->nu_admin_email ) {
		$subject = sprintf( __( '[%s] New User Registration', APP_TD ), $blogname );

		$message  = html( 'p', sprintf( __( 'New user registration on your site %s:', APP_TD ), $blogname ) ) . PHP_EOL;
		$message .= html( 'p', sprintf( __( 'Username: %s', APP_TD ), $user_login ) ) . PHP_EOL;
		$message .= html( 'p', sprintf( __( 'E-mail: %s', APP_TD ), $user_email ) ) . PHP_EOL;

		$email = array( 'to' => get_option( 'admin_email' ), 'subject' => $subject, 'message' => $message );
		$email = apply_filters( 'cp_email_admin_new_user', $email, $user_id, $plaintext_pass );

		appthemes_send_email( $email['to'], $email['subject'], $email['message'] );
	}

	if ( empty( $plaintext_pass ) ) {
		return;
	}

	// check and see if the custom email option has been enabled
	// if so, send out the custom email instead of the default WP one
	if ( $cp_options->nu_custom_email ) {

		// email sent to new user starts here
		$from_name = strip_tags( $cp_options->nu_from_name );
		$from_email = strip_tags( $cp_options->nu_from_email );

		// search and replace any user added variable fields in the subject line
		$subject = stripslashes( $cp_options->nu_email_subject );
		$subject = preg_replace( $find, $replace, $subject );
		$subject = preg_replace( "/%.*%/", "", $subject );

		// search and replace any user added variable fields in the body
		$message = stripslashes( $cp_options->nu_email_body );
		$message = preg_replace( $find, $replace, $message );
		$message = preg_replace( "/%.*%/", "", $message );

		$email = array( 'to' => $user_email, 'subject' => $subject, 'message' => $message, 'from' => $from_email, 'from_name' => $from_name );
		$email = apply_filters( 'cp_email_user_new_user_custom', $email, $user_id, $plaintext_pass );

		APP_Mail_From::apply_once( array( 'email' => $email['from'], 'name' => $email['from_name'] ) );
		if ( $cp_options->nu_email_type == 'text/plain' ) {
			wp_mail( $email['to'], $email['subject'], $email['message'] );
		} else {
			appthemes_send_email( $email['to'], $email['subject'], $email['message'] );
		}

	// send the default email to debug
	} else {

		$subject = sprintf( __( '[%s] Your username and password', APP_TD ), $blogname );

		$message  = html( 'p', sprintf( __( 'Username: %s', APP_TD ), $user_login ) ) . PHP_EOL;
		$message .= html( 'p', sprintf( __( 'Password: %s', APP_TD ), $plaintext_pass ) ) . PHP_EOL;
		$message .= html( 'p', html_link( wp_login_url() ) ) . PHP_EOL;

		$email = array( 'to' => $user_email, 'subject' => $subject, 'message' => $message );
		$email = apply_filters( 'cp_email_user_new_user', $email, $user_id, $plaintext_pass );

		appthemes_send_email( $email['to'], $email['subject'], $email['message'] );

	}

}


/**
 * Sends notification email to buyer when membership was activated.
 *
 * @param object $user
 * @param object $order
 *
 * @return void
 */
function cp_owner_activated_membership_email( $user, $order ) {
	global $cp_options;

	if ( ! $cp_options->membership_activated_email_owner ) {
		return;
	}

	$user_email = $user->user_email;
	$user_login = stripslashes( cp_get_user_name( $user->ID ) );
	$membership = cp_get_membership_package_from_order( $order );
	if ( ! $membership ) {
		return;
	}

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$site_url = home_url( '/' );

	$subject = __( 'Your membership has been activated', APP_TD );

	$message  = html( 'p', sprintf( __( 'Hi %s,', APP_TD ), $user_login ) ) . PHP_EOL;
	$message .= html( 'p', sprintf( __( 'Your membership, "%s" has been activated on our site, and You are ready to post ad listings.', APP_TD ), $membership->pack_name ) ) . PHP_EOL;
	$message .= html( 'p', __( 'You can post your ad by clicking on the following link:', APP_TD ) . '<br />' . html_link( CP_ADD_NEW_URL ) ) . PHP_EOL;
	$message .= html( 'p', __( 'Regards,', APP_TD ) . '<br />' . sprintf( __( 'Your %s Team', APP_TD ), $blogname ) ) . PHP_EOL;
	$message .= html( 'p', html_link( $site_url ) ) . PHP_EOL;

	$email = array( 'to' => $user_email, 'subject' => $subject, 'message' => $message );
	$email = apply_filters( 'cp_email_user_membership_activated', $email, $user, $order );

	appthemes_send_email( $email['to'], $email['subject'], $email['message'] );
}


/**
 * Sends email with receipt to customer after completed purchase.
 *
 * @param object $order
 *
 * @return void
 */
function cp_send_buyer_receipt( $order ) {

	if ( 0 == $order->get_total() && ! apply_filters( 'cp_send_zero_sum_order_emails', false ) ) {
		return;
	}

	$recipient = get_user_by( 'id', $order->get_author() );

	$unique_items = array();

	$items_html = '';
	foreach ( $order->get_items() as $item ) {
		$ptype_obj = get_post_type_object( $item['post']->post_type );
		if ( ! $ptype_obj->public || isset( $unique_items[ $item['post']->ID ] ) ) {
			continue;
		}

		$unique_items[ $item['post']->ID ] = $item['post']->ID;

		if ( $order->get_id() != $item['post']->ID ) {
			$items_html .= html( 'p', html_link( get_permalink( $item['post']->ID ), $item['post']->post_title ) );
		} else {
			$items_html .= html( 'p', html( 'strong', APP_Item_Registry::get_title( $item['type'] ) ) );
		}
	}

	$table = new APP_Order_Summary_Table( $order );
	ob_start();
	$table->show();
	$table_output = ob_get_clean();

	$content = '';
	$content .= html( 'p', sprintf( __( 'Hello %s,', APP_TD ), $recipient->display_name ) );
	$content .= html( 'p', __( 'This email confirms that you have purchased the following items:', APP_TD ) );
	$content .= $items_html;
	$content .= html( 'p', __( 'Order Summary:', APP_TD ) );
	$content .= $table_output;

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject = sprintf( __( '[%1$s] Receipt for your order #%2$d', APP_TD ), $blogname, $order->get_id() );

	$email = array( 'to' => $recipient->user_email, 'subject' => $subject, 'message' => $content );
	$email = apply_filters( 'cp_email_user_receipt', $email, $order );

	appthemes_send_email( $email['to'], $email['subject'], $email['message'] );
}


/**
 * Sends email with receipt to admin after completed purchase.
 *
 * @param object $order
 *
 * @return void
 */
function cp_send_admin_receipt( $order ) {
	global $cp_options;

	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	// allow overriding admin email receipts
	if ( ! apply_filters( 'cp_send_admin_receipt', true, $order ) ) {
		return;
	}

	if ( 0 == $order->get_total() && ! apply_filters( 'cp_send_zero_sum_order_emails', false ) ) {
		return;
	}

	$moderation = $cp_options->moderate_ads;

	$items_html = '';
	$unique_items  = array();
	foreach ( $order->get_items() as $item ) {
		$ptype_obj = get_post_type_object( $item['post']->post_type );
		if ( ! $ptype_obj->public || isset( $unique_items[ $item['post']->ID ] )  ) {
			continue;
		}

		$unique_items[ $item['post']->ID ] = $item['post']->ID;

		if ( $order->get_id() != $item['post']->ID ) {
			$items_html .= html( 'p', html_link( get_permalink( $item['post']->ID ), $item['post']->post_title ) );
		} else {
			$items_html .= html( 'p', APP_Item_Registry::get_title( $item['type'] ) );
		}
	}

	$table = new APP_Order_Summary_Table( $order );
	ob_start();
	$table->show();
	$table_output = ob_get_clean();

	$content = '';
	$content .= html( 'p', __( 'Dear Admin,', APP_TD ) );
	$content .= html( 'p', __( 'You have received payment for the following items:', APP_TD ) );
	$content .= $items_html;
	if ( $moderation && _cp_get_order_ad_id( $order ) ) {
		$content .= html( 'p', __( 'Please review submitted ad listing, and approve it.', APP_TD ) );
	}
	$content .= html( 'p', __( 'Order Summary:', APP_TD ) );
	$content .= $table_output;

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject = sprintf( __( '[%1$s] Received payment for order #%2$d', APP_TD ), $blogname, $order->get_id() );

	$email = array( 'to' => get_option( 'admin_email' ), 'subject' => $subject, 'message' => $content );
	$email = apply_filters( 'cp_email_admin_receipt', $email, $order );

	appthemes_send_email( $email['to'], $email['subject'], $email['message'] );
}


/**
 * Sends email notification to admin if payment failed.
 *
 * @param object $order
 *
 * @return void
 */
function cp_send_admin_failed_transaction( $order ) {

	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject = sprintf( __( '[%1$s] Failed Order #%2$d', APP_TD ), $blogname, $order->get_id() );

	$content = '';
	$content .= html( 'p', sprintf( __( 'Payment for the order #%s has failed.', APP_TD ), $order->get_id() ) );
	$content .= html( 'p', sprintf( __( 'Please <a href="%s">review this order</a>, and if necessary disable assigned services.', APP_TD ), get_edit_post_link( $order->get_id() ) ) );

	$email = array( 'to' => get_option( 'admin_email' ), 'subject' => $subject, 'message' => $content );
	$email = apply_filters( 'cp_email_admin_transaction_failed', $email, $order );

	appthemes_send_email( $email['to'], $email['subject'], $email['message'] );
}


/**
 * Sends email notification to admin if listing require moderation.
 *
 * @param object $post
 *
 * @return void
 */
function cp_notify_admin_moderated_listing( $post ) {
	global $cp_options;

	if ( $post->post_type != APP_POST_TYPE ) {
		return;
	}

	if ( ! $cp_options->ad_edit || ! $cp_options->moderate_edited_ads ) {
		return;
	}

	// don't notify admin when he changing post status in wp-admin
	if ( is_admin() && current_user_can( 'manage_options' ) ) {
		return;
	}

	$blogname = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject = sprintf( __( '[%s] Ad listing awaiting your review', APP_TD ), $blogname );

	$content  = html( 'p', __( 'Dear Admin,', APP_TD ) ) . PHP_EOL;
	$content .= html( 'p', sprintf( __( 'Ad listing, "%s" has been edited and awaiting your review.', APP_TD ), $post->post_title ) ) . PHP_EOL;
	$content .= html( 'p', sprintf( __( 'Please <a href="%s">review this listing</a>, and approve it.', APP_TD ), get_permalink( $post->ID ) ) ) . PHP_EOL;

	$email = array( 'to' => get_option( 'admin_email' ), 'subject' => $subject, 'message' => $content );
	$email = apply_filters( 'cp_email_admin_moderated_listing', $email, $post );

	appthemes_send_email( $email['to'], $email['subject'], $email['message'] );
}
