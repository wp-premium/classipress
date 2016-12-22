<?php

// include form values
require_once ('values.php');

add_action( 'admin_menu', 'appthemes_admin_options', 11 );
add_action( 'admin_head', 'cp_ajax_sortable_js' );
add_action( 'wp_ajax_cp_ajax_update', 'cp_ajax_sort_callback' );

do_action( 'appthemes_add_submenu_page_content' );


/**
 * Setup admin menu pages for Form Layouts & Custom Fields.
 *
 * @return void
 */
function appthemes_admin_options() {
	global $page_hook, $submenu;

	$pages = array( 'app-settings', 'app-email', 'app-pricing', 'layouts', 'fields' );

	if ( ! empty( $_GET['page'] ) && in_array( $_GET['page'], $pages ) ) {
		appthemes_add_instance( array( 'APP_ToolTips' => array( $page_hook ) ) );
	}

	add_submenu_page( 'edit.php?post_type='.APP_POST_TYPE, __( 'Forms', APP_TD ), __('Forms', APP_TD ), 'manage_options', 'layouts', 'cp_form_layouts' );
	add_submenu_page( 'edit.php?post_type='.APP_POST_TYPE, __( 'Custom Fields', APP_TD ), __( 'Custom Fields', APP_TD ), 'manage_options', 'fields', 'cp_custom_fields' );

	// re-order the menu items
	$submenu['edit.php?post_type='.APP_POST_TYPE] = _cp_reorder_menu( $submenu['edit.php?post_type='.APP_POST_TYPE], 'layouts', 2 );
	$submenu['edit.php?post_type='.APP_POST_TYPE] = _cp_reorder_menu( $submenu['edit.php?post_type='.APP_POST_TYPE], 'fields', 3 );

	do_action( 'appthemes_add_submenu_page' );
}

/**
 * Allows specifying the menu position for a given menu slug and retrieve it re-ordered.
 *
 * @since 3.5
 */
function _cp_reorder_menu( $items, $menu_slug, $new_pos ) {

	$items_new = array();

	$index = 0;

	foreach( $items as $item ) {

		// check the menu slug name
		if ( $menu_slug != $item[2] ) {

			// shift the item on the requested position
			if ( $new_pos == $index ) {
				$items_new[ ++$index ] = $item;
			} else {
				$items_new[ $index ] = $item;
			}

		} else {
			$items_new[ $new_pos ] = $item;
		}

		$index++;
	}

	if ( ! empty( $items_new ) ) {
		ksort( $items_new );
	} else {
		$items_new = $items;
	}

	return $items_new;
}

/**
 * Adds into admin head a column sorting JS.
 *
 * @return void
 */
function cp_ajax_sortable_js() {
?>
<script type="text/javascript" >
jQuery(document).ready(function($) {

	// Return a helper with preserved width of cells
	var fixHelper = function(e, ui) {
		ui.children().each(function() {
			jQuery(this).width(jQuery(this).width());
			//ui.placeholder.html('<!--[if IE]><td>&nbsp;</td><![endif]-->');
		});
		return ui;
	};

	jQuery("tbody.sortable").sortable({
		helper: fixHelper,
		opacity: 0.7,
		cursor: 'move',
		// connectWith: 'table.widefat tbody',
		placeholder: 'ui-placeholder',
		forcePlaceholderSize: true,
		items: 'tr',
		update: function() {
			var results = jQuery("tbody.sortable").sortable("toArray"); // pass in the array of row ids based off each tr css id

			var data = { // pass in the action
			action: 'cp_ajax_update',
			rowarray: results
			};

			jQuery("span#loading").html('<img src="<?php echo get_template_directory_uri(); ?>/images/ajax-loading.gif" />');
			jQuery.post(ajaxurl, data, function(theResponse){
				jQuery("span#loading").html(theResponse);
			});
		}
	}).disableSelection();


});

</script>
<?php
}


/**
 * Ajax callback to update positions of form fields.
 *
 * @return void
 */
function cp_ajax_sort_callback() {
	global $wpdb;

	$counter = 1;
	foreach ( $_POST['rowarray'] as $value ) {
		$wpdb->update( $wpdb->cp_ad_meta, array( 'field_pos' => $counter ), array( 'meta_id' => $value ) );
		$counter = $counter + 1;
	}
	die();
}


/**
 * Creates the category checklist box.
 *
 * @param array $checkedcats
 * @param string $exclude (optional)
 *
 * @return string
 */
function cp_category_checklist( $checkedcats, $exclude = '' ) {

	$walker = new Walker_Category_Checklist;

	$args = array();

	if ( is_array( $checkedcats ) ) {
		$args['selected_cats'] = $checkedcats;
	} else {
		$args['selected_cats'] = array();
	}

	$args['popular_cats'] = array();
	$categories = get_categories( array(
		'hide_empty' => 0,
		'taxonomy' 	 => APP_TAX_CAT,
		'exclude' 	 => $exclude,
	) );

	return call_user_func_array( array( &$walker, 'walk' ), array( $categories, 0, $args ) );
}


/**
 * Returns a comma-separated list of categories IDs that should be excluded.
 *
 * @param int $id (optional)
 *
 * @return string
 */
function cp_exclude_cats( $id = null ) {
	global $wpdb;

	$output = array();

	if ( $id ) {
		$sql = $wpdb->prepare( "SELECT form_cats FROM $wpdb->cp_ad_forms WHERE id != %s", $id );
	} else {
		$sql = "SELECT form_cats FROM $wpdb->cp_ad_forms";
	}

	$records = $wpdb->get_results( $sql );

	if ( $records ) {
		foreach ( $records as $record ) {
			$output[] = implode( ',', unserialize( $record->form_cats ) );
		}
	}

	$exclude = cp_unique_str( ',', ( join( ',', $output ) ) );

	return $exclude;
}


/**
 * Returns a comma-separated list of categories links that match form categories.
 *
 * @param string $form_cats A comma-separated list of categories IDs
 *
 * @return string
 */
function cp_match_cats( $form_cats ) {
	$out = array();

	$terms = get_terms( APP_TAX_CAT, array( 'include' => $form_cats, 'hide_empty' => false ) );

	if ( $terms ) {
		foreach ( $terms as $term ) {
			$out[] = edit_term_link( $term->name, '', '', $term, false );
		}
	}

	return join( ', ', $out );
}


/**
 * Returns separated string filtered from duplicates.
 *
 * @param string $separator A separator used in string
 * @param string $str A separated string
 *
 * @return string
 */
function cp_unique_str( $separator, $str ) {
	$str_arr = explode( $separator, $str );
	$result = array_unique( $str_arr );
	$unique_str = implode( ',', $result );

	return $unique_str;
}


