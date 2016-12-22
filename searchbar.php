<?php
/**
 * Theme search bar template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.4
 */
?>

<?php
if ( is_page_template( 'tpl-ads-home.php' ) || is_search() || is_404() || is_tax( APP_TAX_CAT ) || is_tax( APP_TAX_TAG ) || is_singular( APP_POST_TYPE ) ) :

	$args = cp_get_dropdown_categories_search_args( 'bar' );
?>

	<div id="search-bar">

		<div class="searchblock_out">

			<div class="searchblock">

				<form action="<?php echo home_url( '/' ); ?>" method="get" id="searchform" class="form_search">

					<div class="searchfield">

						<input name="s" type="text" id="s" tabindex="1" class="editbox_search" style="<?php cp_display_style( 'search_field_width' ); ?>" value="<?php the_search_query(); ?>" placeholder="<?php esc_attr_e( 'What are you looking for?', APP_TD ); ?>" />

					</div>

					<div class="searchbutcat">

						<button class="dashicons-before btn-topsearch" type="submit" tabindex="3" title="<?php esc_attr_e( 'Search Ads', APP_TD ); ?>" id="go" value="search" name="sa"></button>

						<?php wp_dropdown_categories( $args ); ?>

					</div>

				</form>

			</div> <!-- /searchblock -->

		</div> <!-- /searchblock_out -->

	</div> <!-- /search-bar -->

<?php endif; ?>
