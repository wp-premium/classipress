<?php
/**
 * Form fields for the custom front-end lost password page.
 *
 * Designed for use with the Foundation 6.x framework.
 *
 * @package AppThemes
 * @since 1.1.0
 */

?>
<form action="<?php echo appthemes_get_password_recovery_url( 'login_post' ); ?>" method="post" class="login-form password-recovery-form" name="lostpassform" id="login-form">

	<fieldset>

		<label>
			<?php _e( 'Username or Email', APP_TD ); ?>
			<input type="text" name="user_login" class="required" id="login_username" />
		</label>

		<?php
		/**
		 * Fires inside the lostpassword form tags, before the submit and hidden fields.
		 *
		 * @since 1.0.0
		 */
		do_action( 'lostpassword_form' );
		?>

		<input type="submit" name="lostpass" id="lostpass" class="button expanded" value="<?php esc_attr_e( 'Reset Password', APP_TD ); ?>" />

		<p id="nav" class="text-center">
			<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php _e('Log in') ?></a>
			<?php if ( get_option( 'users_can_register' ) ) : ?>
				| <a href="<?php echo esc_url( wp_registration_url() ); ?>"><?php _e( 'Register', APP_TD ); ?></a>
			<?php endif; ?>
		</p>

	</fieldset>

</form>

<!-- autofocus the field -->
<script>try{document.getElementById('login_username').focus();}catch(e){}</script>
