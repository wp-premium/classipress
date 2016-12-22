<?php
/**
 * Report email notifications
 *
 * @package Components\Reports
 */
class APP_Report_Comments_Email_Notify {

	/**
	 * Comment Type, the custom comment type to use
	 * @var string
	 */
	private static $comment_type = '';


	/**
	 * Sets up the extended comments class
	 *
	 * @param string comment_type	The custom comment type
	 *
	 * @return void
	 */
	public static function init( $comment_type ) {

		if ( ! $comment_type ) {
			trigger_error( 'No custom comment type defined.', E_USER_WARNING );
		}

		self::$comment_type = $comment_type;

		add_filter( 'comment_notification_recipients', array( __CLASS__, 'notify_email_recipients' ), 999, 2 );
		add_filter( 'comment_moderation_recipients', array( __CLASS__, 'notify_email_recipients' ), 999, 2 );

		add_filter( 'comment_notification_headers', array( __CLASS__, 'notify_email_headers' ), 999, 2 );
		add_filter( 'comment_moderation_headers', array( __CLASS__, 'notify_email_headers' ), 999, 2 );

		add_filter( 'comment_notification_subject', array( __CLASS__, 'notify_email_subject' ), 999, 2 );
		add_filter( 'comment_moderation_subject', array( __CLASS__, 'notify_email_subject' ), 999, 2 );

		add_filter( 'comment_notification_text', array( __CLASS__, 'notify_email_text' ), 999, 2 );
		add_filter( 'comment_moderation_text', array( __CLASS__, 'notify_email_text' ), 999, 2 );
	}


	/**
	 * Modify the new comment recipients emails
	 *
	 * @uses apply_filters() Calls 'appthemes_report_notification_recipients'
	 *
	 * @param array $emails
	 * @param int $comment_id
	 *
	 * @return array
	 */
	public static function notify_email_recipients( $emails, $comment_id ) {

		$comment = get_comment( $comment_id );

		if ( ! $comment || $comment->comment_type != self::$comment_type ) {
			return $emails;
		}

		// send only to admin
		$emails = array( get_option( 'admin_email' ) );

		return apply_filters( 'appthemes_report_notification_recipients', $emails, $comment_id );
	}


	/**
	 * Modify the new comment headers
	 *
	 * @uses apply_filters() Calls 'appthemes_report_notification_headers'
	 *
	 * @param string $headers
	 * @param int $comment_id
	 *
	 * @return string
	 */
	public static function notify_email_headers( $headers, $comment_id ) {

		$comment = get_comment( $comment_id );

		if ( ! $comment || $comment->comment_type != self::$comment_type ) {
			return $headers;
		}

		// change Content-Type to html
		$headers = str_replace( 'text/plain', 'text/html', $headers );

		return apply_filters( 'appthemes_report_notification_headers', $headers, $comment_id );
	}


	/**
	 * Modify the new comment author email subject
	 *
	 * @uses apply_filters() Calls 'appthemes_report_notification_subject'
	 *
	 * @param string $subject
	 * @param int $comment_id
	 *
	 * @return string
	 */
	public static function notify_email_subject( $subject, $comment_id ) {

		$comment = get_comment( $comment_id );

		if ( ! $comment || $comment->comment_type != self::$comment_type ) {
			return $subject;
		}

		$post_title = get_the_title( $comment->comment_post_ID );
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$subject = sprintf( __( '[%1$s] New report on "%2$s"', APP_TD ), $blogname, $post_title );

		return apply_filters( 'appthemes_report_notification_subject', $subject, $comment_id );
	}


	/**
	 * Modify the new comment author email text
	 *
	 * @uses apply_filters() Calls 'appthemes_report_notification_text'
	 *
	 * @param string $notify_message
	 * @param int $comment_id
	 *
	 * @return string
	 */
	public static function notify_email_text( $notify_message, $comment_id ) {

		$comment = get_comment( $comment_id );

		if ( ! $comment || $comment->comment_type != self::$comment_type ) {
			return $notify_message;
		}

		$post = get_post( $comment->comment_post_ID );
		if ( ! $post ) {
			return $notify_message;
		}

		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$report = appthemes_get_report( $comment->comment_ID );

		$author = get_comment_author( $comment->comment_ID );

		$notify_message = html( 'p', __( 'Dear Admin,', APP_TD ) ) . PHP_EOL;
		$notify_message .= html( 'p', sprintf( __( 'A new report has been submitted on "%1$s" by %2$s.', APP_TD ), $post->post_title, $author ) ) . PHP_EOL;
		$notify_message .= html( 'p', sprintf( __( 'Reported as: %s', APP_TD ), $comment->comment_content ) ) . PHP_EOL;
		$notify_message .= html( 'p', sprintf( __( 'Edit post: %s', APP_TD ), appthemes_get_edit_post_url( $post->ID, '' ) ) ) . PHP_EOL;

		if ( EMPTY_TRASH_DAYS ) {
			$notify_message .= html( 'p', sprintf( __( 'Trash it: %s', APP_TD ), admin_url( "comment.php?action=trash&c=$comment_id" ) ) ) . PHP_EOL;
		} else {
			$notify_message .= html( 'p', sprintf( __( 'Delete it: %s', APP_TD ), admin_url( "comment.php?action=delete&c=$comment_id" ) ) ) . PHP_EOL;
		}

		$notify_message .= html( 'p', __( 'You will not receive further notification for this post until report has been deleted. However all future reports will be logged and can be viewed on each edit post page.', APP_TD ) ) . PHP_EOL;
		$notify_message .= html( 'p', __( 'Regards,', APP_TD ) . '<br />' . sprintf( __( 'Your %s Team', APP_TD ), $blogname ) ) . PHP_EOL;

		return apply_filters( 'appthemes_report_notification_text', $notify_message, $comment_id );
	}


	/**
	 * Sends notification to admin
	 *
	 * @param object $report
	 *
	 * @return void
	 */
	public static function notify_admin( $report ) {
		$options = appthemes_load_reports_options();
		if ( ! $options->get( array( 'reports', 'send_email' ) ) ) {
			return;
		}

		// notify only once per post about report
		$reports = appthemes_get_post_reports( $report->get_post_ID() );
		if ( count( $reports->reports ) > 1 ) {
			return;
		}

		$emails = apply_filters( 'comment_notification_recipients', array(), $report->get_id() );
		$subject = apply_filters( 'comment_notification_subject', '', $report->get_id() );
		$notify_message = apply_filters( 'comment_notification_text', '', $report->get_id() );

		foreach ( $emails as $email ) {
			appthemes_send_email( $email, $subject, $notify_message );
		}
	}

}

