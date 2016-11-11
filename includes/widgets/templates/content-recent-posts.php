<?php
/*
 * Generic Recent Posts content template
 */
?>
				<div <?php post_class( 'recent-box' ); ?>>

					<?php if ( $instance['show_thumbnail'] ) { ?>

						<div class="recent-box-thumb">

							<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" >

								<?php if ( has_post_thumbnail() ) { ?>

									<?php the_post_thumbnail( 'recent-posts-widget' ); ?>

								<?php } else { ?>

									<img src="<?php echo esc_url( $instance['images_url'] . 'recent-post-thumbnail.jpg' ); ?>" alt="<?php the_title_attribute(); ?>" title="<?php the_title_attribute(); ?>" />

								<?php } ?>

							</a>

						</div><!-- end recent-box-thumb -->

					<?php } ?>

					<div class="recent-box-content">

						<div class="recent-box-info">
							<h4 class="recent-box-title <?php if ( $instance['show_rating'] ) echo 'recent-box-rating'; ?>"><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php echo esc_attr( get_the_title() ? get_the_title() : get_the_ID() ); ?>"><?php if ( get_the_title() ) the_title(); else the_ID(); ?></a></h4>

							<?php if ( $instance['show_rating'] && defined( 'STARSTRUCK_KEY' ) ) : ?>
								<div class="fr">
									<?php echo starstruck_mini_ratings( $instance[ 'post_type' ] ); ?>
								</div>
							<?php endif; ?>

						</div>

						<?php the_excerpt(); ?>

						<?php if ( $instance['show_readmore'] ) : ?>
							<div class="button-new"><i><a href="<?php the_permalink(); ?>"><?php _e( 'Read More', APP_TD );?></a></i></div>
						<?php endif; ?>

					</div>

					<?php if ( $instance['show_date'] ) : ?>
						<span class="recent-post-date"><?php echo get_the_date(); ?></span>
					<?php endif; ?>

				</div><!-- end recent-box -->