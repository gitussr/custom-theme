<?php
/**
 * The document head and site header, including the primary navigation.
 *
 * @package CustomTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#primary">
	<?php esc_html_e( 'Skip to content', 'custom-theme' ); ?>
</a>

<header id="masthead" class="site-header">
	<div class="site-branding">
		<?php if ( has_custom_logo() ) : ?>
			<?php the_custom_logo(); ?>
		<?php else : ?>
			<p class="site-title">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php bloginfo( 'name' ); ?>
				</a>
			</p>
			<?php if ( get_bloginfo( 'description' ) ) : ?>
				<p class="site-description"><?php bloginfo( 'description' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Primary', 'custom-theme' ); ?>">
		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<button
				class="menu-toggle"
				aria-controls="primary-menu"
				aria-expanded="false"
			>
				<?php esc_html_e( 'Menu', 'custom-theme' ); ?>
			</button>
		<?php endif; ?>

		<?php
		wp_nav_menu(
			[
				'theme_location' => 'primary',
				'menu_id'        => 'primary-menu',
				'menu_class'     => 'primary-menu',
				'container'      => false,
				'fallback_cb'    => false,
			]
		);
		?>
	</nav>
</header>
