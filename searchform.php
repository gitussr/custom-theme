<?php
/**
 * Search form, used automatically by get_search_form().
 *
 * @package CustomTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$custom_theme_search_id = 'search-field-' . uniqid();
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="<?php echo esc_attr( $custom_theme_search_id ); ?>" class="screen-reader-text">
		<?php esc_html_e( 'Search for:', 'custom-theme' ); ?>
	</label>
	<input
		type="search"
		id="<?php echo esc_attr( $custom_theme_search_id ); ?>"
		class="search-field"
		placeholder="<?php esc_attr_e( 'Search…', 'custom-theme' ); ?>"
		value="<?php echo get_search_query(); ?>"
		name="s"
	>
	<button type="submit" class="search-submit">
		<?php esc_html_e( 'Search', 'custom-theme' ); ?>
	</button>
</form>
