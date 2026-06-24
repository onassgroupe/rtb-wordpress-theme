<?php
/**
 * Plugin Name:       RTB Search
 * Description:        Moteur de recherche RTB — pertinence (titre pondéré, accents/pluriels), recherche instantanée au fil de la frappe, suggestions (tendances/récents), filtres type & tri. POO, sans dépendance externe.
 * Version:           1.0.0
 * Author:            Onass Groupe
 * Requires PHP:      8.1
 * Text Domain:       rtb-search
 *
 * Inspiré de l'UX de laravel-starter-media (popover + page résultats filtrable),
 * répliqué en natif WordPress (aucun Scout/Meilisearch/IA).
 */

defined( 'ABSPATH' ) || exit;

define( 'RTB_SEARCH_FILE', __FILE__ );
define( 'RTB_SEARCH_DIR', plugin_dir_path( __FILE__ ) );
define( 'RTB_SEARCH_URL', plugin_dir_url( __FILE__ ) );
define( 'RTB_SEARCH_VER', '1.0.0' );

/* Autoload PSR-4 simple : RTB\Search\Foo\Bar → src/Foo/Bar.php */
spl_autoload_register( static function ( string $class ): void {
	$prefix = 'RTB\\Search\\';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}
	$rel  = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
	$file = RTB_SEARCH_DIR . 'src/' . $rel . '.php';
	if ( is_file( $file ) ) {
		require $file;
	}
} );

register_activation_hook( __FILE__, [ \RTB\Search\Analytics\Store::class, 'installTable' ] );

add_action( 'plugins_loaded', static function (): void {
	\RTB\Search\Plugin::instance()->boot();
} );

/* ---------------------------------------------------------------------------
 * Helpers exposés au thème (façade simple sur les services POO).
 * ------------------------------------------------------------------------- */

/** Recherches tendances (les plus fréquentes). Repli sur une liste éditoriale si vide. */
function rtb_search_trending( int $limit = 6 ): array {
	$store    = new \RTB\Search\Analytics\Store();
	$trending = $store->trending( $limit );
	if ( $trending ) {
		return $trending;
	}
	return array_slice( [ 'Conseil des ministres', 'JT de 20H', 'Coupe du Faso', 'Success', 'Langues nationales', 'Économie' ], 0, $limit );
}

/** Recherches récentes. */
function rtb_search_recent( int $limit = 5 ): array {
	return ( new \RTB\Search\Analytics\Store() )->recent( $limit );
}
