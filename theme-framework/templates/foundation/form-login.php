<?php
/**
 * Form fields for the custom front-end login page.
 *
 * Designed for use with the Foundation 6.x framework.
 *
 * @package AppThemes
 * @since 1.1.0
 */

$rememberme = ! empty( $_POST['rememberme'] );
?>
<form action="<?php echo appthemes_get_login_url( 'login_post' ); ?>" method="post" class="login-form" id="login-form">

	<fieldset>

		<label>
			<?php _e( 'Username', APP_TD ); ?>
			<input type="text" name="log" class="required" id="login_username" value="" />
		</label>

		<label>
			<?php _e( 'Password', APP_TD ); ?>
			<input type="password" name="pwd" class="required" id="login_password" value="" />
		</label>

		<?php
		/**
		 * Fires following the 'Password' field in the login form.
		 *
		 * @since 1.0.0
		 */
		do_action( 'login_form' );
		?>

		<input type="checkbox" name="rememberme" class="checkbox" id="rememberme" value="forever" <?php checked( $rememberme ); ?> />
		<label for="rememberme"><?php _e( 'Remember me', APP_TD ); ?></label>

		<input type="submit" name="login" id="login" class="button expanded" value="<?php esc_attr_e( 'Login', APP_TD ); ?>" />

		<p id="nav" class="text-center">
			<?php if ( get_option( 'users_can_register' ) ) : ?>
				<a href="<?php echo esc_url( wp_registration_url() ); ?>"><?php _e( 'Register', APP_TD ); ?></a> |
			<?php endif; ?>
			<a href="<?php echo esc_url( appthemes_get_password_recovery_url() ); ?>"><?php _e( 'Lost your password?', APP_TD ); ?></a>
		</p>

	</fieldset>

	<?php echo APP_Login::redirect_field(); ?>
	<input type="hidden" name="testcookie" value="1" />

</form>

<!-- autofocus the field -->
<script>try{document.getElementById('login_username').focus();}catch(e){}</script>
