<?php
/**
 * Plugin Name:       RTB API
 * Description:        API REST dédiée à l'application mobile (React Native) — namespace /wp-json/rtb/v1 :
 *                     accueil, chaînes & direct, radio, articles, émissions, recherche, catégories, config.
 *                     Lecture seule, sortie échappée, réutilise les helpers du thème et le moteur rtb-search. POO.
 * Version:           1.0.0
 * Author:            Onass Groupe
 * Requires PHP:      8.1
 */

defined( 'ABSPATH' ) || exit;

define( 'RTB_API_FILE', __FILE__ );
define( 'RTB_API_DIR', plugin_dir_path( __FILE__ ) );
define( 'RTB_API_VER', '1.0.0' );
define( 'RTB_API_NS', 'rtb/v1' );

spl_autoload_register( static function ( string $class ): void {
	$prefix = 'RTB\\Api\\';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}
	$file = RTB_API_DIR . 'src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
	if ( is_file( $file ) ) {
		require $file;
	}
} );

add_action( 'plugins_loaded', static function (): void {
	\RTB\Api\Plugin::instance()->boot();
} );
