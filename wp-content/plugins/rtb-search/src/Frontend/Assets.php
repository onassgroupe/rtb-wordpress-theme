<?php

namespace RTB\Search\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Charge le JS/CSS de la recherche instantanée et expose la config (AJAX + nonce).
 */
final class Assets {

	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	public function enqueue(): void {
		$css = RTB_SEARCH_DIR . 'assets/instant.css';
		$js  = RTB_SEARCH_DIR . 'assets/instant.js';
		wp_enqueue_style( 'rtb-search', RTB_SEARCH_URL . 'assets/instant.css', [], is_file( $css ) ? (string) filemtime( $css ) : RTB_SEARCH_VER );
		wp_enqueue_script( 'rtb-search', RTB_SEARCH_URL . 'assets/instant.js', [], is_file( $js ) ? (string) filemtime( $js ) : RTB_SEARCH_VER, true );

		// URL de base de la recherche : doit conserver la locale active (sinon retour au /fr/).
		$home = function_exists( 'rtb_lurl' ) ? rtb_lurl( '/' ) : home_url( '/' );

		wp_localize_script( 'rtb-search', 'RTB_SEARCH', [
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'rtb_instant' ),
			'home'  => $home,
			'i18n'  => [
				'results'   => function_exists( 'rtb_t' ) ? rtb_t( 'Résultats' ) : 'Résultats',
				'trending'  => function_exists( 'rtb_t' ) ? rtb_t( 'Recherches fréquentes' ) : 'Recherches fréquentes',
				'empty'     => function_exists( 'rtb_t' ) ? rtb_t( 'Aucun résultat' ) : 'Aucun résultat',
				'all'       => function_exists( 'rtb_t' ) ? rtb_t( 'Voir tous les résultats' ) : 'Voir tous les résultats',
				'searching' => function_exists( 'rtb_t' ) ? rtb_t( 'Recherche…' ) : 'Recherche…',
			],
		] );
	}
}
