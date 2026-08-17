<?php
/**
 * AJAX Add to Cart and the Mini Cart.
 *
 * @package CustomTheme
 */

declare( strict_types=1 );

namespace CustomTheme\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart state (contents, quantities, totals, sessions) stays entirely
 * WooCommerce's responsibility - this class only calls WC_Cart and reuses
 * WooCommerce's own `woocommerce_add_to_cart_fragments` filter, the same
 * mechanism WooCommerce's native loop AJAX Add to Cart already uses (see
 * README.md, "AJAX Add to Cart"), so the cart count / Mini Cart stay in
 * sync regardless of which flow triggered the update.
 *
 * The AJAX handler here only ever adds SIMPLE products. Variable and
 * grouped products are never sent to it - assets/js/woocommerce.js only
 * intercepts `form.cart` submissions that contain neither a `.variations`
 * table nor a `.woocommerce-grouped-product-list` table, so those product
 * types fall through to WooCommerce's normal form submission untouched.
 */
class Cart {

	private const NONCE_ACTION = 'custom_theme_woocommerce';

	public function init(): void {
		add_action( 'wp_ajax_custom_theme_add_to_cart', [ $this, 'ajax_add_to_cart' ] );
		add_action( 'wp_ajax_nopriv_custom_theme_add_to_cart', [ $this, 'ajax_add_to_cart' ] );

		add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'cart_count_fragment' ] );

		add_action( 'custom_theme_header_actions', [ $this, 'mini_cart_trigger' ] );
	}

	public function ajax_add_to_cart(): void {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed. Please refresh the page and try again.', 'custom-theme' ) ], 403 );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$quantity   = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 1;

		$product = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $product instanceof \WC_Product ) {
			wp_send_json_error( [ 'message' => __( 'This product could not be found.', 'custom-theme' ) ], 404 );
		}

		if ( ! $product->is_type( 'simple' ) ) {
			wp_send_json_error( [ 'message' => __( 'This product requires more information before it can be added to the cart.', 'custom-theme' ) ], 400 );
		}

		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			wp_send_json_error( [ 'message' => __( 'This product is currently unavailable.', 'custom-theme' ) ], 400 );
		}

		if ( $quantity <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Please choose a valid quantity.', 'custom-theme' ) ], 400 );
		}

		$cart_item_key = WC()->cart->add_to_cart( $product->get_id(), $quantity );

		if ( ! $cart_item_key ) {
			$notices = wc_get_notices( 'error' );
			wc_clear_notices();

			wp_send_json_error(
				[
					'message' => $notices ? wp_strip_all_tags( $notices[0]['notice'] ) : __( 'This product could not be added to your cart.', 'custom-theme' ),
				],
				400
			);
		}

		wp_send_json_success(
			[
				'message'   => __( 'Product added to your cart.', 'custom-theme' ),
				'count'     => WC()->cart->get_cart_contents_count(),
				'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', [] ),
			]
		);
	}

	/**
	 * @param array<string, string> $fragments
	 * @return array<string, string>
	 */
	public function cart_count_fragment( array $fragments ): array {
		ob_start();
		?>
		<span class="cart-count"><?php echo esc_html( (string) WC()->cart->get_cart_contents_count() ); ?></span>
		<?php
		$fragments['.cart-count'] = (string) ob_get_clean();

		return $fragments;
	}

	/**
	 * Mini Cart trigger + panel, injected into header.php via the generic
	 * 'custom_theme_header_actions' hook. The panel wraps
	 * woocommerce_mini_cart() (WooCommerce's own cart/mini-cart.php
	 * template - no override) in the same `widget_shopping_cart_content`
	 * class WooCommerce's built-in Cart widget uses, so WooCommerce's own
	 * wc-cart-fragments script recognizes and AJAX-handles the "remove
	 * item" links inside it automatically - no custom remove-item code
	 * needed here.
	 */
	public function mini_cart_trigger(): void {
		?>
		<div class="mini-cart">
			<button
				type="button"
				class="mini-cart-toggle"
				aria-controls="mini-cart-panel"
				aria-expanded="false"
			>
				<?php esc_html_e( 'Cart', 'custom-theme' ); ?>
				<span class="cart-count"><?php echo esc_html( (string) WC()->cart->get_cart_contents_count() ); ?></span>
			</button>

			<div id="mini-cart-panel" class="mini-cart-panel widget_shopping_cart_content" hidden>
				<?php woocommerce_mini_cart(); ?>
			</div>
		</div>
		<?php
	}
}
