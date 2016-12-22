<?php
/**
 * Form fields for the custom front-end registration page.
 *
 * Designed for use with the Foundation 6.x framework.
 *
 * @package AppThemes
 * @since 1.1.0
 */

?>

<form action="<?php echo appthemes_get_registration_url( 'login_post' ); ?>" method="post" class="login-form register-form" name="registerform" id="login-form">

	<fieldset>

		<label>
			<?php _e( 'Username', APP_TD ); ?>
			<input type="text" name="user_login" class="required" id="user_login" value="<?php if ( isset( $_POST['user_login'] ) ) echo esc_attr( wp_unslash( $_POST['user_login'] ) ); ?>" />
		</label>

		<label>
			<?php _e( 'Email', APP_TD ); ?>
			<input type="email" name="user_email" class="required" id="user_email" value="<?php if ( isset( $_POST['user_email'] ) ) echo esc_attr( wp_unslash( $_POST['user_email'] ) ); ?>" />
		</label>

		<?php
		/**
		 * Fires following the 'Email' field in the user registration form.
		 *
		 * @since 1.0.0
		 */
		do_action( 'register_form' );
		?>

		<label>
			<?php _e( 'Password', APP_TD ); ?>
			<input type="password" name="pass1" class="required" id="user_pass1" value="" autocomplete="off" />
		</label>

		<label>
			<?php _e( 'Password Again', APP_TD ); ?>
			<input type="password" name="pass2" class="required" id="user_pass2" value="" autocomplete="off" />
		</label>

		<input type="submit" name="register" id="register" class="button expanded" value="<?php _e( 'Register', APP_TD ); ?>" />

		<p id="nav" class="text-center">
			<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php _e( 'Log in', APP_TD ); ?></a> |
			<a href="<?php echo esc_url( appthemes_get_password_recovery_url() ); ?>"><?php _e( 'Lost your password?', APP_TD ); ?></a>
		</p>

	</fieldset>

	<?php echo APP_Login::redirect_field(); ?>

</form>

<!-- autofocus the field -->
<script>try{document.getElementById('user_login').focus();}catch(e){}</script>
