<?php
/**
 * Search engine and Refine results.
 *
 * @package ClassiPress\Search
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */


// search suggest
add_action( 'wp_ajax_nopriv_ajax-tag-search-front', 'cp_suggest' );
add_action( 'wp_ajax_ajax-tag-search-front', 'cp_suggest' );
// update Search Index
add_action( 'cp_update_listing', 'appthemes_update_search_index' );
// add search query vars
add_filter( 'init', 'cp_add_query_vars' );
// sanitize search term
add_filter( 'get_search_query', 'appthemes_filter' );

// load search filters only on frontend
if ( ! is_admin() ) {
	add_filter( 'posts_join', 'custom_search_join' );
	add_filter( 'posts_where', 'custom_search_where' );
	add_filter( 'posts_where', 'custom_search_refine_where' );
	add_filter( 'posts_join', 'custom_search_refine_join' );
	add_filter( 'posts_groupby', 'custom_search_groupby' );
	add_filter( 'pre_get_posts', 'cp_handle_empty_search_term' );
}


/**
 * Returns category id for search form.
 *
 * @return int
 */
function cp_get_search_catid() {
	global $post;

	$catid = 0;

	if ( is_tax( APP_TAX_CAT ) ) {
		$ad_cat_array = get_term_by( 'slug', get_query_var( APP_TAX_CAT ), APP_TAX_CAT, ARRAY_A );
		$catid = $ad_cat_array['term_id'];
	} else if ( is_singular( APP_POST_TYPE ) ) {
		$term = wp_get_object_terms( $post->ID, APP_TAX_CAT );
		if ( $term ) {
			$catid = $term[0]->term_id;
		}
	} else if ( is_search() ) {
		$catid = get_query_var( 'scat' );
	}

	return absint( $catid );
}


/**
 * Returns search term for refine results form.
 *
 * @return string
 */
function cp_get_search_term() {

	// get not escaped search query
	$search_term = get_search_query( false );

	return $search_term;
}


/**
 * Returns an array of all custom field names. For use in search.
 *
 * @return array
 */
function cp_custom_search_fields() {
	global $wpdb;

	$custom_fields = array();

	$sql = "SELECT field_name FROM $wpdb->cp_ad_fields p WHERE p.field_name LIKE 'cp_%' ";
	$results = $wpdb->get_results( $sql );

	if ( $results ) {
		foreach ( $results as $result ) {
			// put the fields into an array
			$custom_fields[] = $result->field_name;
		}
	}

	return $custom_fields;
}


/**
 * Ajax auto-complete taxonomy search suggest.
 *
 * @return void
 */
function cp_suggest() {
	global $wpdb;

	$s = $_GET['term']; // is this slashed already?

	if ( isset( $_GET['tax'] ) ) {
		$taxonomy = sanitize_title( $_GET['tax'] );
	} else {
		die( 'no taxonomy' );
	}

	if ( false !== strpos( $s, ',' ) ) {
		$s = explode( ',', $s );
		$s = $s[count( $s ) - 1];
	}
	$s = trim( $s );
	if ( strlen( $s ) < 2 ) {
		die( __( 'need at least two characters', APP_TD ) ); // require 2 chars for matching
	}

	$terms = $wpdb->get_col( "
		SELECT t.slug FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id ".
		"WHERE tt.taxonomy = '$taxonomy' AND tt.count > 0 ".
		"AND t.name LIKE (
			'%$s%'
		)" .
		"LIMIT 50"
	);

	if ( empty( $terms ) ) {
		echo json_encode( $terms );
		die;
	} else {
		$i = 0;
		foreach ( $terms as $term ) {
			$results[ $i ] = get_term_by( 'slug', $term, $taxonomy );
			$i++;
		}
		echo json_encode( $results );
		die;
	}
}


/**
 * Displays refine search form based on the category id.
 *
 * @param int $cat_id
 *
 * @return void
 */
