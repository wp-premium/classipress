<?php
/**
 * Custom Forms related functions.
 *
 * @package ClassiPress\Custom-Forms
 * @author  AppThemes
 * @since   ClassiPress 3.4
 */


/**
 * Returns custom form id based on category id passed in.
 *
 * @param int $category_id
 *
 * @return int
 */
function cp_get_form_id( $category_id ) {
	global $wpdb;

	if ( ! $category_id ) {
		return false;
	}

	// so lets search for a catid match and return the id if found
	$sql = "SELECT ID, form_cats FROM $wpdb->cp_ad_forms WHERE form_status = 'active'";

	$results = $wpdb->get_results( $sql );

	if ( $results ) {

		foreach ( $results as $result ) {

			// put the form_cats into an array
			$catarray = unserialize( $result->form_cats );
			if ( ! is_array( $catarray ) ) {
				continue;
			}

			// now search the array for the ad catid
			if ( in_array( $category_id, $catarray ) ) {
				return $result->ID;
			}

		}

	}

	return false;
}


/**
 * Returns form fields for given form ID.
 *
 * @param int $form_id (optional)
 *
 * @return array
 */
function cp_get_custom_form_fields( $form_id = false ) {
	global $wpdb;

	if ( ! $form_id ) {
		// get default custom form fields
		$sql = "SELECT * FROM $wpdb->cp_ad_fields WHERE field_core = '1' ORDER BY field_id asc";

	} else {
		// get custom form fields for given form ID
		$sql = $wpdb->prepare( "SELECT * "
			. "FROM $wpdb->cp_ad_fields f "
			. "INNER JOIN $wpdb->cp_ad_meta m "
			. "ON f.field_id = m.field_id "
			. "WHERE m.form_id = %d "
			. "ORDER BY m.field_pos asc",
			$form_id );

	}

	$form_fields = $wpdb->get_results( $sql );

	if ( $form_fields ) {
		return $form_fields;
	}

	return array();
}


/**
 * Builds and displays ad submission form based on passed form fields.
 *
 * @param array $results
 * @param object $post (optional)
 *
 * @return void
 */