/**
 * Returns unique custom field name.
 *
 * @param string $name
 * @param string $table (optional)
 * @param bool $random (optional)
 *
 * @return string
 */
function cp_make_custom_name( $name, $table = '', $random = false ) {
	global $wpdb;
	$not_unique = false;

	$custom_name = appthemes_clean( $name );
	$custom_name = preg_replace( '/[^a-zA-Z0-9\s]/', '', $custom_name );
	if ( empty( $custom_name ) || $random ) {
		$custom_name = 'id_' . rand( 1, 1000 );
	}
	$custom_name = strtolower( substr( $custom_name, 0, 30 ) );
	$custom_name = 'cp_' . str_replace( ' ', '_', $custom_name );

	if ( $table == 'fields' ) {
		$not_unique = $wpdb->get_var( $wpdb->prepare( "SELECT field_name FROM $wpdb->cp_ad_fields WHERE field_name = %s", $custom_name ) );
	}

	if ( $table == 'forms' ) {
		$not_unique = $wpdb->get_var( $wpdb->prepare( "SELECT form_name FROM $wpdb->cp_ad_forms WHERE form_name = %s", $custom_name ) );
	}

	if ( $not_unique ) {
		return cp_make_custom_name( $name, $table, true );
	}

	return $custom_name;
}


/**
 * Deletes the custom form and the meta custom field data.
 *
 * @param int $form_id
 *
 * @return void
 */
function cp_delete_form( $form_id ) {
	global $wpdb;

	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->cp_ad_forms WHERE id = %s", $form_id ) );
	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->cp_ad_meta WHERE form_id = %s", $form_id ) );
}


/**
 * Displays admin formbuilder.
 *
 * @param array $form_fields
 *
 * @return void
 */
function cp_admin_formbuilder( $form_fields ) {

	$admin_fields_url = get_admin_url( '', 'edit.php?post_type='.APP_POST_TYPE.'&amp;page=layouts' );

	foreach ( $form_fields as $field ) :
	?>
		<tr class="even" id="<?php echo esc_attr( $field->meta_id ); ?>"><!-- id needed for jquery sortable to work -->
			<td class="column-form-preview"><?php echo esc_html( translate( $field->field_label, APP_TD ) ); ?></td>
			<td class="column-form-field">
				<p class="mobile-only"><?php echo $field->field_label; ?></p>
				<?php
				switch ( $field->field_type ) {
					case 'text box':
					?>
						<input name="<?php echo esc_attr( $field->field_name ); ?>" type="text" value="" disabled />
					<?php
					break;

					case 'text area':
					?>
						<textarea class="code" rows="4" cols="40" disabled></textarea>
					<?php
					break;

					case 'radio':
						$options = cp_explode( ',', $field->field_values );
						foreach ( $options as $label ) {
						?>
							<input type="radio" name="radiobutton" value="" disabled />&nbsp;<?php echo $label; ?><br />
						<?php
						}
					break;

					case 'checkbox':
						$options = cp_explode( ',', $field->field_values );
						foreach ( $options as $label ) {
						?>
							<input type="checkbox" name="checkbox" value="" disabled />&nbsp;<?php echo $label; ?><br />
						<?php
						}
					break;

					default: // used for drop-downs, radio buttons, and checkboxes
					?>
						<select name="dropdown">
							<?php
							$options = cp_explode( ',', $field->field_values );

							foreach ( $options as $option ) {
							?>
								<option value="<?php echo esc_attr( $option ); ?>" disabled><?php echo $option; ?></option>
							<?php
							}
							?>
						</select>
					<?php
				} //end switch
				?>
			</td>

			<td style="text-align:center;">
				<?php
				// only show the advanced search checkbox for price, city, and zipcode since they display the sliders
				// all other text fields are not intended for advanced search use
				$ad_search = '';
				if ( $field->field_name == 'cp_price' || $field->field_name == 'cp_city' || $field->field_name == 'cp_zipcode' ) {
					$ad_search = '';
				} else if ( $field->field_perm == 1 ) {
					$ad_search = 'disabled="disabled"';
				}
				?>

				<p class="mobile-only">&nbsp;</p>
				<input type="checkbox" name="<?php echo esc_attr( $field->meta_id ); ?>[field_search]" id="" <?php if ( $field->field_search ) echo 'checked="yes"' ?> <?php if ( $field->field_search ) echo 'checked="yes"' ?> <?php echo $ad_search; ?> value="1" style="" />
			</td>

			<td style="text-align:center;">
				<p class="mobile-only">&nbsp;</p>
				<input type="checkbox" name="<?php echo esc_attr( $field->meta_id ); ?>[field_req]" id="" <?php if ( $field->field_req ) echo 'checked="yes"' ?> <?php if ( $field->field_req ) echo 'checked="yes"' ?> <?php if ( $field->field_perm == 1 ) echo 'disabled="disabled"'; ?> value="1" style="" />
				<?php if ($field->field_perm == 1) { ?>
					<input type="hidden" name="<?php echo esc_attr( $field->meta_id ); ?>[field_req]" checked="yes" value="1" />
				<?php } ?>
			</td>

			<td style="text-align:center;">
				<p class="mobile-only">&nbsp;</p>
				<input type="hidden" name="id[]" value="<?php echo esc_attr( $field->meta_id ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( $field->meta_id ); ?>[id]" value="<?php echo esc_attr( $field->meta_id ); ?>" />

				<?php if ( $field->field_perm == 1 ) { ?>
					<i class="dashicons-before custom-forms-ico remove remove-disabled" title="<?php _e( 'Cannot remove from layout', APP_TD ); ?>"></i>
				<?php } else { ?>
					<a onclick="return confirmBeforeRemove();" href="<?php echo esc_url( add_query_arg( array( 'action' => 'formbuilder', 'id' => $field->form_id, 'del_id' =>  $field->meta_id, 'title' => urlencode( $_GET['title'] ) ), $admin_fields_url ) ); ?>"><i class="dashicons-before custom-forms-ico remove" title="<?php esc_attr_e( 'Remove from layout', APP_TD ); ?>"></i></a>
				<?php } ?>
			</td>
		</tr>

	<?php
	endforeach;
}


/**
 * Adds the default form fields when a form layout is created.
 *
 * @param int $form_id
 *
 * @return void
 */
