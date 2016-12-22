<?php
/**
 * Theme-specific hooks.
 * For general AppThemes hooks, see framework/kernel/hooks.php
 *
 * @package ClassiPress\Hooks
 * @author  AppThemes
 * @since   ClassiPress 3.1
 */


/**
 * Called in sidebar-user.php & author.php to hook into user informations.
 * @since 3.2
 *
 * @param string $location
 *
 * @return void
 */
function cp_author_info( $location ) {
	do_action( 'cp_author_info', $location );
}


/**
 * Called in cp_formbuilder() to hook into form builder.
 * @since 3.2.1
 *
 * @param object $form_fields
 * @param object|bool $post
 *
 * @return void
 */
function cp_action_formbuilder( $form_fields, $post ) {
	do_action( 'cp_action_formbuilder', $form_fields, $post );
}


/**
 * Called in cp_get_ad_details() to hook before ad details.
 * @since 3.3
 *
 * @param object $form_fields
 * @param object $post
 * @param string $location
 *
 * @return void
 */
function cp_action_before_ad_details( $form_fields, $post, $location ) {
	do_action( 'cp_action_before_ad_details', $form_fields, $post, $location );
}


/**
 * Called in cp_get_ad_details() to hook after ad details.
 * @since 3.3
 *
 * @param object $form_fields
 * @param object $post
 * @param string $location
 *
 * @return void
 */
function cp_action_after_ad_details( $form_fields, $post, $location ) {
	do_action( 'cp_action_after_ad_details', $form_fields, $post, $location );
}