if ( ! function_exists( 'cp_formbuilder' ) ) {
	function cp_formbuilder( $results, $post = false ) {
		global $cp_options;

		$custom_fields_array = array();

		foreach ( $results as $result ) {

			// external plugins can modify or disable field
			$result = apply_filters( 'cp_formbuilder_field', $result, $post );
			if ( ! $result ) {
				continue;
			}

			if ( appthemes_str_starts_with( $result->field_name, 'cp_' ) ) {
				$custom_fields_array[] = $result->field_name;
			}
			$post_meta_val = ( $post ) ? get_post_meta($post->ID, $result->field_name, true) : false;
	?>

			<li id="list_<?php echo esc_attr( $result->field_name ); ?>">
				<div class="labelwrapper">
					<label><?php echo esc_html( translate( $result->field_label, APP_TD ) ); ?> <?php if ( $result->field_req ) echo '<span class="colour">(*)</span>'; ?></label>
				</div>

				<?php
					$show_tooltip = !empty( $result->field_tooltip );

					if ( $show_tooltip ) {
						echo html( 'a href="#" tip="'.esc_attr( translate( $result->field_tooltip, APP_TD ) ) .'" tabindex="999"', html( 'div class="dashicons-before helpico"', '&nbsp;' ) );
					} else {

					}

					switch ( $result->field_type ) {

						case 'text box':

							if ( isset( $_POST[ $result->field_name ] ) ) {
								$value = wp_kses_post( appthemes_clean( $_POST[ $result->field_name ] ) );
							} elseif ( $result->field_name == 'post_title' && $post ) {
								$value = $post->post_title;
							} elseif ( $result->field_name == 'tags_input' && $post ) {
								$value = rtrim( trim( cp_get_the_term_list( $post->ID, APP_TAX_TAG ) ), ',' );
							} else {
								$value = $post_meta_val;
							}

							$field_class = ( $result->field_req ) ? 'text required' : 'text';
							if ( 'cp_price' == $result->field_name && $cp_options->clean_price_field ) {
								$field_class .= ' number';
							}
							$field_minlength = ( empty( $result->field_min_length ) ) ? '0' : $result->field_min_length;
							$args = array( 'value' => $value, 'name' => $result->field_name, 'id' => $result->field_name, 'type' => 'text', 'class' => $field_class, 'minlength' => $field_minlength );
							$args = apply_filters( 'cp_formbuilder_' . $result->field_name, $args, $result, $post );

							echo html( 'input', $args );

							break;

						case 'drop-down':

							$options = cp_explode( ',', $result->field_values );
							$html_options = '';

							$html_options .= html( 'option', array( 'value' => '' ), __( '-- Select --', APP_TD ) );
							foreach ( $options as $option ) {
								$args = array( 'value' => $option );
								if ( $option == $post_meta_val ) {
									$args['selected'] = 'selected';
								}
								$args = apply_filters( 'cp_formbuilder_' . $result->field_name . '_option', $args, $result, $post );
								$html_options .= html( 'option', $args, $option );
							}

							$field_class = ( $result->field_req ) ? 'dropdownlist required' : 'dropdownlist';
							$args = array( 'name' => $result->field_name, 'id' => $result->field_name, 'class' => $field_class );
							$args = apply_filters( 'cp_formbuilder_' . $result->field_name, $args, $result, $post );

							echo html( 'select', $args, $html_options );

							break;

						case 'text area':

							if ( isset( $_POST[ $result->field_name ] ) ) {
								$value = wp_kses_post( appthemes_clean( $_POST[ $result->field_name ] ) );
							} elseif ( $result->field_name == 'post_content' && $post ) {
								$value = $post->post_content;
							} else {
								$value = $post_meta_val;
							}

							$field_class = ( $result->field_req ) ? 'required' : '';
							$field_minlength = ( empty( $result->field_min_length ) ) ? '15' : $result->field_min_length;
							$args = array( 'value' => $value, 'name' => $result->field_name, 'id' => $result->field_name, 'rows' => '8', 'cols' => '40', 'class' => $field_class, 'minlength' => $field_minlength );
							$args = apply_filters( 'cp_formbuilder_' . $result->field_name, $args, $result, $post );
							$value = $args['value'];
							unset( $args['value'] );

							if ( $cp_options->allow_html && ! wp_is_mobile() ) {
								cp_editor( $value, $args );
							} else {
								echo html( 'textarea', $args, esc_textarea( $value ) );
							}

							break;

						case 'radio':

							$options = cp_explode( ',', $result->field_values );
							$options = array_map( 'trim', $options );

							$html_radio = '';
							$html_options = '';

							if ( ! $result->field_req ) {
								$args = array( 'value' => '', 'type' => 'radio', 'class' => 'radiolist', 'name' => $result->field_name, 'id' => $result->field_name );
								if ( empty( $post_meta_val ) ) {
									$args['checked'] = 'checked';
								}
								$args = apply_filters( 'cp_formbuilder_' . $result->field_name, $args, $result, $post );
								$html_radio = html( 'input', $args ) . '&nbsp;&nbsp;' . __( 'None', APP_TD );
								$html_options .= html( 'li', array(), $html_radio );
							}

							foreach ( $options as $option ) {
								$field_class = ( $result->field_req ) ? 'radiolist required' : 'radiolist';
								$args = array( 'value' => $option, 'type' => 'radio', 'class' => $field_class, 'name' => $result->field_name, 'id' => $result->field_name );
								if ( $option == $post_meta_val ) {
									$args['checked'] = 'checked';
								}
								$args = apply_filters( 'cp_formbuilder_' . $result->field_name, $args, $result, $post );
								$html_radio = html( 'input', $args ) . '&nbsp;&nbsp;' . $option;
								$html_options .= html( 'li', array(), $html_radio );
							}

							echo html( 'ol', array( 'class' => 'radios' ), $html_options );

							break;

						case 'checkbox':

							$post_meta_val = ( $post ) ? get_post_meta( $post->ID, $result->field_name, false ) : array();
							$options = cp_explode( ',', $result->field_values );
							$options = array_map( 'trim', $options );
							$optionCursor = 1;

							$html_checkbox = '';
							$html_options = '';

							foreach ( $options as $option ) {
								$field_class = ( $result->field_req ) ? 'checkboxlist required' : 'checkboxlist';
								$args = array( 'value' => $option, 'type' => 'checkbox', 'class' => $field_class, 'name' => $result->field_name . '[]', 'id' => $result->field_name . '_' . $optionCursor++ );
								if ( in_array( $option, $post_meta_val ) ) {
									$args['checked'] = 'checked';
								}
								$args = apply_filters( 'cp_formbuilder_' . $result->field_name, $args, $result, $post );
								$html_checkbox = html( 'input', $args ) . '&nbsp;&nbsp;' . $option;
								$html_options .= html( 'li', array(), $html_checkbox );
							}

							echo html( 'ol', array( 'class' => 'checkboxes' ), $html_options );

							break;

					}
					?>

			</li>
	<?php

			echo html( 'div', array( 'class' => 'clr' ) );

		}

		// put all the custom field names into an hidden field so we can process them on save
		$custom_fields_vals = implode( ',', $custom_fields_array );
		echo html( 'input', array( 'type' => 'hidden', 'name' => 'custom_fields_vals', 'value' => $custom_fields_vals ) );

		cp_action_formbuilder( $results, $post );
	}
}


