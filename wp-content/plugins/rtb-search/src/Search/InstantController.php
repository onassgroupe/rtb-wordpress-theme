<?php

namespace RTB\Search\Search;

use RTB\Search\Analytics\Store;

defined( 'ABSPATH' ) || exit;

/**
 * Endpoint AJAX de la recherche instantanée (au fil de la frappe).
 */
final class InstantController {

	public function __construct(
		private Engine $engine,
		private Store $store
	) {}

	public function register(): void {
		add_action( 'wp_ajax_rtb_instant', [ $this, 'handle' ] );
		add_action( 'wp_ajax_nopriv_rtb_instant', [ $this, 'handle' ] );
	}

	public function handle(): void {
		check_ajax_referer( 'rtb_instant', 'nonce' );

		$query = isset( $_GET['q'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['q'] ) ) ) : '';

		// Trop court → on renvoie les recherches tendances pour amorcer.
		if ( mb_strlen( $query ) < 2 ) {
			wp_send_json_success( [
				'query'    => $query,
				'results'  => [],
				'trending' => $this->store->trending( 6 ) ?: $this->fallbackTrending(),
			] );
		}

		$ids     = $this->engine->search( $query, 6, 'all' );
		$results = array_map( [ Results::class, 'format' ], $ids );

		wp_send_json_success( [
			'query'    => $query,
			'results'  => array_values( $results ),
			'trending' => [],
		] );
	}

	/** @return string[] */
	private function fallbackTrending(): array {
		return [ 'Conseil des ministres', 'JT de 20H', 'Coupe du Faso', 'Success', 'Économie' ];
	}
}
