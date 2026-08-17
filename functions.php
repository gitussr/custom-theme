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

require_once CUSTOM_THEME_DIR . '/inc/class-theme.php';
require_once CUSTOM_THEME_DIR . '/inc/class-assets.php';
require_once CUSTOM_THEME_DIR . '/inc/class-cleanup.php';

( new \CustomTheme\Theme() )->init();
( new \CustomTheme\Assets() )->init();
( new \CustomTheme\Cleanup() )->init();
