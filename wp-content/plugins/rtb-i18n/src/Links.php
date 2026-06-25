<?php

namespace RTB\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * Liens conscients de la langue : helper pour le thème + filtres sur les
 * permaliens WordPress + redirection des URLs non préfixées.
 */
final class Links {

	/** URL interne préfixée par la locale courante. */
	public static function url( string $path = '/' ): string {
		$path = '/' . ltrim( $path, '/' );
		return home_url( '/' . Locale::current() . $path );
	}

	/** Préfixe un permalien interne avec la locale courante (idempotent). */
	public static function prefix( $url ) {
		if ( is_admin() || ! is_string( $url ) ) {
			return $url;
		}
		$home = home_url( '/' );
		if ( 0 !== strpos( $url, $home ) ) {
			return $url; // externe
		}
		$rest = ltrim( substr( $url, strlen( $home ) ), '/' );
		if ( preg_match( '#^(' . implode( '|', Locale::LOCALES ) . ')(/|$)#', $rest ) ) {
			return $url; // déjà préfixé
		}
		if ( preg_match( '#^(wp-|xmlrpc|feed)#', $rest ) ) {
			return $url; // technique
		}
		return $home . Locale::current() . '/' . $rest;
	}

	/** Redirige les URLs front non préfixées vers la locale par défaut. */
	public static function maybeRedirect(): void {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() || is_robots() ) {
			return;
		}
		if ( Locale::isPrefixed() || is_404() ) {
			return;
		}
		$uri = $_SERVER['REQUEST_URI'] ?? '/';
		wp_safe_redirect( home_url( '/' . Locale::DEFAULT_LANG . '/' . ltrim( $uri, '/' ) ), 302 );
		exit;
	}
}
