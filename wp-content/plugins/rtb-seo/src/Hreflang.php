<?php

namespace RTB\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * Liens alternatifs hreflang (multilingue Polylang) — fr/en/mos/dyu/ff/gux.
 */
final class Hreflang {

	public function render(): void {
		if ( ! function_exists( 'pll_the_languages' ) ) {
			return;
		}
		$langs = pll_the_languages( [ 'raw' => 1, 'hide_if_no_translation' => 0 ] );
		if ( ! is_array( $langs ) || count( $langs ) < 2 ) {
			return;
		}

		$default = function_exists( 'pll_default_language' ) ? pll_default_language() : '';
		$out     = '';
		foreach ( $langs as $l ) {
			if ( empty( $l['url'] ) ) {
				continue;
			}
			$code = ! empty( $l['locale'] ) ? str_replace( '_', '-', $l['locale'] ) : ( $l['slug'] ?? '' );
			if ( '' === $code ) {
				continue;
			}
			$out .= '<link rel="alternate" hreflang="' . esc_attr( $code ) . '" href="' . esc_url( $l['url'] ) . '">' . "\n";
			if ( ! empty( $default ) && ( $l['slug'] ?? '' ) === $default ) {
				$out .= '<link rel="alternate" hreflang="x-default" href="' . esc_url( $l['url'] ) . '">' . "\n";
			}
		}
		echo $out;
	}
}
