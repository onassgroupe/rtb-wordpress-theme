<?php
/**
 * RTB — Custom Post Types + accesseurs.
 *   rtb_antenne  : chaînes / antennes (rail + tuiles)
 *   rtb_station  : stations radio
 *   rtb_emission : vidéos / replays (grille « Le Journal ») + taxo rtb_emission_cat
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cover d'un contenu : vignette WP → meta rtb_cover_url (CDN) →
 * miniature YouTube (si rtb_video) → image par défaut du thème.
 */
function rtb_post_cover( int $post_id, string $size = 'rtb-card', string $default = 'jt-1.png' ): string {
	$thumb = get_the_post_thumbnail_url( $post_id, $size );
	if ( $thumb ) {
		return $thumb;
	}
	$url = (string) get_post_meta( $post_id, 'rtb_cover_url', true );
	if ( $url ) {
		return rtb_cdnize( $url );
	}
	$yt = (string) get_post_meta( $post_id, 'rtb_video', true );
	if ( $yt ) {
		return 'https://i.ytimg.com/vi/' . rawurlencode( $yt ) . '/hqdefault.jpg';
	}
	return rtb_img( $default );
}

/** URL du direct (player Infomaniak) d'une chaîne, par nom. '' si pas de live → repli YouTube. */
function rtb_antenne_live( string $name ): string {
	$map = array(
		'RTB Télévision' => 'https://player.infomaniak.com?channel=PG99617043325684226&player=12538&autoplay=1',
		'Télé Zénith'    => 'https://player.infomaniak.com?channel=ER99617043325684222&player=12537&autoplay=1',
		'RTB Guiriko'    => 'https://player.infomaniak.com?channel=AH99617043325685050&player=12884&autoplay=1',
	);
	$url = $map[ $name ] ?? '';
	return (string) apply_filters( 'rtb_antenne_live', $url, $name );
}

/** Flux audio de la radio en direct (proxy). */
function rtb_radio_stream(): string {
	return (string) apply_filters( 'rtb_radio_stream', 'https://misty-smoke-beb9.armandkiendre.workers.dev' );
}

/** ID YouTube de la dernière émission (repli « direct indisponible »). */
function rtb_latest_video_id(): string {
	$p = get_posts( array( 'post_type' => 'rtb_emission', 'numberposts' => 1, 'fields' => 'ids' ) );
	return $p ? (string) get_post_meta( (int) $p[0], 'rtb_video', true ) : '';
}

function rtb_register_cpts(): void {

	register_post_type( 'rtb_antenne', [
		'labels' => [
			'name'          => 'Antennes',
			'singular_name' => 'Antenne',
			'add_new_item'  => 'Ajouter une antenne',
			'edit_item'     => 'Modifier l’antenne',
			'menu_name'     => 'Antennes',
		],
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_rest'       => true,
		'exclude_from_search'=> true,
		'menu_icon'          => 'dashicons-video-alt2',
		'menu_position'      => 26,
		'rewrite'            => [ 'slug' => 'chaine' ],
		'supports'           => [ 'title', 'editor', 'thumbnail', 'page-attributes' ],
		'has_archive'        => false,
	] );

	register_post_type( 'rtb_region', [
		'labels' => [
			'name'          => 'Régions',
			'singular_name' => 'Région',
			'add_new_item'  => 'Ajouter une région',
			'edit_item'     => 'Modifier la région',
			'menu_name'     => 'Régions',
		],
		'public'              => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_rest'        => true,
		'exclude_from_search' => true,
		'menu_icon'           => 'dashicons-location',
		'menu_position'       => 28,
		'rewrite'             => [ 'slug' => 'region' ],
		'supports'            => [ 'title', 'editor', 'thumbnail', 'page-attributes' ],
		'has_archive'         => false,
	] );

	register_post_type( 'rtb_station', [
		'labels' => [
			'name'          => 'Stations Radio',
			'singular_name' => 'Station',
			'add_new_item'  => 'Ajouter une station',
			'edit_item'     => 'Modifier la station',
			'menu_name'     => 'Stations Radio',
		],
		'public'        => false,
		'show_ui'       => true,
		'show_in_rest'  => true,
		'menu_icon'     => 'dashicons-microphone',
		'menu_position' => 27,
		'supports'      => [ 'title', 'page-attributes' ],
		'has_archive'   => false,
	] );

	register_post_type( 'rtb_emission', [
		'labels' => [
			'name'          => 'Émissions / Vidéos',
			'singular_name' => 'Émission',
			'add_new_item'  => 'Ajouter une émission',
			'edit_item'     => 'Modifier l’émission',
			'menu_name'     => 'Émissions',
		],
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_rest'       => true,
		'menu_icon'          => 'dashicons-format-video',
		'menu_position'      => 25,
		'rewrite'            => [ 'slug' => 'emissions' ],
		'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
		'has_archive'        => true,
	] );

	register_taxonomy( 'rtb_emission_cat', 'rtb_emission', [
		'labels' => [
			'name'          => 'Catégories d’émission',
			'singular_name' => 'Catégorie',
			'menu_name'     => 'Catégories',
		],
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
	] );

	// Programmes (grands rendez-vous) → URLs propres /programme/{slug}/
	register_taxonomy( 'rtb_programme', 'rtb_emission', [
		'labels' => [
			'name'          => 'Programmes',
			'singular_name' => 'Programme',
			'menu_name'     => 'Programmes',
		],
		'public'            => true,
		'hierarchical'      => false,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => [ 'slug' => 'programme' ],
	] );

	$metas = [
		'rtb_antenne'  => [ 'rtb_mark', 'rtb_kind', 'rtb_accent', 'rtb_now', 'rtb_prog', 'rtb_freq', 'rtb_hero_kicker', 'rtb_hero_headline', 'rtb_hero_meta', 'rtb_hero_dur' ],
		'rtb_station'  => [ 'rtb_freq', 'rtb_tag' ],
		'rtb_emission' => [ 'rtb_dur', 'rtb_by', 'rtb_human_date', 'rtb_video' ],
		'rtb_region'   => [ 'rtb_city', 'rtb_role', 'rtb_accent' ],
	];
	foreach ( $metas as $pt => $keys ) {
		foreach ( $keys as $k ) {
			register_post_meta( $pt, $k, [
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
				'auth_callback' => fn() => current_user_can( 'edit_posts' ),
			] );
		}
	}
}
add_action( 'init', 'rtb_register_cpts' );

