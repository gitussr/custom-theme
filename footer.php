<?php
/**
 * The site footer.
 *
 * @package CustomTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<footer id="colophon" class="site-footer">
	<div class="site-info">
		<p>
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>.
			<?php esc_html_e( 'All rights reserved.', 'custom-theme' ); ?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
