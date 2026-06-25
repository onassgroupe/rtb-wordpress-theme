<?php

namespace RTB\Api;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Limiteur de débit anti-abus pour le namespace rtb/v1.
 * Fenêtre fixe par minute et par IP (transients) — léger, sans dépendance.
 */
final class RateLimiter {

	private const MAX_PER_MINUTE = 120;

	/** @param mixed $result */
	public function maybeLimit( $result, $server, $request ) {
		// Laisse passer si une réponse/erreur est déjà fixée, ou hors de notre namespace.
		if ( null !== $result || ! $request instanceof WP_REST_Request ) {
			return $result;
		}
		if ( 0 !== strpos( ltrim( (string) $request->get_route(), '/' ), RTB_API_NS ) ) {
			return $result;
		}

		$bucket = 'rtb_api_rl_' . md5( $this->clientIp() ) . '_' . (int) floor( time() / MINUTE_IN_SECONDS );
		$count  = (int) get_transient( $bucket );

		if ( $count >= self::MAX_PER_MINUTE ) {
			return new WP_Error(
				'rtb_rate_limited',
				'Trop de requêtes — réessayez dans un instant.',
				[ 'status' => 429 ]
			);
		}
		set_transient( $bucket, $count + 1, 2 * MINUTE_IN_SECONDS );
		return $result;
	}

	/** IP réelle, en tenant compte du proxy (Coolify/Traefik). */
	private function clientIp(): string {
		$candidates = [];
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$candidates = explode( ',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'] );
		}
		$candidates[] = $_SERVER['REMOTE_ADDR'] ?? '';
		foreach ( $candidates as $ip ) {
			$ip = trim( (string) $ip );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
		return 'unknown';
	}
}
