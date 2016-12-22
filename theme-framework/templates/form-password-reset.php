<form action="<?php echo appthemes_get_password_reset_url( 'login_post' ); ?>" method="post" class="login-form password-reset-form" name="resetpassform" id="login-form">
	<p><?php _e( 'Enter your new password below.', APP_TD ); ?></p>
	<fieldset>
		<input type="hidden" id="user_login" value="<?php echo esc_attr( $_GET['login'] ); ?>" autocomplete="off" />

		<div class="form-field">
			<label for="pass1">
				<?php _e( 'New password', APP_TD ); ?>
				<input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" />
			</label>
		</div>
		<div class="form-field">
			<label><?php _e( 'Confirm new password', APP_TD ); ?>
			<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" /></label>
		</div>

		<div class="form-field">
			<div id="pass-strength-result" class="hide-if-no-js"><?php _e( 'Strength indicator', APP_TD ); ?></div>
			<p class="description indicator-hint"><?php _e( 'Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).', APP_TD ); ?></p>
		</div>

		<div class="form-field">
			<input tabindex="2" type="submit" id="resetpass" name="resetpass" value="<?php _e( 'Reset Password', APP_TD ); ?>" />
			<?php do_action( 'lostpassword_form' ); ?>
		</div>

	</fieldset>

	<!-- autofocus the field -->
	<script type="text/javascript">try{document.getElementById('pass1').focus();}catch(e){}</script>

</form>

