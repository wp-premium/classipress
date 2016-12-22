<?php
/**
 * Generic Sidebar template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 1.0
 */

global $cp_options;
?>

<div class="content_right">

<?php
	if ( is_page_template( 'tpl-ads-home.php' ) && ( is_user_logged_in() || get_option( 'users_can_register' ) ) ) {

		$current_user = wp_get_current_user();
?>

		<div id="welcome_widget" class="shadowblock_out">

			<div class="shadowblock">

				<?php if ( ! is_user_logged_in() ) { ?>

					<?php cp_display_message( 'welcome' ); ?>
					<a href="<?php echo appthemes_get_registration_url(); ?>" class="btn_orange"><?php _e( 'Join Now!', APP_TD ); ?></a>

				<?php } else { ?>

					<div class="avatar"><?php appthemes_get_profile_pic( $current_user->ID, $current_user->user_email, 150 ); ?></div>

					<div class="user">

						<p class="welcome-back"><?php _e( 'Welcome back,', APP_TD ); ?> <strong><?php echo $current_user->display_name; ?></strong>.</p>
						<p class="last-login"><?php _e( 'Last logged in:', APP_TD ); ?> <?php echo appthemes_get_last_login( $current_user->ID ); ?></p>
						<p><?php _e( 'Manage your ads or edit your profile from your personalized dashboard.', APP_TD ); ?></p>

					</div><!-- /user -->

					<div class="welcome-buttons">
						<a href="<?php echo CP_DASHBOARD_URL; ?>" class="btn_orange"><?php _e( 'Manage Ads', APP_TD ); ?></a>&nbsp;&nbsp;&nbsp;<a href="<?php echo CP_PROFILE_URL; ?>" class="btn_orange"><?php _e( 'Edit Profile', APP_TD ); ?></a>
					</div>

					<div class="clr"></div>

				<?php } ?>

			</div><!-- /shadowblock -->

		</div><!-- /shadowblock_out -->

	<?php } ?>

	<?php
	if ( is_tax( APP_TAX_CAT ) ) {
		// go get the taxonomy category id so we can filter with it
		// have to use slug instead of name otherwise it'll break with multi-word cats
		if ( ! isset( $filter ) ) {
			$filter = '';
		}

		$ad_cat_array = get_term_by( 'slug', get_query_var( APP_TAX_CAT ), APP_TAX_CAT, ARRAY_A, $filter );

		// build the advanced sidebar search
		cp_show_refine_search( $ad_cat_array['term_id'] );

	} else if ( is_search() ) {

		// build the advanced sidebar search
		cp_show_refine_search( get_query_var( 'scat' ) );

	} // is_search
	?>

	<?php appthemes_before_sidebar_widgets( 'main' ); ?>

	<?php if ( ! dynamic_sidebar( 'sidebar_main' ) ) : ?>

		<!-- no dynamic sidebar setup -->

		<div class="shadowblock_out">

			<div class="shadowblock">

				<h2 class="dotted"><?php _e( 'Meta', APP_TD ); ?></h2>

				<ul>
					<?php wp_register(); ?>
					<li><?php wp_loginout(); ?></li>
					<li><a target="_blank" href="https://www.appthemes.com/" title="Premium WordPress Themes">AppThemes</a></li>
					<li><a target="_blank" href="https://wordpress.org/" title="Powered by WordPress">WordPress</a></li>
					<?php wp_meta(); ?>
				</ul>

				<div class="clr"></div>

			</div><!-- /shadowblock -->

		</div><!-- /shadowblock_out -->

	<?php endif; ?>

	<?php appthemes_after_sidebar_widgets( 'main' ); ?>

</div><!-- /content_right -->
