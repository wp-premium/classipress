<style type="text/css">
		#bank-transfer fieldset{
			margin-bottom: 20px;
		}
		#bank-transfer .content{
			width: auto;
			padding: 20px;
		}
		#bank-transfer pre{
			font-family: Arial, Helvetica, sans-serif;
			font-size: 12px;
			padding-top: 10px;
			white-space: pre-wrap;
		}
	</style>
	<div class="section-head"><h2><?php _e( 'Bank Transfer', APP_TD ); ?></h2></div>
	<form id="bank-transfer">
		<fieldset>
			<div class="featured-head"><h3><?php _e( 'Instructions:', APP_TD ); ?></h3></div>

			<div class="content">
				<pre><?php echo ( isset( $options['message'] ) ) ? $options['message'] : '' ; ?></pre>
			</div>
		</fieldset>
		<fieldset>
			<div class="featured-head"><h3><?php _e( 'Order Information:', APP_TD ); ?></h3></div>

			<div class="content">
<pre><?php _e( 'Order ID:', APP_TD ); ?> <?php echo $order->get_id(); ?> 
<?php _e( 'Order Total:', APP_TD ); ?> <?php echo appthemes_get_price( $order->get_total(), $order->get_currency() ); ?>


<?php _e( 'For questions or problems, please contact us directly at', APP_TD ) ?> <?php echo get_option('admin_email'); ?>
</pre>
			<p>
				<?php printf( __( 'To cancel this request and use a regular gateway instead, <a href="%s">click here</a>.', APP_TD ), get_the_order_cancel_url() ); ?>
			</p>
			</div>
		</fieldset>
	</form>
	<div class="clear"></div>