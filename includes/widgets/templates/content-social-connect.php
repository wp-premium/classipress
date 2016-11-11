<div <?php post_class( 'social-connect-box' ); ?>>

	<ul class="social-media-links">

		<?php foreach( APP_Social_Networks::get_support() as $network_id ): ?>

			<?php if ( ! empty( $instance["social_{$network_id}_inc"] ) && ! empty( $instance["social_{$network_id}_url"] ) ):  ?>

				<li>
					<a <?php echo ( ! empty( $instance['use_tooltips'] ) && ! empty( $instance["social_{$network_id}_inc"] )  && ! empty( $instance["social_{$network_id}_desc"] ) ? 'data-tooltip ' . 'title="'.esc_attr( $instance["social_{$network_id}_desc"] ) . '"' : '' ); ?> class="<?php echo esc_attr( $network_id ); ?>" href="<?php echo esc_url( APP_Social_Networks::get_url( $network_id, $instance["social_{$network_id}_url"] ) ); ?>" target="_blank">

					<?php if ( empty( $instance['images_url'] ) ): ?>

						<i class="fi-social-<?php echo esc_attr( $network_id ); ?>"></i>

					<?php else: ?>

						<img src="<?php echo esc_url( trailingslashit( $instance['images_url'] ) . "{$network_id}.png" ); ?>">

					<?php endif;?>

					<?php if ( empty( $instance['use_tooltips'] ) ) : ?>
						<span><?php echo $instance["social_{$network_id}_desc"]; ?> </span>
					<?php endif; ?>

					</a>
				</li>

			<?php endif; ?>

		<?php endforeach; ?>

	</ul>

</div>

