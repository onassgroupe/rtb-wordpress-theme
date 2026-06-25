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
		// No Polylang flag here (the UI uses emoji flags); a bad flag code would
		// make add_language fail. Retry without flag if a first attempt errors.
		$res = $model->add_language( [
			'name'       => $def['name'],
			'slug'       => $def['slug'],
			'locale'     => $def['locale'],
			'rtl'        => 0,
			'term_group' => $def['term_group'],
			'flag'       => 'bf',
		] );
		if ( is_wp_error( $res ) ) {
			$res = $model->add_language( [
				'name'       => $def['name'],
				'slug'       => $def['slug'],
				'locale'     => $def['locale'],
				'rtl'        => 0,
				'term_group' => $def['term_group'],
			] );
		}
		if ( ! is_wp_error( $res ) ) {
			$added = true;
		}
	}

	if ( $added && method_exists( $model, 'clean_languages_cache' ) ) {
		$model->clean_languages_cache();
	}
	if ( $added && function_exists( 'rtb_cache_clear' ) ) {
		rtb_cache_clear();
	}
}
add_action( 'admin_init', 'rtb_register_polylang_languages' );

/**
 * Traductions d'interface (chaînes du thème) par langue.
 * Anglais : complet et fiable. Langues nationales : BROUILLON généré
 * automatiquement, À FAIRE VALIDER par la RTB dans
 * Réglages → Langues → Traductions des chaînes.
 * @return array<string,array<string,string>>
 */
function rtb_string_translations(): array {
	return [
		'en'  => [
			'Accueil' => 'Home', 'Le Direct' => 'Live', 'Actualités' => 'News', 'Le Journal' => 'Newscast',
			'Émissions' => 'Shows', 'Sport' => 'Sports', 'Régions' => 'Regions', 'Contact' => 'Contact',
			'EN DIRECT' => 'LIVE', 'Regarder en direct' => 'Watch live', 'Guide des programmes' => 'TV guide',
			'Rechercher' => 'Search', 'RECHERCHER SUR RTB' => 'SEARCH RTB', "Toute l'actualité" => 'All the news',
			"À L'ANTENNE MAINTENANT" => 'ON AIR NOW', 'DERNIÈRE MINUTE' => 'BREAKING NEWS',
			'Recherches fréquentes' => 'Popular searches', 'Voir la chaîne' => 'View channel',
			'Regarder la chaîne' => 'Watch channel', 'Toutes les chaînes' => 'All channels',
			'Toutes les émissions' => 'All shows', "L'info des régions" => 'Regional news', 'Tout' => 'All',
			'À LA UNE' => 'TOP STORIES', 'LES GROS TITRES' => 'HEADLINES', 'INFORMATION' => 'NEWS',
			'Le Journal Télévisé' => 'TV Newscast', 'NOS ANTENNES' => 'OUR CHANNELS',
			'GRANDS RENDEZ-VOUS' => 'HIGHLIGHTS', 'PROXIMITÉ' => 'LOCAL',
			'La RTB en régions' => 'RTB across the regions', 'RADIO EN DIRECT' => 'LIVE RADIO',
			'PROGRAMMES' => 'PROGRAMS', 'PLUS DE VIDÉOS' => 'MORE VIDEOS',
			'CATÉGORIES POPULAIRES' => 'POPULAR CATEGORIES',
		],
		'mos' => [
			'Accueil' => 'Yiri', 'Le Direct' => 'Sasa', 'Actualités' => 'Kibaya', 'Le Journal' => 'Kibar-kãsenga',
			'Émissions' => 'Yɛlsgo', 'Sport' => 'Sport', 'Régions' => 'Tẽnsã', 'Contact' => 'Kɛɛnse',
			'EN DIRECT' => 'SASA', 'Rechercher' => 'Bao', 'Tout' => 'Fãa',
		],
		'dyu' => [
			'Accueil' => 'So', 'Le Direct' => 'Sisan', 'Actualités' => 'Kibaruyaw', 'Le Journal' => 'Kunnafoni',
			'Émissions' => 'Porogaramuw', 'Sport' => 'Farikoloɲɛnajɛ', 'Régions' => 'Marabolow', 'Contact' => 'Ɲɔgɔnye',
			'EN DIRECT' => 'SISAN', 'Rechercher' => 'Ɲini', 'Tout' => 'Bɛɛ',
		],
		'ff'  => [
			'Accueil' => 'Suudu', 'Le Direct' => 'Jooni', 'Actualités' => 'Kabaruuji', 'Le Journal' => 'Kabaaru',
			'Émissions' => 'Eɓɓooje', 'Sport' => 'Coftal ɓalli', 'Régions' => 'Diiwanuuji', 'Contact' => 'Jokkondiral',
			'EN DIRECT' => 'JOONI', 'Rechercher' => 'Ɗaɓɓude', 'Tout' => 'Fof',
		],
		'gux' => [
			'Accueil' => 'Deni', 'Le Direct' => 'Mɔanu', 'Actualités' => 'Labaali', 'Sport' => 'Sport',
			'EN DIRECT' => 'MƆANU', 'Rechercher' => 'Lingidi', 'Tout' => 'Kuli',
		],
	];
}

