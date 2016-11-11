<?php
/**
 * Provides a media manager metabox with WordPress's native media UI.
 *
 * @package Framework\Metaboxes
 */
class APP_Media_Manager_Metabox extends APP_Meta_Box {

	/**
	 * Metabox ID.
	 * @var string
	 */
	static $id;

	/**
	 * Sets up metabox.
	 *
	 * @param string $id
	 * @param string $title
	 * @param string|array $post_type
	 * @param string $context (optional)
	 * @param string $priority (optional)
	 *
	 * @return void
	 */
	public function __construct( $id, $title, $post_type, $context = 'normal', $priority = 'default' ) {

		if ( ! current_theme_supports( 'app-media-manager' ) ) {
			return;
		}

		self::$id = $id;

		parent::__construct( "$id-metabox", $title, $post_type, $context, $priority );
	}

	/**
	 * Enqueues admin scripts.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		global $post;

		appthemes_enqueue_media_manager( array( 'post_id' => $post->ID ) );
	}

	/**
	 * Displays metabox content.
	 *
	 * @param object $post
	 *
	 * @return void
	 */
	public function display( $post ) {
		appthemes_media_manager( $post->ID, array( 'id' => self::$id ) );
	}

	/**
	 * Saves media data.
	 *
	 * @param int $post_id
	 *
	 * @return void
	 */
	protected function save( $post_id ) {
		parent::save( $post_id );

		appthemes_handle_media_upload( $post_id );
	}

}
