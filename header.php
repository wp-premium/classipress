<?php
/**
 * Generic Header template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 1.0
 */

global $cp_options;
?>

<div class="header">

	<div class="header_top">

		<div class="header_top_res">

			<p>
				<?php echo cp_login_head(); ?>

				<a href="<?php echo esc_url( appthemes_get_feed_url() ); ?>" class="dashicons-before srvicon rss-icon" target="_blank" title="<?php esc_attr_e( 'RSS Feed', APP_TD ); ?>"></a>

				<?php if ( $cp_options->facebook_id ) { ?>
					<a href="<?php echo appthemes_make_fb_profile_url( $cp_options->facebook_id ); ?>" class="dashicons-before srvicon facebook-icon" target="_blank" title="<?php _e( 'Facebook', APP_TD ); ?>"></a>
				<?php } ?>

				<?php if ( $cp_options->twitter_username ) { ?>
					<a href="https://twitter.com/<?php echo $cp_options->twitter_username; ?>" class="dashicons-before srvicon twitter-icon" target="_blank" title="<?php _e( 'Twitter', APP_TD ); ?>"></a>
				<?php } ?>
			</p>

		</div><!-- /header_top_res -->

	</div><!-- /header_top -->


	<div class="header_main">

		<div class="header_main_bg">

			<div class="header_main_res">

				<div id="logo">

					<?php if ( get_header_image() ) { ?>
						<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
							<img src="<?php header_image(); ?>" class="header-logo" width="<?php echo get_custom_header()->width; ?>" height="<?php echo get_custom_header()->height; ?>" alt="" />
						</a>
					<?php } elseif ( display_header_text() ) { ?>
						<h1 class="site-title">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home">
								<?php bloginfo( 'name' ); ?>
							</a>
						</h1>
					<?php } ?>
					<?php if ( display_header_text() ) { ?>
						<div class="description"><?php bloginfo( 'description' ); ?></div>
					<?php } ?>

				</div><!-- /logo -->

				<div class="adblock">
					<?php appthemes_advertise_header(); ?>
				</div><!-- /adblock -->

				<div class="clr"></div>

			</div><!-- /header_main_res -->

		</div><!-- /header_main_bg -->

	</div><!-- /header_main -->


	<div class="header_menu">

		<div class="header_menu_res">

			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'menu_id' => 'menu-header', 'fallback_cb' => false, 'container' => false ) ); ?>

			<a href="<?php echo esc_url( CP_ADD_NEW_URL ); ?>" class="obtn btn_orange"><?php _e( 'Post an Ad', APP_TD ); ?></a>

			<div class="clr"></div>

		</div><!-- /header_menu_res -->

	</div><!-- /header_menu -->

</div><!-- /header -->
