<?php
/**
 * Facebook like box widget
 *
 * @package Components\Widgets
 */

// facebook like box sidebar widget
class APP_Widget_Facebook extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base'  => 'appthemes_facebook',
			'name'     => __( 'AppThemes Facebook Like Box', APP_TD ),
			'defaults' => array(
				'title'                 => __( 'Facebook Friends', APP_TD ),
				'pid'                   => 'appthemes',
				'width'                 => '310',
				'height'                => '290',
				'hide_cover'            => false,
				'show_facepile'         => true,
				'show_posts'            => false,
				'hide_cta'              => false,
				'small_header'          => false,
				'adapt_container_width' => true,
			),
			'widget_ops' => array(
				'description' => __( 'This places a Facebook page Like Box in your sidebar to attract and gain Likes from visitors.', APP_TD ),
				'classname'   => 'widget-facebook'
			),
			'control_options' => array(),

		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		$title = $instance['title'];
		$href = 'https://www.facebook.com/' . $instance['pid'];
		$width = $instance['width'];
		$height = $instance['height'];
		$hide_cover = $instance['hide_cover'];
		$show_facepile = $instance['show_facepile'];
		$show_posts = $instance['show_posts'];
		$hide_cta = $instance['hide_cta'];
		$small_header = $instance['small_header'];
		$adapt_container_width = $instance['adapt_container_width'];
	?>

		<div id="fb-root"></div>
		<script>(function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0];
		  if (d.getElementById(id)) return;
		  js = d.createElement(s); js.id = id;
		  js.src = "//connect.facebook.net/<?php echo get_locale(); ?>/sdk.js#xfbml=1&version=v2.3&appId=235643263204884";
		  fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));</script>

	<?php
		// widget properties
		// uses 'json_encode' to convert boolean vars to 'true' or 'false' strings
		$properties = array(
			'data-width'                 => $width,
			'data-height'                => $height,
			'data-hide-cover'            => json_encode( $hide_cover ),
			'data-show-facepile'         => json_encode( $show_facepile ),
			'data-show-posts'            => json_encode( $show_posts ),
			'data-hide-cta'              => json_encode( $hide_cta ),
			'data-small-header'          => json_encode( $small_header ),
			'data-adapt-container-width' => json_encode( $adapt_container_width ),
		);
		$properties = array_map( 'esc_attr', $properties );

		$fb_props = array();

		foreach( $properties as $prop => $value ) {
			$fb_props[] = sprintf( ' %1$s = "%2$s" ', $prop, $value );
		}

	?>
		<div class="fb-page" data-href="<?php echo esc_url( $href ); ?>" <?php echo implode( ' ', $fb_props ); ?>  >
			<div class="fb-xfbml-parse-ignore">
				<blockquote cite="<?php echo esc_url( $href ); ?>">
					<a href="<?php echo esc_url( $href ); ?>"><?php echo $title; ?></a>
				</blockquote>
			</div>
		</div>
	<?php
	}

	protected function form_fields() {
		return array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type' => 'text',
				'name' => 'pid',
				'desc' => __( 'Facebook Page ID (e.g: appthemes):', APP_TD ),
			),
			array(
				'type' => 'text',
				'name' => 'width',
				'desc' => __( 'Width (Min. is 180 & Max. is 500):', APP_TD ),
			),
			array(
				'type' => 'text',
				'name' => 'height',
				'desc' => __( 'Height (Min. is 70):', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'hide_cover',
				'desc' => __( 'Hide Cover Photo', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'show_facepile',
				'desc' => __( 'Show Friend\'s Faces', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'show_posts',
				'desc' => __( 'Show Page Posts', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'hide_cta',
				'desc' => __( 'Hide Custom Call to Action Button (if available)', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'small_header',
				'desc' => __( 'Use Small Header', APP_TD ),
			),
			array(
				'type' => 'checkbox',
				'name' => 'adapt_container_width',
				'desc' => __( 'Adapt to Container Width', APP_TD ),
			),
		);

	}
}
