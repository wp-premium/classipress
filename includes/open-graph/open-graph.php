<?php
/**
 * Open Graph
 *
 * @package OpenGraph
 */
class APP_Open_Graph {

	protected $args;


	public function __construct( $args = array() ) {

		// Do not add the tags when official FB plugin is enabled, or it has been disabled by filter
		if ( class_exists( 'Facebook_Loader' ) || apply_filters( 'appthemes_disable_open_graph', false ) ) {
			return;
		}

		$defaults = array(
			'preview_limit' => 190,
			'default_image' => '',
		);

		$this->args = wp_parse_args( $args, $defaults );

		add_action( 'wp_head', array( $this, 'display' ) );
	}


	public function display() {

		foreach ( $this->tags() as $tag => $content ) {
			if ( ! empty( $content ) ) {
				echo '<meta property="' . esc_attr( $tag ) . '" content="' . esc_attr( $content ) . '" />' . "\n";
			}
		}

	}


	public function tags() {

		if ( ! did_action( 'pre_get_posts' ) ) {
			return array();
		}

		$queried_object = get_queried_object();

		$tags = array();
		$default_tags = array(
			'og:type' => 'website',
			'og:locale' => get_locale(),
			'og:site_name' => get_bloginfo( 'name' ),
			'og:image' => $this->get_image_url(),
		);

		if ( is_front_page() ) {

			$tags = array(
				'og:url' => home_url( '/' ),
				'og:title' => get_bloginfo( 'name' ),
				'og:description' => get_bloginfo( 'description' ),
			);

		} else if ( is_singular() ) {

			$tags = array(
				'og:type' => 'article',
				'og:url' => get_permalink( $queried_object ),
				'og:title' => get_the_title( $queried_object ),
				'og:description' => $this->generate_preview( $queried_object->post_content ),
				'article:published_time' => date( 'c', strtotime( $queried_object->post_date_gmt ) ),
				'article:modified_time' => date( 'c', strtotime( $queried_object->post_modified_gmt ) ),
				'article:author' => get_author_posts_url( $queried_object->post_author ),
			);

		} else if ( is_tax() || is_category() || is_tag() ) {

			$tags = array(
				'og:url' => get_term_link( $queried_object ),
				'og:title' => $queried_object->name,
				'og:description' => $this->generate_preview( $queried_object->description ),
			);

		} else if ( is_author() ) {

			$tags = array(
				'og:type' => 'profile',
				'og:url' => get_author_posts_url( $queried_object->ID ),
				'og:title' => $queried_object->display_name,
				'og:description' => $this->generate_preview( $queried_object->user_description ),
				'profile:first_name' => get_the_author_meta( 'first_name', $queried_object->ID ),
				'profile:last_name' => get_the_author_meta( 'last_name', $queried_object->ID ),
			);

		}

		$tags = array_merge( $default_tags, $tags );

		return apply_filters( 'appthemes_open_graph_meta_tags', $tags );
	}


	public function get_image_url() {
		$image_url = '';
		$queried_object = get_queried_object();

		if ( is_singular() ) {
			$images = get_children( array( 'post_parent' => $queried_object->ID, 'post_status' => 'inherit', 'numberposts' => 1, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'ID' ) );
			if ( $images ) {
				$image = array_shift( $images );
				$image = wp_get_attachment_image_src( $image->ID, 'large' );
				$image_url = $image[0];
			}
		} else if ( is_author() ) {
			$image_html = get_avatar( $queried_object->user_email, 200 );
			if ( $image_html && preg_match( "/src='(.*?)'/i", $image_html, $matches ) ) {
				$image_url = $matches[1];
			}
		}

		if ( empty( $image_url ) ) {
			$image_url = $this->args['default_image'];
		}

		return $image_url;
	}


	public function generate_preview( $content ) {

		$content = wp_strip_all_tags( $content, true );
		$content = strip_shortcodes( $content );
		$preview_limit = $this->args['preview_limit'];

		if ( function_exists( 'mb_strlen' ) ) {
			if ( mb_strlen( $content ) > $preview_limit ) {
				$content = mb_substr( $content, 0, $preview_limit ) . '...';
			}
		} else {
			if ( strlen( $content ) > $preview_limit ) {
				$content = substr( $content, 0, $preview_limit ) . '...';
			}
		}

		return $content;
	}

}