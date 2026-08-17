<?php
/**
 * Frontend CSS/JS enqueueing with filemtime()-based cache busting.
 *
 * @package CustomTheme
 */

declare( strict_types=1 );

namespace CustomTheme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {

	public function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
	}

	/**
	 * The theme's only stylesheet and only script. Add more handles here if
	 * the project genuinely needs them - do not create a build step.
	 */
	public function enqueue_frontend_assets(): void {
		$this->enqueue_style( 'custom-theme-main', '/assets/css/main.css' );

		$this->enqueue_script( 'custom-theme-main', '/assets/js/main.js' );
	}

	private function enqueue_style( string $handle, string $relative_path ): void {
		wp_enqueue_style(
			$handle,
			get_template_directory_uri() . $relative_path,
			[],
			self::get_version( $relative_path )
		);
	}

	private function enqueue_script( string $handle, string $relative_path ): void {
		wp_enqueue_script(
			$handle,
			get_template_directory_uri() . $relative_path,
			[],
			self::get_version( $relative_path ),
			[
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);
	}

	/**
	 * Uses the file's last modified time as the version string, so the
	 * browser cache is automatically invalidated whenever a CSS/JS file
	 * changes. Falls back to the theme version if the file is missing.
	 *
	 * Public/static so other asset-loading code (e.g. the optional
	 * WooCommerce module) reuses this exact mechanism instead of
	 * duplicating it. $relative_path is theme-root-relative, e.g.
	 * '/assets/css/main.css'.
	 */
	public static function get_version( string $relative_path ): string {
		$file_path = get_template_directory() . $relative_path;

		return file_exists( $file_path )
			? (string) filemtime( $file_path )
			: (string) wp_get_theme()->get( 'Version' );
	}
}
