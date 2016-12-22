<?php
/**
 * Reports metaboxes
 *
 * @package Components\Reports\Admin\Metaboxes
 */

/**
 * Reports Post metabox
 */
class APP_Report_Post_Metabox extends APP_Meta_Box {

	/**
	 * Setups reports post metabox
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct( 'reports-post', __( 'Reports', APP_TD ), appthemes_reports_get_args( 'post_type' ), 'normal', 'default' );
	}


	/**
	 * Displays metabox content
	 *
	 * @param object $post
	 *
	 * @return void
	 */
	public function display( $post ) {
		$reports = appthemes_get_post_reports( $post->ID );
		$reports = $reports->reports;

		if ( empty( $reports ) ) {
			echo '<p id="no-reports">' . __( 'No reports yet.', APP_TD ) . '</p>';
		} else {
			$table = new APP_Reports_Table_Admin( $post->ID, $reports );
			echo $table->show();
		}

	}

}


/**
 * Reports User metabox
 */
class APP_Report_User_Metabox extends APP_User_Meta_Box {

	/**
	 * Setups reports user metabox
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct( 'reports-user', __( 'Reports', APP_TD ) );
	}


	/**
	 * Displays metabox content
	 *
	 * @param object $post
	 *
	 * @return void
	 */
	public function display( $user ) {
		echo html( 'h3', __( 'Reports', APP_TD ) );

		$reports = appthemes_get_user_reports( $user->ID );
		$reports = $reports->reports;

		if ( empty( $reports ) ) {
			echo '<p id="no-reports">' . __( 'No reports yet.', APP_TD ) . '</p>';
		} else {
			$table = new APP_Reports_Table_Admin( $user->ID, $reports );
			echo $table->show();
		}

	}

}


/**
 * Used to construct and display an reports table
 */
class APP_Reports_Table_Admin extends APP_Table {

	protected $table_id;
	protected $reports;
	protected $args;


	/**
	 * Setups reports table
	 *
	 * @param int $table_id
	 * @param array $reports
	 * @param array $args (optional)
	 *
	 * @return void
	 */
	public function __construct( $table_id, $reports, $args = array() ) {

		$this->table_id = $table_id;

		$this->reports = $reports;

		$this->args = wp_parse_args( $args, array(
			'wrapper_html' => 'table class="reports form-table"',
			'header_wrapper' => 'thead',
			'body_wrapper' => 'tbody',
			'footer_wrapper' => 'tfoot',
			'row_html' => 'tr',
			'cell_html' => 'td',
			'head_cell_html' => 'th',
		) );

	}


	/**
	 * Returns reports table
	 *
	 * @param array $attributes (optional)
	 *
	 * @return string
	 */
	public function show( $attributes = array() ) {
		$this->display_styles();
		$this->display_scripts();

		return $this->table( $this->reports, $attributes, $this->args );
	}


	/**
	 * Returns reports table header
	 *
	 * @param array $items
	 *
	 * @return string
	 */
	protected function header( $items ) {

		$cells = array(
			__( 'Author', APP_TD ),
			__( 'Report', APP_TD ),
			__( 'Date', APP_TD ),
			__( 'Delete', APP_TD ),
		);

		return html( $this->args['row_html'], array(), $this->cells( $cells, $this->args['head_cell_html'] ) );
	}


	/**
	 * Returns reports table row
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	protected function row( $item ) {

		$author_info = html( 'strong', get_comment_author( $item->get_id() ) ) . '<br />';
		$author_email = get_comment_author_email( $item->get_id() );
		if ( ! empty( $author_email ) ) {
			$author_info .= html_link( 'mailto:' . $author_email, $author_email ) . '<br />';
		}
		$author_info .= get_comment_author_IP( $item->get_id() );

		$cells = array(
			$author_info,
			get_comment_text( $item->get_id() ),
			get_comment_date( '', $item->get_id() ),
			'<span class="delete ui-icon ui-icon-circle-minus"></span>',
		);

		return html( 'tr id="report-' . $item->get_id() . '"', array(), $this->cells( $cells ) );
	}


	/**
	 * Outputs reports table styles
	 *
	 * @return void
	 */
	public function display_styles() {
		?>
<style>
.form-table span.delete {
	cursor: pointer;
	display: inline-block;
}

.reports th {
	font-weight: bold;
}

.reports tbody tr:nth-child(odd) {
	background-color: #FCFCFC;
}
</style>
	<?php }


	/**
	 * Outputs reports table scripts
	 *
	 * @return void
	 */
	public function display_scripts() {
	?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
	jQuery(".reports > tbody").on({
		click: function() {
			if ( ! confirm( '<?php echo esc_js( __( 'Are you sure you want to delete this report?', APP_TD ) ); ?>' ) ) {
				return;
			}

			var parent = jQuery(this).parents('tr');
			var report_id = parent.attr('id').split('-')[1];

			jQuery.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: "json",
				data: {
					action: "appthemes-delete-report",
					report_id: report_id,
					nonce: "<?php echo esc_js( wp_create_nonce( 'delete-report' ) ); ?>"
				},
				error: function(XMLHttpRequest, textStatus, errorThrown){
					alert('Error: ' + errorThrown + ' - ' + textStatus + ' - ' + XMLHttpRequest);
				},
				success: function( data ) {
					if (data.success == true) {
						parent.hide();
					} else {
						alert( data.message );
					}
				}
			});
		}
	}, "td span.delete.ui-icon-circle-minus" );
});
//]]>
</script>
	<?php
	}

}

