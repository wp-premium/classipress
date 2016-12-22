<?php
/**
 * Template Name: Login
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

					<h2 class="dotted"><span class="colour"><?php _e( 'Login', APP_TD ); ?></span></h2>

					<?php do_action( 'appthemes_notices' ); ?>

					<p><?php _e( 'Please complete the fields below to login to your account.', APP_TD ); ?></p>

					<div class="left-box">

						<form action="<?php echo appthemes_get_login_url( 'login_post' ); ?>" method="post" class="loginform" id="login-form">

							<p>
								<label for="login_username"><?php _e( 'Username:', APP_TD ); ?></label>
								<input type="text" class="text required" name="log" id="login_username" value="<?php if ( isset( $posted['login_username'] ) ) echo esc_attr( $posted['login_username'] ); ?>" />
							</p>

							<p>
								<label for="login_password"><?php _e( 'Password:', APP_TD ); ?></label>
								<input type="password" class="text required" name="pwd" id="login_password" value="" />
							</p>

							<?php do_action( 'login_form' ); ?>

							<div class="clr"></div>

							<div id="checksave">

								<p class="rememberme">
									<input name="rememberme" class="checkbox" id="rememberme" value="forever" type="checkbox" checked="checked" />
									<label for="rememberme"><?php _e( 'Remember me', APP_TD ); ?></label>
								</p>

								<p class="submit">
									<input type="submit" class="btn_orange" name="login" id="login" value="<?php _e( 'Login &raquo;', APP_TD ); ?>" />
									<?php echo APP_Login::redirect_field(); ?>
									<input type="hidden" name="testcookie" value="1" />
								</p>

								<p class="lostpass">
									<a class="lostpass" href="<?php echo appthemes_get_password_recovery_url(); ?>" title="<?php _e( 'Password Lost and Found', APP_TD ); ?>"><?php _e( 'Lost your password?', APP_TD ); ?></a>
								</p>

								<?php wp_register( '<p class="register">', '</p>' ); ?>

							</div>

						</form>

						<!-- autofocus the field -->
						<script type="text/javascript">try{document.getElementById('login_username').focus();}catch(e){}</script>

					</div>

					<div class="right-box">

					</div><!-- /right-box -->

					<div class="clr"></div>

				</div><!-- /shadowblock -->

			</div><!-- /shadowblock_out -->

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