/* ============================================================
   ACCESSEURS — CPT avec fallback sur inc/data.php
   ============================================================ */

/** @return array<int,array> Antennes prêtes à l'affichage */
function rtb_get_antennes(): array {
	$posts = get_posts( [
		'post_type'   => 'rtb_antenne',
		'numberposts' => 10,
		'orderby'     => 'menu_order',
		'order'       => 'ASC',
	] );

	if ( empty( $posts ) ) {
		return array_map( function ( $a ) {
			$a['cover'] = rtb_cover_src( $a['cover'] );
			return $a;
		}, rtb_default_antennes() );
	}

	$out = [];
	foreach ( $posts as $p ) {
		$cover = get_the_post_thumbnail_url( $p->ID, 'rtb-wide' )
			?: ( rtb_cdnize( (string) get_post_meta( $p->ID, 'rtb_cover_url', true ) ) ?: rtb_img( 'jt-1.png' ) );
		$out[] = [
			'id'        => $p->ID,
			'permalink' => get_permalink( $p ),
			'mark'   => get_post_meta( $p->ID, 'rtb_mark', true ) ?: mb_substr( $p->post_title, 0, 2 ),
			'name'   => $p->post_title,
			'kind'   => get_post_meta( $p->ID, 'rtb_kind', true ) ?: 'TV',
			'accent' => get_post_meta( $p->ID, 'rtb_accent', true ) ?: '#10A653',
			'now'    => get_post_meta( $p->ID, 'rtb_now', true ),
			'prog'   => (int) ( get_post_meta( $p->ID, 'rtb_prog', true ) ?: 40 ),
			'freq'   => get_post_meta( $p->ID, 'rtb_freq', true ),
			'desc'   => wp_strip_all_tags( $p->post_content ),
			'cover'  => $cover,
			'live'   => get_post_meta( $p->ID, 'rtb_live_url', true ) ?: rtb_antenne_live( $p->post_title ),
			'hero'   => [
				'kicker'   => get_post_meta( $p->ID, 'rtb_hero_kicker', true ) ?: 'EN DIRECT',
				'name'     => $p->post_title,
				'headline' => get_post_meta( $p->ID, 'rtb_hero_headline', true ) ?: $p->post_title,
				'meta'     => get_post_meta( $p->ID, 'rtb_hero_meta', true ),
				'dur'      => get_post_meta( $p->ID, 'rtb_hero_dur', true ) ?: 'En direct',
			],
		];
	}
	return $out;
}