function cp_add_core_fields( $form_id ) {
	global $wpdb;

	// check to see if any rows already exist for this form. If so, don't insert any data
	$wpdb->get_results( $wpdb->prepare( "SELECT form_id FROM $wpdb->cp_ad_meta WHERE form_id = %d", $form_id ) );
	if ( $wpdb->num_rows > 0 ) {
		return;
	}

	// get core fields
	$fields = $wpdb->get_results( "SELECT * FROM $wpdb->cp_ad_fields WHERE field_core = '1' ORDER BY field_id ASC" );

	if ( ! $fields ) {
		return;
	}

	$position = 1;

	foreach ( $fields as $field ) {

		$data = array(
			'form_id' => $form_id,
			'field_id' => $field->field_id,
			'field_req' => $field->field_req,
			'field_pos' => $position,
		);
		$insert = $wpdb->insert( $wpdb->cp_ad_meta, $data );

	$position++;
	}

}


/**
 * Creates a form for adding/editing form layout or form field.
 */
function cp_admin_db_fields( $options, $cp_table = '', $cp_id = '' ) {
	global $wpdb;

	$action = 'new';

	if ( $cp_table ) {

		$action = 'edit';

		// gat all the admin fields
		$results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->prefix . $cp_table . " WHERE ". $cp_id ." = %d", $_GET['id'] ) );

		if ( ! $results ) {
			return;
		}

	}

	$field_type = ( ! empty( $results->field_type ) ) ? $results->field_type : '';
	$field_perm = ( ! empty( $results->field_perm ) ) ? $results->field_perm : '';

?>

	<table class="form-table cp-custom">
		<tbody>
<?php
		foreach ( $options as $value ) {

			if ( empty( $value['tip'] ) ) {
				$tooltip = '';
			} else {
				$tooltip  = html( 'i', array(
					'class' => 'at at-tip',
					'data-tooltip' => APP_ToolTips::supports_wp_pointer() ? $value['tip'] : __( 'Click for more info', APP_TD ),
				) );

				if ( ! APP_ToolTips::supports_wp_pointer() ) {
					$tooltip .= html( "div class='tip-content'", $value['tip'] );
				}
			}

			switch( $value['type'] ) {
				case 'title':
			?>
					<thead>
						<tr>
							<th scope="col"><?php echo esc_html( $value['name'] ); ?></th>
							<th class="tip">&nbsp;</th>
							<th scope="col"><?php if ( isset( $value['desc'] ) ) echo $value['desc']; ?>&nbsp;</th>
						</tr>
					</thead>
			<?php
					break;

				case 'text':
					// don't show the meta name field used by WP. This is automatically created by CP.
					if ( 'new' == $action && $value['id'] == 'field_name' ) {
						break;
					}

					$args = array(
						'name'  => $value['id'],
						'id'    => $value['id'],
						'type'  => $value['type'],
						'class' => array(),
						'style' => $value['css'],
					);

					if ( 'edit' == $action ) {
						$args['value'] = $results->{$value['id']};
					} else {
						$args['value'] = ( get_option( $value['id'] ) ) ? get_option( $value['id'] ) : $value['std'];
					}

					if ( ! empty( $value['req'] ) ) {
						$args['class'][] = 'required';
					}

					if ( ! empty( $value['altclass'] ) ) {
						$args['class'][] = $value['altclass'];
					}

					$args['class'] = implode( ' ', $args['class'] );

					if ( ! empty( $value['min'] ) ) {
						$args['minlength'] = $value['min'];
					}

					if ( $value['id'] == 'field_name' ) {
						$args['readonly'] = 'readonly';
					}
			?>
					<tr <?php echo ( $value['vis'] == '0' ? 'id="' . ( 'edit' == $action ? esc_attr( $value['id'] ) . '_row"' : ( ! empty( $value['visid'] ) ? $value['visid'] : 'field_values' ) ) . '" style="display:none;" ' : 'id="' . esc_attr( $value['id'] ) . '_row"' ); ?>>
						<th scope="row app-row">
							<label for="<?php esc_attr( $value['name'] );?>"><?php echo esc_html( $value['name'] ); ?></label><?php echo $tooltip; ?>
						</th>
						<td>
							<label>
								<?php echo html( 'input', $args ); ?>
								<p class="description"><?php echo $value['desc']; ?></p>
							</label>
						</td>
					</tr>
			<?php
					break;

				case 'select':
			?>
					<tr id="<?php echo $value['id']; ?>_row">
						<th scope="row app-row">
							<label for="<?php esc_attr( $value['name'] );?>"><?php echo esc_html( $value['name'] ); ?></label><?php echo $tooltip; ?>
						</th>
						<td>
							<label>
								<select <?php if ( $value['js'] ) echo $value['js']; ?> <?php disabled( in_array( $field_perm, array( 1, 2 ) ) ); ?> name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>">
									<?php foreach ( $value['options'] as $key => $val ) { ?>
										<?php if ( 'edit' == $action ): ?>
											<option value="<?php echo esc_attr( $key ); ?>"<?php if ( isset( $results->{$value['id']} ) && $results->{$value['id']} == $key ) { selected( true ); $field_type_out = $field_type; } ?>><?php echo $val; ?></option>
										<?php else: ?>
											<option value="<?php echo esc_attr( $key ); ?>" <?php selected( get_option( $value['id'] ) == $key ); ?>><?php echo $val; ?></option>
										<?php endif; ?>
									<?php } ?>
								</select>
								<p class="description"><?php echo $value['desc']; ?></p>
								<?php
									// have to submit this field as a hidden value if perms are 1 or 2 since the DISABLED option won't pass anything into the $_POST
									if ( in_array( $field_perm, array( 1, 2 ) ) ) {
										echo html( 'input', array( 'type' => 'hidden', 'name' => esc_attr( $value['id'] ), 'value' => esc_attr( $field_type_out ) ) );
									}
								?>
							</label>
						</td>
					</tr>
			<?php
					break;

				case 'textarea':
					$args = array();

					$args['class'] = array();

					if ( ! empty( $value['altclass'] ) ) {
						$args['class'][] = $value['altclass'];
					}

					if ( 'edit' == $action ) {
						$args['value'] = $results->{$value['id']};
					} else {
						$args['value'] = get_option( $value['id'] );
					}
			?>
					<tr id="<?php echo esc_attr( $value['id'] ); ?>_row"<?php if ( $value['id'] == 'field_values' ) { ?> style="display: none;" <?php } ?>>
						<th scope="row app-row">
							<label for="<?php esc_attr( $value['name'] );?>"><?php echo esc_html( $value['name'] ); ?></label><?php echo $tooltip; ?>
						</th>
						<td>
							<label>
								<textarea rows="10" cols="50" class="<?php echo implode( ' ' , $args['class'] ); ?>" <?php if ( ! empty( $field_perm ) && in_array( $field_perm, array( 1, 2 ) ) && ! in_array( $value['id'], array( 'field_tooltip', 'field_values' ) ) ) { ?>readonly="readonly"<?php } ?> name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>"><?php echo esc_textarea( $args['value'] ); ?></textarea>
								<p class="description"><?php echo $value['desc']; ?></p>
							</label>
						</td>
					</tr>
			<?php
					break;

				case 'checkbox':
					if ( 'edit' == $action ) {
						$args['value'] = $results->{$value['id']};
					} else {
						$args['value'] = get_option( $value['id'] );
					}
			?>
					<tr id="<?php echo $value['id']; ?>_row">
						<th scope="row app-row">
							<label for="<?php esc_attr( $value['name'] );?>"><?php echo esc_html( $value['name'] ); ?></label><?php echo $tooltip; ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" value="1" style="<?php echo $value['css']; ?>" <?php checked( ! empty( $args['value'] ) ); ?> />
								<p class="description"><?php echo $value['desc']; ?></p>
							</label>
					</tr>
			<?php
					break;

				case 'cat_checklist':
			?>
					<tr id="<?php echo $value['id']; ?>_row">
						<th scope="row app-row">
							<label for="<?php esc_attr( $value['name'] );?>"><?php echo esc_html( $value['name'] ); ?></label><?php echo $tooltip; ?>
						</th>
						<td class="forminp">
							<label>
								<div id="form-categorydiv">
									<div class="tabs-panel" id="categories-all" style="<?php echo $value['css']; ?>">
										<ul class="list:category categorychecklist form-no-clear" id="categorychecklist">
											<?php if ( 'edit' == $action ): ?>
												<?php echo cp_category_checklist( unserialize( $results->form_cats ), cp_exclude_cats( $results->id ) ); ?>
											<?php else: ?>
												<?php $catcheck = cp_category_checklist( 0, cp_exclude_cats() ); ?>
												<?php if ( $catcheck ) { echo $catcheck; } else { wp_die( '<p style="color:red;">' . __( 'All your categories are currently being used. You must remove at least one category from another form layout before you can continue.', APP_TD ) . '</p>' ); } ?>
											<?php endif; ?>
										</ul>
									</div>
									<a href="#" class="checkall"><?php _e( 'check all', APP_TD ); ?></a>
								</div>
								<p class="description"><?php echo $value['desc']; ?></p>
							</label>
						</td>
					</tr>
			<?php
					break;
			} // end switch

		} // endforeach
