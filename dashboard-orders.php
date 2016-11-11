<?php if ( ( $orders = cp_get_user_dashboard_orders() ) || get_query_var('order_status') ) : ?>

	<p><?php _e( 'Below is your Order history. You can use the provided filter to filter all the orders.', APP_TD); ?></p>

	<form class="filter" method="get" action="<?php echo esc_url( CP_DASHBOARD_ORDERS_URL ) ?>" >
		<input type="hidden" name="tab" value="orders" />
		<?php foreach( cp_get_order_statuses_verbiages() as $order_status => $name ): ?>

			<?php $checked = (bool) ( ! get_query_var('order_status') || in_array( $order_status, get_query_var('order_status') ) ); ?>

			<p>
				<input type="checkbox" name="order_status[]" value="<?php echo esc_attr( $order_status ); ?>" <?php checked( $checked ); ?> />
				<label for="<?php echo esc_attr( $order_status ); ?>"><?php echo $name; ?></label>
			</p>

		<?php endforeach; ?>

		<p><input type="submit" value="<?php esc_attr_e( 'Filter', APP_TD ); ?>" class="submit"></p>

		<?php if ( get_query_var('order_status') ): ?>
			&mdash; <a href="<?php echo esc_url( add_query_arg( 'tab', 'orders', CP_DASHBOARD_ORDERS_URL ) ); ?>"><?php _e( 'Remove Filters', APP_TD ); ?></a>
		<?php endif; ?>

		<div class="clr"></div>
	</form>

	<?php if ( empty( $orders ) ): ?>

		<div class="pad20"><?php _e( 'No Orders found.', APP_TD ); ?></div>

	<?php else: ?>

		<div class="orders-history-legend">
			<h4><?php _e( 'Legend', APP_TD ); ?></h4>
			<div class="orders-history-statuses">
				<?php _e( 'Pending', APP_TD ); ?>
				<br/><?php _e( 'Failed', APP_TD ); ?>
				<br/><?php _e( 'Completed', APP_TD ); ?>
				<br/><?php _e( 'Activated', APP_TD ); ?>
			</div>
			<div>
				<span><?php echo __( 'Order not processed.', APP_TD ); ?></span>
				<br/><span><?php echo __( 'Order failed or manually canceled.', APP_TD ); ?></span>
				<br/><span><?php echo __( 'Order processed succesfully but pending moderation before activation.', APP_TD ); ?></span>
				<br/><span><?php echo __( 'Order processed succesfully and activated.', APP_TD ); ?></span>
			</div>
		</div>

		<table cellpadding="0" cellspacing="0" class="tblwide footable tablet footable-loaded">
			<thead>
				<tr>
					<th data-class="expand"><?php _e( 'ID', APP_TD ); ?></th>
					<th class="text-center" data-hide="phone"><?php _e( 'Date', APP_TD ); ?></th>
					<th class="text-left" data-hide="phone"><?php _e( 'Order Summary', APP_TD ); ?></th>
					<th class="text-center" data-hide="phone"><?php _e( 'Price', APP_TD ); ?></th>
					<th class="text-center" data-hide="phone"><?php _e( 'Payment/Status', APP_TD ); ?></th>
				</tr>
			</thead>
			<tbody>

			<?php if ( $orders->have_posts() ) : ?>

				<?php while ( $orders->have_posts() ) : $orders->the_post(); ?>

					<?php $order = appthemes_get_order( $orders->post->ID ); ?>
						<tr>
							<td class="order-history-id text-center">#<?php the_ID(); ?></td>
							<td class="date text-center"><strong><?php the_time(__('j M',APP_TD)); ?></strong><br/><span class="year"><?php the_time(__('Y',APP_TD)); ?></span></td>
							<td class="order-history-summary left">
								<span class="order-history-ad"><?php the_order_ad_link( $order ); ?></span>
								<?php echo cp_get_the_order_summary( $order, $output_type = 'html' ); ?>
							</td>
							<td class="order-history-price center"><?php echo appthemes_get_price( $order->get_total() ); ?></td>
							<td class="order-history-payment center"><?php the_orders_history_payment( $order ); ?></td>
						</tr>

				<?php endwhile; ?>

			<?php else: ?>
				<tr><td colspan="7"><?php _e( 'No Orders found.', APP_TD ); ?></td></tr>
			<?php endif; ?>

			</tbody>
		</table>

		<?php appthemes_pagination( '', '', $orders ); ?>

	<?php endif; ?>

<?php else: ?>

	<div class="pad5"></div>
	<p><?php _e( 'You don\'t have any Orders, yet.', APP_TD ); ?></p>
	<div class="pad5"></div>

<?php endif; ?>

<?php wp_reset_postdata(); ?>
