# Google reCaptcha 2.0

Provides support for Google reCaptcha v.2.0.

![captura de ecra 2015-10-26 as 16 38 26](https://cloud.githubusercontent.com/assets/1154179/10735465/05cfa2f2-7c00-11e5-9070-835837ae2499.png)

It provides three helper functions:
* `appthemes_display_recaptcha` - Display the ReCaptcha
* `appthemes_recaptcha_verify` - Verifies the user response
* `appthemes_enqueue_recaptcha_scripts` - Adds action to `wp_enqueue_scripts` and enqueues necessary scripts

Accepted theme support parameters:
- `public_key` *already existed*
- `private_key` *already existed*
- `theme` *already existed* - *new values:* `light` (default) | `dark`
- `size` - **new!** - *values:* `normal` (default) | `compact` (default if `wp_is_mobile()` is **true**)
- `type` - **new!** - *values:* `image` (default) | `audio`

**Changes from older version**
- Themes no longer need to include the reCaptcha library passing it trough the `file` param since the new library is included in the framework. The module still checks for this parameter to fallback to the old reCaptcha in case the old library is still being used.
- The custom `appthemes_recaptcha()` helper used by each theme to display the ReCaptcha is replaced by `appthemes_display_recaptcha()` provided by the module
- The available reCaptcha colors are now limited to: `Light` and `Dark`.

**Usage**

Themes must explicitly enqueue the JS reCaptcha library using the `g-recaptcha` handle or function `appthemes_enqueue_recaptcha_scripts()`, on every page where the reCaptcha will be displayed. This ensures it is not enqueued on every page.

* *Enqueue*

```
if ( current_theme_supports( 'app-recaptcha' ) ) {
	appthemes_enqueue_recaptcha_scripts();
}
```

* *Display*

```
if ( current_theme_supports( 'app-recaptcha' ) ) {
	appthemes_display_recaptcha()
}
```

* *Verify Response*

```
if ( current_theme_supports( 'app-recaptcha' ) ) {

	// Verify the user response.
	$response = appthemes_recaptcha_verify();
	if ( is_wp_error( $response ) ) {
		// Error! - check WP_Error object
		return $response;
	}
	// Success!!
}
```
