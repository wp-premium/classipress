<?php
/**
 * Sidebar Contact template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */

$msg = array();

// if contact form has been submitted, send the email
if ( isset( $_POST['submit'] ) && $_POST['send_email'] == 'yes' ) {

	$result = cp_contact_ad_owner_email( $post->ID );

	if ( $result->get_error_code() ) {
		$error_html = '';
		foreach ( $result->errors as $error ) {
			$error_html .= $error[0] . '<br />';
		}
		$msg = array( 'error', $error_html );
	} else {
		$msg = array( 'success', __( 'Your message has been sent!', APP_TD ) );
	}

}

?>


<form name="mainform" id="mainform" class="form_contact" action="#priceblock2" method="post" enctype="multipart/form-data">

	<?php wp_nonce_field( 'form_contact', '_cp_contact_nonce' ); ?>

	<?php if ( ! empty( $msg ) ): ?>
		<?php appthemes_display_notice( $msg[0], array( $msg[1] ) ); ?>
	<?php endif; ?>

	<p class="dashicons-before contact_msg"><?php _e( 'To inquire about this ad listing, complete the form below to send a message to the ad poster.', APP_TD ); ?></p>

	<ol>
		<li>
			<label><?php _e( 'Name', APP_TD ); ?></label>
			<input name="from_name" id="from_name" type="text" minlength="2" value="<?php if ( isset( $_POST['from_name'] ) ) echo esc_attr( stripslashes( $_POST['from_name'] ) ); ?>" class="text required" />
			<div class="clr"></div>
		</li>

		<li>
			<label><?php _e( 'Email', APP_TD ); ?></label>
			<input name="from_email" id="from_email" type="text" minlength="5" value="<?php if ( isset( $_POST['from_email'] ) ) echo esc_attr( stripslashes( $_POST['from_email'] ) ); ?>" class="text required email" />
			<div class="clr"></div>
		</li>

		<li>
			<label><?php _e( 'Subject', APP_TD ); ?></label>
			<input name="subject" id="subject" type="text" minlength="2" value="<?php _e( 'Re:', APP_TD ); ?> <?php the_title();?>" class="text required" />
			<div class="clr"></div>
		</li>

		<li>
			<label><?php _e( 'Message', APP_TD ); ?></label>
			<textarea name="message" id="message" rows="" cols="" class="text required"><?php if ( isset( $_POST['message'] ) ) echo esc_attr( stripslashes( $_POST['message'] ) ); ?></textarea>
			<div class="clr"></div>
		</li>

		<li>
			<?php cp_maybe_display_recaptcha(); ?>
		</li>

		<li>
			<input name="submit" type="submit" id="submit_inquiry" class="btn_orange" value="<?php _e( 'Send Inquiry', APP_TD ); ?>" />
		</li>

	</ol>

	<input type="hidden" name="send_email" value="yes" />

</form>
