<?php


/**
 * Modifies columns on admin users page.
 *
 * @param array $columns
 *
 * @return array
 */
function cp_manage_users_columns( $columns ) {

	$newcol = array_slice( $columns, 0, 1 );
	$newcol = array_merge( $newcol, array( 'id' => __( 'Id', APP_TD ) ) );
	$columns = array_merge( $newcol, array_slice( $columns, 1 ) );

	$columns['cp_ads_count'] = __( 'Ads', APP_TD );
	$columns['last_login'] = __( 'Last Login', APP_TD );
	$columns['registered'] = __( 'Registered', APP_TD );

	return $columns;
}
add_action( 'manage_users_columns', 'cp_manage_users_columns' );


/**
 * Registers columns as sortable on admin users page.
 *
 * @param array $columns
 *
 * @return array
 */
function cp_users_column_sortable( $columns ) {

	$columns['id'] = 'id';

	return $columns;
}
add_filter( 'manage_users_sortable_columns', 'cp_users_column_sortable' );


/**
 * Displays the coumn values for each user.
 *
 * @param string $r
 * @param string $column_name
 * @param int $user_id
 *
 * @return string
 */
function cp_manage_users_custom_column( $r, $column_name, $user_id ) {

	switch ( $column_name ) {

		case 'cp_ads_count' :
			global $cp_counts;

			if ( ! isset( $cp_counts ) ) {
				$cp_counts = cp_count_ads();
			}

			if ( ! array_key_exists( $user_id, $cp_counts ) ) {
				$cp_counts = cp_count_ads();
			}

			if ( $cp_counts[ $user_id ] > 0 ) {
				$r .= "<a href='edit.php?post_type=" . APP_POST_TYPE . "&author=$user_id' title='" . esc_attr__( 'View ads by this author', APP_TD ) . "' class='edit'>";
				$r .= $cp_counts[ $user_id ];
				$r .= '</a>';
			} else {
				$r .= 0;
			}
			break;

		case 'last_login' :
			$r = get_user_meta( $user_id, 'last_login', true );
			if ( ! empty( $r ) ) {
				$r = appthemes_display_date( $r );
			}
			break;

		case 'registered' :
			$user_info = get_userdata( $user_id );
			$r = $user_info->user_registered;
			if ( ! empty( $r ) ) {
				$r = appthemes_display_date( $r, 'datetime', true );
			}
			break;

		case 'id' :
			$r = $user_id;
			break;

	}

	return $r;
}
add_filter( 'manage_users_custom_column', 'cp_manage_users_custom_column', 10, 3 );


/**
 * Counts the number of ad listings for the user.
 * Use only on admin Users page.
 *
 * @return array
 */
function cp_count_ads() {
	global $wpdb, $wp_list_table;

	$count = array();
	$users = array_keys( $wp_list_table->items );
	$userlist = implode( ',', $users );

	$result = $wpdb->get_results( "SELECT post_author, COUNT(*) FROM $wpdb->posts WHERE post_type = '" . APP_POST_TYPE . "' AND post_author IN ($userlist) GROUP BY post_author", ARRAY_N );
	foreach ( $result as $row ) {
		$count[ $row[0] ] = $row[1];
	}

	foreach ( $users as $id ) {
		if ( ! isset( $count[ $id ] ) ) {
			$count[ $id ] = 0;
		}
	}

	return $count;
}


/**
 * Creates and displays the charts on the dashboard.
 *
 * @return void
 */
