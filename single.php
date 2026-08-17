<?php
/**
 * Template for single Posts.
 *
 * @package CustomTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main">

	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

				<p class="entry-meta">
					<?php
					printf(
						/* translators: 1: post date, 2: post author. */
						esc_html__( 'Posted on %1$s by %2$s', 'custom-theme' ),
						'<time class="entry-date" datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( get_the_date() ) . '</time>',
						'<span class="entry-author">' . esc_html( get_the_author() ) . '</span>'
					);
					?>
				</p>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="entry-thumbnail">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
			<?php endif; ?>

			<div class="entry-content">
				<?php the_content(); ?>
			</div>

			<footer class="entry-footer">
				<?php
				$categories = get_the_category_list( ', ' );
				if ( $categories ) {
					echo '<p class="entry-categories">' . wp_kses_post( $categories ) . '</p>';
				}

				$tags = get_the_tag_list( '', ', ' );
				if ( $tags && ! is_wp_error( $tags ) ) {
					echo '<p class="entry-tags">' . wp_kses_post( $tags ) . '</p>';
				}
				?>
			</footer>
		</article>

		<?php
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
	endwhile;
	?>

</main>

<?php
get_footer();
