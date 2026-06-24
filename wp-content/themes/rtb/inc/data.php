<?php
/**
 * RTB — données de fallback (issues de la maquette).
 * Utilisées tant que les CPT / options ne sont pas remplis, et par le seeder.
 */

defined( 'ABSPATH' ) || exit;

/** URL d'une image asset du thème */
function rtb_img( string $file ): string {
	return get_template_directory_uri() . '/assets/img/' . $file;
}

/**
 * Grille des programmes (EPG) — journée type par chaîne.
 * Chaque créneau : [heure HH:MM, titre, catégorie].
 */
function rtb_get_schedule(): array {
	return [
		[
			'name' => 'RTB Télévision', 'kind' => 'TV', 'accent' => '#10A653',
			'slots' => [
				[ '06:00', 'Réveil RTB', 'Magazine' ],
				[ '07:30', 'Journal Afrique', 'Information' ],
				[ '08:30', 'Éducation & Savoirs', 'Magazine' ],
				[ '10:00', 'Documentaire', 'Découverte' ],
				[ '13:00', 'JT de 13H', 'Journal' ],
				[ '13:45', 'Météo & cours des matières', 'Service' ],
				[ '14:00', 'Femmes de valeur', 'Magazine' ],
				[ '16:00', 'Jeunesse', 'Divertissement' ],
				[ '17:30', 'Sport Box', 'Sport' ],
				[ '19:00', 'JT de 19H', 'Journal' ],
				[ '20:00', 'JT de 20H — Le Grand Journal', 'Journal' ],
				[ '21:00', 'Questions Majeures', 'Débat' ],
				[ '22:30', 'Cinéma burkinabè', 'Cinéma' ],
			],
		],
		[
			'name' => 'Télé Zénith', 'kind' => 'TV', 'accent' => '#F5DE00',
			'slots' => [
				[ '07:00', 'Zénith Matin', 'Magazine' ],
				[ '09:00', 'Clips & musique', 'Divertissement' ],
				[ '12:00', 'Cuisine du terroir', 'Magazine' ],
				[ '14:00', 'Série', 'Fiction' ],
				[ '17:00', 'Plateau jeunes', 'Divertissement' ],
				[ '19:30', 'Success — le magazine', 'Magazine' ],
				[ '21:00', 'Soirée concert', 'Culture' ],
				[ '23:00', 'Zénith Nuit', 'Divertissement' ],
			],
		],
		[
			'name' => 'RTB3 Langues Nationales', 'kind' => 'TV', 'accent' => '#E70C2F',
			'slots' => [
				[ '06:30', 'Journal en mooré', 'Journal' ],
				[ '08:00', 'Santé de proximité', 'Magazine' ],
				[ '12:30', 'Journal en dioula', 'Journal' ],
				[ '14:00', 'Agriculture & élevage', 'Magazine' ],
				[ '17:00', 'Journal en fulfuldé', 'Journal' ],
				[ '19:00', 'Tribune des langues', 'Débat' ],
				[ '20:30', 'Journal en gulmancéma', 'Journal' ],
				[ '21:30', 'Contes & traditions', 'Culture' ],
			],
		],
		[
			'name' => 'RTB Guiriko', 'kind' => 'RÉGION', 'accent' => '#10A653',
			'slots' => [
				[ '06:00', 'Guiriko Matin', 'Magazine' ],
				[ '09:00', "L'invité de l'Ouest", 'Entretien' ],
				[ '13:00', 'Journal régional', 'Journal' ],
				[ '15:00', 'Économie locale', 'Magazine' ],
				[ '18:00', 'Sports des Hauts-Bassins', 'Sport' ],
				[ '20:00', 'Grand journal Guiriko', 'Journal' ],
				[ '21:30', 'Soirée culturelle', 'Culture' ],
			],
		],
		[
			'name' => 'Radio Burkina', 'kind' => 'RADIO', 'accent' => '#F5DE00',
			'slots' => [
				[ '05:30', 'Matinale Radio Burkina', 'Magazine' ],
				[ '07:00', 'Journal parlé', 'Journal' ],
				[ '09:00', 'Forum des auditeurs', 'Débat' ],
				[ '12:00', 'Journal parlé de la mi-journée', 'Journal' ],
				[ '14:00', 'Musiques du Faso', 'Divertissement' ],
				[ '17:00', "Le rendez-vous de l'économie", 'Magazine' ],
				[ '19:00', 'Grand journal parlé', 'Journal' ],
				[ '21:00', 'Nuit musicale', 'Divertissement' ],
			],
		],
	];
}

/**
 * Réécrit une URL d'image rtb.bf vers le CDN Jetpack i0.wp.com (redimensionné, sans
 * protection hotlink). Laisse les autres URLs intactes.
 */
