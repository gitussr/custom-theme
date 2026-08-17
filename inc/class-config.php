<?php
/**
 * Central feature-flag switchboard.
 *
 * @package CustomTheme
 */

declare( strict_types=1 );

namespace CustomTheme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One place to enable/disable optional theme modules, instead of scattering
 * ad-hoc checks across the theme. Add a key here when a new optional
 * module needs a switch; check it once in functions.php when deciding
 * whether to require() that module's files.
 */
class Config {

	private const FEATURES = [
		// Loads the optional WooCommerce customization layer (inc/woocommerce/).
		// This does NOT install, activate, or disable the WooCommerce plugin -
		// it only controls whether this theme's WooCommerce-specific code runs.
		// See README.md, "WooCommerce Module".
		'woocommerce' => true,
	];

	public static function is_enabled( string $feature ): bool {
		return ! empty( self::FEATURES[ $feature ] );
	}
}
