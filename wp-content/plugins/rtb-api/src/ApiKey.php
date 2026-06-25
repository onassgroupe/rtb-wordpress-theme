<?php

namespace RTB\Api;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Authentification des requêtes : clé fournie via l'en-tête `X-RTB-Api-Key`
 * (ou le paramètre `api_key`), validée contre les clés gérées (KeyStore) ou la
 * constante d'environnement RTB_API_KEY.
 */
final class ApiKey {

	/** permission_callback : true si clé valide & non révoquée, sinon 401. */
	public function authorize( WP_REST_Request $request ) {
		$provided = $this->provided( $request );

		if ( '' !== $provided ) {
			if ( defined( 'RTB_API_KEY' ) && '' !== (string) RTB_API_KEY && hash_equals( (string) RTB_API_KEY, $provided ) ) {
				return true;
			}
			if ( KeyStore::verify( $provided ) ) {
				return true;
			}
		}

		return new WP_Error(
			'rtb_unauthorized',
			'Clé API requise ou invalide. Fournissez l\'en-tête X-RTB-Api-Key.',
			[ 'status' => 401 ]
		);
	}

	private function provided( WP_REST_Request $request ): string {
		$header = $request->get_header( 'X-RTB-Api-Key' );
		if ( $header ) {
			return trim( $header );
		}
		$param = $request->get_param( 'api_key' );
		return $param ? trim( (string) $param ) : '';
	}
}
