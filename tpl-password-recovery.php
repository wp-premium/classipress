<?php
/**
 * Template Name: Password Recovery
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.2
 */
?>


<div class="content">

	<div class="content_botbg">

		<div class="content_res">

			<!-- full block -->
			<div class="shadowblock_out">

				<div class="shadowblock">

					<h2 class="dotted"><span class="colour"><?php _e( 'Password Recovery', APP_TD ); ?></span></h2>

					<?php do_action( 'appthemes_notices' ); ?>

					<p><?php _e( 'Please enter your username or email address. A new password will be emailed to you.', APP_TD ); ?></p>

					<div class="left-box">

						<form action="<?php echo appthemes_get_password_recovery_url( 'login_post' ); ?>" method="post" class="loginform password-recovery-form" name="lostpassform" id="lostpasswordform">

							<p>
								<label for="login_username"><?php _e( 'Username or Email:', APP_TD ); ?></label>
								<input type="text" class="text required" name="user_login" id="login_username" />
							</p>

							<div id="checksave">
								<?php do_action( 'lostpassword_form' ); ?>
								<p class="submit"><input type="submit" class="btn_orange" name="lostpass" id="lostpass" value="<?php _e( 'Reset Password', APP_TD ); ?>" tabindex="100" /></p>
							</div>

							<!-- autofocus the field -->
							<script type="text/javascript">try{document.getElementById('login_username').focus();}catch(e){}</script>

						</form>

					</div>

					<div class="right-box">

					</div><!-- /right-box -->

					<div class="clr"></div>

				</div><!-- /shadowblock -->

			</div><!-- /shadowblock_out -->

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