if ( ! function_exists( 'cp_show_refine_search' ) ) :
function cp_show_refine_search( $cat_id ) {
	global $wpdb;

	$form_id = cp_get_form_id( $cat_id );

	if ( ! $form_id ) {
		return;
	}

	// now we should have the formid so show the form layout based on the category selected
	$sql = $wpdb->prepare( "SELECT f.field_label, f.field_name, f.field_type, f.field_values, f.field_perm, m.field_search, m.meta_id, m.field_pos, m.field_req, m.form_id "
		. "FROM $wpdb->cp_ad_fields f "
		. "INNER JOIN $wpdb->cp_ad_meta m ON f.field_id = m.field_id "
		. "WHERE m.form_id = %s AND m.field_search = '1' "
		. "ORDER BY m.field_pos ASC", $form_id );

	$results = $wpdb->get_results( $sql );

	if ( $results ) {
		echo cp_refine_search_builder( $results ); // loop through the custom form fields and display them
	}

}
endif;


/**
 * Displays refine search form based on the form fields.
 *
 * @param array $results
 *
 * @return void
 */
if ( ! function_exists( 'cp_refine_search_builder' ) ) :
	function cp_refine_search_builder( $results ) {
		global $wpdb, $cp_options;

		$cp_min_price = str_replace( ',', '', $wpdb->get_var( "SELECT min( CAST( m.meta_value AS UNSIGNED ) ) FROM $wpdb->postmeta m INNER JOIN $wpdb->posts p ON m.post_id = p.ID WHERE m.meta_key = 'cp_price' AND p.post_status = 'publish'" ) );
		$cp_max_price = str_replace( ',', '', $wpdb->get_var( "SELECT max( CAST( m.meta_value AS UNSIGNED ) ) FROM $wpdb->postmeta m INNER JOIN $wpdb->posts p ON m.post_id = p.ID WHERE m.meta_key = 'cp_price' AND p.post_status = 'publish'" ) );
		$show_precise = ( $cp_max_price > 1000 ) ? true : false;

		$locarray = array();
?>
	<script type="text/javascript">
	// <![CDATA[
	// toggles the refine search field values
	jQuery(document).ready(function() {
		jQuery('div.handle').click(function() {
			jQuery(this).next('div.element').animate({
				height: ['toggle', 'swing'],
				opacity: 'toggle' }, 200
			);

			jQuery(this).toggleClass('close', 'open');
			return false;
		});
		<?php foreach ( $_GET as $field => $val ) : ?>
			jQuery('.<?php echo esc_js($field); ?> div.handle').toggleClass('close', 'open');
			jQuery('.<?php echo esc_js($field); ?> div.element').show();
		<?php endforeach; ?>

	});
	// ]]>
	</script>

	<div id="refine_widget" class="shadowblock_out">

		<div class="shadowblock">

			<h2 class="dotted"><?php _e( 'Refine Results', APP_TD ); ?></h2>

			<ul class="refine">

				<form action="<?php echo home_url( '/' ); ?>" method="get" name="refine-search">
				<?php if ( ! is_tax( APP_TAX_CAT ) ) { ?>
					<input type="hidden" name="s" value="<?php echo esc_attr( cp_get_search_term() ); ?>" />
					<input type="hidden" name="scat" value="<?php echo esc_attr( cp_get_search_catid() ); ?>" />
				<?php } else { ?>
					<input type="hidden" name="<?php echo esc_attr( get_query_var( 'taxonomy' ) ); ?>" value="<?php echo esc_attr( get_query_var( 'term' ) ); ?>" />
				<?php } ?>

					<?php
					// grab the price and location fields first and put into a separate array
					// then remove them from the results array so they don't print out again
					foreach ( $results as $key => $value ) {

						switch ( $value->field_name ) :

							case 'cp_city':
								$locarray[0] = $results[ $key ];
								unset( $results[ $key ] );
							break;

							case 'cp_zipcode':
								$locarray[1] = $results[ $key ];
								unset( $results[ $key ] );
							break;

							case 'cp_price':
								$locarray[2] = $results[ $key ];
								unset( $results[ $key ] );
							break;

						endswitch;
					}

					// sort array by key so we get the city/zip code first
					ksort( $locarray );

					// both zip code and city have been checked
					if ( array_key_exists( 0, $locarray ) && array_key_exists( 1, $locarray ) ) {
						$flabel = sprintf( __( '%1$s or %2$s', APP_TD ), $locarray[0]->field_label, $locarray[1]->field_label );
						$fname = 'cp_city_zipcode';
					} elseif ( array_key_exists( 0, $locarray ) ) { // must be the city only
						$flabel = $locarray[0]->field_label;
						$fname = 'cp_city_zipcode';
					} elseif ( array_key_exists( 1, $locarray ) ) { // must be the zip code only
						$flabel = $locarray[1]->field_label;
						$fname = 'cp_city_zipcode';
					}

					$distance_unit = ( 'mi' == $cp_options->distance_unit ) ? __( 'miles', APP_TD ) : __( 'kilometers', APP_TD );
					// show the city/zip code field and radius slider bar
					if ( array_key_exists( 0, $locarray ) || array_key_exists( 1, $locarray ) ) :
					?>
						<script type="text/javascript">
						// <![CDATA[
							jQuery(document).ready(function() {
								jQuery('#dist-slider').slider( {
									range: 'min',
									min: 0,
									max: 100,
									value: <?php echo esc_js( isset( $_GET['distance'] ) ? intval( $_GET['distance'] ) : '50' ); ?>,
									step: 5,
									slide: function(event, ui) {
										jQuery('#distance').val(ui.value + ' <?php echo esc_js( $distance_unit ); ?>');
									}
								});
								jQuery('#distance').val(jQuery('#dist-slider').slider('value') + ' <?php echo esc_js( $distance_unit ); ?>');
							});
						// ]]>
						</script>

						<li class="distance">
							<label class="title"><?php echo $flabel; ?></label>
							<input name="<?php echo esc_attr( $fname ); ?>" id="<?php echo esc_attr( $fname ); ?>" type="text" minlength="2" value="<?php if ( isset( $_GET[ $fname ] ) ) echo esc_attr( $_GET[ $fname ] ); ?>" class="text" />
							<div class="clr"></div>
							<label for="distance" class="title"><?php _e( 'Radius', APP_TD ); ?>:</label>
							<input type="text" id="distance" name="distance" />
							<div id="dist-slider"></div>
						</li>

					<?php

					endif;

					// now loop through the other special fields
					foreach ( $locarray as $value ) :

						// show the price field range slider
						if ( $value->field_name == 'cp_price' ) {
							$curr_symbol = $cp_options->curr_symbol;
							if ( isset( $_GET['amount'] ) ) {
								$amount = explode( ' - ', $_GET['amount'] );
							} else if ( isset( $_GET['price_min'] ) && isset( $_GET['price_max'] ) ) {
								$amount = array( $_GET['price_min'], $_GET['price_max'] );
							}
							$amount[0] = empty( $amount[0] ) ? $cp_min_price : $amount[0];
							$amount[1] = empty( $amount[1] ) ? $cp_max_price : $amount[1];
							$amount[0] = str_replace( array( ',', $curr_symbol, ' ' ), '', $amount[0] );
							$amount[1] = str_replace( array( ',', $curr_symbol, ' ' ), '', $amount[1] );

							if ( $cp_options->refine_price_slider ) {
							?>

							<script type="text/javascript">
							// <![CDATA[
								jQuery(document).ready(function() {

									jQuery("#precise_price").click(function() {
										precise_price = ( jQuery(this).is(":checked") ) ? true : false;
										cp_show_price_slider(<?php echo esc_js( intval( $cp_min_price ) ); ?>, <?php echo esc_js( intval( $cp_max_price ) ); ?>, <?php echo esc_js( intval( $amount[0] ) ); ?>, <?php echo esc_js( intval( $amount[1] ) ); ?>, precise_price);
									});
									precise_price = ( jQuery("#precise_price").is(":checked") ) ? true : false;
									cp_show_price_slider(<?php echo esc_js( intval( $cp_min_price ) ); ?>, <?php echo esc_js( intval( $cp_max_price ) ); ?>, <?php echo esc_js( intval( $amount[0] ) ); ?>, <?php echo esc_js( intval( $amount[1] ) ); ?>, precise_price);

								});
							// ]]>
							</script>

							<li class="amount">
								<label class="title"><?php echo esc_html( translate( $value->field_label, APP_TD ) ); ?>:</label>
								<input type="text" id="amount" name="amount" />
								<div id="slider-range"></div>
								<?php if ( $show_precise ) { ?>
									<label class="title"><?php echo esc_html( __( 'Precise price', APP_TD ) ); ?>:</label><input type="checkbox" id="precise_price" name="precise_price" <?php checked( isset( $_GET['precise_price'] ) ); ?> />
								<?php } ?>
							</li>
							<?php
							} else {
							?>
							<li class="price_min_max">
								<label class="title"><?php echo esc_html( translate( $value->field_label, APP_TD ) ); ?> (<?php echo $cp_options->curr_symbol; ?>)</label>
								<input type="text" class="text" id="price_min" name="price_min" placeholder="<?php _e( 'from', APP_TD ); ?>" value="<?php if ( isset( $_GET['price_min'] ) ) echo esc_attr( $_GET['price_min'] ); ?>" /> &ndash;
								<input type="text" class="text" id="price_max" name="price_max" placeholder="<?php _e( 'to', APP_TD ); ?>" value="<?php if ( isset( $_GET['price_max'] ) ) echo esc_attr( $_GET['price_max'] ); ?>" />
							</li>
							<?php
							}

						}

					endforeach;


					foreach ( $results as $key => $result ) {
						if ( in_array( $result->field_type, array( 'radio', 'checkbox', 'drop-down', 'text box', 'text area' ) ) ) {
							echo cp_refine_fields( $result->field_label, $result->field_name, $result->field_values, $result->field_type );
						}
					}
					?>
					<div class="pad10"></div>
					<button class="obtn btn_orange" type="submit" tabindex="1" id="go" value="Go" name="sa"><?php _e( 'Refine Results &rsaquo;&rsaquo;', APP_TD ); ?></button>

					<input type="hidden" name="refine_search" value="yes" />

				</form>

			</ul>

			<div class="clr"></div>

		</div>

	</div>

<?php
	}
