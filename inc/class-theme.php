<?php
/**
 * Core theme setup: supports, navigation, and optional WooCommerce declarations.
 *
 * @package CustomTheme
 */

declare( strict_types=1 );

namespace CustomTheme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Theme {

	public function init(): void {
		add_action( 'after_setup_theme', [ $this, 'setup' ] );
		add_action( 'after_setup_theme', [ $this, 'register_menus' ] );
		add_action( 'after_setup_theme', [ $this, 'woocommerce_support' ] );
	}

	/**
	 * Core theme supports. Only what a general-purpose custom theme actually needs.
	 */
	public function setup(): void {
		load_theme_textdomain( 'custom-theme', get_template_directory() . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'custom-logo' );
		add_theme_support( 'responsive-embeds' );

		add_theme_support(
			'html5',
			[
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			]
		);

		// Block Editor: keep the theme's typography/colors from being overridden,
		// support wide/full alignment, and load frontend styles into the editor.
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/main.css' );
	}

	/**
	 * Single primary menu. Rendered with wp_nav_menu() in header.php, never hardcoded links.
	 */
	public function register_menus(): void {
		register_nav_menus(
			[
				'primary' => __( 'Primary Menu', 'custom-theme' ),
			]
		);
	}

	/**
	 * Basic WooCommerce compatibility declarations.
	 *
	 * Only runs if WooCommerce is active, and adds nothing when it isn't -
	 * the theme stays lightweight on non-WooCommerce sites. This baseline
	 * compatibility is intentionally NOT gated by the optional WooCommerce
	 * module switch (CustomTheme\Config::is_enabled('woocommerce')) - it
	 * must keep working even when that module is switched off, since
	 * declaring 'woocommerce' theme support changes how WooCommerce renders
	 * its pages (see woocommerce_wrapper_start()/_end() below). The
	 * enhanced product/cart/filter layer lives in inc/woocommerce/ and is
	 * what the module switch actually controls.
	 *
	 * If a project never needs WooCommerce, this method can be safely deleted
	 * (see README.md, section "WooCommerce Module").
	 */
	public function woocommerce_support(): void {
		if ( ! class_exists( '\WooCommerce' ) ) {
			return;
		}

		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		// Declaring 'woocommerce' theme support tells WooCommerce to skip its
		// own default page wrapper and defer to the theme instead. Without
		// these two hooks, WooCommerce pages would render with no <main>
		// wrapper at all.
		add_action( 'woocommerce_before_main_content', [ $this, 'woocommerce_wrapper_start' ] );
		add_action( 'woocommerce_after_main_content', [ $this, 'woocommerce_wrapper_end' ] );
	}

	public function woocommerce_wrapper_start(): void {
		echo '<main id="primary" class="site-main">';
	}

	public function woocommerce_wrapper_end(): void {
		echo '</main>';
	}
}