/**
 * Displays preview of submitted ad listing.
 *
 * @param array $results
 * @param array $postvals
 *
 * @return void
 */
function cp_formbuilder_review( $results, $postvals ) {
	global $cp_options;

	$category = get_term_by( 'id', $postvals['cat'], APP_TAX_CAT );
?>

	<li>
		<div class="labelwrapper">
			<label><strong><?php _e( 'Category:', APP_TD ); ?></strong></label>
		</div>
		<div class="review"><?php echo $category->name; ?></div>
		<div class="clr"></div>
	</li>

	<?php
		foreach( $results as $result ):

			// external plugins can modify or disable field
			$result = apply_filters( 'cp_formbuilder_review_field', $result );
			if ( ! $result ) {
				continue;
			}
	?>
			<li>
				<div class="labelwrapper">
					<label><strong><?php echo esc_html( translate( $result->field_label, APP_TD ) ); ?>:</strong></label>
				</div>
				<div class="review">

					<?php
						// text areas should display formatting
						// other fields should be stripped
						if ( $result->field_type == 'text area' ) {
							$t = $postvals[ $result->field_name ];
							if ( ! $cp_options->allow_html ) {
								$t = strip_tags( $t );
							}
							echo wpautop( $t );
						} else if ( $result->field_type == 'checkbox' ) {
							if ( isset( $postvals[ $result->field_name ] ) && is_array( $postvals[ $result->field_name ] ) )
								echo strip_tags( implode( ", ", $postvals[ $result->field_name ] ) );
						} else {
							echo strip_tags( $postvals[ $result->field_name ] );
						}
					?>

				</div>
				<div class="clr"></div>
			</li>
<?php
	endforeach;

}


/**
 * Displays form for submitting ad listing based on the category id.
 *
 * @param int $category_id
 * @param object $listing
 *
 * @return void
 */
if ( ! function_exists( 'cp_show_form' ) ) {
	function cp_show_form( $category_id, $listing ) {

		$form_id = cp_get_form_id( $category_id );
		$form_fields = cp_get_custom_form_fields( $form_id );

		if ( $form_fields ) {

			// loop through the custom form fields and display them
			cp_formbuilder( $form_fields, $listing );

		} else {

			// display the default form since there isn't a custom form for this cat
			cp_show_default_form( $listing );

		}

		// show the image, featured ad, payment type and other options
		cp_other_fields( $listing->ID );

	}
}


/**
 * Displays default form for submitting ad listing.
 *
 * @param object $listing
 *
 * @return void
 */
if ( ! function_exists( 'cp_show_default_form' ) ) {
	function cp_show_default_form( $listing ) {

		$form_fields = cp_get_custom_form_fields();

		if ( $form_fields ) {

			// loop through the custom form fields and display them
			cp_formbuilder( $form_fields, $listing );

		} else {

			echo __( 'ERROR: no results found for the default ad form.', APP_TD ) . '<br />';

		}

	}
}


/**
 * Displays preview of submitted ad listing.
 *
 * @param array $postvals
 *
 * @return void
 */