/** @return array<int,array> Émissions (option: filtre catégorie par slug) */
function rtb_get_emissions( int $limit = 6 ): array {
	$posts = get_posts( [
		'post_type'   => 'rtb_emission',
		'numberposts' => $limit,
		'orderby'     => 'date',
		'order'       => 'DESC',
	] );

	if ( empty( $posts ) ) {
		return array_map( function ( $e ) {
			$e['cover']     = rtb_cover_src( $e['cover'] );
			$e['permalink'] = '#';
			return $e;
		}, array_slice( rtb_default_emissions(), 0, $limit ) );
	}

	$out = [];
	foreach ( $posts as $p ) {
		$terms = get_the_terms( $p->ID, 'rtb_emission_cat' );
		$cat   = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'Vidéo';
		$out[] = [
			'title'     => $p->post_title,
			'cat'       => $cat,
			'dur'       => get_post_meta( $p->ID, 'rtb_dur', true ) ?: '—',
			'by'        => get_post_meta( $p->ID, 'rtb_by', true ) ?: 'RTB Multimédia',
			'date'      => get_post_meta( $p->ID, 'rtb_human_date', true ) ?: get_the_date( 'j M Y', $p ),
			'cover'     => rtb_post_cover( $p->ID ),
			'permalink' => get_permalink( $p ),
		];
	}
	return $out;
}

/** Émissions d'une catégorie donnée (slug ou nom de terme). */
function rtb_get_emissions_in_cat( string $cat, int $limit = 4 ): array {
	$posts = get_posts( [
		'post_type'   => 'rtb_emission',
		'numberposts' => $limit,
		'orderby'     => 'date',
		'order'       => 'DESC',
		'tax_query'   => [ [
			'taxonomy' => 'rtb_emission_cat',
			'field'    => is_numeric( $cat ) ? 'term_id' : 'name',
			'terms'    => $cat,
		] ],
	] );
	$out = [];
	foreach ( $posts as $p ) {
		$out[] = [
			'title'     => $p->post_title,
			'by'        => get_post_meta( $p->ID, 'rtb_by', true ) ?: 'RTB Télévision',
			'dur'       => get_post_meta( $p->ID, 'rtb_dur', true ),
			'cover'     => rtb_post_cover( $p->ID ),
			'permalink' => get_permalink( $p ),
		];
	}
	return $out;
}

/** Catégories d'émission distinctes (pour les onglets) */
function rtb_get_emission_cats(): array {
	$terms = get_terms( [ 'taxonomy' => 'rtb_emission_cat', 'hide_empty' => true ] );
	if ( $terms && ! is_wp_error( $terms ) ) {
		return array_map( fn( $t ) => $t->name, $terms );
	}
	// fallback : catégories distinctes des données
	$cats = array_unique( array_column( rtb_default_emissions(), 'cat' ) );
	return array_values( $cats );
}

/** @return array<int,array> Régions (CPT, fallback data) */
function rtb_get_regions(): array {
	$posts = get_posts( [ 'post_type' => 'rtb_region', 'numberposts' => 20, 'orderby' => 'menu_order', 'order' => 'ASC' ] );
	$accents = [ '#10A653', '#F5DE00', '#E70C2F', '#0B7A3B' ];
	if ( empty( $posts ) ) {
		$out = [];
		foreach ( rtb_default_regions() as $i => $r ) {
			$r['permalink'] = home_url( '/regions' );
			$r['accent']    = $accents[ $i % 4 ];
			$out[] = $r;
		}
		return $out;
	}
	$out = [];
	foreach ( $posts as $i => $p ) {
		$out[] = [
			'name'      => $p->post_title,
			'city'      => get_post_meta( $p->ID, 'rtb_city', true ),
			'role'      => get_post_meta( $p->ID, 'rtb_role', true ),
			'accent'    => get_post_meta( $p->ID, 'rtb_accent', true ) ?: $accents[ $i % 4 ],
			'permalink' => get_permalink( $p ),
		];
	}
	return $out;
}

/** @return array<int,array> Stations radio */
function rtb_get_stations(): array {
	$posts = get_posts( [
		'post_type'   => 'rtb_station',
		'numberposts' => 8,
		'orderby'     => 'menu_order',
		'order'       => 'ASC',
	] );

	if ( empty( $posts ) ) {
		return rtb_default_stations();
	}

	$out = [];
	foreach ( $posts as $p ) {
		$out[] = [
			'name'   => $p->post_title,
			'freq'   => get_post_meta( $p->ID, 'rtb_freq', true ),
			'tag'    => get_post_meta( $p->ID, 'rtb_tag', true ),
			'stream' => get_post_meta( $p->ID, 'rtb_stream', true ),
		];
	}
	return $out;
}
