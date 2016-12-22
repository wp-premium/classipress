<fieldset id="<?php echo esc_attr( $atts['id'] ); ?>" class="media-manager <?php echo esc_attr( $atts['class'] ); ?>">

	<?php if ( $atts['title'] ): ?>
		<legend><?php echo $atts['title']; ?></legend>
	<?php endif; ?>

		<div id="<?php echo esc_attr( $atts['id'] ); ?>" class="media_placeholder">
			<?php if ( empty( $atts['attachment_ids'] ) && empty( $atts['embed_urls'] ) ): ?>

				<div class="no-media">
					<?php echo $atts['no_media_text']; ?>
				</div>

			<?php endif; ?>

			<div class="media-attachments">
				<?php appthemes_output_attachments( $atts['attachment_ids'], $atts['attachment_params'] ); ?>
			</div>
			<div class="media-embeds">
				<?php appthemes_output_embed_urls( $atts['embed_urls'], $atts['embed_params'] ); ?>
			</div>
		</div>

		<input type="button" group_id="<?php echo esc_attr( $atts['id'] ); ?>" class="button small upload_button" upload_text="<?php echo esc_attr( $atts['upload_text'] ); ?>" manage_text="<?php echo esc_attr( $atts['manage_text'] ); ?>" value="<?php echo esc_attr( $atts['button_text'] ); ?>">

</fieldset>