function rtb_cdnize( string $url, int $w = 640, int $h = 360 ): string {
	if ( '' === $url ) {
		return $url;
	}
	// Idempotent : on repart TOUJOURS du chemin réel, sans préfixe CDN ni query résiduelle
	// (sinon un double appel laisse un « &ssl=1 » collé au chemin → 404).
	if ( str_contains( $url, 'i0.wp.com' ) ) {
		$url = preg_replace( '#^https?://i0\.wp\.com/#i', '', $url ); // retire le préfixe CDN
	} elseif ( str_contains( $url, 'www.rtb.bf/wp-content' ) ) {
		$url = preg_replace( '#^https?://#', '', $url );
	} else {
		return $url; // pas une image rtb.bf → inchangée
	}
	$path = preg_replace( '#[?&].*$#', '', $url ); // enlève toute query (resize, quality, ssl…)
	return 'https://i0.wp.com/' . ltrim( $path, '/' ) . '?resize=' . $w . ',' . $h . '&quality=72&ssl=1';
}

/** Résout une cover : URL distante (via CDN) telle quelle, sinon asset local du thème. */
function rtb_cover_src( string $cover ): string {
	if ( preg_match( '#^https?://#', $cover ) ) {
		return rtb_cdnize( $cover );
	}
	return rtb_img( $cover );
}

/** Contenus par défaut de la page À propos (utilisés par le Customizer ET le rendu). */
function rtb_about_defaults(): array {
	return array(
		'mission' => "La RTB est le service public audiovisuel du Burkina Faso. Sa mission : garantir un accès équitable à une information fiable, valoriser les cultures nationales et accompagner le développement du pays à travers la télévision et la radio.\nPrésente sur l'ensemble du territoire et dans les principales langues nationales — mooré, dioula, fulfuldé, gulmancéma — la RTB porte la voix de toutes les régions et de la diaspora.",
		'values'  => "Indépendance éditoriale\nRigueur et déontologie\nProximité avec les populations\nService public pour tous",
		'history' => "1963 | Naissance de la radiodiffusion nationale.\n1974 | Lancement de la télévision nationale.\n1995 | Modernisation et extension de la couverture sur tout le territoire.\n2010 | Passage progressif à la diffusion numérique.\n2020 | Essor des plateformes web et réseaux sociaux.\n2026 | Refonte numérique : direct, replays, multilingue, accessibilité.",
		'awards'  => "2024 | Nuit des Galian | Distinctions de la presse burkinabè décernées à des productions de la RTB.\n2022 | Couverture nationale | Reconnaissance pour la couverture des grands rendez-vous du pays.\n2019 | Langues nationales | Saluée pour la promotion des langues nationales à l'antenne.",
		'team'    => "Direction Générale | Pilotage stratégique de la RTB\nDirection de la Télévision | RTB Télé · Télé Zénith · RTB3\nDirection de la Radio | Radio Burkina & stations\nDirection de l'Information | Rédactions TV & radio\nDirection Technique | Diffusion & numérique\nDirections Régionales | Présence dans les 13 régions",
	);
}

/** Messages ticker par défaut */
function rtb_default_tickers(): array {
	return [
		"Conseil des ministres : adoption d'un plan d'investissement agricole",
		'Coupe du Faso : les Étalons en stage de préparation à Ouagadougou',
		'Éducation : 240 nouvelles salles de classe livrées avant la rentrée',
		'Culture : la SNC de Bobo-Dioulasso dévoile son programme',
	];
}

/**
 * Antennes / chaînes (rail « à l'antenne » + tuiles « Nos Antennes »).
 * mark, name, kind, accent, now (programme en cours), prog (%),
 * cover, desc, freq (radio), hero (kicker/headline/meta/name/dur).
 */
