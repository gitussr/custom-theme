<?php
/**
 * Template for archives: category, tag, author, date, and custom taxonomy
 * archives all fall back to this single template.
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
		<?php
		the_archive_title( '<h1 class="page-title">', '</h1>' );
		the_archive_description( '<div class="archive-description">', '</div>' );
		?>
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
		<p><?php esc_html_e( 'Nothing found.', 'custom-theme' ); ?></p>
		<?php
	endif;
	?>

</main>

<?php
get_footer();