endif;


/**
 * Displays form field for refine search form.
 *
 * @param string $label
 * @param string $name
 * @param string $values
 * @param string $type
 *
 * @return void
 */
function cp_refine_fields( $label, $name, $values, $type ) {
	if ( in_array( $type, array( 'radio', 'checkbox', 'drop-down' ) ) ) {
?>

	<li class="<?php echo esc_attr( $name ); ?>">
		<label class="title"><?php echo esc_html( translate( $label, APP_TD ) ); ?></label>

		<div class="handle close dashicons-before"></div>

		<div class="element">

			<?php
			$options = cp_explode( ',', $values );
			$optionCursor = 1;
			$checked = '';
			?>

			<div class="scrollbox">

				<ol class="checkboxes">

					<?php
					$cur = ( isset( $_GET[ $name ] ) && is_array( $_GET[ $name ] ) ) ? array_map( 'stripslashes', $_GET[ $name ] ) : array();
					foreach ( $options as $option ) {
						if ( $cur ) {
							$checked = in_array( $option, $cur ) ? " checked='checked'" : '';
						}
					?>
						<li>
							<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $option ); ?>" <?php echo $checked; ?> />&nbsp;<label for="<?php echo esc_attr( $name ); ?>[]"><?php echo esc_html( $option ); ?></label>
						</li> <!-- #checkbox -->
					<?php } ?>

				</ol> <!-- #checkbox-wrap -->

			</div> <!-- #end scrollbox -->

		</div> <!-- #end element -->

		<div class="clr"></div>

	</li>
<?php
	} else {
?>
	<li class="<?php echo esc_attr( $name ); ?>">
		<label class="title"><?php echo esc_html( translate( $label, APP_TD ) ); ?></label>
		<input name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" type="text" minlength="2" value="<?php if ( isset( $_GET[ $name ] ) ) echo esc_attr( stripslashes( $_GET[ $name ] ) ); ?>" class="text" />
		<div class="clr"></div>
	</li>
<?php
	}
}


