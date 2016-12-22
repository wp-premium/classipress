<?php
/**
 * Cron job, scheduled tasks.
 *
 * @package ClassiPress\Cron
 * @author  AppThemes
 * @since   ClassiPress 3.0.4
 */

add_action( 'init', 'cp_schedule_membership_reminder' );
add_action( 'init', 'cp_schedule_expire_check' );

add_action( 'cp_ad_expired_check', 'cp_check_expired_cron' );
add_action( 'cp_send_membership_reminder', 'cp_membership_reminder_cron' );


### hook callbacks

/**
 * Schedules the expired ads check.
 *
 * @return void
 */
function cp_schedule_expire_check() {
	global $cp_options;

	$recurrance = $cp_options->ad_expired_check_recurrance;
	if ( empty( $recurrance ) ) {
		$recurrance = 'daily';
	}

	// clear schedule if prune ads disabled or recurrance set to none
	if ( ! $cp_options->post_prune || $recurrance == 'none' ) {
		if ( wp_next_scheduled( 'cp_ad_expired_check' ) ) {
			wp_clear_scheduled_hook( 'cp_ad_expired_check' );
		}
		return;
	}

	// set schedule if does not exist
	if ( ! wp_next_scheduled( 'cp_ad_expired_check' ) ) {
		wp_schedule_event( time(), $recurrance, 'cp_ad_expired_check' );
		return;
	}

	// re-schedule if settings changed
	$schedule = wp_get_schedule( 'cp_ad_expired_check' );
	if ( $schedule && $schedule != $recurrance ) {
		wp_clear_scheduled_hook( 'cp_ad_expired_check' );
		wp_schedule_event( time(), $recurrance, 'cp_ad_expired_check' );
	}

}


/**
 * Prunes expired ads from site, scheduled with WP Cron.
 *
 * @return void
 */
function cp_check_expired_cron() {
	global $wpdb, $cp_options;

	$message = '';
	$links_list = '';
	$subject = __( 'ClassiPress Ads Expired', APP_TD );

	if ( $cp_options->post_prune ) {

		// get expired ads
		$args = array(
			'post_type' => APP_POST_TYPE,
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => 'cp_sys_expire_date',
					'value' => current_time( 'mysql' ),
					'compare' => '<',
				),
			),
			'no_found_rows' => true,
		);
		$expired = new WP_Query( $args );

		if ( isset( $expired->posts ) && is_array( $expired->posts ) ) {
			foreach ( $expired->posts as $post_id ) {
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
				$links_list .= html( 'li', html_link( get_permalink( $post_id ) ) ) . PHP_EOL;
			}
		}

		$message .= html( 'p', __( 'Your cron job has run successfully. ', APP_TD ) );
		if ( empty( $links_list ) ) {
			$message .= html( 'p', __( 'No expired ads were found.', APP_TD ) );
		} else {
			$message .= html( 'p', __( 'The following ads expired and have been taken down from your website: ', APP_TD ) );
			$message .= html( 'ul', $links_list );
		}

	} else {
		$expired = false;
		$message .= html( 'p', __( 'Your cron job has run successfully. However, the pruning ads option is turned off so no expired ads were taken down from the website.', APP_TD ) );
	}

	$message .= html( 'p', __( 'Regards,', APP_TD ) );
	$message .= html( 'p', __( 'ClassiPress', APP_TD ) );

	if ( $cp_options->prune_ads_email ) {
		$email = array( 'to' => get_option( 'admin_email' ), 'subject' => $subject, 'message' => $message );
		$email = apply_filters( 'cp_email_admin_ads_expired', $email, $expired );

		appthemes_send_email( $email['to'], $email['subject'], $email['message'] );
	}

}


/**
 * Sends email reminder about ending membership plan, default is 7 days before expire.
 * Cron jobs execute the following function once per day.
 *
 * @return void
 */
