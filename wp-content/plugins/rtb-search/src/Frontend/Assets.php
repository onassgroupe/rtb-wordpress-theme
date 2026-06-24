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

		wp_localize_script( 'rtb-search', 'RTB_SEARCH', [
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'rtb_instant' ),
			'home'  => home_url( '/' ),
			'i18n'  => [
				'results'  => __( 'Résultats', 'rtb-search' ),
				'trending' => __( 'Recherches fréquentes', 'rtb-search' ),
				'empty'    => __( 'Aucun résultat', 'rtb-search' ),
				'all'      => __( 'Voir tous les résultats', 'rtb-search' ),
				'searching' => __( 'Recherche…', 'rtb-search' ),
			],
		] );
	}
}
