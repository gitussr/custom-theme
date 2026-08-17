<?php
/**
 * Coordinates the optional WooCommerce customization layer.
 *
 * @package CustomTheme
 */

declare( strict_types=1 );

namespace CustomTheme\WooCommerce;

use CustomTheme\Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Only ever loaded from functions.php when WooCommerce is active AND
 * CustomTheme\Config::is_enabled('woocommerce') is true. Baseline
 * WooCommerce compatibility (theme support, content wrapper) lives in
 * CustomTheme\Theme and is NOT gated by this class, so the site keeps
 * rendering correctly even if this module is switched off.
 */
class Woocommerce {

	public function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_footer', [ $this, 'notice_region' ] );

		( new Product() )->init();
		( new Cart() )->init();
		( new Quick_View() )->init();
		( new Filters() )->init();
	}

	/**
	 * Loads assets only on WooCommerce pages (shop, product, cart, checkout,
	 * account) - never sitewide just because WooCommerce is installed.
	 */
	public function enqueue_assets(): void {
		if ( ! $this->is_woocommerce_context() ) {
			return;
		}

		wp_enqueue_style(
			'custom-theme-woocommerce',
			get_template_directory_uri() . '/assets/css/woocommerce.css',
			[ 'custom-theme-main' ],
			Assets::get_version( '/assets/css/woocommerce.css' )
		);

		wp_enqueue_script(
			'custom-theme-woocommerce',
			get_template_directory_uri() . '/assets/js/woocommerce.js',
			[],
			Assets::get_version( '/assets/js/woocommerce.js' ),
			[
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);

		wp_localize_script(
			'custom-theme-woocommerce',
			'customThemeWooCommerce',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'custom_theme_woocommerce' ),
				'i18n'    => [
					'error'    => __( 'Something went wrong. Please try again.', 'custom-theme' ),
					'close'    => __( 'Close', 'custom-theme' ),
					'filtered' => __( '%d products found.', 'custom-theme' ),
				],
			]
		);
	}

	/**
	 * Accessible, screen-reader-announced status region for AJAX
	 * add-to-cart / filter feedback. Empty until JavaScript populates it.
	 */
	public function notice_region(): void {
		if ( ! $this->is_woocommerce_context() ) {
			return;
		}

		echo '<div id="custom-theme-notice" class="custom-theme-notice" role="status" aria-live="polite"></div>';
	}

	private function is_woocommerce_context(): bool {
		return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
	}
}
