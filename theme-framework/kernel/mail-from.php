<?php
/**
 * Modify WP email sender name and email address,
 * Defaults to admin email and blog name.
 *
 * @package ThemeFramework\Mail-From
 */

class APP_Mail_From {

	private static $args = array();
	private static $tmp = array();
	private static $blacklisted_email = false;

	/**
	 * Initialize custom email headers
	 *
	 * @param array $args
	 * - 'email' an sender email address
	 * - 'name' an sender name
	 * - 'reply' should the 'Reply-To' header be sent
	 */
	static function init( $args = array() ) {

		self::$args = wp_parse_args( $args, self::get_defaults() );
		self::add();
		add_action( 'admin_notices', array( __CLASS__, 'blacklisted_email_notice' ) );
	}

	/**
	 * Changes custom email header, applies just once
	 *
	 * @param array $args
	 * - 'email' an sender email address
	 * - 'name' an sender name
	 * - 'reply' should the 'Reply-To' header be sent
	 */
	static function apply_once( $args = array() ) {
		self::$tmp = ( ! empty( self::$args ) ) ? self::$args : false;
		self::init( $args );
		add_action( 'phpmailer_init', array( __CLASS__, '_reset' ) );
	}

	/**
	 * Disables custom email header, applies just once
	 */
	static function disable_once() {
		self::remove();
		add_action( 'phpmailer_init', array( __CLASS__, '_reset' ) );
	}

	/**
	 * Adds filters on wp_mail()
	 */
	static function add() {
		if ( ! self::_is_valid() ) {
			return;
		}

		add_filter( 'wp_mail', array( __CLASS__, 'check_headers' ) );
		add_filter( 'wp_mail', array( __CLASS__, 'mail_reply' ) );
		add_filter( 'wp_mail_from', array( __CLASS__, 'mail_from' ) );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'mail_from_name' ) );
	}

	/**
	 * Removes filters from wp_mail()
	 */
	static function remove() {
		remove_filter( 'wp_mail', array( __CLASS__, 'check_headers' ) );
		remove_filter( 'wp_mail', array( __CLASS__, 'mail_reply' ) );
		remove_filter( 'wp_mail_from', array( __CLASS__, 'mail_from' ) );
		remove_filter( 'wp_mail_from_name', array( __CLASS__, 'mail_from_name' ) );
	}

	/**
	 * Reverts headers to previous state after use of apply_once() & disable_once()
	 */
	static function _reset() {
		if ( ! empty( self::$tmp ) ) {
			self::$args = self::$tmp;
			self::$tmp = array();
		} elseif ( self::$tmp === false ) {
			self::$args = array();
			self::$tmp = array();
			self::remove();
		} else {
			self::add();
		}
		remove_action( 'phpmailer_init', array( __CLASS__, '_reset' ) );
	}

	/**
	 * Checks if args are valid and exists
	 *
	 * @return bool
	 */
	static function _is_valid() {
		$args = array( 'name', 'email', 'reply' );
		foreach ( $args as $arg ) {
			if ( ! isset( self::$args[ $arg ] ) ) {
				return false;
			}
		}

		if ( ! is_email( self::$args['email'] ) ) {
			return false;
		}

		// check if email provider is not blacklisted, if so, use default WP email address
		if ( self::is_on_blacklist( self::$args['email'] ) ) {
			self::$blacklisted_email = self::$args['email'];
			self::$args['email'] = self::get_default_email();
		}

		if ( empty( self::$args['name'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks and disables class functionality if custom headers are passed to wp_mail() without default email address, applies on 'wp_mail' hook
	 *
	 * @param array $mail
	 *
	 * @return array
	 */
	static function check_headers( $mail ) {
		if ( self::_is_valid() && ! empty( $mail['headers'] ) ) {
			$headers = is_array( $mail['headers'] ) ? implode( "\n", $mail['headers'] ) : $mail['headers'];

			if ( stripos( $headers, self::get_wordpress_default_email() ) === false ) {
				self::disable_once();
			}
		}

		return $mail;
	}

	/**
	 * Adds 'Reply-To' header, applies on 'wp_mail' hook
	 *
	 * @param array $mail
	 *
	 * @return array
	 */
	static function mail_reply( $mail ) {
		if ( ! self::_is_valid() ) {
			return $mail;
		}

		if ( ! self::$args['reply'] ) {
			return $mail;
		}

		$replyto = sprintf( "Reply-To: %s <%s> \r\n", self::$args['name'], self::$args['email'] );
		if ( is_array( $mail['headers'] ) ) {
			$mail['headers'][] = $replyto;
		} else {
			$mail['headers'] .= $replyto;
		}

		return $mail;
	}

	/**
	 * Returns sender email, applies on 'wp_mail_from' hook
	 *
	 * @param string $from_email
	 *
	 * @return string
	 */
	static function mail_from( $from_email ) {
		if ( self::_is_valid() ) {
			return self::$args['email'];
		}

		return $from_email;
	}

	/**
	 * Returns sender name, applies on 'wp_mail_from_name' hook
	 *
	 * @param string $from_name
	 *
	 * @return string
	 */
	static function mail_from_name( $from_name ) {
		if ( self::_is_valid() ) {
			return self::$args['name'];
		}

		return $from_name;
	}

	/**
	 * Checks if given email address is on blacklist. Some email providers do not allow to use their addresses in the From field.
	 *
	 * @uses apply_filters() Calls 'appthemes_mail_from_blacklist' hook
	 *
	 * @return bool
	 */
	static function is_on_blacklist( $email ) {
		$exclude_domains = array(
			'yahoo.com',
			'aol.com',
		);
		$exclude_domains = apply_filters( 'appthemes_mail_from_blacklist', $exclude_domains );

		foreach ( $exclude_domains as $domain ) {
			if ( stripos( $email, $domain ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns default email address
	 *
	 * @uses apply_filters() Calls 'appthemes_mail_from_default_email' hook
	 *
	 * @return string
	 */
	static function get_default_email() {
		$domain = self::get_site_domain();
		$email = "noreply@$domain";

		return apply_filters( 'appthemes_mail_from_default_email', $email );
	}

	/**
	 * Returns WordPress default email address
	 *
	 * @return string
	 */
	static function get_wordpress_default_email() {
		$domain = self::get_site_domain();
		$email = "wordpress@$domain";

		return $email;
	}

	/**
	 * Returns site domain
	 *
	 * @return string
	 */
	static function get_site_domain() {
		// Strip 'www.' from URL
		$domain = preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );

		return $domain;
	}

	/**
	 * Returns defaults
	 *
	 * @uses apply_filters() Calls 'appthemes_mail_from_defaults' hook
	 *
	 * @return array
	 */
	static function get_defaults() {
		$defaults = array(
			'email' => get_option( 'admin_email' ),
			'name' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'reply' => false,
		);
		return apply_filters( 'appthemes_mail_from_defaults', $defaults );
	}

	/**
	 * Displays admin notice about blacklisted email on General Settings page
	 *
	 * @return void
	 */
	public static function blacklisted_email_notice() {
		global $pagenow;

		if ( $pagenow != 'options-general.php' || ! self::$blacklisted_email || self::$blacklisted_email != get_option( 'admin_email' ) ) {
			return;
		}

		$notice = sprintf( __( 'Email provider of "%1$s" address do not allow to use their email addresses by other servers. The "%2$s" address will be used to send out emails.', APP_TD ), self::$blacklisted_email, self::get_default_email() );
		echo scb_admin_notice( $notice, 'error' );
	}

}

