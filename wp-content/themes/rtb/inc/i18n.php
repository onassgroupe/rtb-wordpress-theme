<?php
/**
 * RTB — repli i18n.
 *
 * La logique multilingue vit désormais dans le plugin **RTB i18n** (préfixe
 * d'URL). Ce fichier ne fournit que des fonctions de repli, utilisées si le
 * plugin est inactif : le site reste alors fonctionnel en français.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'rtb_current_lang' ) ) {
	function rtb_current_lang(): string {
		return 'fr';
	}
}

if ( ! function_exists( 'rtb_t' ) ) {
	function rtb_t( string $s ): string {
		return $s;
	}
}

if ( ! function_exists( 'rtb_lurl' ) ) {
	function rtb_lurl( string $path = '/' ): string {
		return home_url( '/' . ltrim( $path, '/' ) );
	}
}

if ( ! function_exists( 'rtb_lang_defs' ) ) {
	function rtb_lang_defs(): array {
		return [
			[ 'name' => 'Français',   'slug' => 'fr',  'flag' => '🇫🇷' ],
			[ 'name' => 'English',    'slug' => 'en',  'flag' => '🇬🇧' ],
			[ 'name' => 'Mooré',      'slug' => 'mos', 'flag' => '🇧🇫' ],
			[ 'name' => 'Dioula',     'slug' => 'dyu', 'flag' => '🇧🇫' ],
			[ 'name' => 'Fulfuldé',   'slug' => 'ff',  'flag' => '🇧🇫' ],
			[ 'name' => 'Gulmancéma', 'slug' => 'gux', 'flag' => '🇧🇫' ],
		];
	}
}

if ( ! function_exists( 'rtb_lang_flag' ) ) {
	function rtb_lang_flag( string $slug ): string {
		foreach ( rtb_lang_defs() as $l ) {
			if ( $l['slug'] === strtolower( $slug ) ) {
				return $l['flag'];
			}
		}
		return '🌐';
	}
}

if ( ! function_exists( 'rtb_languages' ) ) {
	function rtb_languages(): array {
		$out = [];
		foreach ( rtb_lang_defs() as $d ) {
			$out[] = [
				'name'    => $d['name'],
				'slug'    => $d['slug'],
				'flag'    => $d['flag'],
				'url'     => 'fr' === $d['slug'] ? home_url( '/' ) : '#',
				'current' => 'fr' === $d['slug'],
			];
		}
		return $out;
	}
}

if ( ! function_exists( 'pll__' ) ) {
	function pll__( $s ) { return $s; }
}
if ( ! function_exists( 'pll_e' ) ) {
	function pll_e( $s ) { echo esc_html( $s ); }
}
if ( ! function_exists( 'pll_current_language' ) ) {
	function pll_current_language( $field = 'slug' ) { return 'fr'; }
}
