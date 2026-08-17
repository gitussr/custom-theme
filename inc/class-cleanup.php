<?php
/**
 * Removes unnecessary WordPress-generated frontend output.
 *
 * This only strips <head> discovery links, the generator meta tag, and
 * emoji scripts/styles. It never disables the underlying WordPress
 * features (REST API, feeds, XML-RPC, login) - only their unsolicited
 * frontend output. See README.md, section "WordPress Cleanup" for the
 * full rationale of each removal.
 *
 * @package CustomTheme
 */

declare( strict_types=1 );

namespace CustomTheme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cleanup {

	public function init(): void {
		add_action( 'init', [ $this, 'remove_head_links' ] );

		$this->disable_emojis();

		// Removes "WordPress X.Y.Z" from the RSS feed generator tag.
		add_filter( 'the_generator', '__return_empty_string' );
	}

	/**
	 * Discovery links that leak information or add unused <head> requests.
	 * The main feed link (feed_links) is intentionally left in place.
	 */
	public function remove_head_links(): void {
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}

	/**
	 * Disables the emoji detection script/style and the related DNS
	 * prefetch, editor plugin, and RSS/email filters WordPress adds by
	 * default. Modern browsers render emoji natively, so this payload is
	 * unnecessary weight on every page load.
	 */
	private function disable_emojis(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );

		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		add_filter( 'tiny_mce_plugins', [ $this, 'remove_tinymce_emoji_plugin' ] );
		add_filter( 'wp_resource_hints', [ $this, 'remove_emoji_dns_prefetch' ], 10, 2 );
	}

	/**
	 * @param string[] $plugins
	 * @return string[]
	 */
	public function remove_tinymce_emoji_plugin( array $plugins ): array {
		return array_diff( $plugins, [ 'wpemoji' ] );
	}

	/**
	 * @param string[] $urls
	 * @return string[]
	 */
	public function remove_emoji_dns_prefetch( array $urls, string $relation_type ): array {
		if ( 'dns-prefetch' !== $relation_type ) {
			return $urls;
		}

		return array_filter(
			$urls,
			static fn( string $url ): bool => false === strpos( $url, 's.w.org' )
		);
	}
}