/**
 * Refines posts on the ad category taxonomy pages.
 *
 * @param object $query
 *
 * @return object
 */
function cp_pre_get_posts( $query ) {
	global $wpdb, $cp_options;

	if ( $query->is_archive && isset( $query->query_vars['ad_cat'] ) ) {
		$meta_query = array();
		$price_set = false;
		foreach ( $_GET as $key => $value ) {
			if ( empty( $value ) ) {
				continue;
			}
			switch ( $key ) {
				case 'cp_city_zipcode' :
					$api_key = $cp_options->api_key;
					$region = $cp_options->gmaps_region;
					$value = urlencode( $value );
					$geocode = json_decode( wp_remote_retrieve_body( wp_remote_get( "https://maps.googleapis.com/maps/api/geocode/json?address=$value&region=$region&key=$api_key" ) ) );
					if ( 'OK' == $geocode->status ) {
						$query->set( 'app_geo_query', array(
							'lat' => $geocode->results[0]->geometry->location->lat,
							'lng' => $geocode->results[0]->geometry->location->lng,
							'rad' => intval( $_GET['distance'] ),
						) );
					} else {
						// Google Maps API error
					}
					break;

				case 'amount' :
				case 'price_min' :
				case 'price_max' :
					if ( $price_set ) {
						break;
					}
					$price_set = true;
					if ( $cp_options->refine_price_slider ) {
						$value = str_replace( array( $cp_options->curr_symbol, ' ' ), '', $value );
						$value = str_replace( ' ', '', $value );
						$value = explode( '-', $value );
					} else {
						$price_min = empty( $_GET['price_min'] ) ? 0 : (int) $_GET['price_min'];
						$price_max = empty( $_GET['price_max'] ) ? 9999999999 : (int) $_GET['price_max'];
						$value = array( $price_min, $price_max );
					}
					$meta_query[] = array(
								'key' => 'cp_price',
								'value' => $value,
								'compare' => 'BETWEEN',
								'type' => 'numeric',
					);
					break;

				default :
					if ( 'cp_' == substr( $key, 0, 3 ) ) {
						$value = wp_kses_post( $value );
						$field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->cp_ad_fields WHERE field_name = %s", $key ) );
						if ( $field === null ) {
							break;
						}
						$compare = ( in_array( $field->field_type, array( 'radio', 'checkbox', 'drop-down' ) ) ) ? 'IN' : 'LIKE';
						$meta_query[] = array(
							'key'   => $key,
							'value' => $value,
							'compare' => $compare
						);
					}
					break;
			}
		}
		$query->set( 'meta_query', $meta_query );
	}

	return $query;
}
add_filter( 'pre_get_posts', 'cp_pre_get_posts' );