function cp_show_review( $postvals ) {

	if ( empty( $postvals['fid'] ) ) {
		// get default form fields
		$form_fields = cp_get_custom_form_fields();
	} else {
		$form_fields = cp_get_custom_form_fields( $postvals['fid'] );
	}

	if ( $form_fields ) {

		// loop through the custom form fields and display them
		cp_formbuilder_review( $form_fields, $postvals );

	} else {

		printf( __( 'ERROR: The form template for form ID %s does not exist or the session variable is empty.', APP_TD ), $postvals['fid'] );

	}
?>

	<hr class="bevel" />
	<div class="clr"></div>


	<li>
		<div class="labelwrapper">
			<label><?php _e( 'Ad Listing Fee:', APP_TD ); ?></label>
		</div>
		<div id="review"><?php if ( cp_payments_is_enabled() ) { appthemes_display_price( $postvals['cp_sys_ad_listing_fee'] ); } else { _e( 'FREE', APP_TD ); } ?></div>
		<div class="clr"></div>
	</li>

	<?php if ( ! empty( $postvals['featured_ad'] ) ) { ?>
		<li>
			<div class="labelwrapper">
				<label><?php _e( 'Featured Listing Fee:', APP_TD ); ?></label>
			</div>
			<div id="review"><?php appthemes_display_price( $postvals['cp_sys_feat_price'] ); ?></div>
			<div class="clr"></div>
		</li>
	<?php } ?>

	<?php do_action( 'cp_review_premium_options' ); ?>

	<?php if ( isset( $postvals['membership_pack'] ) ) { ?>
		<li>
			<div class="labelwrapper">
				<label><?php _e( 'Membership:', APP_TD ); ?></label>
			</div>
			<div id="review"><?php echo cp_get_membership_package_benefit_text( $postvals['membership_pack'] ); ?></div>
			<div class="clr"></div>
		</li>
	<?php } ?>

	<hr class="bevel-double" />
	<div class="clr"></div>

	<li>
		<div class="labelwrapper">
			<label><?php _e( 'Total Amount Due:', APP_TD ); ?></label>
		</div>
		<div id="review"><strong>
		<?php
			if ( cp_payments_is_enabled() ) appthemes_display_price( $postvals['cp_sys_total_ad_cost'] ); else _e( '--', APP_TD );
		?>
		</strong></div>
		<div class="clr"></div>
	</li>

	<?php
		if ( cp_payments_is_enabled() ) {
			do_action( 'appthemes_purchase_fields' );
		}
	?>

<?php
}


/**
 * Displays the non-custom fields below the main listing submission form.
 *
 * @param int $listing_id
 *
 * @return void
 */
function cp_other_fields( $listing_id ) {
	global $cp_options;

	// are images on ads allowed
	if ( $cp_options->ad_images ) {
		if ( appthemes_plupload_is_enabled() ) {

			echo html( 'div class="ad-details-images-sep"', '&nbsp;' );

			appthemes_plupload_form( $listing_id );

		} else {
			$images_count = cp_get_ad_images( $listing_id );
			cp_ad_edit_image_input_fields( $images_count );
		}
	}

	// show the chargeable options if enabled
	if ( cp_payments_is_enabled() ) {

		// show the featured ad box if enabled
		if ( $cp_options->sys_feat_price ) {
		?>

			<div class="ad-details-featured-sep"></div>

			<li id="list_featured_ad" class="withborder">
				<div class="labelwrapper">
					<label><?php printf( __( 'Featured Listing %s', APP_TD ), appthemes_get_price( $cp_options->sys_feat_price ) ); ?></label>
				</div>
				<div class="clr"></div>
				<input name="featured_ad" value="1" type="checkbox" <?php checked( is_sticky( $listing_id ) ); ?> />
				<?php _e( 'Your listing will appear in the featured slider section at the top of the front page.', APP_TD ); ?>
				<div class="clr"></div>
			</li>

		<?php
		}

		do_action( 'cp_form_premium_options' );

		if ( $cp_options->price_scheme == 'single' ) {
		?>

			<li>
				<div class="labelwrapper">
					<label><?php _e( 'Ad Package:', APP_TD ); ?></label>
				</div>

				<?php
				// go get all the active ad packs and create a drop-down of options
				$packages = cp_get_listing_packages();

				if ( $packages ) {
				?>

					<select name="ad_pack_id" class="dropdownlist required">

					<?php
						foreach ( $packages as $package ) {
							// external plugins can modify or disable field
							$result = apply_filters( 'cp_package_field', $package, 'ad' );
							if ( ! $package ) {
								continue;
							}
					?>
							<option value="<?php echo esc_attr( $package->ID ); ?>"><?php echo esc_attr( $package->pack_name ); ?></option>
						<?php } ?>

					</select>

				<?php
				} else {
					_e( 'Error: no ad pack has been defined. Please contact the site administrator.', APP_TD );
				}
				?>

				<div class="clr"></div>
			</li>

		<?php } ?>

	<?php

	}

}