function rtb_default_antennes(): array {
	$u = 'https://www.rtb.bf/wp-content/uploads/';
	return [
		[
			'mark' => 'rtb', 'name' => 'RTB Télévision', 'kind' => 'TV',
			'accent' => '#10A653', 'now' => 'JT de 20H du 21 juin 2026', 'prog' => 62,
			'cover' => $u . '2026/05/Capture-decran-2026-02-25-201146.png', 'desc' => 'La chaîne généraliste nationale.',
			'hero' => [
				'kicker' => 'EN DIRECT · JOURNAL', 'name' => 'RTB Télévision',
				'headline' => 'JT de 20H du 21 juin 2026',
				'meta' => "Édition du soir · l'essentiel de l'actualité nationale",
				'dur' => 'En direct · 20H00',
			],
		],
		[
			'mark' => 'TZ', 'name' => 'Télé Zénith', 'kind' => 'TV',
			'accent' => '#F5DE00', 'now' => 'Success — le magazine', 'prog' => 31,
			'cover' => $u . '2026/06/vlcsnap-2026-06-17-21h44m25s931.png', 'desc' => 'Prenez de la Hauteur ! Divertissement & culture.',
			'hero' => [
				'kicker' => 'EN DIRECT · MAGAZINE', 'name' => 'Télé Zénith — Prenez de la Hauteur !',
				'headline' => 'Success du 17 juin 2026',
				'meta' => 'Le magazine de la réussite burkinabè',
				'dur' => 'En cours · 21:39',
			],
		],
		[
			'mark' => 'R3', 'name' => 'RTB3 Langues Nat.', 'kind' => 'TV',
			'accent' => '#E70C2F', 'now' => 'Journal en langues nationales', 'prog' => 46,
			'cover' => $u . '2026/06/vlcsnap-2026-06-15-19h26m07s146.png', 'desc' => 'Information de proximité.',
			'hero' => [
				'kicker' => 'EN DIRECT · PROXIMITÉ', 'name' => 'RTB3 Langues Nationales',
				'headline' => 'Le journal en langues nationales',
				'meta' => 'Mooré · Dioula · Fulfuldé',
				'dur' => 'En cours · 18 min',
			],
		],
		[
			'mark' => 'GK', 'name' => 'RTB Guiriko', 'kind' => 'RÉGION',
			'accent' => '#10A653', 'now' => 'Guiriko Matin', 'prog' => 80,
			'cover' => $u . '2020/04/vlcsnap-2020-02-18-20h44m13s684.png', 'desc' => "Antenne de l'Ouest, Bobo-Dioulasso.",
			'hero' => [
				'kicker' => 'EN DIRECT · RÉGION', 'name' => 'RTB Guiriko',
				'headline' => 'Guiriko Matin',
				'meta' => 'En direct de Bobo-Dioulasso',
				'dur' => 'En cours · 41 min',
			],
		],
		[
			'mark' => 'RB', 'name' => 'Radio Burkina', 'kind' => 'RADIO',
			'accent' => '#F5DE00', 'now' => 'Le journal parlé', 'prog' => 25,
			'cover' => $u . '2024/12/radiogut.png', 'desc' => 'La radio nationale, 92.5 FM.', 'freq' => '92.5 FM',
			'hero' => [
				'kicker' => 'EN DIRECT · RADIO', 'name' => 'Radio Burkina',
				'headline' => 'Le journal parlé de la mi-journée',
				'meta' => 'Radio nationale · 99.9 FM',
				'dur' => 'En direct',
			],
		],
	];
}

/**
 * Émissions / vidéos (grille « Le Journal Télévisé »).
 * Données réelles rtb.bf (juin 2026) — covers = vraies images du site.
 */
function rtb_default_emissions(): array {
	$u = 'https://www.rtb.bf/wp-content/uploads/';
	return [
		[ 'title' => 'JT de 20H du 21 juin 2026',       'cat' => 'JT 20H',   'dur' => '38 min', 'by' => 'RTB Télévision', 'date' => '21 juin 2026', 'cover' => $u . '2026/05/Capture-decran-2026-02-25-201146.png' ],
		[ 'title' => 'JT de 13H du 21 juin 2026',       'cat' => 'JT 13H',   'dur' => '24 min', 'by' => 'RTB Télévision', 'date' => '21 juin 2026', 'cover' => $u . '2026/06/vlcsnap-2026-06-15-13h05m32s345.png' ],
		[ 'title' => 'Débat de presse du 21 juin 2026', 'cat' => 'Magazine', 'dur' => '52 min', 'by' => 'RTB Télévision', 'date' => '21 juin 2026', 'cover' => $u . '2026/06/vlcsnap-2026-06-14-11h05m37s084.png' ],
		[ 'title' => 'JT de 19H du 19 juin 2026',       'cat' => 'JT 19H',   'dur' => '19 min', 'by' => 'RTB Télévision', 'date' => '19 juin 2026', 'cover' => $u . '2026/06/vlcsnap-2026-06-15-19h26m07s146.png' ],
		[ 'title' => 'Success du 17 juin 2026',         'cat' => 'Magazine', 'dur' => '21:39', 'by' => 'Télé Zénith',    'date' => '17 juin 2026', 'cover' => $u . '2026/06/vlcsnap-2026-06-17-21h44m25s931.png' ],
		[ 'title' => 'Questions Majeures du 14 juin 2026', 'cat' => 'Magazine', 'dur' => '47 min', 'by' => 'RTB Télévision', 'date' => '14 juin 2026', 'cover' => $u . '2026/06/questions-majeures.png' ],
	];
}

/** Stations radio (cartes section Radio) */
function rtb_default_stations(): array {
	return [
		[ 'name' => 'Radio Burkina',     'freq' => '92.5 FM',  'tag' => 'NATIONALE' ],
		[ 'name' => 'Radio Rurale',      'freq' => '99.2 FM',  'tag' => 'PROXIMITÉ' ],
		[ 'name' => 'RTB Bobo',          'freq' => '94.8 FM',  'tag' => 'RÉGION' ],
		[ 'name' => 'Canal Arc-en-ciel', 'freq' => '105.0 FM', 'tag' => 'JEUNESSE' ],
	];
}

