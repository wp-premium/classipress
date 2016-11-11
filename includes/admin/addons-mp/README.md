# AppThemes Add-ons Marketplace Module

The AppThemes Marketplace is now closer to the customer.

This module provides an add-ons browser that mimics WordPress plugins marketplace for AppThemes own add-ons.
It's flexible enough to allows each seller to use it to display their own customized versions.

**Basic Usage:**

The default usage for the full marketplace. The 'Add-ons' sub-menu item will be displayed under the main theme menu.

`add_theme_support( 'app-addons-mp' );`

**Note:** By default, the module files are expected do be placed under the theme `/includes/admin/addons-mp` folder. This can be changed by setting a different path under the `URL` parameter.

**Available Options:**

* `url`   - The URL location of the module *(string)*
	*  default is `<theme template directory>/includes/admin/addons-mp`
* `theme` - The default theme to which display add-ons *(string|array)*
* `mp`    - The marketplace and related page content parameteres *(array)*
  * `menu_slug` - The slug name for the marketplace page and menu *(array)*
     * `menu` - The menu params *(array)*
       * `scbAdminPage` - see [scbAdminPage](https://github.com/scribu/wp-scb-framework/blob/54b521e37ed54244e19a58d497ac690efe7b578b/AdminPage.php#L5)
     * `filters` - The marketplace filters visible to the user *(array)* (shows all if omitted)
       * `themes` - List of themes to display *(string|array|boolean)* (shows all if omitted)
       * `categories` - List of categories to display *(string|array|boolean)* (shows all if omitted)
         * valid categories: ` 'plugins', 'payment-gateways', 'child-themes', 'general-themes' `
       * `authors` - List of authors to display *(string|array|boolean)* (shows all if omitted)

**Advanced Usage Examples:**

Overrides the main AppThemes marketplace to show items from AppThemes for HireBee, under 'Projects':
```
add_theme_support( 'app-addons-mp', array(
    'theme' => array( 'hirebee' ),
    'mp' => array(
        'app-addons-mp' => array(
            'menu' => array(
                'parent' => 'edit.php?post_type=project',
            ),
            'filters' => array(
                'authors' => 'appthemes',
            ),
        ),
      ),
  ),
);
```

Show 'johndoe' seller's marketplace plugins and child themes for *Vantage* and *ClassiPress*:
```
add_theme_support( 'app-addons-mp', array(
    'mp' => array(
        'johndoe-addons' => array(
            'menu' => array(
                'parent' => 'johndoe-plugin-menu-parent-slug',
            ),
            'filters' => array(
				'themes' => array( 'vantage', 'classipress' ),
                'authors' => 'john doe',
				'categories' => array( 'plugins', 'child-themes' ),
            ),
        ),
      ),
  ),
);
```

Show 'johndoe' seller's marketplace plugins under the 'Settings' menu:
```
add_theme_support( 'app-addons-mp', array(
    'mp' => array(
        'johndoe-addons' => array(
            'menu' => array(
                'parent' => 'options-general.php',
            ),
            'filters' => array(
                'authors' => 'john doe',
				'categories' => array( 'plugins' ),
            ),
        ),
      ),
  ),
);
```

**Available Hooks:**

*Actions:*

* `appthemes_addons_mp_addon_after` - Use it for displaying extra information for each addon. Recommended for displaying coupon codes.
	* params: `addon` object - The addon object

*Filters:*

* `appthemes_addons_mp_markup_<screen-id>"` - Use it for changing the default addons markup.
	* params:
		* `output` string - The markup string
		* `addon` object - The addon object


