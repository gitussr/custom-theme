<?php
/**
 * Product card customization.
 *
 * @package CustomTheme
 */

declare( strict_types=1 );

namespace CustomTheme\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the Quick View button to the standard WooCommerce loop item via
 * WooCommerce's own action hooks. No template override was needed:
 * woocommerce/content-product.php (image, sale badge, title, rating,
 * price, Add to Cart) is left completely untouched, so this single hook
 * addition is automatically reused everywhere WooCommerce renders that
 * template - shop, category/tag archives, search results, related
 * products, and upsells.
 */
class Product {

	public function init(): void {
		add_action( 'woocommerce_after_shop_loop_item', [ $this, 'quick_view_button' ], 15 );
	}

	public function quick_view_button(): void {
		global $product;

		if ( ! $product instanceof \WC_Product || ! $product->is_visible() ) {
			return;
		}

		printf(
			'<button type="button" class="quick-view-button" data-product-id="%1$d" aria-haspopup="dialog">%2$s</button>',
			(int) $product->get_id(),
			esc_html__( 'Quick View', 'custom-theme' )
		);
	}
}
