# Widgets component


Provides common widgets for all themes and plugins.

New instances of widgets can be created as it is or with passing parameters (to ensure compatibility with existing themes).
Also instances can be initiated by extending the base classes.
Allows multiple instances of the same class with different parameter sets.
In all cases instances must have unique identifiers `id_base`.

Base class extends [scbWidget](https://github.com/scribu/wp-scb-framework/blob/master/Widget.php) class and inherits advantages of [scbForms](https://github.com/scribu/wp-scb-framework/blob/master/Forms.php) class for displaying and processing widget forms.

Also it has own styles/scripts enqueue system. Styles files loads only if appropriate widget activated at least on one sudebar/area in general.
Scripts files loads only if appropriate widget activated at least on one sidebar/area of **current page**.

At the moment prepeared following widget classes:

* APP_Widget_Facebook - facebook like box sidebar widget
* APP_Widget_125_Ads - simple advertizing widget
* APP_Widget_Recent_Posts - Customizable Post Loops widget

It is assumed that a list of common widgets will be considerably expanded.

## Widget files naming conventions:

All widget classes located in separated files.
Module uses autoloader for automatic loading widget classes files.
Developer only have to instatiate classes.

Files and Classes naming is very important for Autoloader. So, here the naming conventions:

Files:

* All words in file name separated by Hyphens;
* First word in file name is `widget`;
* Other words are lowercased widget class name without prefix `APP_`;

Classes:

* Prefix `APP_Widget_`;
* Class names should use capitalized words separated by underscores. Any acronyms should be all upper case.

### Examples:

Class name: `APP_Widget_Recent_Posts`

File name: `widget-recent-posts.php`

## Parameters:

* `id_base` - Root id for all widgets of this type
* `name` - Name for this widget type
* `defaults` - Default widget form values:
	* `title` - Widget title
	* `images_url` (optional) - Images folder URL with trailing slash. By default `get_template_directory_uri() . '/includes/widgets/images/'`,
	* `style_url` (optional) - URL to widget styles file
	* `script_url` (optional) - URL to widget scripts file,
	* ... Other widget specific values
* `widget_ops` - Option array passed to wp_register_sidebar_widget()
	* `description` - Widget description
	* `classname` - CSS class name
* `control_options` - Option array passed to wp_register_widget_control()
	* `width` - Widget control width
	* `height` - Widget control height

## Usage:

Load component

```php
require_once dirname( __FILE__ ) . '/includes/widgets/load.php';
```

Create new instances in `functions.php` or `includes/widgets.php` file of the theme.

Using Framework API:

```php
appthemes_add_instance( array(
	'APP_Widget_Facebook',
	'APP_Widget_125_Ads' => array(
		array(
			'id_base' => 'custom_id',
			'name' => 'Custom Name',
			'defaults' => array(
				'images_url'=> get_template_directory_uri() . '/images/',
			),
		),
	),
) );
```

Direct instantiation:

```php
new APP_Widget_Facebook;
new APP_Widget_125_Ads( array(
		'id_base' => 'custom_id',
		'name' => 'Custom Name',
		'defaults' => array(
			'images_url'=> get_template_directory_uri() . '/images/',
		),
	)
);
```