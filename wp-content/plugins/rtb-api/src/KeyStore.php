<?php

namespace RTB\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Gestion dynamique des clés API (création, liste, révocation).
 * Seuls des HASH SHA-256 sont stockés — la clé en clair n'est affichée qu'une fois.
 */
final class KeyStore {

	private const OPTION = 'rtb_api_keys';

	/** @return array<string,array<string,mixed>> enregistrements bruts (id => record) */
	private static function raw(): array {
		$v = get_option( self::OPTION, [] );
		return is_array( $v ) ? $v : [];
	}

	/** Liste pour l'admin (sans le hash). @return array<int,array<string,mixed>> */
	public static function all(): array {
		$out = [];
		foreach ( self::raw() as $rec ) {
			unset( $rec['hash'] );
			$out[] = $rec;
		}
		usort( $out, static fn( $a, $b ) => ( $b['created'] ?? 0 ) <=> ( $a['created'] ?? 0 ) );
		return $out;
	}

	/** Crée une clé et renvoie la valeur EN CLAIR (à montrer une seule fois). */
	public static function create( string $label ): string {
		$plain          = wp_generate_password( 48, false, false );
		$id             = 'k_' . bin2hex( random_bytes( 6 ) );
		$keys           = self::raw();
		$keys[ $id ]    = [
			'id'        => $id,
			'label'     => sanitize_text_field( $label ) ?: 'Sans nom',
			'prefix'    => substr( $plain, 0, 6 ),
			'hash'      => hash( 'sha256', $plain ),
			'created'   => time(),
			'last_used' => 0,
			'revoked'   => false,
		];
		update_option( self::OPTION, $keys, false );
		return $plain;
	}

	public static function revoke( string $id ): void {
		$keys = self::raw();
		if ( isset( $keys[ $id ] ) ) {
			$keys[ $id ]['revoked'] = true;
			update_option( self::OPTION, $keys, false );
		}
	}

	public static function delete( string $id ): void {
		$keys = self::raw();
		if ( isset( $keys[ $id ] ) ) {
			unset( $keys[ $id ] );
			update_option( self::OPTION, $keys, false );
		}
	}

	/** Vérifie une clé en clair contre les clés actives (comparaison constante). */
	public static function verify( string $plain ): bool {
		$hash = hash( 'sha256', $plain );
		$keys = self::raw();
		$hit  = null;
		foreach ( $keys as $id => $rec ) {
			if ( empty( $rec['revoked'] ) && hash_equals( (string) ( $rec['hash'] ?? '' ), $hash ) ) {
				$hit = $id;
				break;
			}
		}
		if ( null === $hit ) {
			return false;
		}
		// Met à jour "dernière utilisation" au plus une fois par heure (limite les écritures).
		$now = time();
		if ( $now - (int) ( $keys[ $hit ]['last_used'] ?? 0 ) > HOUR_IN_SECONDS ) {
			$keys[ $hit ]['last_used'] = $now;
			update_option( self::OPTION, $keys, false );
		}
		return true;
	}

	/** Migration : convertit l'ancienne clé unique (option rtb_api_key) en enregistrement. */
	public static function maybeMigrate(): void {
		if ( ! empty( self::raw() ) ) {
			return;
		}
		$legacy = (string) get_option( 'rtb_api_key', '' );
		if ( '' === $legacy ) {
			return;
		}
		$id   = 'k_' . bin2hex( random_bytes( 6 ) );
		$keys = [
			$id => [
				'id'        => $id,
				'label'     => 'Clé initiale',
				'prefix'    => substr( $legacy, 0, 6 ),
				'hash'      => hash( 'sha256', $legacy ),
				'created'   => time(),
				'last_used' => 0,
				'revoked'   => false,
			],
		];
		update_option( self::OPTION, $keys, false );
		delete_option( 'rtb_api_key' );
	}
}
