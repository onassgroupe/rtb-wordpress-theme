<?php

namespace RTB\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * Liens alternatifs hreflang (multilingue rtb-i18n) — fr/en/mos/dyu/ff/gux.
 * Chaque langue pointe vers la même page sous son préfixe d'URL.
 */
final class Hreflang {

	public function render(): void {
		if ( ! function_exists( 'rtb_current_lang' ) || ! defined( 'RTB_INNER_PATH' ) ) {
			return;
		}

		$inner   = RTB_INNER_PATH;
		$qs      = ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
		$locales = [ 'fr', 'en', 'mos', 'dyu', 'ff', 'gux' ];

		$out = '';
		foreach ( $locales as $code ) {
			$href = home_url( '/' . $code . $inner ) . $qs;
			$out .= '<link rel="alternate" hreflang="' . esc_attr( $code ) . '" href="' . esc_url( $href ) . '">' . "\n";
		}
		$out .= '<link rel="alternate" hreflang="x-default" href="' . esc_url( home_url( '/fr' . $inner ) . $qs ) . '">' . "\n";
		echo $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- déjà échappé ci-dessus.
	}
}
