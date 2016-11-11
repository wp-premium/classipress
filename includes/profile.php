<?php
/**
 * User Profile.
 *
 * @package ClassiPress\Profile
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */


/**
 * Adds more contact methods to user profile.
 *
 * @param array $methods
 *
 * @return array
 */
function cp_user_contact_methods( $methods ) {
	// remove old WP default contact methods
	$methods = array_diff_key( $methods, array_flip( array( 'aim', 'yim', 'jabber' ) ) );

	$methods['twitter_id'] = __( 'Twitter', APP_TD );
	$methods['facebook_id'] = __( 'Facebook', APP_TD );

	return $methods;
}
add_action( 'user_contactmethods', 'cp_user_contact_methods', 9 );


/**
 * Returns description for additional contact methods.
 *
 * @param string $field
 *
 * @return string
 */
function cp_profile_fields_description( $field ) {
	$description = array(
		'twitter_id' => __( 'Enter your Twitter username without the URL.', APP_TD ),
		'facebook_id' =>
			__( 'Enter your Facebook username without the URL.', APP_TD ) . '<br />' .
			__( 'Don\'t have one yet?', APP_TD ) .
			sprintf( ' <a target="_blank" href="https://www.facebook.com/username/">%s</a>', __( 'Get a custom URL.', APP_TD ) ),
	);
	return isset( $description[ $field ] ) ? '<br /><span class="description">' . $description[ $field ] . '</span>' : '';
}


global $appthemes_extended_profile_fields;

$appthemes_extended_profile_fields = array(
	'active_membership_pack' => array(
		'title'=> __( 'Active Membership Pack', APP_TD ),
		'protected' => 'yes',
		'type' => 'active_membership_pack',
		'description' =>  __( 'Custom Membership Pack active for the user. Can only be changed by admins.', APP_TD ),
		'admin_description' => __( 'Enter Pack ID to activate membership for user.', APP_TD )
	),
	'membership_expires' => array(
		'title'=> __( 'Membership Pack Expires Date', APP_TD ),
		'protected' => 'yes',
		'type' => 'date',
		'description' =>  __( 'Date for unlimited/dealer posting (if enabled). Can only be changed by admins.', APP_TD ),
		'admin_description' => __( 'Enter date in format <code>Y-m-d H:i:s</code> Example date: <code>2012-01-26 13:25:00</code>', APP_TD )
	)
);
$appthemes_extended_profile_fields = apply_filters('appthemes_extended_profile_fields', $appthemes_extended_profile_fields);



/**
 * Displays the additional user profile fields.
 *
 * @param object $user
 *
 * @return void
 */
if ( ! function_exists( 'cp_profile_fields' ) ) {
	function cp_profile_fields( $user ) {
		global $appthemes_extended_profile_fields;
?>
		<h3><?php _e( 'Extended Profile', APP_TD ); ?></h3>
		<table class="form-table">

		<?php
			foreach ( $appthemes_extended_profile_fields as $field_id => $field_values ) :

				if ( isset( $field_values['protected'] ) && $field_values['protected'] == 'yes' && ! is_admin() ) {
					$protected = 'disabled="disabled"';
				} else {
					$protected = '';
				}

				//TODO - use this value for display purposes while protecting stored value
				//prepare, modify, or filter the field value based on the field ID
				switch ( $field_id ) :
					case 'active_membership_pack':
						$user_active_pack = get_the_author_meta( $field_id, $user->ID );
						$package = cp_get_membership_package( $user_active_pack );
						$the_display_value = ( $package ) ? $package->pack_name : false;
					break;
					default:
						$the_display_value = false;
					break;
				endswitch;

				$the_value = get_the_author_meta( $field_id, $user->ID );

				//begin writing the row and heading
			?>
					<tr id="<?php echo esc_attr( $field_id ); ?>_row">
						<th><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $field_values['title'] ); ?></label></th>
							<td>
								<?php
									//print the appropriate profile field based on the type of field
									switch ( $field_values['type'] ) :

										case 'date':
											$display_date = ( ! empty( $the_value ) ) ? appthemes_display_date( $the_value ) : '';
											if ( ! $protected ):
									?>
												<input type="text" name="<?php echo esc_attr( $field_id ); ?>" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $the_value ); ?>" class="regular-text" size="35" /><br />
												<span class="description"><?php echo esc_attr( $field_values['admin_description'] ); ?><br /></span>
									<?php
											endif;
									?>
											<input type="text" name="<?php echo esc_attr( $field_id ); ?>_display" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $display_date ); ?>" class="regular-text" size="35" disabled="disabled" /><br />
											<span class="description"><?php echo $field_values['description']; ?></span>
									<?php
										break;

										case 'active_membership_pack':
											if ( ! $protected ):
									?>
												<input type="text" name="<?php echo esc_attr( $field_id ); ?>" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $the_value ); ?>" class="regular-text" size="35" /><br />
												<span class="description"><?php echo esc_attr( $field_values['admin_description'] ); ?><br /></span>
									<?php
											endif;
									?>
											<input type="text" name="<?php echo esc_attr( $field_id ); ?>_display" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $the_display_value ); ?>" class="regular-text" size="35" disabled="disabled" /><br />
											<span class="description"><?php echo $field_values['description']; ?></span>
									<?php
										break;

										default:
								?>
											<input type="text" name="<?php echo esc_attr( $field_id ); ?>" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $the_value ); ?>" class="regular-text" size="35" <?php echo esc_attr( $protected ); ?> /><br />
											<span class="description"><?php echo $field_values['description']; ?></span>
								<?php
										break;

										//close the row
								?>
							</td>
						</tr>
			<?php
				endswitch;

			endforeach;
			?>

		</table>

<?php
	}
}
add_action( 'show_user_profile', 'cp_profile_fields', 0 );
add_action( 'edit_user_profile', 'cp_profile_fields' );


/**
 * Saves the user profile fields.
 *
 * @param int $user_id
 *
 * @return void
 */
if ( ! function_exists( 'cp_profile_fields_save' ) ) {
	function cp_profile_fields_save( $user_id ) {
		global $appthemes_extended_profile_fields;

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		/* Copy and paste this line for additional fields. Make sure to change 'twitter' to the field ID. */
		foreach ( $appthemes_extended_profile_fields as $field_id => $field_values ) :

			if ( ! isset( $_POST[ $field_id ] ) ) {
				continue;
			}

			switch ( $field_values['protected'] ) :
				case 'yes':
					// make sure the user is an admin or has the ability to edits all user accounts
					if ( current_user_can( 'edit_users' ) ) {
						update_user_meta( $user_id, $field_id, sanitize_text_field( $_POST[ $field_id ] ) );
					}
				break;
				default:
					update_user_meta( $user_id, $field_id, sanitize_text_field( $_POST[ $field_id ] ) );
				break;
			endswitch;

		endforeach;

	}
}
add_action( 'personal_options_update', 'cp_profile_fields_save' );
add_action( 'edit_user_profile_update', 'cp_profile_fields_save' );
