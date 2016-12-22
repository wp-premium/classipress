<?php
/**
 * The template for displaying Comments.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 1.0
 */


// Prevent direct file calls
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( post_password_required() ) { ?>

	<p class="nocomments"><?php _e( 'This post is password protected. Enter the password to view comments.', APP_TD ); ?></p>
<?php
	return;
}
?>


<?php if ( have_comments() ) : ?>

	<div class="shadowblock_out start">

		<div class="shadowblock">

			<div id="comments">

				<div id="comments_wrap">

					<h2 class="dotted"><?php comments_number( __( 'No Responses', APP_TD ), __( 'One Response', APP_TD ), __( '% Responses', APP_TD ) ); ?> <?php _e( 'to', APP_TD ); ?> <span>&#8220;<?php the_title(); ?>&#8221;</span></h2>

					<?php appthemes_before_page_comments(); ?>

					<ol class="commentlist">

						<?php appthemes_list_page_comments(); ?>

					</ol>

					<?php appthemes_after_page_comments(); ?>

					<div class="navigation">

						<div class="alignleft"><?php previous_comments_link( '&laquo; ' . __( 'Older Comments', APP_TD ), 0 ); ?></div>

						<div class="alignright"><?php next_comments_link( __( 'Newer Comments', APP_TD ) . ' &raquo;', 0 ); ?></div>

						<div class="clr"></div>

					</div><!-- /navigation -->

					<div class="clr"></div>

					<?php appthemes_before_page_pings(); ?>

					<?php $carray = separate_comments( $comments ); // get the comments array to check for pings ?>

					<?php if ( ! empty( $carray['pings'] ) ) : // pings include pingbacks & trackbacks ?>

						<h2 class="dotted" id="pings"><?php _e( 'Trackbacks/Pingbacks', APP_TD ); ?></h2>

						<ol class="pinglist">

							<?php appthemes_list_page_pings(); ?>

						</ol>

					<?php endif; ?>

					<?php appthemes_after_page_pings(); ?>

					<?php appthemes_before_page_respond(); ?>

					<?php if ( 'open' == $post->comment_status ) : ?>

						<?php appthemes_before_page_comments_form(); ?>

						<?php appthemes_page_comments_form(); ?>

						<?php appthemes_after_page_comments_form(); ?>

					<?php endif; // open ?>

					<?php appthemes_after_page_respond(); ?>

				</div> <!-- /comments_wrap -->

			</div><!-- /comments -->

		</div><!-- /shadowblock -->

	</div><!-- /shadowblock_out -->

<?php elseif ( 'open' == $post->comment_status ) : ?>

	<div class="shadowblock_out start">

		<div class="shadowblock">

			<div id="comments">

				<div id="comments_wrap">

					<?php appthemes_before_page_respond(); ?>

					<?php appthemes_before_page_comments_form(); ?>

					<?php appthemes_page_comments_form(); ?>

					<?php appthemes_after_page_comments_form(); ?>

					<?php appthemes_after_page_respond(); ?>

				</div> <!-- /comments_wrap -->

			</div><!-- /comments -->

		</div><!-- /shadowblock -->

	</div><!-- /shadowblock_out -->

<?php endif; ?>
