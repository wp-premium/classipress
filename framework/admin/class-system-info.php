<?php
/**
 * System Info admin page
 *
 * @package Framework\Settings
 */

/**
 * System Info admin page
 */
class APP_System_Info extends APP_Tabs_Page {

	/**
	 * Constructor
	 */
	public function __construct( $args = array(), $options = null ) {

		if ( ! is_a( $options, 'scbOptions' ) ) {
			$options = new scbOptions( 'app_system_info', false );
		}

		$this->textdomain = APP_TD;

		$this->args = wp_parse_args( $args, array(
			'page_title'            => __( 'System Info', APP_TD ),
			'menu_title'            => __( 'System Info', APP_TD ),
			'page_slug'             => 'app-system-info',
			'parent'                => 'app-dashboard',
			'screen_icon'           => 'options-general',
			'admin_action_priority' => 11,
		) );

		// Disables localization for downloading report, so it's always in english.
		add_filter( 'gettext', array( $this, 'disable_report_localization' ), 99, 3 );

		parent::__construct( $options );
	}

	/**
	 * Disable report localization.
	 */
	function disable_report_localization( $translated_text, $text, $domain ) {

		if ( empty( $_POST['action'] ) || empty( $_POST['download_system_info'] ) ) {
			return $translated_text;
		}

		return $text;
	}

	/**
	 * Check security before downloading txt export.
	 */
	function form_handler() {

		if ( empty( $_POST['action'] ) || ! $this->tabs->contains( $_POST['action'] ) ) {
			return;
		}

		check_admin_referer( $this->nonce );

		if ( ! empty( $_POST['download_system_info'] ) ) {
			$this->download_system_info();
		} else {
			parent::form_handler();
		}
	}

