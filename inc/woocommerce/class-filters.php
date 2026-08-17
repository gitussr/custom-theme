<?php
/**
 * Lightweight shop filters (category, attributes, price, stock) with
 * optional AJAX filtering.
 *
 * @package CustomTheme
 */

declare( strict_types=1 );

namespace CustomTheme\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a filter panel built only from taxonomies/attributes that
 * actually exist and actually have products - never hardcoded values.
 *
 * Price filtering and sorting are NOT reimplemented here: WooCommerce's
 * own WC_Query already applies `min_price`/`max_price` query args to the
 * main product query natively, and the ordering dropdown is WooCommerce's
 * own `woocommerce_catalog_ordering()` (already hooked by default). This
 * class only adds category/attribute/stock filtering, which WooCommerce
 * does not provide out of the box, via `pre_get_posts` on the main query.
 *
 * The exact same filter-application logic is reused for the AJAX
 * re-query, so a filtered URL loaded directly (no JS) and a filtered
 * result produced via AJAX always agree - one source of truth, not two
 * parallel filtering implementations.
 *
 * SEO: pages with two or more active filters get a noindex,follow robots
 * directive (via the core `wp_robots` filter) so search engines don't
 * attempt to index every possible filter combination, while still being
 * able to crawl through to the canonical, unfiltered archive.
 */
class Filters {

	private const NONCE_ACTION = 'custom_theme_woocommerce';
	private const FILTER_KEYS  = [ 'product_cat', 'stock_status', 'min_price', 'max_price' ];

	public function init(): void {
		add_action( 'woocommerce_before_shop_loop', [ $this, 'render_filters' ], 5 );
		add_action( 'pre_get_posts', [ $this, 'apply_query_filters' ] );
		add_filter( 'wp_robots', [ $this, 'maybe_noindex_filtered_results' ] );

		add_action( 'wp_ajax_custom_theme_filter_products', [ $this, 'ajax_filter_products' ] );
		add_action( 'wp_ajax_nopriv_custom_theme_filter_products', [ $this, 'ajax_filter_products' ] );
	}

