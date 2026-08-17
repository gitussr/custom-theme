<?php
/**
 * Comment list and comment form. Loaded via comments_template().
 *
 * @package CustomTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">

	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php
			$custom_theme_comment_count = get_comments_number();
			printf(
				/* translators: %s: number of comments. */
				esc_html( _n( '%s Comment', '%s Comments', $custom_theme_comment_count, 'custom-theme' ) ),
				esc_html( number_format_i18n( $custom_theme_comment_count ) )
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				[
					'style'      => 'ol',
					'short_ping' => true,
				]
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			[
				'prev_text' => esc_html__( 'Older Comments', 'custom-theme' ),
				'next_text' => esc_html__( 'Newer Comments', 'custom-theme' ),
			]
		);
	endif;

	if ( ! comments_open() && get_comments_number() ) :
		?>
		<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'custom-theme' ); ?></p>
		<?php
	endif;

	comment_form();
	?>

</div>
