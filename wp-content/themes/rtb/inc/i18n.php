<?php
/**
 * RTB — couche multilingue (Polylang).
 * - Shims si Polylang est inactif (le site reste fonctionnel).
 * - Enregistrement des chaînes du thème → traduisibles dans
 *   l'admin (Langues → Traductions des chaînes).
 * - Helper du sélecteur de langue.
 */

defined( 'ABSPATH' ) || exit;

/* Shims — Polylang désactivé : on renvoie la chaîne telle quelle. */
if ( ! function_exists( 'pll__' ) ) {
	function pll__( $s ) { return $s; }
}
if ( ! function_exists( 'pll_e' ) ) {
	function pll_e( $s ) { echo esc_html( $s ); }
}
if ( ! function_exists( 'pll_register_string' ) ) {
	function pll_register_string( $name, $string, $group = 'RTB', $multiline = false ) {}
}
if ( ! function_exists( 'pll_current_language' ) ) {
	function pll_current_language( $field = 'slug' ) { return 'fr'; }
}

/** Traduction courte d'une chaîne du thème. */
function rtb_t( string $s ): string {
	return pll__( $s );
}

/** Liste des chaînes de l'UI du thème, enregistrées pour traduction admin. */
function rtb_theme_strings(): array {
	return [
		// Navigation
		'Accueil', 'Le Direct', 'Actualités', 'Le Journal', 'Émissions', 'Sport', 'Régions', 'Contact',
		// Actions / labels
		'EN DIRECT', 'Regarder en direct', 'Guide des programmes', 'Rechercher', 'RECHERCHER SUR RTB',
		"Toute l'actualité", "À L'ANTENNE MAINTENANT", 'DERNIÈRE MINUTE', 'Recherches fréquentes',
		'Voir la chaîne', 'Regarder la chaîne', 'Toutes les chaînes', 'Toutes les émissions', "L'info des régions", 'Tout',
		// Sur-titres / sections
		'À LA UNE', 'LES GROS TITRES', 'INFORMATION', 'Le Journal Télévisé', 'NOS ANTENNES',
		'GRANDS RENDEZ-VOUS', 'PROXIMITÉ', 'La RTB en régions', 'RADIO EN DIRECT', 'PROGRAMMES',
		// Footer
		'PLUS DE VIDÉOS', 'CATÉGORIES POPULAIRES',
	];
}

/* Rendre les CPT/taxonomies du thème traduisibles par Polylang. */
add_filter( 'pll_get_post_types', function ( $types, $is_settings ) {
	$types['rtb_emission'] = 'rtb_emission';
	return $types;
}, 10, 2 );
add_filter( 'pll_get_taxonomies', function ( $tax, $is_settings ) {
	$tax['rtb_emission_cat'] = 'rtb_emission_cat';
	$tax['rtb_programme']    = 'rtb_programme';
	return $tax;
}, 10, 2 );

/** Enregistre les chaînes pour l'éditeur de traductions Polylang. */
function rtb_register_strings(): void {
	foreach ( rtb_theme_strings() as $s ) {
		pll_register_string( $s, $s, 'RTB Thème' );
	}
}
add_action( 'init', 'rtb_register_strings' );

/**
 * Langues disponibles pour le sélecteur (format simple).
 * @return array<int,array{name:string,slug:string,url:string,current:bool}>
 */
function rtb_languages(): array {
	if ( ! function_exists( 'pll_the_languages' ) ) {
		return [];
	}
	$list = pll_the_languages( [ 'raw' => 1, 'hide_if_empty' => 0, 'display_names_as' => 'name' ] );
	if ( ! is_array( $list ) ) {
		return [];
	}
	$out = [];
	foreach ( $list as $l ) {
		$out[] = [
			'name'    => $l['name'] ?? strtoupper( $l['slug'] ?? '' ),
			'slug'    => strtoupper( $l['slug'] ?? '' ),
			'url'     => $l['url'] ?? '#',
			'current' => ! empty( $l['current_lang'] ),
		];
	}
	return $out;
}