	/**
	 * Filter panel: category, per-attribute, stock, and price controls.
	 * Plain GET form, so it works with JavaScript disabled (a normal page
	 * reload with a filtered query string) and is progressively enhanced
	 * into an AJAX request by assets/js/woocommerce.js.
	 */
	public function render_filters(): void {
		$categories = $this->get_filterable_categories();
		$attributes = $this->get_filterable_attributes();

		if ( ! $categories && ! $attributes ) {
			return;
		}
		?>
		<form class="shop-filters" method="get">
			<?php if ( $categories ) : ?>
				<fieldset class="shop-filter shop-filter-categories">
					<legend><?php esc_html_e( 'Category', 'custom-theme' ); ?></legend>
					<?php foreach ( $categories as $category ) : ?>
						<label>
							<input
								type="checkbox"
								name="product_cat[]"
								value="<?php echo esc_attr( $category->slug ); ?>"
								<?php checked( $this->is_term_selected( 'product_cat', $category->slug ) ); ?>
							>
							<?php echo esc_html( $category->name ); ?>
						</label>
					<?php endforeach; ?>
				</fieldset>
			<?php endif; ?>

			<?php foreach ( $attributes as $attribute ) : ?>
				<fieldset class="shop-filter shop-filter-attribute">
					<legend><?php echo esc_html( $attribute['label'] ); ?></legend>
					<?php foreach ( $attribute['terms'] as $term ) : ?>
						<label>
							<input
								type="checkbox"
								name="attribute_<?php echo esc_attr( $attribute['taxonomy'] ); ?>[]"
								value="<?php echo esc_attr( $term->slug ); ?>"
								<?php checked( $this->is_term_selected( 'attribute_' . $attribute['taxonomy'], $term->slug ) ); ?>
							>
							<?php echo esc_html( $term->name ); ?>
						</label>
					<?php endforeach; ?>
				</fieldset>
			<?php endforeach; ?>

			<fieldset class="shop-filter shop-filter-price">
				<legend><?php esc_html_e( 'Price', 'custom-theme' ); ?></legend>
				<label>
					<?php esc_html_e( 'Min', 'custom-theme' ); ?>
					<input type="number" min="0" step="1" name="min_price" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['min_price'] ?? '' ) ) ); ?>">
				</label>
				<label>
					<?php esc_html_e( 'Max', 'custom-theme' ); ?>
					<input type="number" min="0" step="1" name="max_price" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['max_price'] ?? '' ) ) ); ?>">
				</label>
			</fieldset>

			<label class="shop-filter shop-filter-stock">
				<input type="checkbox" name="stock_status" value="instock" <?php checked( isset( $_GET['stock_status'] ) && 'instock' === $_GET['stock_status'] ); ?>>
				<?php esc_html_e( 'In stock only', 'custom-theme' ); ?>
			</label>

			<button type="submit" class="shop-filter-apply"><?php esc_html_e( 'Apply Filters', 'custom-theme' ); ?></button>
			<a class="shop-filter-clear" href="<?php echo esc_url( $this->get_clear_url() ); ?>"><?php esc_html_e( 'Clear Filters', 'custom-theme' ); ?></a>
		</form>
		<?php
	}

	/**
	 * Applies category/attribute/stock filters (from $_GET) to the main
	 * shop/product-taxonomy query. Price filtering is handled natively by
	 * WooCommerce's own WC_Query; sorting by WooCommerce's own catalog
	 * ordering - neither is duplicated here.
	 */
	public function apply_query_filters( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! is_shop() && ! is_product_taxonomy() ) {
			return;
		}

		$args = $this->apply_request_filters(
			[
				'tax_query'  => $query->get( 'tax_query' ) ?: [],
				'meta_query' => $query->get( 'meta_query' ) ?: [],
			],
			wp_unslash( $_GET )
		);

		$query->set( 'tax_query', $args['tax_query'] );

		if ( ! empty( $args['meta_query'] ) ) {
			$query->set( 'meta_query', $args['meta_query'] );
		}
	}

	/**
	 * Re-runs the shop query with the submitted filters and returns just
	 * the product grid + pagination markup - not a full page - so the
	 * frontend can swap it in without a reload. Uses the theme's normal
	 * product card (wc_get_template_part('content','product')), so
	 * filtered results look identical to a normal page load.
	 */
	public function ajax_filter_products(): void {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed. Please refresh the page and try again.', 'custom-theme' ) ], 403 );
		}

		$paged    = isset( $_POST['paged'] ) ? max( 1, absint( wp_unslash( $_POST['paged'] ) ) ) : 1;
		$orderby  = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : '';
		$ordering = WC()->query->get_catalog_ordering_args( $orderby );

		$visibility_terms = wc_get_product_visibility_term_ids();

		$args = [
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'paged'               => $paged,
			'posts_per_page'      => apply_filters( 'loop_shop_per_page', (int) get_option( 'posts_per_page' ) ),
			'orderby'             => $ordering['orderby'],
			'order'               => $ordering['order'],
			'ignore_sticky_posts' => true,
			'tax_query'           => [
				[
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => [ $visibility_terms['exclude-from-catalog'] ?? 0 ],
					'operator' => 'NOT IN',
				],
			],
		];

		if ( ! empty( $ordering['meta_key'] ) ) {
			$args['meta_key'] = $ordering['meta_key']; // phpcs:ignore -- matches WooCommerce's own catalog ordering args.
		}

		$args = $this->apply_request_filters( $args, wp_unslash( $_POST ) );

		$query = new \WP_Query( $args );

		ob_start();

		if ( $query->have_posts() ) {
			woocommerce_product_loop_start();

			while ( $query->have_posts() ) {
				$query->the_post();
				wc_get_template_part( 'content', 'product' );
			}

			woocommerce_product_loop_end();
		} else {
			wc_get_template( 'loop/no-products-found.php' );
		}

		$products_html = (string) ob_get_clean();

		$pagination_html = (string) paginate_links(
			[
				'total'     => (int) $query->max_num_pages,
				'current'   => $paged,
				'type'      => 'plain',
				'prev_text' => __( 'Previous', 'custom-theme' ),
				'next_text' => __( 'Next', 'custom-theme' ),
			]
		);

		wp_reset_postdata();

		wp_send_json_success(
			[
				'products'   => $products_html,
				'pagination' => $pagination_html,
				'count'      => (int) $query->found_posts,
			]
		);
	}

	public function maybe_noindex_filtered_results( array $robots ): array {
		if ( ( is_shop() || is_product_taxonomy() ) && $this->has_multiple_active_filters() ) {
			$robots['noindex'] = true;
		}

		return $robots;
	}

	/**
	 * @param array{tax_query?: array<int|string, mixed>, meta_query?: array<int, mixed>} $args
	 * @param array<string, mixed>                                                        $request $_GET or $_POST, already wp_unslash()ed.
	 * @return array{tax_query: array<int|string, mixed>, meta_query: array<int, mixed>}
	 */
	private function apply_request_filters( array $args, array $request ): array {
		$tax_query = $args['tax_query'] ?? [];

		$categories = $this->extract_terms( $request, 'product_cat' );
		if ( $categories ) {
			$tax_query[] = [
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $categories,
			];
		}

		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
			$terms    = $this->extract_terms( $request, 'attribute_' . $taxonomy );

			if ( $terms ) {
				$tax_query[] = [
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $terms,
				];
			}
		}

		if ( count( array_filter( $tax_query, 'is_array' ) ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		$args['tax_query'] = $tax_query;

		if ( isset( $request['stock_status'] ) && 'instock' === $request['stock_status'] ) {
			$meta_query   = $args['meta_query'] ?? [];
			$meta_query[] = [
				'key'   => '_stock_status',
				'value' => 'instock',
			];
			$args['meta_query'] = $meta_query;
		}

		return $args;
	}

	/**
	 * @param array<string, mixed> $request
	 * @return string[]
	 */
	private function extract_terms( array $request, string $key ): array {
		if ( empty( $request[ $key ] ) ) {
			return [];
		}

		$raw = is_array( $request[ $key ] ) ? $request[ $key ] : explode( ',', (string) $request[ $key ] );

		return array_values( array_filter( array_map( 'sanitize_title', $raw ) ) );
	}

	/**
	 * @return \WP_Term[]
	 */
	private function get_filterable_categories(): array {
		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
			]
		);

		return is_wp_error( $terms ) ? [] : $terms;
	}

	/**
	 * @return array<int, array{taxonomy: string, label: string, terms: \WP_Term[]}>
	 */
	private function get_filterable_attributes(): array {
		$attributes = [];

		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
			$terms    = get_terms(
				[
					'taxonomy'   => $taxonomy,
					'hide_empty' => true,
				]
			);

			if ( is_wp_error( $terms ) || ! $terms ) {
				continue;
			}

			$attributes[] = [
				'taxonomy' => $taxonomy,
				'label'    => $attribute->attribute_label,
				'terms'    => $terms,
			];
		}

		return $attributes;
	}

	private function is_term_selected( string $key, string $slug ): bool {
		return in_array( $slug, $this->extract_terms( wp_unslash( $_GET ), $key ), true );
	}

	private function has_multiple_active_filters(): bool {
		$active = 0;

		foreach ( self::FILTER_KEYS as $key ) {
			if ( ! empty( $_GET[ $key ] ) ) {
				++$active;
			}
		}

		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			if ( ! empty( $_GET[ 'attribute_' . wc_attribute_taxonomy_name( $attribute->attribute_name ) ] ) ) {
				++$active;
			}
		}

		return $active >= 2;
	}

	private function get_clear_url(): string {
		if ( is_shop() ) {
			$shop_url = get_permalink( wc_get_page_id( 'shop' ) );

			return $shop_url ? $shop_url : home_url( '/' );
		}

		$queried = get_queried_object();

		if ( $queried instanceof \WP_Term ) {
			$link = get_term_link( $queried );

			if ( ! is_wp_error( $link ) ) {
				return $link;
			}
		}

		return home_url( '/' );
	}
}