/**
 * Refines posts by geo location.
 *
 * @param array $clauses
 * @param object $wp_query
 *
 * @return array
 */
function cp_posts_clauses( $clauses, $wp_query ) {
	global $wpdb, $cp_options;

	$geo_query = $wp_query->get( 'app_geo_query' );
	if ( ! $geo_query ) {
		return $clauses;
	}

	extract( $geo_query, EXTR_SKIP );

	$R = ( 'mi' == $cp_options->distance_unit ) ? 3959 : 6371;
	$table = $wpdb->cp_ad_geocodes;

	$clauses['join'] .= " INNER JOIN $table ON ($wpdb->posts.ID = $table.post_id)";

	$clauses['where'] .= $wpdb->prepare( " AND ( %d * acos( cos( radians(%f) ) * cos( radians(lat) ) * cos( radians(lng) - radians(%f) ) + sin( radians(%f) ) * sin( radians(lat) ) ) ) < %d", $R, $lat, $lng, $lat, $rad );

	return $clauses;
}
add_filter( 'posts_clauses', 'cp_posts_clauses', 10, 2 );


/**
 * Group posts by ID on the search pages.
 *
 * @param string $groupby
 *
 * @return string
 */
function custom_search_groupby( $groupby ) {
	global $wpdb, $wp_query;

	if ( is_search() && isset( $_GET['s'] ) ) {
		$groupby = "$wpdb->posts.ID";

		remove_filter( 'posts_groupby', 'custom_search_groupby' );
	}

	return $groupby;
}