/**
 * Displays image upload fields on create ad page.
 *
 * @return void
 */
function cp_image_input_fields() {
	global $cp_options;

	for ( $i = 0; $i < $cp_options->num_images; $i++ ) {
		$required = ( $cp_options->require_images && $i == 0 ) ? 'required' : '';
?>
		<li>
			<div class="labelwrapper">
				<label><?php printf( __( 'Image %s', APP_TD ), $i+1 ); ?>:</label>
			</div>
			<input type="file" id="upload<?php echo $i+1; ?>" name="image[]" value="<?php if ( isset( $_POST['image' . $i ] ) ) echo $_POST['image' . $i ]; ?>" class="fileupload <?php echo $required; ?>" onchange="enableNextImage(this, <?php echo $i+2; ?>);" <?php if ( $i > 0 ) echo 'disabled="disabled"'; ?> >
			<div class="clr"></div>
		</li>
<?php
	}
?>

	<p class="light"><?php printf( __( '%sKB max file size per image', APP_TD ), $cp_options->max_image_size ); ?></p>
	<div class="clr"></div>

<?php
}


/**
 * Displays all the custom fields on the single ad page, by default they are placed in the list area.
 *
 * @param int $post_id
 * @param int $category_id
 * @param string $location (optional)
 *
 * @return void
 */
if ( ! function_exists( 'cp_get_ad_details' ) ) {
	function cp_get_ad_details( $post_id, $category_id, $location = 'list' ) {

		$form_id = cp_get_form_id( $category_id );
		$form_fields = cp_get_custom_form_fields( $form_id );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		if ( ! $form_fields ) {
			_e( 'No ad details found.', APP_TD );
			return;
		}

		// allows to hook before ad details
		cp_action_before_ad_details( $form_fields, $post, $location );

		foreach ( $form_fields as $field ) {

			// external plugins can modify or disable field
			$field = apply_filters( 'cp_ad_details_field', $field, $post, $location );
			if ( ! $field ) {
				continue;
			}

			$disallow_fields = array( 'cp_price', 'cp_currency' );
			if ( in_array( $field->field_name, $disallow_fields ) ) {
				continue;
			}

			$post_meta_val = get_post_meta( $post->ID, $field->field_name, true );
			if ( empty( $post_meta_val ) ) {
				continue;
			}

			if ( $location == 'list' ) {
				if ( $field->field_type == 'text area' ) {
					continue;
				}

				if ( $field->field_type == 'checkbox' ) {
					$post_meta_val = get_post_meta( $post->ID, $field->field_name, false );
					$post_meta_val = implode( ", ", $post_meta_val );
				}

				$args = array( 'value' => $post_meta_val, 'label' => $field->field_label, 'id' => $field->field_name, 'class' => '' );
				$args = apply_filters( 'cp_ad_details_' . $field->field_name, $args, $field, $post, $location );

				if ( $args ) {
					echo '<li id="' . $args['id'] . '" class="' . $args['class'] . '"><span>' . esc_html( translate( $args['label'], APP_TD ) ) . ':</span> ' . appthemes_make_clickable( $args['value'] ) . '</li>';
				}

			} elseif ( $location == 'content' ) {
				if ( $field->field_type != 'text area' ) {
					continue;
				}
				$args = array( 'value' => $post_meta_val, 'label' => $field->field_label, 'id' => $field->field_name, 'class' => 'custom-text-area dotted' );
				$args = apply_filters( 'cp_ad_details_' . $field->field_name, $args, $field, $post, $location );

				if ( $args ) {
					echo '<div id="' . $args['id'] . '" class="' . $args['class'] . '"><h3>' . esc_html( translate( $args['label'], APP_TD ) ) . '</h3> ' . wpautop( appthemes_make_clickable( $args['value'] ) ) . '</div>';
				}

			}
		}

		// allows to hook after ad details
		cp_action_after_ad_details( $form_fields, $post, $location );
	}
}
