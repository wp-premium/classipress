<?php
/**
 * System Info admin page
 *
 * @package Framework\Settings
 */
class APP_System_Info extends APP_Tabs_Page {


	function __construct( $args = array(), $options = null ) {

		if ( ! is_a( $options, 'scbOptions' ) ) {
			$options = new scbOptions( 'app_system_info', false );
		}

		$this->textdomain = APP_TD;

		$this->args = wp_parse_args( $args, array(
			'page_title' => __( 'System Info', APP_TD ),
			'menu_title' => __( 'System Info', APP_TD ),
			'page_slug' => 'app-system-info',
			'parent' => 'app-dashboard',
			'screen_icon' => 'options-general',
			'admin_action_priority' => 11,
		) );

		// disables localization for downloading report, so it's always in english
		add_filter( 'gettext', array( $this, 'disable_report_localization' ), 99, 3 );

		parent::__construct( $options );
	}


	function disable_report_localization( $translated_text, $text, $domain ) {
		if ( empty( $_POST['action'] ) || empty( $_POST['download_system_info'] ) ) {
			return $translated_text;
		}

		return $text;
	}


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


	function download_system_info() {
		$tab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : 'info';
		$filename = 'app-system-' . $tab . '-' . date( 'Y-m-d' ) . '.txt';

		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ), true );

		$form_fields = array();

		foreach ( $this->tab_sections[ $_POST['action'] ] as $section ) {
			echo "\r\n### " . $section['title'] . " ###\r\n";
			foreach ( $section['fields'] as $field ) {
				$tip = ( empty( $field['tip'] ) ) ? '' : $field['tip'] . " \t";
				echo "\r\n" . $field['title'] . ": \t" . $tip . $field['desc'] . "\r\n";
			}
		}
		die();
	}


	protected function init_tabs() {
		global $wpdb;

		$this->tabs->add( 'info', __( 'System Info', APP_TD ) );
		$this->tabs->add( 'cron', __( 'Cron Jobs', APP_TD ) );


		$current_theme = wp_get_theme();
		if ( is_child_theme() ) {
			$current_theme = wp_get_theme( $current_theme->Template );
		}


		$this->tab_sections['info']['theme'] = array(
			'title' => __( 'Theme Info', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Theme Name', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'theme_name' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => $current_theme->Name,
				),
				array(
					'title' => __( 'Theme Version', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'theme_version' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => $current_theme->Version,
				),
			),
		);


		$this->tab_sections['info']['wp'] = array(
			'title' => __( 'WordPress Info', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Home URL', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'home_url' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => home_url(),
				),
				array(
					'title' => __( 'Site URL', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'site_url' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => site_url(),
				),
				array(
					'title' => __( 'Theme Path', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'theme_path' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => get_template_directory_uri(),
				),
				array(
					'title' => __( 'WP Version', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'wp_version' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( is_multisite() ) ? get_bloginfo( 'version' ) . ' - ' . __( 'Multisite', APP_TD ) : get_bloginfo( 'version' ),
				),
				array(
					'title' => __( 'WP Memory Limit', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'wp_memory_limit' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => size_format( wp_convert_hr_to_bytes( WP_MEMORY_LIMIT ) ),
				),
				array(
					'title' => __( 'WP Max Upload Size', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'wp_max_upload_size' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => size_format( wp_max_upload_size() ),
				),
				array(
					'title' => __( 'WP Debug Mode', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'wp_debug_mode' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? __( 'Yes', APP_TD ) : __( 'No', APP_TD ),
				),
				array(
					'title' => __( 'Force SSL Admin', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'force_ssl_admin' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) ? __( 'Yes', APP_TD ) : __( 'No', APP_TD ),
				),
				array(
					'title' => __( 'Child Theme', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'child_theme' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( is_child_theme() ) ? __( 'Yes', APP_TD ) : __( 'No', APP_TD ),
				),
			),
		);


		$this->tab_sections['info']['server'] = array(
			'title' => __( 'Server Info', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Server Software', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'server_software' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => $_SERVER['SERVER_SOFTWARE'],
				),
				array(
					'title' => __( 'PHP Version', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'php_version' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( function_exists( 'phpversion' ) ) ? phpversion() : __( 'Function phpversion() is not available.', APP_TD ),
				),
				array(
					'title' => __( 'MySQL Version', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'mysql_version' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => $wpdb->db_version(),
				),
				array(
					'title' => __( 'PHP Post Max Size', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'post_max_size' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( function_exists( 'ini_get' ) ) ? size_format( wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) ) ) : __( 'Function ini_get() is not available.', APP_TD ),
				),
				array(
					'title' => __( 'PHP Max Input Vars', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'max_input_vars' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( function_exists( 'ini_get' ) ) ? ini_get( 'max_input_vars' ) : __( 'Function ini_get() is not available.', APP_TD ),
				),
				array(
					'title' => __( 'PHP Time Limit', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'max_execution_time' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( function_exists( 'ini_get' ) ) ? ini_get( 'max_execution_time' ) : __( 'Function ini_get() is not available.', APP_TD ),
				),
				array(
					'title' => __( 'Upload Max Filesize', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'upload_max_filesize' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( function_exists( 'ini_get' ) ) ? size_format( wp_convert_hr_to_bytes( ini_get( 'upload_max_filesize' ) ) ) : __( 'Function ini_get() is not available.', APP_TD ),
				),
				array(
					'title' => __( 'Display Errors', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'display_errors' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( function_exists( 'ini_get' ) ) ? ( ini_get( 'display_errors' ) ? __( 'Yes', APP_TD ) : __( 'No', APP_TD ) ) : __( 'Function ini_get() is not available.', APP_TD ),
				),
				array(
					'title' => __( 'SUHOSIN Installed', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'suhosin' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( extension_loaded( 'suhosin' ) ) ? __( 'Yes', APP_TD ) : __( 'No', APP_TD ),
				),
			),
		);


		$this->tab_sections['info']['image'] = array(
			'title' => __( 'Image Support', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'GD Library Installed', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'gd_library' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) ) ? __( 'Yes', APP_TD ) : __( 'No', APP_TD ),
				),
				array(
					'title' => __( 'Image Upload Path', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'wp_upload_path' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( $uploads = wp_upload_dir() ) ? $uploads['url'] : '',
				),
			),
		);


		$this->tab_sections['info']['other'] = array(
			'title' => __( 'Other Checks', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'JSON Decode', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'json_decode' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( function_exists( 'json_decode' ) ) ? __( 'Yes', APP_TD ) : __( 'No', APP_TD ),
				),
				array(
					'title' => __( 'cURL Enabled', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'curl' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( function_exists( 'curl_init' ) ) ? __( 'Yes', APP_TD ) : __( 'No', APP_TD ),
				),
				array(
					'title' => __( 'fsockopen Enabled', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'fsockopen' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( function_exists( 'fsockopen' ) ) ? __( 'Yes', APP_TD ) : __( 'No', APP_TD ),
				),
				array(
					'title' => __( 'OpenSSL Enabled', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'openssl_open' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( function_exists( 'openssl_open' ) ) ? __( 'Yes', APP_TD ) : __( 'No', APP_TD ),
				),
				array(
					'title' => __( 'WP Remote Post', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'wp_remote_post' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => ( $this->test_wp_remote_post() ) ? __( 'wp_remote_post() test was successful.', APP_TD ) : __( 'wp_remote_post() test failed.', APP_TD ),
				),
			),
		);


		$this->tab_sections['info']['plugins'] = array(
			'title' => __( 'Plugins', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Installed Plugins', APP_TD ),
					'type' => 'text',
					'name' => array( 'system_info', 'installed_plugins' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => $this->get_installed_plugins(),
				),
			),
		);


		$this->tab_sections['cron']['info'] = array(
			'title' => __( 'Cron Jobs', APP_TD ),
			'fields' => $this->cronjob_fields(),
			'renderer' => array( $this, 'render_cronjob_fields' ),
		);


	}


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


	private function test_wp_remote_post() {
		$paypal_adr = 'https://www.paypal.com/cgi-bin/webscr';
		$request['cmd'] = '_notify-validate';
		$params = array(
			'timeout' => 10,
			'user-agent' => 'WordPress/' . get_bloginfo( 'version' ),
			'sslverify' => false,
			'body' => $request,
		);
		$response = wp_remote_post( $paypal_adr, $params );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			return true;
		}

		return false;
	}


	private function get_installed_plugins() {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		$separator = ( empty( $_POST['action'] ) ) ? '<br />' : "\r\n";
		$installed_plugins = array( $separator );

		foreach ( $active_plugins as $plugin ) {
			$plugin_data = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );

			if ( empty( $plugin_data['Name'] ) ) {
				continue;
			}

			$installed_plugins[] = $plugin_data['Name'] . ' ' . $plugin_data['Version'] . ', by ' . $plugin_data['Author'] . ', URL: ' . $plugin_data['PluginURI'];
		}

		return implode( $separator, $installed_plugins );
	}


	function cronjob_fields() {
		$options = array();

		$seconds_offset = get_option( 'gmt_offset' ) * 3600;
		$cron = _get_cron_array();
		$schedules = wp_get_schedules();

		foreach ( $cron as $timestamp => $cronhooks ) {
			foreach ( (array) $cronhooks as $hook => $events ) {
				foreach ( (array) $events as $key => $event ) {
					$date = date_i18n( 'Y-m-d G:i', $timestamp + $seconds_offset );
					$frequency = ( empty( $event['schedule'] ) ) ? __( 'One-off event', APP_TD ) : $schedules [ $event['schedule'] ]['display'];

					$options[] = array(
						'title' => $hook,
						'type' => 'text',
						'name' => array( 'system_info', 'cron', $hook ),
						'extra' => array(
							'style' => 'display: none;'
						),
						'desc' => $date,
						'tip' => $frequency,
					);

				}
			}
		}

		return $options;
	}


	function render_cronjob_fields( $section, $section_id ) {
		$output = '';

		if ( empty( $section['fields'] ) ) {
			$output = __( 'You haven&#39;t created any cron job tasks yet.', APP_TD );
			echo $this->table_wrap( $output );
			return;
		}

		$output .= html( "tr",
			html( "th", html( 'strong', __( 'Hook Name', APP_TD ) ) ),
			html( "th", html( 'strong', __( 'Frequency', APP_TD ) ) ),
			html( "th", html( 'strong', __( 'Next Run Date', APP_TD ) ) )
		);

		$fields = $this->cronjob_fields();

		foreach ( $fields as $field ) {

			if ( isset( $field['desc'] ) ) {
				$field['desc'] = html( 'span class="description"', $field['desc'] );
			}

			$output .= html( "tr",
				html( "th scope='row'", $field['title'] ),
				html( "td class='frequency'", html( 'span class="description"', $field['tip'] ) ),
				html( "td", scbForms::input( $field, $this->options->get() ) )
			);
		}

		echo $this->table_wrap( $output );
	}


}

