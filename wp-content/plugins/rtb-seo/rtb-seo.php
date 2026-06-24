<?php
/**
 * Plugin Name:       RTB SEO
 * Description:        SEO complet pour la RTB — meta description, canonical, robots, Open Graph, Twitter Cards, données structurées (WebSite + SearchAction, NewsMediaOrganization, NewsArticle, VideoObject, fil d'Ariane) et hreflang multilingue (Polylang). POO, sans dépendance.
 * Version:           1.0.0
 * Author:            Onass Groupe
 * Requires PHP:      8.1
 *
 * Supersède les sorties SEO inline du thème (meta description + schema) pour éviter les doublons.
 */

defined( 'ABSPATH' ) || exit;

define( 'RTB_SEO_FILE', __FILE__ );
define( 'RTB_SEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'RTB_SEO_VER', '1.0.0' );

spl_autoload_register( static function ( string $class ): void {
	$prefix = 'RTB\\Seo\\';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}
	$file = RTB_SEO_DIR . 'src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
	if ( is_file( $file ) ) {
		require $file;
	}
} );

add_action( 'plugins_loaded', static function (): void {
	\RTB\Seo\Plugin::instance()->boot();
} );