function cp_dashboard_charts() {
	global $wpdb, $cp_options;

	$sql = "SELECT COUNT(post_title) as total, post_date FROM $wpdb->posts WHERE post_type = %s AND post_date > %s GROUP BY DATE(post_date) DESC";
	$results = $wpdb->get_results( $wpdb->prepare( $sql, APP_POST_TYPE, appthemes_mysql_date( current_time( 'mysql' ), -30 ) ) );

	$listings = array();

	// put the days and total posts into an array
	foreach ( (array) $results as $result ) {
		$the_day = date( 'Y-m-d', strtotime( $result->post_date ) );
		$listings[ $the_day ] = $result->total;
	}

	// setup the last 30 days
	for ( $i = 0; $i < 30; $i++ ) {
		$each_day = date( 'Y-m-d', strtotime( '-' . $i . ' days' ) );
		// if there's no day with posts, insert a goose egg
		if ( ! in_array( $each_day, array_keys( $listings ) ) ) {
			$listings[ $each_day ] = 0;
		}
	}

	// sort the values by date
	ksort( $listings );

	// Get sales - completed orders with a cost
	$results = array();
	$currency_symbol = $cp_options->curr_symbol;
	if ( current_theme_supports( 'app-payments' ) ) {
		$sql = "SELECT sum( m.meta_value ) as total, p.post_date FROM $wpdb->postmeta m INNER JOIN $wpdb->posts p ON m.post_id = p.ID WHERE m.meta_key = 'total_price' AND p.post_status IN ( '" . APPTHEMES_ORDER_COMPLETED . "', '" . APPTHEMES_ORDER_ACTIVATED . "' ) AND p.post_date > %s GROUP BY DATE(p.post_date) DESC";
		$results = $wpdb->get_results( $wpdb->prepare( $sql, appthemes_mysql_date( current_time( 'mysql' ), -30 ) ) );
		$currency_symbol = APP_Currencies::get_current_symbol();
	}

	$sales = array();

	// put the days and total posts into an array
	foreach ( (array) $results as $result ) {
		$the_day = date( 'Y-m-d', strtotime( $result->post_date ) );
		$sales[ $the_day ] = $result->total;
	}

	// setup the last 30 days
	for ( $i = 0; $i < 30; $i++ ) {
		$each_day = date( 'Y-m-d', strtotime( '-' . $i . ' days' ) );
		// if there's no day with posts, insert a goose egg
		if ( ! in_array( $each_day, array_keys( $sales ) ) ) {
			$sales[ $each_day ] = 0;
		}
	}

	// sort the values by date
	ksort( $sales );
?>

<div id="placeholder"></div>

<script language="javascript" type="text/javascript">
// <![CDATA[
jQuery(function() {

	var posts = [
		<?php
		foreach ( $listings as $day => $value ) {
			$sdate = strtotime( $day );
			$sdate = $sdate * 1000; // js timestamps measure milliseconds vs seconds
			$newoutput = "[$sdate, $value],\n";
			echo $newoutput;
		}
		?>
	];

	var sales = [
		<?php
		foreach ( $sales as $day => $value ) {
			$sdate = strtotime( $day );
			$sdate = $sdate * 1000; // js timestamps measure milliseconds vs seconds
			$newoutput = "[$sdate, $value],\n";
			echo $newoutput;
		}
		?>
	];


	var placeholder = jQuery("#placeholder");

	var output = [
		{
			data: posts,
			label: "<?php _e( 'New Ad Listings', APP_TD ); ?>",
			symbol: ''
		},
		{
			data: sales,
			label: "<?php _e( 'Total Sales', APP_TD ); ?>",
			symbol: '<?php echo $currency_symbol; ?>',
			yaxis: 2
		}
	];

	var options = {
		series: {
			lines: { show: true },
			points: { show: true }
		},
		grid: {
			tickColor:'#f4f4f4',
			hoverable: true,
			clickable: true,
			borderColor: '#f4f4f4',
			backgroundColor:'#FFFFFF'
		},
		xaxis: {
			mode: 'time',
			timeformat: "%m/%d"
		},
		yaxis: {
			min: 0
		},
		y2axis: {
			min: 0,
			tickFormatter: function(v, axis) {
				return "<?php echo $currency_symbol; ?>" + v.toFixed(axis.tickDecimals)
			}
		},
		legend: {
			position: 'nw'
		}
	};

	jQuery.plot(placeholder, output, options);

	// reload the plot when browser window gets resized
	jQuery(window).resize(function() {
		jQuery.plot(placeholder, output, options);
	});

	function showChartTooltip(x, y, contents) {
		jQuery('<div id="charttooltip">' + contents + '</div>').css( {
			position: 'absolute',
			display: 'none',
			top: y + 5,
			left: x + 5,
			opacity: 1
		} ).appendTo("body").fadeIn(200);
	}

	var previousPoint = null;
	jQuery("#placeholder").bind("plothover", function (event, pos, item) {
		jQuery("#x").text(pos.x.toFixed(2));
		jQuery("#y").text(pos.y.toFixed(2));
		if (item) {
			if (previousPoint != item.datapoint) {
				previousPoint = item.datapoint;

				jQuery("#charttooltip").remove();
				var x = new Date(item.datapoint[0]), y = item.datapoint[1];
				var xday = x.getDate(), xmonth = x.getMonth()+1; // jan = 0 so we need to offset month
				showChartTooltip(item.pageX, item.pageY, xmonth + "/" + xday + " - <b>" + item.series.symbol + y + "</b> " + item.series.label);
			}
		} else {
			jQuery("#charttooltip").remove();
			previousPoint = null;
		}
	});
});
// ]]>
</script>


<?php
}

