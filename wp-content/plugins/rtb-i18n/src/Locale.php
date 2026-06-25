<?php

namespace RTB\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * État de la locale courante, détectée depuis le préfixe d'URL (/{locale}/...).
 */
final class Locale {

	public const LOCALES      = [ 'fr', 'en', 'mos', 'dyu', 'ff', 'gux' ];
	public const DEFAULT_LANG = 'fr';

	private static string $current  = self::DEFAULT_LANG;
	private static string $inner    = '/';
	private static bool   $prefixed = false;

	/**
	 * Détecte et retire le préfixe de langue de REQUEST_URI, avant que WordPress
	 * ne parse la requête, pour que le contenu sous-jacent (partagé) soit résolu.
	 */
	public static function detect(): void {
		$uri   = $_SERVER['REQUEST_URI'] ?? '/';
		$split = explode( '?', $uri, 2 );
		$path  = $split[0];
		$qs    = isset( $split[1] ) ? '?' . $split[1] : '';

		if ( preg_match( '#^/(' . implode( '|', self::LOCALES ) . ')(/.*)?$#', $path, $m ) ) {
			self::$current  = $m[1];
			self::$inner    = ( isset( $m[2] ) && '' !== $m[2] ) ? $m[2] : '/';
			self::$prefixed = true;
			$_SERVER['REQUEST_URI'] = self::$inner . $qs;
			if ( isset( $_SERVER['PATH_INFO'] ) ) {
				$_SERVER['PATH_INFO'] = self::$inner;
			}
		} else {
			self::$inner = $path;
		}
	}

	public static function current(): string {
		return self::$current;
	}

	public static function inner(): string {
		return self::$inner;
	}

	public static function isPrefixed(): bool {
		return self::$prefixed;
	}

	public static function isDefault(): bool {
		return self::DEFAULT_LANG === self::$current;
	}

	/** @return array<int,array{name:string,slug:string,flag:string}> */
	public static function all(): array {
		return [
			[ 'name' => 'Français',   'slug' => 'fr',  'flag' => '🇫🇷' ],
			[ 'name' => 'English',    'slug' => 'en',  'flag' => '🇬🇧' ],
			[ 'name' => 'Mooré',      'slug' => 'mos', 'flag' => '🇧🇫' ],
			[ 'name' => 'Dioula',     'slug' => 'dyu', 'flag' => '🇧🇫' ],
			[ 'name' => 'Fulfuldé',   'slug' => 'ff',  'flag' => '🇧🇫' ],
			[ 'name' => 'Gulmancéma', 'slug' => 'gux', 'flag' => '🇧🇫' ],
		];
	}

	public static function flag( string $slug ): string {
		foreach ( self::all() as $l ) {
			if ( $l['slug'] === strtolower( $slug ) ) {
				return $l['flag'];
			}
		}
		return '🌐';
	}
}
