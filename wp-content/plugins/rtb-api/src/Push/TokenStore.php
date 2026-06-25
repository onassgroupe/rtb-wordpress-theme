<?php

namespace RTB\Api\Push;

defined( 'ABSPATH' ) || exit;

/**
 * Stockage des tokens push Expo des appareils (option WP).
 * Clé = token Expo ; valeur = { platform, locale, created, seen }.
 */
final class TokenStore {

	private const OPTION = 'rtb_push_tokens';
	private const MAX    = 20000; // garde-fou mémoire/option

	/** @return array<string,array<string,mixed>> */
	private static function raw(): array {
		$v = get_option( self::OPTION, [] );
		return is_array( $v ) ? $v : [];
	}

	/** Token Expo valide : ExponentPushToken[...] ou ExpoPushToken[...]. */
	public static function isValid( string $token ): bool {
		return (bool) preg_match( '/^Expo(nent)?PushToken\[[^\]]+\]$/', $token );
	}

	public static function add( string $token, string $platform, string $locale ): bool {
		if ( ! self::isValid( $token ) ) {
			return false;
		}
		$tokens = self::raw();
		if ( ! isset( $tokens[ $token ] ) && count( $tokens ) >= self::MAX ) {
			return false; // plafond atteint
		}
		$tokens[ $token ] = [
			'platform' => in_array( $platform, [ 'ios', 'android' ], true ) ? $platform : 'unknown',
			'locale'   => preg_replace( '/[^a-z]/', '', strtolower( $locale ) ) ?: 'fr',
			'created'  => $tokens[ $token ]['created'] ?? time(),
			'seen'     => time(),
		];
		update_option( self::OPTION, $tokens, false );
		return true;
	}

	public static function remove( string $token ): void {
		$tokens = self::raw();
		if ( isset( $tokens[ $token ] ) ) {
			unset( $tokens[ $token ] );
			update_option( self::OPTION, $tokens, false );
		}
	}

	/** @param string[] $list */
	public static function removeMany( array $list ): void {
		if ( ! $list ) {
			return;
		}
		$tokens = self::raw();
		foreach ( $list as $t ) {
			unset( $tokens[ $t ] );
		}
		update_option( self::OPTION, $tokens, false );
	}

	/** @return string[] tous les tokens enregistrés */
	public static function all(): array {
		return array_keys( self::raw() );
	}

	public static function count(): int {
		return count( self::raw() );
	}
}
