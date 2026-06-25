<?php
/**
 * API globale du plugin RTB i18n — fonctions utilisées par les templates du thème.
 *
 * Les fonctions sont gardées par function_exists : sur le front, les plugins
 * sont chargés AVANT le thème, donc ces versions (plugin) gagnent ; le thème ne
 * fournit que des replis si le plugin est inactif.
 *
 * @package RTB\I18n
 */

use RTB\I18n\Links;
use RTB\I18n\Locale;
use RTB\I18n\Switcher;
use RTB\I18n\Translator;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'rtb_current_lang' ) ) {
	function rtb_current_lang(): string {
		return Locale::current();
	}
}

if ( ! function_exists( 'rtb_t' ) ) {
	function rtb_t( string $s ): string {
		return Translator::t( $s );
	}
}

if ( ! function_exists( 'rtb_lurl' ) ) {
	function rtb_lurl( string $path = '/' ): string {
		return Links::url( $path );
	}
}

if ( ! function_exists( 'rtb_languages' ) ) {
	function rtb_languages(): array {
		return Switcher::languages();
	}
}

if ( ! function_exists( 'rtb_lang_defs' ) ) {
	function rtb_lang_defs(): array {
		return Locale::all();
	}
}

if ( ! function_exists( 'rtb_lang_flag' ) ) {
	function rtb_lang_flag( string $slug ): string {
		return Locale::flag( $slug );
	}
}

if ( ! function_exists( 'pll__' ) ) {
	function pll__( $s ) { return rtb_t( (string) $s ); }
}
if ( ! function_exists( 'pll_e' ) ) {
	function pll_e( $s ) { echo esc_html( rtb_t( (string) $s ) ); }
}
if ( ! function_exists( 'pll_current_language' ) ) {
	function pll_current_language( $field = 'slug' ) { return rtb_current_lang(); }
}
