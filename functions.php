<?php
/**
 * Theme bootstrap.
 *
 * Loads the light OOP theme classes and wires them up. Template files stay
 * WordPress-native; this file only requires and initializes classes.
 *
 * @package CustomTheme
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CUSTOM_THEME_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'CUSTOM_THEME_DIR', get_template_directory() );
define( 'CUSTOM_THEME_URI', get_template_directory_uri() );

require_once CUSTOM_THEME_DIR . '/inc/class-config.php';
require_once CUSTOM_THEME_DIR . '/inc/class-theme.php';
require_once CUSTOM_THEME_DIR . '/inc/class-assets.php';
require_once CUSTOM_THEME_DIR . '/inc/class-cleanup.php';

( new \CustomTheme\Theme() )->init();
( new \CustomTheme\Assets() )->init();
( new \CustomTheme\Cleanup() )->init();

/**
 * Optional WooCommerce customization layer. Loaded only when WooCommerce
 * is active AND the 'woocommerce' feature is enabled in
 * CustomTheme\Config - this is the theme's single feature-loading gate,
 * not scattered checks across multiple files. Baseline WooCommerce
 * compatibility (CustomTheme\Theme::woocommerce_support()) runs
 * regardless of this switch, so the site keeps working correctly either
 * way. See README.md, "WooCommerce Module".
 */
if ( \CustomTheme\Config::is_enabled( 'woocommerce' ) && class_exists( '\WooCommerce' ) ) {
	require_once CUSTOM_THEME_DIR . '/inc/woocommerce/class-woocommerce.php';
	require_once CUSTOM_THEME_DIR . '/inc/woocommerce/class-product.php';
	require_once CUSTOM_THEME_DIR . '/inc/woocommerce/class-cart.php';
	require_once CUSTOM_THEME_DIR . '/inc/woocommerce/class-quick-view.php';
	require_once CUSTOM_THEME_DIR . '/inc/woocommerce/class-filters.php';

	( new \CustomTheme\WooCommerce\Woocommerce() )->init();
}
