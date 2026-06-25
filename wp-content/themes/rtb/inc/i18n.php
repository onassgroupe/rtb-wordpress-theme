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
 * Langues de la RTB : français, anglais + 4 langues nationales du Burkina.
 * @return array<int,array{name:string,slug:string,locale:string,flag:string,term_group:int}>
 */
function rtb_lang_defs(): array {
	return [
		[ 'name' => 'Français',   'slug' => 'fr',  'locale' => 'fr_FR', 'flag' => 'fr', 'term_group' => 0 ],
		[ 'name' => 'English',    'slug' => 'en',  'locale' => 'en_US', 'flag' => 'us', 'term_group' => 1 ],
		[ 'name' => 'Mooré',      'slug' => 'mos', 'locale' => 'mos',   'flag' => 'bf', 'term_group' => 2 ],
		[ 'name' => 'Dioula',     'slug' => 'dyu', 'locale' => 'dyu',   'flag' => 'bf', 'term_group' => 3 ],
		[ 'name' => 'Fulfuldé',   'slug' => 'ff',  'locale' => 'ff',    'flag' => 'bf', 'term_group' => 4 ],
		[ 'name' => 'Gulmancéma', 'slug' => 'gux', 'locale' => 'gux',   'flag' => 'bf', 'term_group' => 5 ],
	];
}

/** Drapeau (emoji) associé à un code langue. Les langues nationales = drapeau du Burkina. */
function rtb_lang_flag( string $slug ): string {
	$map = [ 'fr' => '🇫🇷', 'en' => '🇬🇧', 'mos' => '🇧🇫', 'dyu' => '🇧🇫', 'ff' => '🇧🇫', 'gux' => '🇧🇫' ];
	return $map[ strtolower( $slug ) ] ?? '🌐';
}

/**
 * Enregistre les langues RTB dans Polylang si elles manquent.
 * Exécuté en admin uniquement (création ponctuelle), via l'API du modèle Polylang.
 */
function rtb_register_polylang_languages(): void {
	if ( ! function_exists( 'PLL' ) || ! PLL() || ! isset( PLL()->model ) ) {
		return;
	}
	$model = PLL()->model;
	if ( ! method_exists( $model, 'add_language' ) ) {
		return;
	}

	$have = [];
	foreach ( $model->get_languages_list() as $l ) {
		$have[ $l->slug ] = true;
	}

	$added = false;
	foreach ( rtb_lang_defs() as $def ) {
		if ( isset( $have[ $def['slug'] ] ) ) {
			continue;
		}
		$res = $model->add_language( [
			'name'       => $def['name'],
			'slug'       => $def['slug'],
			'locale'     => $def['locale'],
			'rtl'        => 0,
			'flag'       => $def['flag'],
			'term_group' => $def['term_group'],
		] );
		if ( ! is_wp_error( $res ) ) {
			$added = true;
		}
	}

	if ( $added && method_exists( $model, 'clean_languages_cache' ) ) {
		$model->clean_languages_cache();
	}
}
add_action( 'admin_init', 'rtb_register_polylang_languages' );

/**
 * Langues affichées dans le sélecteur (noms natifs, jamais traduits).
 * Utilise Polylang quand il est configuré ; sinon retombe sur la liste statique.
 * @return array<int,array{name:string,slug:string,flag:string,url:string,current:bool}>
 */
function rtb_languages(): array {
	$defaults = [];
	foreach ( rtb_lang_defs() as $def ) {
		$defaults[] = [
			'name'    => $def['name'],
			'slug'    => $def['slug'],
			'flag'    => rtb_lang_flag( $def['slug'] ),
			'url'     => 'fr' === $def['slug'] ? home_url( '/' ) : '#',
			'current' => 'fr' === $def['slug'],
		];
	}

	if ( ! function_exists( 'pll_the_languages' ) ) {
		return $defaults;
	}
	$list = pll_the_languages( [ 'raw' => 1, 'hide_if_empty' => 0, 'display_names_as' => 'name' ] );
	if ( ! is_array( $list ) || ! $list ) {
		return $defaults;
	}
	$out = [];
	foreach ( $list as $l ) {
		$slug  = $l['slug'] ?? '';
		$out[] = [
			'name'    => $l['name'] ?? strtoupper( $slug ),
			'slug'    => $slug,
			'flag'    => rtb_lang_flag( $slug ),
			'url'     => $l['url'] ?? '#',
			'current' => ! empty( $l['current_lang'] ),
		];
	}
	return $out;
}