/**
 * Joins additional tables in search queries.
 *
 * @param string $join
 *
 * @return string
 */
function custom_search_join( $join ) {
	global $wpdb, $wp_query;

	if ( is_search() && isset( $_GET['s'] ) ) {

		if ( ! cp_search_index_enabled() ) {
			$join  = " LEFT JOIN $wpdb->term_relationships AS r ON ($wpdb->posts.ID = r.object_id) ";
			$join .= " LEFT JOIN $wpdb->term_taxonomy AS x ON (r.term_taxonomy_id = x.term_taxonomy_id) ";
			$join .= " AND (x.taxonomy = '".APP_TAX_TAG."' OR x.taxonomy = '".APP_TAX_CAT."' OR 1=1) ";
		}

		// if an ad category is selected, limit results to that cat only
		$catid = get_query_var( 'scat' );

		if ( ! empty( $catid ) ) {

			// put the catid into an array
			(array) $include_cats[] = $catid;

			// get all sub cats of catid and put them into the array
			$descendants = get_term_children( (int) $catid, APP_TAX_CAT );

			foreach ( $descendants as $key => $value ) {
				$include_cats[] = $value;
			}

			// take catids out of the array and separate with commas
			$include_cats = "'" . implode( "', '", $include_cats ) . "'";

			// add the category filter to show anything within this cat or it's children
			$join .= " INNER JOIN $wpdb->term_relationships AS tr2 ON ($wpdb->posts.ID = tr2.object_id) ";
			$join .= " INNER JOIN $wpdb->term_taxonomy AS tt2 ON (tr2.term_taxonomy_id = tt2.term_taxonomy_id) ";
			$join .= " AND tt2.term_id IN ($include_cats) ";

		}

		if ( ! cp_search_index_enabled() ) {
			$join .= " INNER JOIN $wpdb->postmeta AS m ON ($wpdb->posts.ID = m.post_id) ";
			$join .= " LEFT JOIN $wpdb->terms AS t ON x.term_id = t.term_id ";
		}

		remove_filter( 'posts_join', 'custom_search_join' );
	}

	return $join;
}


/**
 * Builds the WHERE part in search queries.
 *
 * @param string $where
 *
 * @return string
 */
