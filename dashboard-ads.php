<?php if ( $listings = cp_get_user_dashboard_listings() ) : ?>

	<?php
		$paged = 1;
		// build the row counter depending on what page we're on
		if ( get_query_var('tab') && 'ads' == get_query_var('tab') ) {
			$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
		}
		$posts_per_page = $listings->get( 'posts_per_page' );
		$i = ( $paged != 1 ) ? ( $paged * $posts_per_page - $posts_per_page ) : 0;
	?>

	<p><?php _e( 'Below you will find a listing of all your classified ads. Click on one of the options to perform a specific task. If you have any questions, please contact the site administrator.', APP_TD ); ?></p>

	<table style="border:0;" cellpadding="4" cellspacing="1" class="tblwide footable">
		<thead>
			<tr>
				<th class="listing-count" data-class="dashicons-before expand">&nbsp;</th>
				<th class="listing-title">&nbsp;<?php _e( 'Title', APP_TD ); ?></th>
				<?php if ( current_theme_supports( 'app-stats' ) ) { ?>
					<th class="listing-views" data-hide="phone"><?php _e( 'Views', APP_TD ); ?></th>
				<?php } ?>
				<th class="listing-status" data-hide="phone"><?php _e( 'Status', APP_TD ); ?></th>
				<th class="listing-options" data-hide="phone"><?php _e( 'Options', APP_TD ); ?></th>
			</tr>
		</thead>
		<tbody>

		<?php while ( $listings->have_posts() ) : $listings->the_post(); $i++; ?>

			<?php appthemes_load_template( 'content-dashboard-' . get_post_type() . '.php', array( 'i' => $i ) ); ?>

		<?php endwhile; ?>

		</tbody>

	</table>

	<?php appthemes_pagination( '', '', $listings ); ?>

<?php else : ?>

	<div class="pad5"></div>
	<p><?php _e( 'You currently have no classified ads.', APP_TD ); ?></p>
	<div class="pad5"></div>

<?php endif; ?>

<?php wp_reset_postdata(); ?>
