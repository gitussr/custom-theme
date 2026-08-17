<?php
/**
 * Front page template. Used only when a static page is set as the site's
 * front page (Settings > Reading). Falls back to home.php / index.php
 * (not present in this boilerplate) when the front page shows the latest
 * posts instead.
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
			<?php if ( get_the_title() ) : ?>
				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</header>
			<?php endif; ?>

			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</article>
		<?php
	endwhile;
	?>

</main>

<?php
get_footer();
