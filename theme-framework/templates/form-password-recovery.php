<form action="<?php echo appthemes_get_password_recovery_url( 'login_post' ); ?>" method="post" class="login-form password-recovery-form" name="lostpassform" id="login-form">

	<p><?php _e( 'Please enter your username or email address. A new password will be emailed to you.', APP_TD ); ?></p>

	<fieldset>

		<div class="form-field">
			<label>
				<?php _e( 'Username or Email:', APP_TD ); ?>
				<input type="text" class="text required" name="user_login" tabindex="2" id="login_username" />
			</label>
		</div>

		<?php do_action( 'lostpassword_form' ); ?>

		<div class="form-field">
			<input tabindex="3" type="submit" id="lostpass" name="lostpass" value="<?php _e( 'Reset Password', APP_TD ); ?>" />
		</div>

	</fieldset>

	<!-- autofocus the field -->
	<script type="text/javascript">try{document.getElementById('login_username').focus();}catch(e){}</script>

</form>
