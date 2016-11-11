<?php
/**
 * Set Deleted post status ajax action
 *
 * @package Framework\Views\Ajax\Delete
 */

define( 'APP_POST_STATUS_DELETED', 'deleted' );

/**
 * Set Deleted post status ajax view
 */
class APP_Ajax_View_Delete_Post extends APP_Ajax_View {

	public function __construct( $ajax_action = 'appthemes_delete_post', $action_var = 'delete' ) {

		parent::__construct( $ajax_action, $action_var );

	}

	public function init() {

		register_post_status( APP_POST_STATUS_DELETED, array(
			'label'                     => _x( 'Deleted', 'listing', APP_TD ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Deleted <span class="count">(%s)</span>', 'Deleted <span class="count">(%s)</span>', APP_TD ),
		) );

		parent::init();
	}

	protected function condition() {
		return parent::condition() && ( $_POST[ $this->action_var ] == 1 );
	}

	protected function response_message( $data ) {

		$post_id = (int) $_POST['item_id'];

		if ( ! current_user_can( 'edit_post', $post_id ) ) {

			$data['status'] = 'error';
			$data['notice'] = sprintf ( __( 'You do not have permission to delete that listing.', APP_TD ) );

			return $data;
		}

		wp_update_post( array(
			'ID'          => $post_id,
			'post_status' => APP_POST_STATUS_DELETED
		) );

		$message        = __( "Deleted listing '%s'.", APP_TD );
		$data['notice'] = sprintf( $message, get_the_title( $post_id ) );

		return $data;
	}

	public function ajax_response( $status, $notice, $item_id, $redirect_url = '' ) {
		ob_start();
		appthemes_display_notice( $status, $notice );
		$notice = ob_get_clean();

		$result = array(
			'html'     => '',
			'status'   => $status,
			'notice'   => $notice,
		);

		die ( json_encode( $result ) );
	}

	public function get_actions( $item_id ) {
		return array(1);
	}

	public function get_button_args( $item_id, $action ) {
		$args = parent::get_button_args( $item_id, $action );

		$args['content'] = __( 'Delete', APP_TD );
		$args['class']   = "delete-listing button alert";

		return $args;
	}
}

function appthemes_delete_button( $item_id = '' ) {

	$view = appthemes_get_ajax_view( 'appthemes_delete_post' );
	if( empty( $item_id ) ) {
		$item_id = get_the_ID();
	}

	echo $view->render( $item_id );

}