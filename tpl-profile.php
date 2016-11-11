<?php
/**
 * Template Name: User Profile
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */

global $wp_version;

$current_user = wp_get_current_user(); // grabs the user info and puts into vars
?>

<div class="content">

	<div class="content_botbg">

		<div class="content_res">

			<!-- left block -->
			<div class="content_left">

				<div class="shadowblock_out">

					<div class="shadowblock">

						<h1 class="single dotted"><?php _e( 'Edit Profile', APP_TD ); ?></h1>

						<?php do_action( 'appthemes_notices' ); ?>

						<form name="profile" id="your-profile" action="" method="post">

						<?php wp_nonce_field( 'app-edit-profile' ); ?>

							<input type="hidden" name="from" value="profile" />
							<input type="hidden" name="checkuser_id" value="<?php echo $user_ID; ?>" />


							<table class="form-table">

								<tr>
									<th><label for="user_login"><?php _e( 'Username', APP_TD ); ?></label></th>
									<td><input type="text" name="user_login" class="regular-text" id="user_login" value="<?php echo esc_attr( $current_user->user_login ); ?>" maxlength="100" disabled /></td>
								</tr>

								<tr>
									<th><label for="first_name"><?php _e( 'First Name', APP_TD ); ?></label></th>
									<td><input type="text" name="first_name" class="regular-text" id="first_name" value="<?php echo esc_attr( $current_user->first_name ); ?>" maxlength="100" /></td>
								</tr>

								<tr>
									<th><label for="last_name"><?php _e( 'Last Name', APP_TD ); ?></label></th>
									<td><input type="text" name="last_name" class="regular-text" id="last_name" value="<?php echo esc_attr( $current_user->last_name ); ?>" maxlength="100" /></td>
								</tr>

								<tr>
									<th><label for="nickname"><?php _e( 'Nickname', APP_TD ); ?></label></th>
									<td><input type="text" name="nickname" class="regular-text required" id="nickname" value="<?php echo esc_attr( $current_user->nickname ); ?>" maxlength="100" /></td>
								</tr>

								<tr>
									<th><label for="display_name"><?php _e( 'Display Name', APP_TD ); ?></label></th>
									<td>
										<select name="display_name" class="regular-dropdown" id="display_name">
										<?php foreach ( appthemes_get_user_profile_display_name_options() as $id => $item ) { ?>
											<option id="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $item ); ?>" <?php selected( $current_user->display_name, $item ); ?>><?php echo esc_attr( $item ); ?></option>
										<?php } ?>
										</select>
									</td>
								</tr>

								<tr>
									<th><label for="email"><?php _e( 'Email', APP_TD ); ?></label></th>
									<td><input type="email" name="email" class="regular-text required" id="email" value="<?php echo esc_attr( $current_user->user_email ); ?>" maxlength="100" /></td>
								</tr>

								<tr>
									<th><label for="url"><?php _e( 'Website', APP_TD ); ?></label></th>
									<td><input type="text" name="url" class="regular-text" id="url" value="<?php echo esc_url( $current_user->user_url ); ?>" maxlength="100" /></td>
								</tr>

								<tr>
									<th><label for="description"><?php _e( 'About Me', APP_TD ); ?></label></th>
									<td><textarea name="description" class="regular-text" id="description" rows="10" cols="50"><?php echo esc_textarea( $current_user->description ); ?></textarea></td>
								</tr>

								<?php foreach ( _wp_get_user_contactmethods( $current_user ) as $name => $desc ) : ?>
									<tr>
										<th><label for="<?php echo $name; ?>"><?php echo apply_filters( 'user_' . $name . '_label', $desc ); ?>:</label></th>
										<td>
											<input type="text" name="<?php echo $name; ?>" class="text regular-text" id="<?php echo $name; ?>" value="<?php echo esc_attr( $current_user->$name ); ?>" />
											<?php echo cp_profile_fields_description( $name ); ?>
										</td>
									</tr>
								<?php endforeach; ?>

								<?php

								$show_password_fields = apply_filters( 'show_password_fields', true );

								if ( $show_password_fields ): ?>

									<?php if ( $wp_version < 4.3 ) : ?>

										<tr>
											<th><label for="pass1"><?php _e( 'New Password', APP_TD ); ?></label></th>
											<td>
												<input type="password" name="pass1" class="regular-text" id="pass1" maxlength="50" value="" /><br />
												<span class="description"><?php _e( 'Leave this field blank unless you would like to change your password.', APP_TD ); ?></span>
											</td>
										</tr>

										<tr>
											<th><label for="pass2"><?php _e( 'Password Again', APP_TD ); ?></label></th>
											<td>
												<input type="password" name="pass2" class="regular-text" id="pass2" maxlength="50" value="" /><br />
												<span class="description"><?php _e( 'Type your new password again.', APP_TD ); ?></span>
											</td>
										</tr>

										<tr>
											<th>&nbsp;</th>
											<td>
												<div id="pass-strength-result"><?php _e( 'Strength indicator', APP_TD ); ?></div><br /><br /><br />
												<span class="description"><?php _e( 'Your password should be at least seven characters long.', APP_TD ); ?></span>
											</td>
										</tr>

									<?php else: ?>

										<tr class="user-pass1-wrap manage-password">
											<th>
												<label for="pass1"><?php _e( 'New Password', APP_TD ); ?></label>
											</th>
											<td>
												<button type="button" class="btn_orange wp-generate-pw hide-if-no-js"><?php _e( 'Generate Password', APP_TD ); ?></button>
												<div class="wp-pwd hide-if-js">

													<?php $initial_password = wp_generate_password( 24 ); ?>

													<input type="password" id="pass1" name="pass1" class="regular-text" autocomplete="off" data-pw="<?php echo esc_attr( $initial_password ); ?>" aria-describedby="pass-strength-result" />
													<input type="text" style="display:none" name="pass2" id="pass2" autocomplete="off" />

													<div>
														<button type="button" class="btn_orange wp-hide-pw hide-if-no-js" data-start-masked="<?php echo (int) isset( $_POST['pass1'] ); ?>" data-toggle="0" aria-label="<?php esc_attr_e( 'Hide password' ); ?>">
															<span class="dashicons dashicons-hidden"></span>
															<span class="text"><?php _e( 'Hide', APP_TD ); ?></span>
														</button>
														<button type="button" class="btn_orange wp-cancel-pw hide-if-no-js" data-toggle="0" aria-label="<?php esc_attr_e( 'Cancel password change', APP_TD ); ?>">
															<span class="text"><?php _e( 'Cancel', APP_TD ); ?></span>
														</button>
													</div>

													<p>
														<div id="pass-strength-result"><?php _e( 'Strength indicator', APP_TD ); ?></div>
														<span class="description"><?php _e( 'Your password should be at least seven characters long.', APP_TD ); ?></span>
													</p>

												</div>
											</td>
										</tr>

									<?php endif; ?>

								<?php endif; ?>

							</table>

							<br />

							<?php
								do_action( 'profile_personal_options', $current_user );
								do_action( 'show_user_profile', $current_user );
							?>

							<br />

							<p class="submit center">
								<input type="hidden" name="action" value="app-edit-profile" />
								<input type="hidden" name="user_id" id="user_id" value="<?php echo $user_ID; ?>" />
								<input type="hidden" name="admin_color" value="<?php echo esc_attr( $current_user->admin_color ); ?>" />
								<input type="hidden" name="rich_editing" value="<?php echo esc_attr( $current_user->rich_editing ); ?>" />
								<input type="hidden" name="comment_shortcuts" value="<?php echo esc_attr( $current_user->comment_shortcuts ); ?>" />

								<?php if ( _get_admin_bar_pref( 'front', $user_ID ) ): ?>
									<input type="hidden" name="admin_bar_front" value="true" />
								<?php endif; ?>

								<input type="submit" id="cpsubmit" class="btn_orange" value="<?php _e( 'Update Profile &raquo;', APP_TD ); ?>" name="submit" />
							</p>

						</form>

					</div><!-- /shadowblock -->

				</div><!-- /shadowblock_out -->

			</div><!-- /content_left -->

			<?php get_sidebar( 'user' ); ?>

			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
