<?php
/**
 * AppThemes CSV Importer
 *
 *
 * @package Framework\Importer
 */

require_once ABSPATH . 'wp-admin/includes/import.php';

/**
 * CSV Importer class
 */
class APP_Importer extends scbAdminPage {
	var $post_type;
	var $fields;
	var $custom_fields;
	var $taxonomies;
	var $tax_meta;
	var $geodata;
	var $attachments;
	var $url_remap = array();

	/**
	 * Importer construnctor
	 *
	 * Args can have 3 elements:
	 * <code>
	 * $args = array(
	 *     'taxonomies' => array( 'valid', 'taxonomies' ),
	 *     'custom_fields' => array(
	 *         'csv_key' => 'internal_key',
	 *         'csv_key' => array(
	 *             'internal_key' => 'key',
	 *             'default' => 'value'
	 *         )
	 *     ),
	 *     'tax_meta' => array( array( 'tax' => array( 'csv_key' => 'tax_key' ) ),
	 * );
	 * </code>
	 *
	 * @param string $post_type Post type
	 * @param array $fields Field names array
	 * @param array $args Extra arguments
	 */
	public function __construct( $post_type = 'post', $fields, $args = '' ) {
		$args = wp_parse_args( $args, array(
			'taxonomies' => array(),
			'custom_fields' => array(),
			'tax_meta' => array(),
			'geodata' => false,
			'attachments' => false,
		) );

		$this->post_type = $post_type;
		$this->fields = $fields;
		$this->taxonomies = $args['taxonomies'];
		$this->tax_meta = $args['tax_meta'];
		$this->geodata = $args['geodata'];
		$this->attachments = $args['attachments'];

		$this->custom_fields = array();
		foreach ( $args['custom_fields'] as $csv_key => $data ) {
			if ( ! is_array( $data ) ) {
				$data = array( 'internal_key' => $data );
			}

			$this->custom_fields[ $csv_key ] = wp_parse_args( $data, array(
				'internal_key' => $csv_key,
				'default' => '',
			) );
		}

		parent::__construct();
	}