	/**
	 * Download a txt export of all system info settings.
	 */
	function download_system_info() {

		$tab      = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : 'info';
		$filename = 'app-system-' . $tab . '-' . date( 'Y-m-d' ) . '.txt';

		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ), true );

		$form_fields = array();

		foreach ( $this->tab_sections[ $_POST['action'] ] as $section ) {

			echo "\r\n### " . $section['title'] . " ###\r\n";

			foreach ( $section['fields'] as $field ) {

				// Replace HTML unicode with english.
				$field['desc'] = str_replace( array( '&ndash;', '&#10004;' ), array( __( 'No', APP_TD ), __( 'Yes', APP_TD ) ), $field['desc'] );

				echo $field['title'] . "\t" . strip_tags( trim( $field['desc'] ) ) . "\r\n";
			}
		}
		die();
	}

	/**
	 * Initialize the page tabs.
	 */
	protected function init_tabs() {
		global $wpdb;

		$this->tabs->add( 'info', __( 'System Info', APP_TD ) );
		$this->tabs->add( 'cron', __( 'Cron Jobs', APP_TD ) );


		$current_theme = wp_get_theme();

		// Get the parent theme info if child theme is active.
		if ( is_child_theme() ) {
			$parent_theme = wp_get_theme( $current_theme->Template );
		}


		$this->tab_sections['info']['theme'] = array(
			'title'  => __( 'Theme Info', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Name', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'theme_name' ),
					'tip'   => __( 'The name of the current active theme.', APP_TD ),
					'desc'  => $current_theme->Name,
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'Version', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'theme_version' ),
					'tip'   => __( 'The version of the current active theme.', APP_TD ),
					'desc'  => $current_theme->Version,
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'Author URL', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'author_url' ),
					'tip'   => __( 'The theme developers url.', APP_TD ),
					'desc'  => $current_theme->{'Author URI'},
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'Theme Path', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'theme_path' ),
					'tip'   => __( 'The path of the current active theme.', APP_TD ),
					'desc'  => get_stylesheet_directory_uri(),
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'Child Theme', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'child_theme' ),
					'tip'   => __( 'Check if a child theme is active.', APP_TD ),
					'desc'  => ( is_child_theme() ) ? '&#10004; ' . sprintf( __( 'Using parent theme %s v%s', APP_TD ), $parent_theme->Name, $parent_theme->Version ) : '&ndash;',
					'extra' => array(
						'style' => 'display: none;',
					),
				),
			),
		);


		$this->tab_sections['info']['wp'] = array(
			'title'  => __( 'WordPress Info', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Home URL', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'home_url' ),
					'tip'   => __( "The url of your website's homepage.", APP_TD ),
					'desc'  => home_url(),
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'Site URL', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'site_url' ),
					'tip'   => __( 'The url of your WordPress install.', APP_TD ),
					'desc'  => site_url(),
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'WP Version', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'wp_version' ),
					'tip'   => __( 'The version of WordPress installed.', APP_TD ),
					'desc'  => get_bloginfo( 'version' ),
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'WP Multisite', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'wp_multisite' ),
					'tip'   => __( 'Check to see if WordPress Multisite is enabled.', APP_TD ),
					'desc'  => ( is_multisite() ) ? '&#10004;' : '&ndash;',
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'WP Memory Limit', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'wp_memory_limit' ),
					'tip'   => __( 'The maximum amount of memory (RAM) that your site can use.', APP_TD ),
					'desc'  => size_format( wp_convert_hr_to_bytes( WP_MEMORY_LIMIT ) ),
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'WP Remote Post', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'wp_remote_post' ),
					'tip'   => __( 'Checks to see if your hosting server is able to post information to another server. PayPal IPN uses this method of communication when sending back transaction information.', APP_TD ),
					'desc'  => $this->test_wp_remote_post(),
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'WP Debug Mode', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'wp_debug_mode' ),
					'tip'   => __( 'Check to see if WordPress debug mode is enabled.', APP_TD ),
					'desc'  => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '&#10004;' : '&ndash;',
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'Force SSL Admin', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'force_ssl_admin' ),
					'tip'   => __( 'Check to see if your site forces SSL (Secure Sockets Layer) also known as TLS (Transport Layer Security).', APP_TD ),
					'desc'  => ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) ? '&#10004;' : '&ndash;',
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'Language', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'wp_locale' ),
					'tip'   => __( 'The current language used by WordPress. The default value is en_US (English).', APP_TD ),
					'desc'  => get_locale(),
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'Language File Path', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'language_file_path' ),
					'tip'   => sprintf( __( 'Where to put your .mo language file for this theme (e.g. %1$s-%2$s.mo). The wp-admin "Site Language" drop-down must also be set to your locale.', APP_TD ), APP_TD, get_locale() ),
					'desc'  => WP_LANG_DIR . '/themes/',
					'extra' => array(
						'style' => 'display: none;',
					),
				),
			),
		);


		$this->tab_sections['info']['server'] = array(
			'title'  => __( 'Server Info', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Server Software', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'server_software' ),
					'tip'   => __( 'The web server your hosting server is running.', APP_TD ),
					'desc'  => $_SERVER['SERVER_SOFTWARE'],
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'PHP Version', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'php_version' ),
					'tip'   => __( 'The version of PHP installed on your hosting server. We recommend a minimum PHP version of 5.4.', APP_TD ),
					'desc'  => ( function_exists( 'phpversion' ) ) ? phpversion() : __( 'Function phpversion() is not available.', APP_TD ),
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'PHP Post Max Size', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'post_max_size' ),
					'tip'   => __( 'The largest file size that can be contained in one $_POST.', APP_TD ),
					'desc'  => ( function_exists( 'ini_get' ) ) ? size_format( wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) ) ) : __( 'Function ini_get() is not available.', APP_TD ),
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'PHP Time Limit', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'max_execution_time' ),
					'tip'   => __( 'The amount of time (in seconds) your server will spend on a task before timing out. Set this higher to avoid timeout issues.', APP_TD ),
					'desc'  => ( function_exists( 'ini_get' ) ) ? ini_get( 'max_execution_time' ) : __( 'Function ini_get() is not available.', APP_TD ),
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'PHP Max Input Vars', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'max_input_vars' ),
					'tip'   => __( 'The maximum number of variables your server can use for a single function to avoid overloads.', APP_TD ),
					'desc'  => ( function_exists( 'ini_get' ) ) ? number_format_i18n( ini_get( 'max_input_vars' ) ) : __( 'Function ini_get() is not available.', APP_TD ),
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'SUHOSIN Installed', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'suhosin' ),
					'tip'   => __( 'Suhosin is an advanced protection system for PHP installations. Your host provider typically configures this.', APP_TD ),
					'desc'  => ( extension_loaded( 'suhosin' ) ) ? '&#10004;' : '&ndash;',
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'MySQL Version', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'mysql_version' ),
					'tip'   => __( 'The version of MySQL installed on your hosting server. We recommend a minimum mySQL version of 5.5.', APP_TD ),
					'desc'  => $wpdb->db_version(),
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'Max Upload Size', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'max_upload_size' ),
					'tip'   => __( 'The largest file size that can be uploaded to your WordPress installation. If you get timeout errors when uploading a large file, this should be increased.', APP_TD ),
					'desc'  => size_format( wp_max_upload_size() ),
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'Display Errors', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'display_errors' ),
					'tip'   => __( 'Checks if your hosting server has PHP errors turned on. If so, they will display on the screen. This should be turned off in a production environment.', APP_TD ),
					'desc'  => ( function_exists( 'ini_get' ) ) ? ( ini_get( 'display_errors' ) ? '&#10004;' : '&ndash;' ) : '<span class="notice-error">' . __( 'Function ini_get() is not available.', APP_TD ) . '</span>',
					'extra' => array(
						'style' => 'display: none;',
					),
				),
			),
		);


		$this->tab_sections['info']['image'] = array(
			'title'  => __( 'Image Support', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'GD Library', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'gd_library' ),
					'tip'   => __( 'Checks to see if your hosting server has the GD Library installed which allows WordPress to dynamically manipulate images.', APP_TD ),
					'desc'  => ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) ) ? '&#10004;' : '&ndash;',
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'Image Upload Path', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'wp_upload_path' ),
					'tip'   => __( 'The WordPress image upload path on your hosting server.', APP_TD ),
					'desc'  => ( $uploads = wp_upload_dir() ) ? $uploads['url'] : '',
					'extra' => array(
						'style' => 'display: none;',
					),
				),
			),
		);


		$this->tab_sections['info']['other'] = array(
			'title'  => __( 'Other Checks', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'JSON Decode', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'json_decode' ),
					'tip'   => __( 'Checks to see if the JSON Decode function is enabled on your hosting server.', APP_TD ),
					'desc'  => ( function_exists( 'json_decode' ) ) ? '&#10004;' : '&ndash;',
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'cURL', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'curl' ),
					'tip'   => __( 'Checks to see if the CURL function is enabled on your hosting server. Payment gateways and sometimes other plugins usually required this in order to work properly.', APP_TD ),
					'desc'  => ( function_exists( 'curl_init' ) ) ? '&#10004;' : '&ndash;',
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'fsockopen', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'fsockopen' ),
					'tip'   => __( 'Checks to see if the fsockopen function is enabled on your hosting server. Payment gateways and sometimes other plugins usually required this in order to work properly.', APP_TD ),
					'desc'  => ( function_exists( 'fsockopen' ) ) ? '&#10004;' : '&ndash;',
					'extra' => array(
						'style' => 'display: none;',
					),
				),
				array(
					'title' => __( 'OpenSSL', APP_TD ),
					'type'  => 'text',
					'name'  => array( 'system_info', 'openssl_open' ),
					'tip'   => __( 'Checks to see if the OpenSSL function is enabled on your hosting server. This is sometimes required by plugins but typically not for payment gateways.', APP_TD ),
					'desc'  => ( function_exists( 'openssl_open' ) ) ? '&#10004;' : '&ndash;',
					'extra' => array(
						'style' => 'display: none;',
					),
				),
			),
		);


		$this->tab_sections['info']['plugins'] = array(
			'title'  => __( 'Active Plugins', APP_TD ) . ' (' . number_format_i18n( count( (array) get_option( 'active_plugins' ) ) ) . ')',
			'fields' => $this->get_installed_plugins(),
		);


		$this->tab_sections['cron']['info'] = array(
			'title'    => __( 'Cron Jobs', APP_TD ),
			'fields'   => $this->cronjob_fields(),
			'renderer' => array( $this, 'render_cronjob_fields' ),
		);

	}

	/**
	 * Stuff to put in the page footer.
	 */
	function page_footer() {
		parent::page_footer();
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	if ( $("form input[name^='system_info']").length ) {
		$('form input[type=submit]').val('<?php esc_attr_e( 'Download Report', APP_TD ); ?>');
		$('form p.submit').append('<input type="hidden" name="download_system_info" value="1" />');
	}
});
</script>
<?php
	}

	/**
	 * Test to see if wp_remote_post works against PayPal.
	 */
	private function test_wp_remote_post() {

		$url = 'https://www.paypal.com/cgi-bin/webscr';

		$params = array(
			'timeout'    => 30,
			'user-agent' => 'WordPress/' . get_bloginfo( 'version' ),
			'sslverify'  => false,
			'body'       => array(
				'cmd' => '_notify-validate',
			),
		);

		// Connect to PayPal.
		$response = wp_safe_remote_post( $url, $params );

		// Success.
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			return '&#10004;';

		} else {

			$output = '<span class="text-error">' . __( "wp_remote_post() failed. PayPal IPN won't work with your server at this time. Contact your host provider.", APP_TD ) . '</span>';

			// Include an error message or status code if provided.
			if ( is_wp_error( $response ) ) {
				$output .= '<br>' . sprintf( __( 'Error: %s', APP_TD ), $response->get_error_message() );
			} else {
				$output .= '<br>' . sprintf( __( 'Status code: %s', APP_TD ), $response['response']['code'] );
			}

			return $output;
		}
	}

	/**
	 * Get all installed plugins so we can display them.
	 */
	private function get_installed_plugins() {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		$plugin_data = array();

		// Put all the plugin fields into an array.
		foreach ( $active_plugins as $active_plugin ) {
			$plugin_data[] = @get_plugin_data( WP_PLUGIN_DIR . '/' . $active_plugin, false, false );
		}

		$installed_plugins = array();

		// Setup the nested array with all the fields we require.
		foreach ( $plugin_data as $plugin ) {

			$name    = ! empty( $plugin['Name'] ) ? $plugin['Name'] : '&ndash;';
			$desc    = ! empty( $plugin['Description'] ) ? $plugin['Description'] : '';
			$version = ! empty( $plugin['Version'] ) ? __( 'Version', APP_TD ) . ' ' . $plugin['Version'] : '';
			$author  = ! empty( $plugin['Author'] ) ? ' | ' . __( 'By', APP_TD ) . ' ' . strip_tags( $plugin['Author'] ) : '';
			$url     = ! empty( $plugin['PluginURI'] ) ? ' | <a href="' . $plugin['PluginURI'] . '" target="_blank">' . __( 'Plugin site', APP_TD ) . '</a>' : '';

			$output = array(
				'title' => $name,
				'type'  => 'text',
				'name'  => array( 'system_info', sanitize_title_with_dashes( $name ) ),
				'tip'   => $desc,
				'desc'  => $version . $author . $url,
				'extra' => array(
					'style' => 'display: none;',
				),
			);

			array_push( $installed_plugins, $output );

		}

		return $installed_plugins;

	}

	/**
	 * Loop through and grab all cron jobs.
	 */
	function cronjob_fields() {

		$options        = array();
		$seconds_offset = get_option( 'gmt_offset' ) * 3600;
		$cron           = _get_cron_array();
		$schedules      = wp_get_schedules();

		foreach ( $cron as $timestamp => $cronhooks ) {
			foreach ( (array) $cronhooks as $hook => $events ) {
				foreach ( (array) $events as $key => $event ) {
					$date = date_i18n( 'Y-m-d G:i', $timestamp + $seconds_offset );
					$frequency = ( empty( $event['schedule'] ) ) ? __( 'One-off event', APP_TD ) : $schedules [ $event['schedule'] ]['display'];

					$options[] = array(
						'title' => $hook,
						'type'  => 'text',
						'name'  => array( 'system_info', 'cron', $hook ),
						'desc'  => $date,
						'tip'   => $frequency,
						'extra' => array(
							'style' => 'display: none;',
						),
					);

				}
			}
		}

		return $options;
	}

	/**
	 * Build the cron jobs fields html output.
	 */
	function render_cronjob_fields( $section, $section_id ) {
		$output = '';

		if ( empty( $section['fields'] ) ) {
			$output = __( 'You haven&#39;t created any cron job tasks yet.', APP_TD );
			echo $this->table_wrap( $output );
			return;
		}

		$output .= html( 'tr',
			html( 'th', html( 'strong', __( 'Hook Name', APP_TD ) ) ),
			html( 'th', html( 'strong', __( 'Frequency', APP_TD ) ) ),
			html( 'th', html( 'strong', __( 'Next Run Date', APP_TD ) ) )
		);

		$fields = $this->cronjob_fields();

		foreach ( $fields as $field ) {

			if ( isset( $field['desc'] ) ) {
				$field['desc'] = html( 'span class="description"', $field['desc'] );
			}

			$output .= html( 'tr',
				html( 'th scope="row"', $field['title'] ),
				html( 'td class="frequency"', html( 'span class="description"', $field['tip'] ) ),
				html( 'td', scbForms::input( $field, $this->options->get() ) )
			);
		}

		echo $this->table_wrap( $output );
	}


}
