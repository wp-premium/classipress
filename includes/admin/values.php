<?php
/**
 *
 * Here is where all the admin field data is stored
 * All the data is stored in arrays and then looped though
 * @author AppThemes
 * @version 3.0
 *
 * Array param definitions are as follows:
 * name    = field name
 * desc    = field description
 * tip     = question mark tooltip text
 * id      = database column name or the WP meta field name
 * css     = any on-the-fly styles you want to add to that field
 * type    = type of html field
 * req     = if the field is required or not (1=required)
 * min     = minimum number of characters allowed before saving data
 * std     = default value. not being used
 * js      = allows you to pass in javascript for onchange type events
 * vis     = if field should be visible or not. used for dropdown values field
 * visid   = this is the row css id that must correspond with the dropdown value that controls this field
 * options = array of drop-down option value/name combo
 * altclass = adds a new css class to the input field (since v3.1)
 *
 *
 */
global $options_new_form, $options_new_field;


$options_new_form = array(

	array( 'type' => 'notab' ),

	array(
		'name' => __( 'Form Name', APP_TD ),
		'desc' => '',
		'tip'  => __( 'Create a form name that best describes what category or categories this form will be used for. (i.e. Auto Form, Clothes Form, General Form, etc). It will not be visible on your site.', APP_TD ),
		'id'   => 'form_label',
		'css'  => '',
		'type' => 'text',
		'vis'  => '',
		'req'  => '1',
		'std'  => '',
		'altclass' => 'regular-text',
	),

	array(
		'name' => __( 'Form Description', APP_TD ),
		'desc' => '',
		'tip'  => __( 'Enter a description of your new form layout. It will not be visible on your site.', APP_TD ),
		'id'   => 'form_desc',
		'css'  => '',
		'type' => 'textarea',
		'vis'  => '',
		'req'  => '1',
		'min'  => '5',
		'std'  => '',
		'altclass' => 'large-text code',
	),

	array(
		'name' => __( 'Available Categories', APP_TD ),
		'desc' => '',
		'tip'  => __( 'You can assign a form layout to multiple categories. A category can only have one form layout assigned to it. Categories not listed are being used on a different form layout. Any unselected categories will use the default ad form.', APP_TD ),
		'id'   => 'post_category[]',
		'css'  => '',
		'type' => 'cat_checklist',
		'vis'  => '',
		'req'  => '1',
		'std'  => ''
	),

	array(
		'name' => __( 'Status', APP_TD ),
		'desc' => '',
		'tip'  => __( 'If you do not want this new form live on your site yet, select inactive.', APP_TD ),
		'id'   => 'form_status',
		'css'  => '',
		'std'  => '',
		'js'   => '',
		'vis'  => '',
		'req'  => '1',
		'type' => 'select',
		'options' => array(
			'active'   => __( 'Active', APP_TD ),
			'inactive' => __( 'Inactive', APP_TD )
		),
	),

	array( 'type' => 'notabend' ),

);


$options_new_field = array (

	array( 'type' => 'notab' ),

	array(
		'name' => __( 'Field Name', APP_TD ),
		'desc' => '',
		'tip'  => __( 'Create a field name that best describes what this field will be used for. (i.e. Color, Size, etc). It will be visible on your site.', APP_TD ),
		'id'   => 'field_label',
		'css'  => '',
		'type' => 'text',
		'req'  => '1',
		'vis'  => '',
		'min'  => '2',
		'std'  => '',
		'altclass' => 'regular-text',
	),

	array(
		'name' => __( 'Meta Name', APP_TD ),
		'desc' => '',
		'tip'  => __( 'This field is used by WordPress so you cannot modify it. Doing so could cause problems displaying the field on your ads.', APP_TD ),
		'id'   => 'field_name',
		'css'  => '',
		'type' => 'text',
		'req'  => '1',
		'vis'  => '',
		'min'  => '5',
		'std'  => '',
		'dis'  => '1',
		'altclass' => 'regular-text',
	),

	array(
		'name' => __( 'Field Description', APP_TD ),
		'desc' => '',
		'tip'  => __( 'Enter a description of your new form layout. It will not be visible on your site.', APP_TD ),
		'id'   => 'field_desc',
		'css'  => '',
		'type' => 'textarea',
		'req'  => '1',
		'vis'  => '',
		'min'  => '5',
		'std'  => '',
		'altclass' => 'large-text code',
	),

	array(
		'name' => __( 'Field Tooltip', APP_TD ),
		'desc' => '',
		'tip'  => __( 'This will create a ? tooltip icon next to this field on the submit ad page.', APP_TD ),
		'id'   => 'field_tooltip',
		'css'  => '',
		'type' => 'textarea',
		'req'  => '0',
		'vis'  => '',
		'min'  => '5',
		'std'  => '',
		'altclass' => 'large-text code',
	),

	array(
		'name' => __( 'Field Type', APP_TD ),
		'desc' => '',
		'tip'  => __( 'This is the type of field you want to create.', APP_TD ),
		'id'   => 'field_type',
		'css'  => '',
		'std'  => '',
		'js'   => 'onchange="show(this)"',
		'req'  => '1',
		'vis'  => '',
		'min'  => '',
		'type' => 'select',
		'options' => array(
			'text box'  => __( 'text box', APP_TD ),
			'drop-down' => __( 'drop-down', APP_TD ),
			'text area' => __( 'text area', APP_TD ),
			'radio'     => __( 'radio buttons', APP_TD ),
			'checkbox'  => __( 'checkboxes', APP_TD ),
		),
	),

	array(
		'name' => __( 'Minimum Length', APP_TD ),
		'desc' => '',
		'tip'  => __( 'Defines the minimum number of characters required for this field. Enter a number like 2 or enter 0 to make the field optional.', APP_TD ),
		'id'   => 'field_min_length',
		'css'  => '',
		'type' => 'text',
		'req'  => '0',
		'vis'  => '0',
		'min'  => '',
		'std'  => '',
		'altclass' => 'regular-text',
	),

	array(
		'name' => __( 'Field Values', APP_TD ),
		'desc' => '',
		'tip'  => __( 'Enter a comma separated list of values you want to appear in this drop-down box. (i.e. XXL,XL,L,M,S,XS). Do not separate values with the return key.', APP_TD ) . ' ' . __( 'Escape commas in values with prepending backslash (i.e. 1\,000, 2\,000)', APP_TD ),
		'id'   => 'field_values',
		'css'  => '',
		'type' => 'textarea',
		'req'  => '',
		'min'  => '1',
		'std'  => '',
		'vis'  => '0',
		'altclass' => 'regular-text',
	),

/*
	array(
		'name' => __( 'Add to Search Widget', APP_TD ),
		'desc' => '',
		'tip' => __( 'Checking this will include this field on the search box on your website. It is perfect for things like regional search. (Note: It should only be used for text or drop-down fields.)', APP_TD ),
		'id' => 'field_search',
		'css' => '',
		'type' => 'checkbox',
		'req' => '1',
		'min' => '5',
		'std' => '',
	),
*/
	array( 'type' => 'notabend' ),

);

