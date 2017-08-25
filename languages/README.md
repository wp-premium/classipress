## Custom Language File Warning

Do not place your custom translation files here. They will be deleted the next time you update the theme.

Your translated `.mo` file should go in your language file directory (`WP_LANG_DIR`) which most likely is `/wp-content/languages/themes/`.

### Example File Placement
Let's say you've translated ClassiPress into German. You've got the de.mo file and need to rename it so WordPress recognizes it (`classipress-de_DE.mo`) and place it in the language file directory.


`/wp-content/languages/themes/classipress-de_DE.mo`

### Where is My Language File Directory?
We've added a page which will tell you this. Login to your website and visit the "System Info" page (`http://www.your-site.com/wp-admin/admin.php?page=app-system-info`). Under the "WordPress Info" section, look for the "Language File Path" entry.

***Note:*** Before the `.mo` file will work, you'll also need to make sure your wp-admin "Site Language" is set to the same locale otherwise it won't work properly.

If our theme can't find a custom language file in your `WP_LANG_DIR` directory, it will fallback to this directory.
