<?php
/**
 * Template for search results.
 *
 * @package CustomTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main">

	<header class="page-header">
		<h1 class="page-title">
			<?php
			printf(
				/* translators: %s: search query. */
				esc_html__( 'Search Results for: %s', 'custom-theme' ),
				'<span>' . esc_html( get_search_query() ) . '</span>'
			);
			?>
		</h1>
		<?php get_search_form(); ?>
	</header>

	<?php if ( have_posts() ) : ?>

		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<?php
					the_title(
						'<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">',
						'</a></h2>'
					);
					?>
				</header>

				<div class="entry-content">
					<?php the_excerpt(); ?>
				</div>
			</article>
			<?php
		endwhile;

		the_posts_pagination();

	else :
		?>
		<p><?php esc_html_e( 'No results found.', 'custom-theme' ); ?></p>
		<?php
	endif;
	?>

</main>

<?php
get_footer();