/**
 * Articles « À la Une » (posts) — données réelles rtb.bf (juin 2026).
 */
function rtb_default_aune(): array {
	$u = 'https://www.rtb.bf/wp-content/uploads/';
	return [
		[
			'cat' => 'Société', 'color' => '#10A653', 'lead' => true,
			'title' => "Nuit de l'arbre 2026 : cérémonie de récompense",
			'excerpt' => "La cérémonie a distingué les acteurs engagés pour le reboisement et la préservation de l'environnement au Burkina Faso.",
			'date' => '20 juin 2026', 'iso' => '2026-06-20 21:18:00', 'cover' => $u . '2026/06/vlcsnap-2026-06-20-21h18m47s439.png',
		],
		[
			'cat' => 'Infos', 'color' => '#E70C2F',
			'title' => 'Nuit de récompense des prix Galian, édition 2026',
			'excerpt' => "Les meilleures productions de la presse burkinabè ont été primées.",
			'date' => '19 juin 2026', 'iso' => '2026-06-19 22:58:00', 'cover' => $u . '2026/06/Capture-decran-2026-06-19-225812.png',
		],
		[
			'cat' => 'Santé', 'color' => '#0B7A3B',
			'title' => "Santémag : l'acné chez les adultes et les adolescents",
			'excerpt' => "Le magazine santé revient sur les causes et les traitements de l'acné.",
			'date' => '18 juin 2026', 'iso' => '2026-06-18 19:30:00', 'cover' => $u . '2026/06/santemag.png',
		],
		[
			'cat' => 'Politique', 'color' => '#B58200',
			'title' => 'Compte rendu du Conseil des ministres du 21 mai 2026',
			'excerpt' => "Le Gouvernement a examiné plusieurs dossiers relatifs au développement national.",
			'date' => '21 mai 2026', 'iso' => '2026-05-21 18:00:00', 'cover' => $u . '2026/05/Capture-decran-2026-02-25-201146.png',
		],
	];
}

/** Les 13 régions du Burkina Faso (chef-lieu + rôle RTB). */
function rtb_default_regions(): array {
	return [
		[ 'name' => 'Centre', 'city' => 'Ouagadougou', 'role' => 'Siège national · RTB Télévision & Radio Burkina' ],
		[ 'name' => 'Hauts-Bassins', 'city' => 'Bobo-Dioulasso', 'role' => "RTB Guiriko · antenne de l'Ouest" ],
		[ 'name' => 'Sahel', 'city' => 'Dori', 'role' => 'Bureau régional & correspondance' ],
		[ 'name' => 'Est', 'city' => "Fada N'Gourma", 'role' => 'Bureau régional & correspondance' ],
		[ 'name' => 'Nord', 'city' => 'Ouahigouya', 'role' => 'Bureau régional & correspondance' ],
		[ 'name' => 'Centre-Ouest', 'city' => 'Koudougou', 'role' => 'Bureau régional & correspondance' ],
		[ 'name' => 'Cascades', 'city' => 'Banfora', 'role' => 'Bureau régional & correspondance' ],
		[ 'name' => 'Centre-Est', 'city' => 'Tenkodogo', 'role' => 'Bureau régional & correspondance' ],
		[ 'name' => 'Boucle du Mouhoun', 'city' => 'Dédougou', 'role' => 'Bureau régional & correspondance' ],
		[ 'name' => 'Centre-Nord', 'city' => 'Kaya', 'role' => 'Bureau régional & correspondance' ],
		[ 'name' => 'Plateau-Central', 'city' => 'Ziniaré', 'role' => 'Bureau régional & correspondance' ],
		[ 'name' => 'Sud-Ouest', 'city' => 'Gaoua', 'role' => 'Bureau régional & correspondance' ],
		[ 'name' => 'Centre-Sud', 'city' => 'Manga', 'role' => 'Bureau régional & correspondance' ],
	];
}

/** Catégories populaires (footer) */
function rtb_default_categories(): array {
	return [
		[ 'name' => 'Télévision',          'count' => '12 326' ],
		[ 'name' => 'Journaux Télévisés',  'count' => '11 787' ],
		[ 'name' => 'Infos',               'count' => '4 724' ],
		[ 'name' => 'Émissions TV',        'count' => '2 878' ],
		[ 'name' => 'Société',             'count' => '1 664' ],
		[ 'name' => 'Émissions radio',     'count' => '1 368' ],
		[ 'name' => 'Politique',           'count' => '1 092' ],
		[ 'name' => 'Conseil des Ministres','count' => '764' ],
	];
}