/** Pré-remplit les traductions de chaînes Polylang (sans écraser une saisie existante). */
function rtb_seed_string_translations(): void {
	if ( ! class_exists( 'PLL_MO' ) || ! function_exists( 'PLL' ) || ! PLL() || ! isset( PLL()->model ) ) {
		return;
	}
	$map = rtb_string_translations();
	$any = false;
	foreach ( PLL()->model->get_languages_list() as $lang ) {
		if ( empty( $map[ $lang->slug ] ) ) {
			continue;
		}
		$mo      = new PLL_MO();
		$mo->import_from_db( $lang );
		$changed = false;
		foreach ( $map[ $lang->slug ] as $src => $tr ) {
			$cur = $mo->translate( $src );
			if ( '' === $cur || $cur === $src ) { // pas encore traduit → on sème le brouillon
				$mo->add_entry( $mo->make_entry( $src, $tr ) );
				$changed = true;
			}
		}
		if ( $changed ) {
			$mo->export_to_db( $lang );
			$any = true;
		}
	}
	// De nouvelles traductions → purge le cache de page pour que le front se rafraîchisse.
	if ( $any && function_exists( 'rtb_cache_clear' ) ) {
		rtb_cache_clear();
	}
}
add_action( 'admin_init', 'rtb_seed_string_translations', 20 );

/**
 * URL interne consciente de la langue : préfixe le chemin avec la langue
 * courante (sauf langue par défaut), pour conserver la langue à la navigation.
 */
function rtb_lurl( string $path = '/' ): string {
	$path = '/' . ltrim( $path, '/' );
	$cur  = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : '';
	$def  = function_exists( 'pll_default_language' ) ? pll_default_language() : 'fr';
	if ( ! $cur || $cur === $def ) {
		return home_url( $path );
	}
	// If the path maps to a static page, return its translation's real permalink
	// (unique slug) so the URL resolves cleanly instead of 301-redirecting.
	$clean = trim( $path, '/' );
	if ( '' !== $clean && false === strpos( $clean, '#' ) && false === strpos( $clean, '?' ) && function_exists( 'pll_get_post' ) ) {
		$src = get_page_by_path( $clean );
		if ( $src ) {
			$tid = pll_get_post( $src->ID, $cur );
			if ( $tid ) {
				return get_permalink( $tid );
			}
		}
	}
	// Fallback: prefix the language (home, CPT archives, translated categories…).
	return home_url( '/' . $cur . $path );
}

/**
 * Crée les traductions Polylang des pages statiques clés si elles manquent,
 * pour que /xx/direct, /xx/radio, etc. se résolvent (rendu via le template +
 * traductions de chaînes ; contenu éditorial à traduire ensuite par la RTB).
 */
function rtb_ensure_page_translations(): void {
	if ( ! function_exists( 'pll_set_post_language' ) || ! function_exists( 'pll_save_post_translations' )
		|| ! function_exists( 'pll_get_post' ) || ! function_exists( 'PLL' ) || ! PLL() ) {
		return;
	}
	$def   = function_exists( 'pll_default_language' ) ? pll_default_language() : 'fr';
	$langs = [];
	foreach ( PLL()->model->get_languages_list() as $l ) {
		$langs[] = $l->slug;
	}

	$created = false;
	foreach ( [ 'direct', 'radio', 'grille', 'contact', 'regions', 'a-propos', 'plan-du-site' ] as $slug ) {
		$src = get_page_by_path( $slug );
		if ( ! $src ) {
			continue;
		}
		if ( ! pll_get_post( $src->ID, $def ) ) {
			pll_set_post_language( $src->ID, $def );
		}
		$group = [ $def => (int) $src->ID ];
		$tpl   = get_post_meta( $src->ID, '_wp_page_template', true );

		foreach ( $langs as $lang ) {
			if ( $lang === $def ) {
				continue;
			}
			$existing = pll_get_post( $src->ID, $lang );
			if ( $existing ) {
				$group[ $lang ] = (int) $existing;
				continue;
			}
			$new_id = wp_insert_post( [
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $src->post_title,
				'post_name'    => $src->post_name . '-' . $lang, // unique slug per language
				'post_content' => $src->post_content,
				'post_parent'  => $src->post_parent,
			] );
			if ( $new_id && ! is_wp_error( $new_id ) ) {
				pll_set_post_language( $new_id, $lang );
				if ( $tpl ) {
					update_post_meta( $new_id, '_wp_page_template', $tpl );
				}
				$group[ $lang ] = (int) $new_id;
				$created        = true;
			}
		}
		pll_save_post_translations( $group );
	}

	if ( $created && function_exists( 'rtb_cache_clear' ) ) {
		rtb_cache_clear();
	}
}
add_action( 'admin_init', 'rtb_ensure_page_translations', 30 );

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
