<?php
/**
 * Sidebar Recent Comments template.
 *
 * @package ClassiPress\Templates
 * @author  AppThemes
 * @since   ClassiPress 3.0
 */

global $wpdb;

// TODO: Use get_comments()
$sql = "SELECT DISTINCT ID, post_title, post_password, comment_ID,
	comment_post_ID, comment_author, comment_author_email, comment_date_gmt, comment_approved,
	comment_type,comment_author_url,
	SUBSTRING(comment_content,1,115) as excerpt
	FROM $wpdb->comments
	LEFT OUTER JOIN $wpdb->posts ON ($wpdb->comments.comment_post_ID = $wpdb->posts.ID)
	WHERE comment_approved = '1' AND comment_type = '' AND
	post_password = ''
	ORDER BY comment_date_gmt DESC LIMIT 5";

$comments = $wpdb->get_results( $sql );
?>


<ul class="side-comments">

	<?php foreach ( $comments as $comment ) { ?>

		<li>

			<?php echo get_avatar( $comment, 140 ); ?>

			<div class="comment">
				<p><?php echo strip_tags( $comment->comment_author ); ?> - <a href="<?php echo esc_url( get_permalink( $comment->ID ) ); ?>#comment-<?php echo $comment->comment_ID; ?>" title="<?php esc_attr_e( 'Comment on article ', APP_TD ); ?>'<?php echo $comment->post_title; ?>'"><?php echo strip_tags( $comment->excerpt ); ?>...</a></p>
			</div>

			<div class="clr"></div>

		</li>

	<?php }	?>

</ul>
