<?php
/**
 * 404 (Not Found) template.
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
		<h1 class="page-title"><?php esc_html_e( 'Page Not Found', 'custom-theme' ); ?></h1>
	</header>

	<div class="page-content">
		<p>
			<?php esc_html_e( 'The page you were looking for could not be found. It may have been moved or no longer exists.', 'custom-theme' ); ?>
		</p>

		<p>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Return to the homepage', 'custom-theme' ); ?>
			</a>
		</p>

		<?php get_search_form(); ?>
	</div>

</main>

<?php
get_footer();