function cp_membership_reminder_cron() {
	global $wpdb, $cp_options;

	if ( ! $cp_options->membership_ending_reminder_email ) {
		return;
	}

	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	$subject = sprintf( __( 'Membership Subscription Ending on %s', APP_TD ), $blogname );
	$siteurl = home_url( '/' );

	$days_before = $cp_options->membership_ending_reminder_days;
	$days_before = is_numeric( $days_before ) ? $days_before : 7;
	$timestamp = wp_next_scheduled( 'cp_send_membership_reminder' );
	$timestamp = $timestamp - ( 1 * 24 * 60 * 60 ) + ( get_option( 'gmt_offset' ) * 3600 ); // minus 1 day to get current schedule time, plus GMT offset
	$date_max = date( 'Y-m-d H:i:s', $timestamp + ( $days_before * 24 * 60 * 60 ) );
	$date_min = date( 'Y-m-d H:i:s', $timestamp + ( ( $days_before - 1 ) * 24 * 60 * 60 ) );

	$query_users = $wpdb->prepare( "SELECT $wpdb->users.ID FROM $wpdb->users
		LEFT JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id
		WHERE $wpdb->usermeta.meta_key = 'membership_expires'
		AND $wpdb->usermeta.meta_value < %s
		AND $wpdb->usermeta.meta_value > %s
		", $date_max, $date_min );

	$userids = $wpdb->get_col( $query_users );

	if ( ! $userids ) {
		return;
	}

	$users = array();

	foreach ( $userids as $user_id ) {
		$user = get_userdata( $user_id );
		$mailto = $user->user_email;
		$user_login = appthemes_clean( $user->user_login );

		$membership = cp_get_membership_package( $user->active_membership_pack );
		$membership_pack_name = appthemes_clean( $membership->pack_name );
		$membership_expires = appthemes_display_date( $user->membership_expires );

		$message  = html( 'p', sprintf( __( 'Hi %s,', APP_TD ), $user_login ) ) . PHP_EOL;
		$message .= html( 'p', sprintf( __( 'Your membership pack will expire in %d days! Please renew your membership to continue posting classified ads.', APP_TD ), $days_before ) ) . PHP_EOL;

		$message_details  = __( 'Membership Details', APP_TD ) . '<br />';
		$message_details .= __( '-----------------', APP_TD ) . '<br />';
		$message_details .= sprintf( __( 'Membership Pack: %s', APP_TD ), $membership_pack_name ) . '<br />';
		$message_details .= sprintf( __( 'Membership Expires: %s', APP_TD ), $membership_expires ) . '<br />';
		$message_details .= sprintf( __( 'Renew Your Membership Pack: %s', APP_TD ), html_link( CP_MEMBERSHIP_PURCHASE_URL ) ) . '<br />';

		$message .= html( 'p', $message_details ) . PHP_EOL;
		$message .= html( 'p', sprintf( __( 'For questions or problems, please contact us directly at %s', APP_TD ), get_option( 'admin_email' ) ) );
		$message .= html( 'p', __( 'Regards,', APP_TD ) . '<br />' . sprintf( __( 'Your %s Team', APP_TD ), $blogname ) );
		$message .= html( 'p', html_link( $siteurl ) );

		$email = array( 'to' => $mailto, 'subject' => $subject, 'message' => $message );
		$email = apply_filters( 'cp_email_user_membership_reminder', $email, $user_id );

		appthemes_send_email( $email['to'], $email['subject'], $email['message'] );

		$users[ $user_id ] = array(
			'user'       => html_link( sprintf( 'mailto:%s', $user->user_email ), $user->user_login ),
			'membership' => $membership->pack_name,
			'expires'    => $user->membership_expires,
		);

	}

	// allow overriding admin notifications
	if ( ! apply_filters( 'cp_admin_membership_reminder', true, $users ) ) {
		return;
	}

	### notify admin

	// loop through the users again to notify the admin about expiring memberships
	foreach( $users as $user_id => $data ) {
		 $items[] = $data;
	}

	if ( ! empty( $items ) ) {
		$table = new APP_Email_Table( $items );

		$admin_email = get_option('admin_email');

		$message  = html( 'p', __( 'Dear Admin,', APP_TD  ) ) . PHP_EOL;
		$message .= html( 'p', sprintf( __( 'Membership pack for these users expire in %d days! These users will need to renew their membership to continue posting classified ads on your site:', APP_TD ), $days_before ) ) . PHP_EOL;
		$message .= html( 'p', $table->display() ) . PHP_EOL;

		$email = array( 'to' => $admin_email, 'subject' => $subject, 'message' => $message );

		appthemes_send_email( $email['to'], $email['subject'], $email['message'] );
	}
}


/**
 * Schedules a daily event to send membership reminder emails.
 *
 * @return void
 */
function cp_schedule_membership_reminder() {
	if ( ! wp_next_scheduled( 'cp_send_membership_reminder' ) ) {
		wp_schedule_event( time(), 'daily', 'cp_send_membership_reminder' );
	}
}
