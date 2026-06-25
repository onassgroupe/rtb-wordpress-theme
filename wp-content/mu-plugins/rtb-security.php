<?php
/**
 * RTB Security Hardening (mu-plugin) — auto-chargé, sans configuration.
 *
 * Corrige les points relevés à l'audit :
 *  1. Énumération d'utilisateurs : ferme /wp-json/wp/v2/users (anonyme) + ?author=
 *  2. xmlrpc.php désactivé entièrement (amplification brute-force / pingback)
 *  3. En-têtes de sécurité HTTP (clickjacking, MIME-sniffing, HSTS, referrer…)
 *  5. Masque la divulgation de version (X-Powered-By)
 *
 * Volontairement conservateur : aucune CSP stricte (casserait Alpine/YouTube/polices) —
 * à ajouter en Report-Only plus tard si besoin.
 */

defined( 'ABSPATH' ) || exit;

/* ── 2) Couper xmlrpc.php au plus tôt (la requête définit XMLRPC_REQUEST avant wp-load). ── */
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	exit( 'XML-RPC services are disabled on this site.' );
}
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', static fn() => [] );
// Retire l'en-tête X-Pingback (et la balise RSD) qui annoncent xmlrpc.
add_filter( 'wp_headers', static function ( array $headers ): array {
	unset( $headers['X-Pingback'] );
	return $headers;
} );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_generator' ); // masque <meta generator> (version WP)

/* ── 1) Énumération d'utilisateurs via l'API REST (anonyme uniquement). ── */
add_filter( 'rest_endpoints', static function ( array $endpoints ): array {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}
	unset( $endpoints['/wp/v2/users'] );
	unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	return $endpoints;
} );

/* ── 1bis) Énumération via ?author=N (redirige les archives d'auteur vers l'accueil). ── */
add_action( 'template_redirect', static function (): void {
	if ( is_user_logged_in() ) {
		return;
	}
	$is_author_probe = isset( $_GET['author'] ) || is_author();
	if ( $is_author_probe ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}, 0 );

/* ── 3 + 5) En-têtes de sécurité HTTP sur chaque réponse. ── */
add_action( 'send_headers', static function (): void {
	header_remove( 'X-Powered-By' ); // 5) divulgation de version PHP
	header( 'X-Frame-Options: SAMEORIGIN' );          // anti-clickjacking
	header( 'X-Content-Type-Options: nosniff' );       // anti MIME-sniffing
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: geolocation=(), microphone=(), camera=(), interest-cohort=()' );
	header( 'Cross-Origin-Opener-Policy: same-origin' );
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload' );
	}
} );