?>
		</tbody>
	</table>
<?php
}


/**
 * Handles form layouts admin page.
 *
 * @return void
 */
function cp_form_layouts() {
	global $options_new_form, $wpdb, $current_user;

	$current_user = wp_get_current_user();

	// check to prevent php "notice: undefined index" msg when php strict warnings is on
	if ( isset( $_GET['action'] ) ) $theswitch = $_GET['action']; else $theswitch ='';

		$admin_fields_url = get_admin_url( '', 'edit.php?post_type='.APP_POST_TYPE.'&amp;page=layouts' );
?>

		<script type="text/javascript">
		/* <![CDATA[ */
		/* initialize the form validation */
		jQuery(document).ready(function($) {
			$("#mainform").validate({errorClass: "invalid"});
		});
		/* ]]> */
		</script>

		<?php
		switch ( $theswitch ) {
			case 'addform':
			?>

				<div class="wrap">
					<h2><?php _e( 'New Form Layout', APP_TD ); ?></h2>

					<?php
					// check and make sure the form was submitted and the hidden fcheck id matches the cookie fcheck id
					if ( isset($_POST['submitted']) ) {

						if ( !isset($_POST['post_category']) ) {
							wp_die( '<p style="color:red;">' . __( 'Error: Please select at least one category.', APP_TD ) . " <a href='#' onclick='history.go(-1);return false;'>" . __( 'Go back', APP_TD ) . '</a></p>' );
						}

						$data = array(
							'form_name' => cp_make_custom_name( $_POST['form_label'], 'forms' ),
							'form_label' => appthemes_clean( $_POST['form_label'] ),
							'form_desc' => appthemes_clean( $_POST['form_desc'] ),
							'form_cats' => serialize( $_POST['post_category'] ),
							'form_status' => appthemes_clean( $_POST['form_status'] ),
							'form_owner' => appthemes_clean( $_POST['form_owner'] ),
							'form_created' => current_time( 'mysql' ),
						);

						$insert = $wpdb->insert( $wpdb->cp_ad_forms, $data );

						if ( $insert ) {
						?>

							<p style="text-align:center;padding-top:50px;font-size:22px;"><?php _e( 'Creating your form.....', APP_TD ); ?><br /><br /><img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" /></p>
							<meta http-equiv="refresh" content="0; URL=<?php echo $admin_fields_url; ?>">

						<?php
						} // end $insert

					} else {
					?>

				<form method="post" id="mainform" action="">

					<?php echo cp_admin_db_fields( $options_new_form ); ?>

					<p class="submit"><input class="btn button-primary" name="save" type="submit" value="<?php _e( 'Create New Form', APP_TD ); ?>" />&nbsp;&nbsp;&nbsp;
					<input class="btn button-secondary" name="cancel" type="button" onClick="location.href='<?php echo $admin_fields_url; ?>'" value="<?php esc_attr_e( 'Cancel', APP_TD ); ?>" /></p>
					<input name="submitted" type="hidden" value="yes" />
					<input name="form_owner" type="hidden" value="<?php echo $current_user->user_login; ?>" />

				</form>

			<?php
			} // end isset $_POST
			?>

			</div><!-- end wrap -->

		<?php
		break;

			case 'editform':
			?>

				<div class="wrap">
					<h2><?php _e( 'Edit Form Properties', APP_TD ); ?></h2>

					<?php
					if ( isset( $_POST['submitted'] ) && $_POST['submitted'] == 'yes' ) {

						if ( ! isset( $_POST['post_category'] ) ) {
							wp_die( '<p style="color:red;">' . __( 'Error: Please select at least one category.', APP_TD ) . " <a href='#' onclick='history.go(-1);return false;'>" . __( 'Go back', APP_TD ) . '</a></p>' );
						}

						$data = array(
							'form_label' => appthemes_clean($_POST['form_label']),
							'form_desc' => appthemes_clean($_POST['form_desc']),
							'form_cats' => serialize($_POST['post_category']),
							'form_status' => appthemes_clean($_POST['form_status']),
							'form_owner' => appthemes_clean($_POST['form_owner']),
							'form_modified' => current_time('mysql'),
						);

						$wpdb->update( $wpdb->cp_ad_forms, $data, array( 'id' => $_GET['id'] ) );

					?>

						<p style="text-align:center;padding-top:50px;font-size:22px;"><?php _e( 'Saving your changes.....', APP_TD ); ?><br /><br /><img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" /></p>
						<meta http-equiv="refresh" content="0; URL=<?php echo $admin_fields_url; ?>">

					<?php
					} else {
					?>

				<form method="post" id="mainform" action="">

					<?php echo cp_admin_db_fields( $options_new_form, 'cp_ad_forms', 'id' ); ?>

					<p class="submit"><input class="btn button-primary" name="save" type="submit" value="<?php _e( 'Save changes', APP_TD ); ?>" />&nbsp;&nbsp;&nbsp;
					<input class="btn button-secondary" name="cancel" type="button" onClick="location.href='<?php echo $admin_fields_url; ?>'" value="<?php esc_attr_e( 'Cancel', APP_TD ); ?>" /></p>
					<input name="submitted" type="hidden" value="yes" />
					<input name="form_owner" type="hidden" value="<?php echo $current_user->user_login; ?>" />

				</form>

			<?php
			} // end isset $_POST
			?>

			</div><!-- end wrap -->

		<?php
		break;

		/**
		* Form Builder Page
		* Where fields are added to form layouts
		*/
		case 'formbuilder':
			?>
				<div class="wrap">
					<h2><?php _e( 'Edit Form Layout', APP_TD ); ?></h2>

					<?php
					// add fields to page layout on left side
					if ( isset( $_POST['field_id'] ) ) {

						// take selected checkbox array and loop through ids
						foreach ( $_POST['field_id'] as $value ) {
							$data = array(
								'form_id' => appthemes_clean( $_POST['form_id'] ),
								'field_id' => appthemes_clean( $value ),
								'field_pos' => '99',
							);

							$insert = $wpdb->insert( $wpdb->cp_ad_meta, $data );
						} // end foreach

					} // end $_POST

					// update form layout positions and required fields on left side.
					if ( isset( $_POST['formlayout'] ) ) {

						// loop through the post array and update the required checkbox and field position
						foreach ( $_POST as $key => $value ) :

							// since there's some $_POST values we don't want to process, only give us the
							// numeric ones which means it contains a meta_id and we want to update it
							if ( is_numeric($key) ) {

								// quick hack to prevent php "notice: undefined index:" msg when php strict warnings is on
								if ( ! isset( $value['field_req'] ) ) $value['field_req'] = '0';
								if ( ! isset( $value['field_search'] ) ) $value['field_search'] = '0';

								$data = array(
									'field_req' => appthemes_clean( $value['field_req'] ),
									'field_search' => appthemes_clean( $value['field_search'] ),
								);

								$wpdb->update( $wpdb->cp_ad_meta, $data, array( 'meta_id' => $key ) );

							} // end if_numeric

						endforeach; // end for each

						echo scb_admin_notice( __( 'Your changes have been saved.', APP_TD ) );

					} // end isset $_POST


			// check to prevent php "notice: undefined index" msg when php strict warnings is on
			if ( isset( $_GET['del_id'] ) ) $theswitch = $_GET['del_id']; else $theswitch ='';


			// Remove items from form layout
			if ( $theswitch ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->cp_ad_meta WHERE meta_id = %s", $_GET['del_id'] ) );
			}

			//update the forms modified date
			$data = array(
				'form_modified' => current_time('mysql'),
			);

			$wpdb->update( $wpdb->cp_ad_forms, $data, array( 'id' => $_GET['id'] ) );
			?>

			<div class="edit-fields-panel">

				<h3><?php _e( 'Form Name', APP_TD ); ?> - <?php echo ucfirst( urldecode( $_GET['title'] ) ); ?>&nbsp;&nbsp;&nbsp;&nbsp;<span id="loading"></span></h3>
				<br/>

				<form method="post" id="mainform" action="">
					<table class="widefat wp-list-table fixed striped">
						<thead>
							<tr>
								<th scope="col" class="manage-columns column-form-preview"><?php _e( 'Form Preview', APP_TD ); ?></th>
								<th scope="col" class="manage-columns column-form-field">&nbsp;</th>
								<th scope="col" class="manage-columns column-form-options" style="text-align:center;" title="<?php _e( 'Show field in the category refine search sidebar', APP_TD ); ?>"><?php _e( 'Adv. Search', APP_TD ); ?></th>
								<th scope="col" class="manage-columns column-form-required" style="text-align:center;"><?php _e( 'Required', APP_TD ); ?></th>
								<th scope="col" class="manage-columns column-form-remove" style="text-align:center;"><?php _e( 'Remove', APP_TD ); ?></th>
							</tr>
						</thead>

						<tbody class="sortable">
							<?php

								// If this is the first time this form is being customized then auto
								// create the core fields and put in cp_meta db table
								echo cp_add_core_fields( $_GET['id'] );

								$form_fields = cp_get_custom_form_fields( $_GET['id'] );

								if ( $form_fields ) {
									echo cp_admin_formbuilder( $form_fields );
								} else {
							?>
									<tr>
										<td colspan="5" style="text-align: center;"><p><br /><?php _e( 'No fields have been added to this form layout yet.', APP_TD ); ?><br /><br /></p></td>
									</tr>
							<?php
								} // end $results
							?>
						</tbody>
					</table>

					<div class="clear"></div>

					<p class="submit">
						<input class="btn button-primary" name="save" type="submit" value="<?php _e( 'Save Changes', APP_TD ); ?>" />&nbsp;&nbsp;&nbsp;
						<input class="btn button-secondary" name="cancel" type="button" onClick="location.href='<?php echo $admin_fields_url; ?>'" value="<?php esc_attr_e( 'Cancel', APP_TD ); ?>" />
						<input name="formlayout" type="hidden" value="yes" />
						<input name="form_owner" type="hidden" value="<?php $current_user->user_login; ?>" />
					</p>
				</form>
			</div>

			<div class="fields-panel">

				<h3><?php _e( 'Available Fields', APP_TD ); ?></h3>
				<br/>

				<form method="post" id="mainform" action="">
					<table class="widefat wp-list-table fixed striped">
						<thead>
							<tr>
								<th id="cb" scope="col" class="manage-column column-cb check-column"><input type="checkbox"/></th>
								<th scope="col" class="manage-column column-field-name"><?php _e( 'Field Name', APP_TD ); ?></th>
								<th scope="col" class="manage-column column-field-type"><?php _e( 'Type', APP_TD ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							// Select all available fields not currently on the form layout.
							// Also exclude any core fields since they cannot be removed from the layout.
							$sql = $wpdb->prepare( "SELECT f.field_id,f.field_label,f.field_type "
								 . "FROM $wpdb->cp_ad_fields f "
								 . "WHERE f.field_id "
								 . "NOT IN (SELECT m.field_id "
								 . "FROM $wpdb->cp_ad_meta m "
								 . "WHERE m.form_id =  %s) "
								 . "AND f.field_perm <> '1'",
								 $_GET['id']);

							$results = $wpdb->get_results( $sql );

							if ( $results ) {

								foreach ( $results as $result ) {
								?>
									<tr class="even">
										<th class="check-column" scope="row"><input type="checkbox" value="<?php echo esc_attr( $result->field_id ); ?>" name="field_id[]"/></th>
										<td class="column-field-name"><?php echo esc_html( translate( $result->field_label, APP_TD ) ); ?></td>
										<td class="column-field-type"><?php echo $result->field_type; ?></td>
									</tr>
								<?php
								} // end foreach

							} else {
							?>
								<tr>
									<td colspan="4" style="text-align: center;"><p><br /><?php _e( 'No fields are available.', APP_TD ); ?><br /><br /></p></td>
								</tr>
							<?php
							} // end $results
							?>
						</tbody>
					</table>

					<div class="clear"></div>

					<p class="submit">
						<input class="btn button-primary" name="save" type="submit" value="<?php esc_attr_e( 'Add Fields to Form Layout', APP_TD ); ?>" />
						<input name="form_id" type="hidden" value="<?php echo esc_attr( $_GET['id'] ); ?>" />
						<input name="submitted" type="hidden" value="yes" />
					</p>
				</form>
			</div>
		</div><!-- /wrap -->
		<?php
		break;

		case 'delete':
			// delete the form based on the form id
			cp_delete_form( $_GET['id'] );
			?>
			<p style="text-align:center;padding-top:50px;font-size:22px;"><?php _e( 'Deleting form layout.....', APP_TD ); ?><br /><br /><img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" /></p>
			<meta http-equiv="refresh" content="0; URL=<?php echo $admin_fields_url; ?>">
		<?php
		break;

		default:
			$results = $wpdb->get_results( "SELECT * FROM $wpdb->cp_ad_forms ORDER BY id desc" );
		?>
			<div class="wrap">
				<h2><?php _e( 'Form Layouts', APP_TD ); ?>&nbsp;<a class="add-new-h2" href="<?php echo esc_url( add_query_arg( 'action', 'addform', $admin_fields_url ) ); ?>"><?php _e( 'Add New', APP_TD ); ?></a></h2>

				<p class="admin-msg"><?php _e( 'Form layouts allow you to create your own custom ad submission forms. Each form is essentially a container for your fields and can be applied to one or all of your categories. If you do not create any form layouts, the default one will be used. To change the default form, create a new form layout and apply it to all categories.', APP_TD ); ?></p>

				<table id="tblspacer" class="wp-list-table widefat fixed">

					<thead>
						<tr>
							<th scope="col" style="width:35px;">#</th>
							<th scope="col" class="manage-column column-name"><?php _e( 'Name', APP_TD ); ?></th>
							<th scope="col" class="manage-column column-description"><?php _e( 'Description', APP_TD ); ?></th>
							<th scope="col" class="manage-column column-categories"><?php _e( 'Categories', APP_TD ); ?></th>
							<th scope="col" class="manage-column column-modified" style="width:150px;"><?php _e( 'Modified', APP_TD ); ?></th>
							<th scope="col" class="manage-column column-status" style="width:75px;"><?php _e( 'Status', APP_TD ); ?></th>
							<th scope="col" class="manage-column column-actions" style="text-align:center;width:100px;"><?php _e( 'Actions', APP_TD ); ?></th>
						</tr>
					</thead>

					<?php
					if ( $results ) {
					  $rowclass = '';
					  $i=1;
					?>

					<tbody id="list">
					<?php
						foreach ( $results as $result ) {
							$rowclass = 'even' == $rowclass ? 'alt' : 'even';
					  ?>
							<tr class="<?php echo $rowclass; ?>">
								<td style="padding-left:10px;"><?php echo $i; ?>.</td>
								<td class="column-name"><a href="<?php echo esc_url( add_query_arg( array( 'action' => 'editform', 'id' => $result->id ), $admin_fields_url ) ); ?>"><strong><?php echo $result->form_label; ?></strong></a></td>
								<td class="column-description"><?php echo $result->form_desc; ?></td>
								<td class="column-categories"><?php echo cp_match_cats( unserialize($result->form_cats) ); ?></td>
								<td class="column-modified"><?php echo appthemes_display_date( '0000-00-00 00:00:00' != $result->form_modified ? $result->form_modified : $result->form_created ); ?> <?php _e( 'by', APP_TD ); ?> <?php echo $result->form_owner; ?></td>
								<td class="column-status"><?php echo cp_get_status_i18n( $result->form_status ); ?></td>
								<td class="column-actions" style="text-align:center"><a href="<?php echo esc_url( add_query_arg( array( 'action' => 'formbuilder', 'id' => $result->id, 'title' => urlencode( $result->form_label ) ), $admin_fields_url ) ); ?>"><i class="dashicons-before custom-forms-ico edit-layout wp-ui-text-highlight" title="<?php _e( 'Edit form layout', APP_TD ); ?>"></i></a>&nbsp;&nbsp;&nbsp;
									<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'editform', 'id' => $result->id ), $admin_fields_url ) ); ?>"><i class="dashicons-before custom-forms-ico edit-properties wp-ui-text-highlight" title="<?php _e( 'Edit form properties', APP_TD ); ?>"></i></a>&nbsp;&nbsp;&nbsp;
									<a onclick="return confirmBeforeDelete();" href="<?php echo esc_url( add_query_arg( array( 'action' => 'delete', 'id' => $result->id ), $admin_fields_url ) ); ?>"><i class="dashicons-before custom-forms-ico remove" title="<?php _e( 'Delete form layout', APP_TD ); ?>"></i></a>
								</td>
							</tr>
					  <?php
						$i++;

						} // end for each
					  ?>
					  </tbody>
					<?php
					} else {
					?>
						<tr>
							<td colspan="7"><?php _e( 'No form layouts found.', APP_TD ); ?></td>
						</tr>
					<?php
					} // end $results
					?>
				</table>
			</div><!-- end wrap -->

		<?php
		} // end switch
		?>
		<script type="text/javascript">
			/* <![CDATA[ */
				function confirmBeforeDelete() { return confirm("<?php _e( 'Are you sure you want to delete this?', APP_TD ); ?>"); }
				function confirmBeforeRemove() { return confirm("<?php _e( 'Are you sure you want to remove this?', APP_TD ); ?>"); }
			/* ]]> */
		</script>
