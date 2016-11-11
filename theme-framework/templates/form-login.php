<form action="<?php echo appthemes_get_login_url( 'login_post' ); ?>" method="post" class="login-form" id="login-form">

	<fieldset>

		<div class="form-field">
			<label>
				<?php _e( 'Username:', APP_TD ); ?>
				<input type="text" name="log" class="text regular-text required" tabindex="2" id="login_username" value="" />
			</label>
		</div>

		<div class="form-field">
			<label>
				<?php _e( 'Password:', APP_TD ); ?>
				<input type="password" name="pwd" class="text regular-text required" tabindex="3" id="login_password" value="" />
			</label>
		</div>

		<div class="form-field">
				<input tabindex="5" type="submit" id="login" name="login" value="<?php _e( 'Login', APP_TD ); ?>" />
				<?php echo APP_Login::redirect_field(); ?>
				<input type="hidden" name="testcookie" value="1" />
		</div>

		<div class="form-field">
				<input type="checkbox" name="rememberme" class="checkbox" tabindex="4" id="rememberme" value="forever" />
				<label for="rememberme"><?php _e( 'Remember me', APP_TD ); ?></label>
		</div>

		<div class="form-field">
			<a href="<?php echo appthemes_get_password_recovery_url(); ?>"><?php _e( 'Lost your password?', APP_TD ); ?></a>
		</div>

		<?php wp_register( '<div class="form-field" id="register">', '</div>' ); ?>

		<?php do_action( 'login_form' ); ?>

	</fieldset>

	<!-- autofocus the field -->
	<script type="text/javascript">try{document.getElementById('login_username').focus();}catch(e){}</script>

</form>