function custom_search_where( $where ) {
	global $wpdb, $wp_query, $cp_options;

	$old_where = $where; // intercept the old where statement

	if ( is_search() && isset( $_GET['s'] ) ) {

		if ( ! cp_search_index_enabled() ) {
			// get the custom fields to add to search
			$customs = cp_custom_search_fields();
			// add some internal custom fields to search
			$customs = array_merge( $customs, array( 'cp_sys_ad_conf_id' ) );
		}

		$query = '';

		$var_q = stripslashes( $_GET['s'] );
		//empty the s parameter if set to default search text
		if ( __( 'What are you looking for?', APP_TD ) == $var_q ) {
			$var_q = '';
		}

		if ( isset( $_GET['sentence'] ) || $var_q == '' ) {
			$search_terms = array($var_q);
		} else {
			preg_match_all( '/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $var_q, $matches );
			$search_terms = array_map( create_function( '$a', 'return trim($a, "\\"\'\\n\\r ");' ), $matches[0] );
		}

		if ( ! isset( $_GET['exact'] ) ) {
			$_GET['exact'] = '';
		}

		$n = ( $_GET['exact'] ) ? '' : '%';

		$searchand = '';

		foreach ( (array) $search_terms as $term ) {
			$term = addslashes_gpc( $term );

			$query .= "{$searchand}(";

			if ( ! cp_search_index_enabled() ) {
				$query .= "($wpdb->posts.post_title LIKE '{$n}{$term}{$n}')";
				$query .= " OR ($wpdb->posts.post_content LIKE '{$n}{$term}{$n}')";
				$query .= " OR ((t.name LIKE '{$n}{$term}{$n}')) OR ((t.slug LIKE '{$n}{$term}{$n}'))";

				foreach ( $customs as $custom ) {
					$query .= " OR (";
					$query .= "(m.meta_key = '$custom')";
					$query .= " AND (m.meta_value LIKE '{$n}{$term}{$n}')";
					$query .= ")";
				}
			} else {
				$query .= "($wpdb->posts.post_content_filtered LIKE '{$n}{$term}{$n}')";
			}

			$query .= ")";
			$searchand = ' AND ';
		}

		$term = esc_sql( $var_q );

		if ( ! isset( $_GET['sentence'] ) && count( $search_terms ) > 1 && $search_terms[0] != $var_q ) {
			if ( ! cp_search_index_enabled() ) {
				$query .= " OR ($wpdb->posts.post_title LIKE '{$n}{$term}{$n}')";
				$query .= " OR ($wpdb->posts.post_content LIKE '{$n}{$term}{$n}')";
			} else {
				$query .= " OR ($wpdb->posts.post_content_filtered LIKE '{$n}{$term}{$n}')";
			}
		}

		if ( ! empty( $query ) ) {

			$where = " AND ({$query}) AND ($wpdb->posts.post_status = 'publish') ";

			// setup the array for post types
			$post_type_array = array();

			// always include the ads post type
			$post_type_array[] = APP_POST_TYPE;

			// check to see if we include blog posts
			if ( ! $cp_options->search_ex_blog ) {
				$post_type_array[] = 'post';
			}

			// check to see if we include pages
			if ( ! $cp_options->search_ex_pages ) {
				$post_type_array[] = 'page';
			}

			// build the post type filter sql from the array values
			$post_type_filter = "'" . implode( "','", $post_type_array ) . "'";

			// return the post type sql to complete the where clause
			$where .= " AND ($wpdb->posts.post_type IN ($post_type_filter)) ";

		}

		remove_filter( 'posts_where', 'custom_search_where' );
	}

	return $where;
}


/**
 * Builds the WHERE part in refine search queries.
 *
 * @param string $where
 *
 * @return string
 */
function custom_search_refine_where( $where ) {
	global $wpdb, $wp_query, $refine_count, $cp_options;

	$refine_count = 0; // count how many post meta we query
	$old_where = $where; // intercept the old where statement

	if ( is_search() && isset( $_GET['s'] ) && isset( $_GET['refine_search'] ) ) {
		$query = '';
		$price_set = false;
		foreach ( $_GET as $key => $value ) {
			if ( empty( $value ) ) {
				continue;
			}
				switch ( $key ) {
					case 'cp_city_zipcode' :
						$api_key = $cp_options->api_key;
						$region = $cp_options->gmaps_region;
						$value = urlencode( $value );
						$geocode = json_decode( wp_remote_retrieve_body( wp_remote_get( "https://maps.googleapis.com/maps/api/geocode/json?address=$value&region=$region&key=$api_key" ) ) );
						if ( 'OK' == $geocode->status ) {
							$wp_query->set( 'search_geo_query', array(
								'lat' => $geocode->results[0]->geometry->location->lat,
								'lng' => $geocode->results[0]->geometry->location->lng,
								'rad' => intval( $_GET['distance'] ),
							) );
						} else {
							// Google Maps API error
						}
						break;

					case 'amount' :
					case 'price_min' :
					case 'price_max' :
						if ( $price_set ) {
							break;
						}
						$price_set = true;
						$refine_count++;
						if ( $cp_options->refine_price_slider ) {
							$value = str_replace( array( $cp_options->curr_symbol, ' ' ), '', $value );
							$value = str_replace( ' ', '', $value );
							$value = explode( '-', $value );
						} else {
							$price_min = empty( $_GET['price_min'] ) ? 0 : (int) $_GET['price_min'];
							$price_max = empty( $_GET['price_max'] ) ? 9999999999 : (int) $_GET['price_max'];
							$value = array( $price_min, $price_max );
						}

						$query .= " AND (";
						$query .= "(mt" . $refine_count . ".meta_key = 'cp_price')";
						$query .= " AND (CAST(mt" . $refine_count . ".meta_value AS SIGNED) BETWEEN '$value[0]' AND '$value[1]')";
						$query .= ")";
						break;

					default :
						if ( 'cp_' == substr( $key, 0, 3 ) ) {
							$value = wp_kses_post( $value );
							$field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->cp_ad_fields WHERE field_name = %s", $key ) );
							if ( $field === null ) {
								break;
							}
							$refine_count++;
							if ( is_array( $value ) ) {
								$value = implode( "','", $value );
							}

							$compare = ( in_array( $field->field_type, array( 'radio', 'checkbox', 'drop-down' ) ) ) ? "IN ('$value')" : "LIKE '%$value%'";

							$query .= " AND (";
							$query .= "(mt" . $refine_count . ".meta_key = '$key')";
							$query .= " AND (CAST(mt" . $refine_count . ".meta_value AS CHAR) $compare)";
							$query .= ")";
						}
						break;
				}

		}

		$geo_query = $wp_query->get( 'search_geo_query' );
		if ( $geo_query ) {
			extract( $geo_query, EXTR_SKIP );
			$R = ( 'mi' == $cp_options->distance_unit ) ? 3959 : 6371;
			$query .= $wpdb->prepare( " AND ( %d * acos( cos( radians(%f) ) * cos( radians(lat) ) * cos( radians(lng) - radians(%f) ) + sin( radians(%f) ) * sin( radians(lat) ) ) ) < %d", $R, $lat, $lng, $lat, $rad );
		}

		if ( ! empty( $query ) ) {
			$where .= $query;
		}

		remove_filter( 'posts_where', 'custom_search_refine_where' );
	}

	return $where;
}


/**
 * Joins additional tables in refine search queries.
 *
 * @param string $join
 *
 * @return string
 */
function custom_search_refine_join( $join ) {
	global $wpdb, $wp_query, $refine_count;

	if ( is_search() && isset( $_GET['s'] ) && isset( $_GET['refine_search'] ) ) {

		$geo_query = $wp_query->get( 'search_geo_query' );
		if ( $geo_query ) {
			$table = $wpdb->cp_ad_geocodes;
			$join .= " INNER JOIN $table ON ($wpdb->posts.ID = $table.post_id)";
		}

		if ( isset( $refine_count ) && is_numeric( $refine_count ) && $refine_count > 0 ) {
			for ( $i = 1; $i <= $refine_count; $i++ ) {
				$join .= " INNER JOIN $wpdb->postmeta AS mt".$i." ON ($wpdb->posts.ID = mt".$i.".post_id) ";
			}
		}

		remove_filter( 'posts_join', 'custom_search_refine_join' );
	}

	return $join;
}


/**
 * Sets search page when the search term is empty.
 *
 * @param object $query
 *
 * @return object
 */
function cp_handle_empty_search_term( $query ) {

	if ( isset( $_GET['s'] ) && empty( $_GET['s'] ) && $query->is_main_query() ) {
		$query->is_search = true;
		$query->is_home = false;
	}

	return $query;
}


/**
 * Adds query var for search functions.
 *
 * @return void
 */
function cp_add_query_vars() {
	global $wp;
	$wp->add_query_var( 'scat' );
}
