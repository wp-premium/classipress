<?php
/**
 * Attachments template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 1.0
 */
?>


<div class="content">

	<div class="content_botbg">

		<div class="content_res">

			<!-- full block -->
			<div class="shadowblock_out">

				<div class="shadowblock">

					<div class="post">

						<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

							<?php if ( ! empty( $post->post_parent ) ) { ?>

								<p class="page-title"><a href="<?php echo get_permalink( $post->post_parent ); ?>" title="<?php esc_attr( printf( __( 'Return to %s', APP_TD ), get_the_title( $post->post_parent ) ) ); ?>" rel="gallery"><?php
									printf( '<span class="meta-nav">' . __( '&larr; Return to %s', APP_TD ) . '</span>', get_the_title( $post->post_parent ) );
									?></a></p>

							<?php } ?>

							<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

								<h2 class="attach-title"><?php the_title(); ?></h2>

								<div class="attach-meta">
								<?php
									printf( __( '<span class="%1$s">By</span> %2$s', APP_TD ),
										'meta-prep meta-prep-author',
										sprintf( '<span class="author vcard"><a class="url fn n" href="%1$s" title="%2$s" rel="author">%3$s</a></span>',
											get_author_posts_url( get_the_author_meta( 'ID' ) ),
											sprintf( esc_attr__( 'View all ads by %s', APP_TD ), get_the_author() ),
											get_the_author()
										)
									);
								?>

									<span class="meta-sep">|</span>

								<?php
									printf( __( '<span class="%1$s">Uploaded</span> %2$s', APP_TD ),
										'meta-prep meta-prep-entry-date',
										sprintf( '<span class="entry-date"><abbr class="published" title="%1$s">%2$s</abbr></span>',
											esc_attr( get_the_time() ),
											get_the_date()
										)
									);

									if ( wp_attachment_is_image() ) {
										echo ' <span class="meta-sep">|</span> ';
										$metadata = wp_get_attachment_metadata();
										printf( __( 'Full size is %s pixels', APP_TD ),
											sprintf( '<a href="%1$s" title="%2$s">%3$s &times; %4$s</a>',
												wp_get_attachment_url(),
												esc_attr( __( 'Link to full-size image', APP_TD ) ),
												$metadata['width'],
												$metadata['height']
											)
										);
									}
								?>

									<?php edit_post_link( __( 'Edit', APP_TD ), '<span class="meta-sep">|</span> <span class="edit-link">', '</span>' ); ?>

								</div><!-- /attach-meta -->

								<div class="entry-content">

									<div class="entry-attachment">

									<?php if ( wp_attachment_is_image() ) : ?>

									<?php
										$attachments = array_values( get_children( array( 'post_parent' => $post->post_parent, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order ID' ) ) );

										foreach ( $attachments as $k => $attachment ) {
											if ( $attachment->ID == $post->ID ) {
												break;
											}
										}

										$k++;
										// If there is more than 1 image attachment in a gallery
										if ( count( $attachments ) > 1 ) {
											if ( isset( $attachments[ $k ] ) ) {
												// get the URL of the next image attachment
												$next_attachment_url = get_attachment_link( $attachments[ $k ]->ID );
											} else {
												// or get the URL of the first image attachment
												$next_attachment_url = get_attachment_link( $attachments[ 0 ]->ID );
											}
										} else {
											// or, if there's only 1 image attachment, get the URL of the image
											$next_attachment_url = wp_get_attachment_url();
										}
									?>

										<p class="attachment"><a href="<?php echo $next_attachment_url; ?>" title="<?php echo esc_attr( get_the_title() ); ?>" rel="attachment">
											<?php
												$attachment_width  = apply_filters( 'appthemes_attachment_size', 800 );
												$attachment_height = apply_filters( 'appthemes_attachment_height', 800 );
												echo wp_get_attachment_image( $post->ID, array( $attachment_width, $attachment_height ) );
											?></a></p>

										<div id="nav-below" class="navigation">

											<div class="next-prev"><?php previous_image_link( false, __( '&larr; prev', APP_TD ) ); ?>&nbsp;&nbsp;&nbsp;<?php next_image_link( false, __( 'next &rarr;', APP_TD ) ); ?></div>

										</div><!-- /nav-below -->

									<?php else : ?>

										<a href="<?php echo wp_get_attachment_url(); ?>" title="<?php echo esc_attr( get_the_title() ); ?>" rel="attachment"><?php echo basename( get_permalink() ); ?></a>

									<?php endif; ?>

									</div><!-- /entry-attachment -->


								</div><!-- /entry-content -->

							</div><!-- /post -->

						<?php endwhile; // end of the loop ?>

						<div class="clr"></div>


					</div><!--/post-->

				</div><!-- /shadowblock -->

			</div><!-- /shadowblock_out -->

			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