<?php
} // end function


/**
 * Handles form fields admin page.
 *
 * @return void
 */
function cp_custom_fields() {
	global $options_new_field, $wpdb, $current_user;

	$current_user = wp_get_current_user();
?>

	<!-- show/hide the dropdown field values tr -->
	<script type="text/javascript">
	/* <![CDATA[ */
		jQuery(document).ready(function() {
			jQuery("#mainform").validate({errorClass: "invalid"});
		});

		function show(o){
			if(o){switch(o.value){
				case 'drop-down': jQuery('#field_values_row').show(); jQuery('#field_min_length_row').hide(); break;
				case 'radio': jQuery('#field_values_row').show(); jQuery('#field_min_length_row').hide(); break;
				case 'checkbox': jQuery('#field_values_row').show(); jQuery('#field_min_length_row').hide(); break;
				case 'text box': jQuery('#field_min_length_row').show(); jQuery('#field_values_row').hide(); break;
				default: jQuery('#field_values_row').hide(); jQuery('#field_min_length_row').hide();
			}}
		}

		//show/hide immediately on document load
		jQuery(document).ready(function() {
			show(jQuery('#field_type').get(0));
		});

		//hide unwanted options for cp_currency field
		jQuery(document).ready(function() {
			var field_name = jQuery('#field_name').val();
			if(field_name == 'cp_currency'){
				jQuery("#field_type option[value='text box']").attr("disabled", "disabled");
				jQuery("#field_type option[value='text area']").attr("disabled", "disabled");
				jQuery("#field_type option[value='checkbox']").attr("disabled", "disabled");
			}
		});
	/* ]]> */
	</script>

	<?php
	$theswitch = ( isset( $_GET['action'] ) ) ? $_GET['action'] : '';

	$admin_fields_url = get_admin_url( '', 'edit.php?post_type='.APP_POST_TYPE.'&amp;page=fields' );

	switch ( $theswitch ) {
		case 'addfield':
		?>
			<div class="wrap">
				<h2><?php _e( 'New Custom Field', APP_TD ); ?></h2>

				<?php
				// check and make sure the form was submitted
				if ( isset( $_POST['submitted'] ) ) {

					$_POST['field_search'] = ''; // we aren't using this field so set it to blank for now to prevent notice

					$data = array(
						'field_name' => cp_make_custom_name( $_POST['field_label'], 'fields' ),
						'field_label' => appthemes_clean( $_POST['field_label'] ),
						'field_desc' => appthemes_clean( $_POST['field_desc'] ),
						'field_tooltip' => appthemes_clean( $_POST['field_tooltip'] ),
						'field_type' => appthemes_clean( $_POST['field_type'] ),
						'field_values' => appthemes_clean( $_POST['field_values'] ),
						'field_search' => appthemes_clean( $_POST['field_search'] ),
						'field_owner' => appthemes_clean( $_POST['field_owner'] ),
						'field_created' => current_time( 'mysql' ),
						'field_modified' => current_time( 'mysql' ),
					);

					$insert = $wpdb->insert( $wpdb->cp_ad_fields, $data );

					if ( $insert ) :
						do_action( 'cp_custom_fields', 'addfield', $wpdb->insert_id );
					?>
						<p style="text-align:center;padding-top:50px;font-size:22px;"><?php _e( 'Creating your field.....', APP_TD ); ?>
							<br/><br/><img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" />
						</p>
						<meta http-equiv="refresh" content="0; URL=<?php echo $admin_fields_url; ?>" >
					<?php
					endif;
					die;

				} else {
				?>
					<form method="post" id="mainform" action="">

						<?php cp_admin_db_fields( $options_new_field ); ?>

						<p class="submit">
							<input class="btn button-primary" name="save" type="submit" value="<?php esc_attr_e( 'Create New Field', APP_TD ); ?>" />&nbsp;&nbsp;&nbsp;
							<input class="btn button-secondary" name="cancel" type="button" onClick="location.href='<?php echo $admin_fields_url; ?>'" value="<?php _e( 'Cancel', APP_TD ); ?>" />
							<input name="submitted" type="hidden" value="yes" />
							<input name="field_owner" type="hidden" value="<?php echo esc_attr( $current_user->user_login ); ?>" />
						</p>
					</form>

				<?php } ?>
			</div><!-- end wrap -->
			<?php
			break;

		case 'editfield':
		?>
			<div class="wrap">
				<h2><?php _e( 'Edit Custom Field', APP_TD ); ?></h2>

				<?php
				if ( isset( $_POST['submitted'] ) && $_POST['submitted'] == 'yes' ) {

					$data = array(
						'field_name' => appthemes_clean( $_POST['field_name'] ),
						'field_label' => appthemes_clean( $_POST['field_label'] ),
						'field_desc' => appthemes_clean( $_POST['field_desc'] ),
						'field_tooltip' => esc_attr( appthemes_clean( $_POST['field_tooltip'] ) ),
						'field_type' => appthemes_clean( $_POST['field_type'] ),
						'field_values' => appthemes_clean( $_POST['field_values'] ),
						'field_min_length' => appthemes_clean( $_POST['field_min_length'] ),
						//'field_search' => appthemes_clean( $_POST['field_search'] ),
						'field_owner' => appthemes_clean( $_POST['field_owner'] ),
						'field_modified' => current_time( 'mysql' ),
					);

					$wpdb->update( $wpdb->cp_ad_fields, $data, array( 'field_id' => $_GET['id'] ) );
					do_action( 'cp_custom_fields', 'editfield', $_GET['id'] );
				?>

					<p style="text-align:center;padding-top:50px;font-size:22px;">
						<?php _e( 'Saving your changes.....', APP_TD ); ?><br /><br />
						<img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" />
					</p>
					<meta http-equiv="refresh" content="0; URL=<?php echo $admin_fields_url; ?>">

				<?php
				} else {
				?>
					<form method="post" id="mainform" action="">

						<?php cp_admin_db_fields( $options_new_field, 'cp_ad_fields', 'field_id' ); ?>

						<p class="submit">
							<input class="btn button-primary" name="save" type="submit" value="<?php _e( 'Save changes', APP_TD ); ?>" />&nbsp;&nbsp;&nbsp;
							<input class="btn button-secondary" name="cancel" type="button" onClick="location.href='<?php echo $admin_fields_url; ?>'" value="<?php _e( 'Cancel', APP_TD ); ?>" />
							<input name="submitted" type="hidden" value="yes" />
							<input name="field_owner" type="hidden" value="<?php echo $current_user->user_login; ?>" />
						</p>
					</form>
				<?php } ?>
			</div><!-- end wrap -->

			<?php
			break;


		case 'delete':

			// check and make sure this fields perms allow deletion
			$sql = $wpdb->prepare( "SELECT field_perm FROM $wpdb->cp_ad_fields WHERE field_id = %d LIMIT 1", $_GET['id'] );
			$results = $wpdb->get_row( $sql );

			// if it's not greater than zero, then delete it
			if ( ! ( $results->field_perm > 0 ) ) {
				do_action( 'cp_custom_fields', 'delete', $_GET['id'] );

				$delete = $wpdb->prepare( "DELETE FROM $wpdb->cp_ad_fields WHERE field_id = %d", $_GET['id'] );
				$wpdb->query( $delete );
			}
		?>
			<p style="text-align:center;padding-top:50px;font-size:22px;"><?php _e( 'Deleting custom field.....', APP_TD ); ?><br /><br /><img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" /></p>
			<meta http-equiv="refresh" content="0; URL=<?php echo $admin_fields_url; ?>">
			<?php
			break;


		// show the table of all custom fields
		default:
			$sql = "SELECT * FROM $wpdb->cp_ad_fields ORDER BY field_name desc";
			$results = $wpdb->get_results( $sql );
		?>
			<div class="wrap">
				<h2><?php _e( 'Custom Fields', APP_TD ); ?>&nbsp;<a class="add-new-h2" href="<?php echo esc_url( add_query_arg( 'action', 'addfield', $admin_fields_url ) ); ?>"><?php _e( 'Add New', APP_TD ); ?></a></h2>

				<p class="admin-msg"><?php _e( 'Custom fields allow you to customize your ad submission forms and collect more information. Each custom field needs to be added to a form layout in order to be visible on your website. You can create unlimited custom fields and each one can be used across multiple form layouts. It is highly recommended to NOT delete a custom field once it is being used on your ads because it could cause ad editing problems for your customers.', APP_TD ); ?></p>

				<table id="tblspacer" class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-columns column-cfield-number">#</th>
							<th scope="col" class="manage-columns column-cfield-name"><?php _e( 'Name', APP_TD ); ?></th>
							<th scope="col" class="manage-columns column-cfield-type"><?php _e( 'Type', APP_TD ); ?></th>
							<th scope="col" class="manage-columns column-cfield-description"><?php _e( 'Description', APP_TD ); ?></th>
							<th scope="col" class="manage-columns column-cfield-modified"><?php _e( 'Modified', APP_TD ); ?></th>
							<th scope="col" class="manage-columns column-cfield-actions" style="text-align: center;"><?php _e( 'Actions', APP_TD ); ?></th>
						</tr>
					</thead>

				<?php
					if ( $results ) {
				?>
						<tbody id="list">
						<?php
							$rowclass = '';
							$i = 1;

							foreach ( $results as $result ) {
								$rowclass = ( 'even' == $rowclass ) ? 'alt' : 'even';
							?>
								<tr class="<?php echo $rowclass; ?>">
									<td class="column-cfield-number" style="padding-left:10px;"><?php echo $i; ?>.</td>
									<td class="column-cfield-name"><a href="<?php echo esc_url( add_query_arg( array( 'action' => 'editfield', 'id' => $result->field_id ), $admin_fields_url ) ); ?>"><strong><?php echo esc_html( translate( $result->field_label, APP_TD ) ); ?></strong></a></td>
									<td class="column-cfield-type"><?php echo $result->field_type; ?></td>
									<td class="column-cfield-description"><?php echo esc_html( translate( $result->field_desc, APP_TD ) ); ?></td>
									<td class="column-cfield-modified"><?php echo appthemes_display_date( $result->field_modified ); ?> <?php _e( 'by', APP_TD ); ?> <?php echo $result->field_owner; ?></td>
									<td class="column-cfield-actions" style="text-align:center">
										<?php
										// show the correct edit options based on perms
										switch ( $result->field_perm ) {
											case '1': // core fields no editing
											case '2': // core fields some editing
										?>
												<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'editfield', 'id' => $result->field_id ), $admin_fields_url ) ); ?>"><i class="dashicons-before custom-forms-ico edit-properties wp-ui-text-highlight" title="<?php _e( 'Edit', APP_TD ); ?>"></i></a>&nbsp;&nbsp;&nbsp;
												<i class="dashicons-before custom-forms-ico remove remove-disabled wp-ui-text-highlight" title="<?php _e( 'Delete', APP_TD ); ?>"></i>
												<?php
												break;

											default: // regular fields full editing
										?>
												<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'editfield', 'id' => $result->field_id ), $admin_fields_url ) ); ?>"><i class="dashicons-before custom-forms-ico edit-properties wp-ui-text-highlight" title="<?php _e( 'Edit', APP_TD ); ?>"></i></a>&nbsp;&nbsp;&nbsp;
												<a onclick="return confirmBeforeDelete();" href="<?php echo esc_url( add_query_arg( array( 'action' => 'delete', 'id' => $result->field_id ), $admin_fields_url ) ); ?>"><i class="dashicons-before custom-forms-ico remove wp-ui-text-highlight" title="<?php _e( 'Delete', APP_TD ); ?>"></i></a>
												<?php
												break;

										} // endswitch
										?>
									</td>
								</tr>
							<?php
							$i++;

						} // endforeach;
						?>
						</tbody>
					<?php
					} else {
					?>
						<tr>
							<td colspan="5"><?php _e( 'No custom fields found. This usually means your install script did not run correctly. Go back and try reactivating the theme again.', APP_TD ); ?></td>
						</tr>
					<?php } ?>
				</table>
			</div><!-- end wrap -->
	<?php } ?>

	<script type="text/javascript">
	/* <![CDATA[ */
		function confirmBeforeDelete() { return confirm("<?php _e( 'WARNING: Deleting this field will prevent any existing ads currently using this field from displaying the field value. Deleting fields is NOT recommended unless you do not have any existing ads using this field. Are you sure you want to delete this field?? (This cannot be undone)', APP_TD ); ?>"); }
	/* ]]> */
	</script>
<?php
}


/**
 * Deletes all the ClassiPress database tables.
 *
 * @return void
 */
function cp_delete_db_tables() {
	global $wpdb, $app_db_tables;

	$notice = '';

	foreach ( $app_db_tables as $key => $value ) {
		$sql = "DROP TABLE IF EXISTS " . $wpdb->prefix . $value;
		$wpdb->query( $sql );

		if ( $notice ) {
			$notice .= '<br/>';
		}

		$notice .= printf( __( "Table '%s' has been deleted.", APP_TD ), $value );
	}

	echo scb_admin_notice( $notice );
}


/**
 * Deletes all the ClassiPress options.
 *
 * @return void
 */
function cp_delete_all_options() {
	global $wpdb;

	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name like 'cp_%'" );
	echo scb_admin_notice( __( 'All ClassiPress options have been deleted from the WordPress options table.', APP_TD ) );
}


/**
 * Flushes the theme transients caches.
 *
 * @return string
 */
function cp_flush_all_cache() {
	global $app_transients;

	$output = '';

	foreach ( $app_transients as $key => $value ) {
		delete_transient( $value );
		$output .= sprintf( __( "ClassiPress '%s' cache has been flushed.", APP_TD ) . '<br />', $value );
	}

	return $output;
}