	function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title' => __( 'CSV Importer', APP_TD ),
			'menu_title' => __( 'Importer', APP_TD ),
			'page_slug' => 'app-importer',
			'parent' => 'app-dashboard',
			'screen_icon' => 'tools',
		);
	}

	function form_handler() {} // handled in page_content()

	function page_content() {
		if ( isset( $_GET['step'] ) && 1 == $_GET['step'] ) {
			$this->import();
		}

		if ( defined( 'WP_DEBUG' ) && isset( $_GET['step'] ) && 'export' == $_GET['step'] ) {
			$wud = wp_upload_dir();

			$name = '/export-' . substr( md5( rand() ), 0, 8 ) . '.csv';

			$this->export( $wud['basedir'] . $name );

			echo scb_admin_notice( 'CSV Generated: ' . html_link( $wud['baseurl'] . $name ) );
		}

		echo '<div class="narrow">';
		echo '<p>' . __( "Howdy! Upload a CSV file containing your data and we'll import it into this site. The file must be in the correct format for the import to work.", APP_TD ) . '</p>';
		$this->import_upload_form( add_query_arg( array( 'step' => '1' ) ) );
		echo '</div>';
	}

	private function import_upload_form( $action ) {
		$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size = size_format( $bytes );
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
	?>
			<div class="error"><p><?php _e( 'Before you can upload your import file, you will need to fix the following error:', APP_TD ); ?></p>
			<p><strong><?php echo $upload_dir['error']; ?></strong></p></div>
	<?php
		} else {
	?>
			<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr( wp_nonce_url( $action, 'import-upload' ) ); ?>">
				<table class="form-table">
					<tbody>

						<?php do_action( 'appthemes_before_import_upload_row' ); ?>

						<tr>
							<th>
								<label for="upload"><?php _e( 'Select a File', APP_TD ); ?></label>
							</th>
							<td>
								<input type="file" id="upload" name="import" size="25" />
								<input type="hidden" name="action" value="save" />
								<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
								<small><?php printf( __('(Maximum size: %s)', APP_TD ), $size ); ?></small>
							</td>
						</tr>

						<?php do_action( 'appthemes_after_import_upload_row' ); ?>

					</tbody>
				</table>

				<?php do_action( 'appthemes_after_import_upload_form' ); ?>

				<?php submit_button( __( 'Upload file and import', APP_TD ), 'button' ); ?>
			</form>
	<?php
		}
	}

	private function import() {
		check_admin_referer( 'import-upload' );

		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) ) {
			echo scb_admin_notice( __( 'Sorry, there has been an error.', APP_TD ), 'error' );
			echo scb_admin_notice( esc_html( $file['error'] ), 'error' );
			return false;
		}

		$c = $this->process( $file['file'] );

		if ( $this->attachments ) {
			$this->backfill_attachment_urls();
		}

		if ( false === $c ) {
			echo scb_admin_notice( __( 'The file could not be processed.', APP_TD ), 'error' );
		} else {
			echo scb_admin_notice( sprintf( __( 'Imported %s items.', APP_TD ), number_format_i18n( $c ) ) );
		}
	}

	private function process( $file ) {
		$handle = fopen( $file, 'r' );

		$headers = fgetcsv( $handle );

		if ( ! $headers ) {
			return false;
		}

		$count = 0;

		setlocale( LC_ALL, get_locale() . '.' . get_option( 'blog_charset' ) );

		while ( false !== $values = fgetcsv( $handle ) ) {
			// ignore blank lines
			if ( null === $values[0] ) {
				continue;
			}

			$row = array_combine( $headers, $values );

			// ignore invalid lines
			if ( ! $row ) {
				continue;
			}

			if ( $this->import_row( $row ) ) {
				$count++;
			}
		}

		fclose( $handle );

		return $count;
	}

	private function export( $file ) {
		$handle = fopen( $file, 'w+' );

		$posts = get_posts( array(
			'post_type' => $this->post_type,
			'nopaging' => true,
		) );

		$post = array_shift( $posts );
		$row = $this->export_row( $post );

		fputcsv( $handle, array_keys( $row ) );
		fputcsv( $handle, $row );

		foreach ( $posts as $post ) {
			fputcsv( $handle, $this->export_row( $post ) );
		}

		fclose( $handle );
	}

	private function export_row( $post ) {
		$user = get_user_by( 'id', $post->post_author );
		if ( $user ) {
			$post->post_author = $user->user_login;
		}

		$row = array();

		foreach ( $this->fields as $col => $field ) {
			$row[ $col ] = $post->$field;
		}

		foreach ( $this->custom_fields as $col => $data ) {
			$row[ $col ] = get_post_meta( $post->ID, $data['internal_key'], true );
		}

		foreach ( $this->taxonomies as $col ) {
			$terms = get_the_terms( $post->ID, $col );
			if ( ! $terms ) {
				$row[ $col ] = '';
			} else {
				$row[ $col ] = implode( ',', wp_list_pluck( $terms, 'name' ) );
			}
		}

		// TODO: tax_meta

		if ( $this->geodata ) {
			$coord = appthemes_get_coordinates( $post->ID );
			$row['lat'] = $coord->lat;
			$row['lng'] = $coord->lng;
		}

		return $row;
	}

	private function import_row( $row ) {
		$post = array(
			'post_type' => $this->post_type,
			'post_status' => 'publish',
		);
		$post_meta = array();

		$tax_input = array();
		$tax_meta = array();

		foreach ( $this->fields as $col => $field ) {
			if ( isset( $row[ $col ] ) ) {
				$post[ $field ] = $row[ $col ];
			}
		}

		foreach ( $this->custom_fields as $col => $data ) {
			if ( isset( $row[ $col ] ) ) {
				$val = $row[ $col ];
			} elseif ( '' !== $data['default'] ) {
				$val = $data['default'];
			} else {
				continue;
			}

			$post_meta[ $data['internal_key'] ] = $val;
		}

		foreach ( $this->taxonomies as $col ) {
			if ( isset( $row[ $col ] ) ) {
				$tax_input[ $col ] = array_filter( array_map( 'trim', explode( ',', $row[ $col ] ) ) );
			}
		}

		foreach ( $this->tax_meta as $tax => $fields ) {
			foreach ( $fields as $col => $key ) {
				if ( isset( $row[ $col ] ) ) {
					$term = $tax_input[ $tax ][0];
					$tax_meta[ $tax ][ $term ][ $key ] = $row[ $col ];
				}
			}
		}

		$data = compact( array( 'post', 'post_meta', 'tax_input', 'tax_meta' ) );
		$data = apply_filters( 'appthemes_importer_import_row_data', $data );

		if ( ! $data ) {
			return false;
		}
		// export array as variables
		extract( $data, EXTR_OVERWRITE );

		foreach ( $tax_meta as $tax => $terms ) {
			foreach ( $terms as $term => $meta_data ) {
				if ( empty( $term ) ) {
					continue;
				}

				$t = appthemes_maybe_insert_term( $term, $tax );
				if ( is_wp_error( $t ) ) {
					continue;
				}

				foreach ( $meta_data as $meta_key => $meta_value ) {
					if ( 'desc' == substr( $meta_key, -4 ) ) {
						wp_update_term( $t['term_id'], $tax, array( 'description' => sanitize_text_field( $meta_value ) ) );
					} else {
						update_metadata( $tax, $t['term_id'], $meta_key, $meta_value );
					}
				}
			}
		}

		foreach ( $tax_input as $tax => $terms ) {
			$_terms = array();
			foreach ( $terms as $term ) {
				if ( empty( $term ) ) {
					continue;
				}

				$t = appthemes_maybe_insert_term( $term, $tax );
				if ( ! is_wp_error( $t ) ) {
					$_terms[] = (int) $t['term_id'];
				}
			}
			$post['tax_input'][ $tax ] = $_terms;
		}

		if ( ! empty( $post['post_author'] ) ) {
			$user = get_user_by( 'login', $post['post_author'] );
			if ( $user ) {
				$post['post_author'] = $user->ID;
			}
		}

		if ( ! empty( $post['post_date'] ) ) {
			$post['post_date'] = date( 'Y-m-d H:i:s', strtotime( $post['post_date'] ) );
		}

		if ( ! in_array( $post['post_status'], get_post_stati() ) ) {
			$post['post_status'] = 'publish';
		}

		$post = apply_filters( 'appthemes_importer_import_row_post', $post );
		$post_id = wp_insert_post( $post, true );
		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		$post_meta = apply_filters( 'appthemes_importer_import_row_post_meta', $post_meta );
		foreach ( $post_meta as $meta_key => $meta_value ) {
			add_post_meta( $post_id, $meta_key, $meta_value, true );
		}

		if ( $this->geodata ) {
			appthemes_set_coordinates( $post_id, $row['lat'], $row['lng'] );
		}

		if ( $this->attachments && isset( $row['attachments'] ) ) {

			$attachments = explode( ',', $row['attachments'] );

			if ( ! empty( $attachments ) ) {
				$imported_attachments = array();
				foreach ( $attachments as $attachment_url ) {

					$attachment_url = trim( $attachment_url );
					$attachment_id = $this->process_attachment( $attachment_url, $post_id );

					if ( ! is_wp_error( $attachment_id ) ) {
						$imported_attachments[ $attachment_url ] = $attachment_id;
					}

				}

				if ( isset( $row['featured_attachment'] ) ) {
					if ( ! empty( $imported_attachments[ $row['featured_attachment'] ] ) ) {
						update_post_meta( $post_id, '_thumbnail_id', $imported_attachments[ $row['featured_attachment'] ] );
					}
				}
			}
		}

		do_action( 'appthemes_importer_import_row_after', $post_id, $row );

		return true;
	}

	function process_attachment( $url, $parent_post_id ) {
		$post = array();

		$post['post_parent'] = $parent_post_id;

		// if the URL is absolute, but does not contain address, then upload it assuming base_site_url
		if ( preg_match( '|^/[\w\W]+$|', $url ) ) {
			$url = rtrim( site_url(), '/' ) . $url;
		}

		$tmp = download_url( $url );
		$file_array = array(
			'name' => basename( $url ),
			'tmp_name' => $tmp
		);

		// Check for download errors
		if ( is_wp_error( $tmp ) ) {
			@unlink( $file_array['tmp_name'] );
			return $tmp;
		}

		$id = media_handle_sideload( $file_array, $parent_post_id );
		// Check for handle sideload errors.
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			return $id;
		}

		$attachment_url = wp_get_attachment_url( $id );

		$info = wp_check_filetype( get_attached_file( $id ) );

		// remap resized image URLs, works by stripping the extension and remapping the URL stub.
		if ( preg_match( '!^image/!', $info['type'] ) ) {
			$parts = pathinfo( $url );
			$name = basename( $parts['basename'], ".{$parts['extension']}" ); // PATHINFO_FILENAME in PHP 5.2

			$parts_new = pathinfo( $attachment_url );
			$name_new = basename( $parts_new['basename'], ".{$parts_new['extension']}" );

			$this->url_remap[ $parts['dirname'] . '/' . $name ] = $parts_new['dirname'] . '/' . $name_new;
		}

		return $id;
	}

	/**
	 * Use stored mapping information to update old attachment URLs
	 */
	function backfill_attachment_urls() {
		global $wpdb;
		// make sure we do the longest urls first, in case one is a substring of another
		uksort( $this->url_remap, array( &$this, 'cmpr_strlen' ) );

		foreach ( $this->url_remap as $from_url => $to_url ) {
			// remap urls in post_content
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)", $from_url, $to_url ) );
			// remap enclosure urls
			$result = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key='enclosure'", $from_url, $to_url ) );
		}
	}

	// return the difference in length between two strings
	function cmpr_strlen( $a, $b ) {
		return strlen( $b ) - strlen( $a );
	}

}
