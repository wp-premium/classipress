<?php
/**
 * Set Favorites ajax action
 *
 * @package Framework\Views\Ajax\Favorites
 */

define( 'APP_FAVORITES_CONNECTION', 'appthemes_favorites' );

/**
 * Favorites ajax view
 */
class APP_Ajax_View_Favorites extends APP_Ajax_View {

	protected $args;

	public function __construct( $ajax_action = 'appthemes_favorites', $action_var = 'favorite' ) {

		$this->set_args();
		parent::__construct( $ajax_action, $action_var );

	}

	protected function set_args() {

		$args_sets = get_theme_support( 'app-ajax-favorites' );
		$args      = array();

		if ( ! is_array( $args_sets ) ) {
			$args_sets = array();
		}

		foreach ( $args_sets as $args_set ) {
			foreach ( $args_set as $key => $arg ) {
				if ( ! isset( $args[ $key ] ) ) {
					$args[ $key ] = $arg;
				} elseif ( is_array( $arg ) ) {
					$args[ $key ] = wp_parse_args( (array) $args[ $key ], $arg );
				}
			}
		}

		$this->args = $args;
	}

	public function init() {

		if ( ! isset( $this->args['post_types'] ) || empty( $this->args['post_types'] ) ) {
			return;
		}

		p2p_register_connection_type( array(
			'name' => APP_FAVORITES_CONNECTION,
			'from' => (array) $this->args['post_types'],
			'to'   => 'user'
		) );

		parent::init();
	}

	protected function condition() {
		return parent::condition() && in_array( $_POST[ $this->action_var ], array('add', 'delete') );
	}

	protected function is_fave( $item_id ) {
		$count = p2p_get_connections( APP_FAVORITES_CONNECTION, array (
			'direction' => 'from',
			'from' 		=> $item_id,
			'to' 		=> get_current_user_id(),
			'fields' 	=> 'count'
		) );

		return (bool) $count;
	}

	public function get_actions( $item_id ) {

		$actions = array();

		if ( ! $this->is_fave( $item_id ) || ! is_user_logged_in() ) {
			$actions[] = 'add';
		} else {
			$actions[] = 'delete';
		}

		return $actions;
	}

	public function get_button_args( $item_id, $action ) {
		$args = parent::get_button_args( $item_id, $action );

		if ( 'add' === $action ) {
			$args['content'] = __( 'Favorite', APP_TD );
			$args['class']   = "fave-button fave-link button";

		} elseif ( 'delete' === $action ) {
			$args['content'] = __( 'Delete Favorite', APP_TD );
			$args['class']   = "fave-button unfave-link button";
		}

		return $args;
	}

	protected function response_message( $data ) {

		if ( ! is_user_logged_in() ) {

			$data['redirect'] = esc_url( $_POST['current_url'] );
			$data['status']   = 'error';
			$data['notice']   = sprintf ( __( 'You must <a href="%1$s">login</a> to be able to favorite listings.', APP_TD ), wp_login_url( $data['redirect'] ) );

			return $data;
		}

		$p2p = p2p_type( APP_FAVORITES_CONNECTION );
		$user_id = get_current_user_id();
		$post_id = (int) $_POST['item_id'];

		if ( 'add' == $_POST[ $this->action_var ] ) {
			$date           = current_time( 'mysql' );
			$data['status'] = $p2p->connect( $post_id, $user_id, array( 'date' => $date ) );
			$message        = __("Added '%s' to your favorites.", APP_TD);
			$data['notice'] = sprintf( $message, get_the_title( $post_id ) );
		} else {
			$data['status'] = $p2p->disconnect( $post_id, $user_id );
			$message        = __( "Removed '%s' from your favorites.", APP_TD );
			$data['notice'] = sprintf( $message, get_the_title( $post_id ) );
		}

		if ( is_wp_error( $p2p ) ) {
			$data['status'] = 'error';
			$message        = __( "Could not add '%s' to favorites at this time.", APP_TD );
			$data['notice'] = sprintf( $message, get_the_title( $post_id ) );
		}

		return $data;
	}

}

function appthemes_favorite_button( $item_id = '' ) {

	$view = appthemes_get_ajax_view( 'appthemes_favorites' );
	if( empty( $item_id ) ) {
		$item_id = get_the_ID();
	}

	echo $view->render( $item_id );

}