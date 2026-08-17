<?php
/**
 * AJAX Quick View.
 *
 * @package CustomTheme
 */

declare( strict_types=1 );

namespace CustomTheme\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns a small, self-contained product HTML fragment - never a whole
 * page. Simple products get an inline Add to Cart button that reuses the
 * exact same `.ajax-add-to-cart` flow as the single product page (see
 * assets/js/woocommerce.js and Cart::ajax_add_to_cart()). Every other
 * product type (variable, grouped, external/affiliate) gets a "View full
 * details" link to the product page instead of a partial or incorrect
 * variation interface.
 */
class Quick_View {

	private const NONCE_ACTION = 'custom_theme_woocommerce';

	public function init(): void {
		add_action( 'wp_ajax_custom_theme_quick_view', [ $this, 'ajax_quick_view' ] );
		add_action( 'wp_ajax_nopriv_custom_theme_quick_view', [ $this, 'ajax_quick_view' ] );
	}

	public function ajax_quick_view(): void {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed. Please refresh the page and try again.', 'custom-theme' ) ], 403 );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $product instanceof \WC_Product || ! $product->is_visible() ) {
			wp_send_json_error( [ 'message' => __( 'This product could not be found.', 'custom-theme' ) ], 404 );
		}

		wp_send_json_success( [ 'html' => $this->render( $product ) ] );
	}

	private function render( \WC_Product $product ): string {
		ob_start();
		?>
		<div class="quick-view-content">
			<div class="quick-view-image">
				<?php echo wp_kses_post( $product->get_image( 'medium' ) ); ?>
			</div>

			<div class="quick-view-summary">
				<h2 id="quick-view-title" class="quick-view-title"><?php echo esc_html( $product->get_name() ); ?></h2>

				<?php echo wp_kses_post( wc_get_rating_html( (float) $product->get_average_rating() ) ); ?>

				<p class="quick-view-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>

				<?php if ( $product->get_short_description() ) : ?>
					<div class="quick-view-description">
						<?php echo wp_kses_post( wc_format_content( $product->get_short_description() ) ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) : ?>
					<button
						type="button"
						class="quick-view-add-to-cart ajax-add-to-cart"
						data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>"
					>
						<?php esc_html_e( 'Add to Cart', 'custom-theme' ); ?>
					</button>
				<?php endif; ?>

				<p class="quick-view-permalink">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
						<?php esc_html_e( 'View full details', 'custom-theme' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
