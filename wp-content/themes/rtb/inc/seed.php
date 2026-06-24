<?php
/**
 * RTB — Seeder. Pré-remplit CPT + posts + options si vides.
 * Déclenché sur after_switch_theme. Ré-exécutable : ne duplique pas.
 */

defined( 'ABSPATH' ) || exit;

/** Copie un asset du thème dans la médiathèque, renvoie l'ID d'attachement. */
function rtb_sideload_asset( string $filename, int $parent = 0 ): int {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	// Déjà importé ? (recherche par _rtb_asset meta)
	$existing = get_posts( [
		'post_type'   => 'attachment',
		'numberposts' => 1,
		'meta_key'    => '_rtb_asset',
		'meta_value'  => $filename,
		'fields'      => 'ids',
	] );
	if ( ! empty( $existing ) ) {
		return (int) $existing[0];
	}

	$src = get_template_directory() . '/assets/img/' . $filename;
	if ( ! file_exists( $src ) ) {
		return 0;
	}

	$upload = wp_upload_dir();
	$dest   = trailingslashit( $upload['path'] ) . $filename;
	if ( ! copy( $src, $dest ) ) {
		return 0;
	}

	$filetype = wp_check_filetype( $dest, null );
	$attach_id = wp_insert_attachment( [
		'post_mime_type' => $filetype['type'],
		'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
		'post_status'    => 'inherit',
		'post_parent'    => $parent,
	], $dest, $parent );

	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		return 0;
	}

	$meta = wp_generate_attachment_metadata( $attach_id, $dest );
	wp_update_attachment_metadata( $attach_id, $meta );
	update_post_meta( $attach_id, '_rtb_asset', $filename );
	return (int) $attach_id;
}

/** Affecte la langue par défaut (Polylang) à un contenu seedé. */
function rtb_seed_lang( int $post_id ): void {
	if ( function_exists( 'pll_set_post_language' ) && ! pll_get_post_language( $post_id ) ) {
		pll_set_post_language( $post_id, pll_default_language() ?: 'fr' );
	}
}

/** Crée les rubriques (catégories) avec slugs alignés sur le menu. Renvoie [nom => term_id]. */
function rtb_seed_categories(): array {
	$ids = [];
	foreach ( rtb_seed_categories_map() as $name => $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( ! $term ) {
			$res = wp_insert_term( $name, 'category', [ 'slug' => $slug ] );
			$tid = is_wp_error( $res ) ? 0 : (int) $res['term_id'];
		} else {
			$tid = (int) $term->term_id;
		}
		if ( $tid ) {
			$ids[ $name ] = $tid;
			if ( function_exists( 'pll_set_term_language' ) && ! pll_get_term_language( $tid ) ) {
				pll_set_term_language( $tid, pll_default_language() ?: 'fr' );
			}
		}
	}
	return $ids;
}

/** Affecte la cover d'un post : URL distante → meta rtb_cover_url ; fichier local → média + thumbnail. */
function rtb_seed_cover( int $post_id, string $cover ): void {
	if ( '' === $cover ) {
		return;
	}
	if ( preg_match( '#^https?://#', $cover ) ) {
		update_post_meta( $post_id, 'rtb_cover_url', $cover );
		return;
	}
	$att = rtb_sideload_asset( $cover, $post_id );
	if ( $att ) {
		set_post_thumbnail( $post_id, $att );
	}
}

function rtb_seed_initial_data(): void {

	/* ---------- Options / theme_mods (identité, contact, ticker) ---------- */
	if ( ! get_theme_mod( 'rtb_ticker' ) ) {
		set_theme_mod( 'rtb_ticker', implode( "\n", rtb_default_tickers() ) );
	}

	/* ---------- Antennes ---------- */
	if ( ! get_posts( [ 'post_type' => 'rtb_antenne', 'numberposts' => 1, 'fields' => 'ids', 'post_status' => 'any' ] ) ) {
		$order = 0;
		foreach ( rtb_default_antennes() as $a ) {
			$id = wp_insert_post( [
				'post_type'    => 'rtb_antenne',
				'post_status'  => 'publish',
				'post_title'   => $a['name'],
				'post_content' => $a['desc'],
				'menu_order'   => $order++,
			] );
			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, 'rtb_mark', $a['mark'] );
			update_post_meta( $id, 'rtb_kind', $a['kind'] );
			update_post_meta( $id, 'rtb_accent', $a['accent'] );
			update_post_meta( $id, 'rtb_now', $a['now'] );
			update_post_meta( $id, 'rtb_prog', $a['prog'] );
			update_post_meta( $id, 'rtb_freq', $a['freq'] ?? '' );
			update_post_meta( $id, 'rtb_hero_kicker', $a['hero']['kicker'] );
			update_post_meta( $id, 'rtb_hero_headline', $a['hero']['headline'] );
			update_post_meta( $id, 'rtb_hero_meta', $a['hero']['meta'] );
			update_post_meta( $id, 'rtb_hero_dur', $a['hero']['dur'] );
			rtb_seed_cover( $id, $a['cover'] );
		}
	}

	/* ---------- Régions ---------- */
	if ( ! get_posts( [ 'post_type' => 'rtb_region', 'numberposts' => 1, 'fields' => 'ids', 'post_status' => 'any' ] ) ) {
		$accents = [ '#10A653', '#F5DE00', '#E70C2F', '#0B7A3B' ];
		$order   = 0;
		foreach ( rtb_default_regions() as $i => $r ) {
			$id = wp_insert_post( [
				'post_type'    => 'rtb_region',
				'post_status'  => 'publish',
				'post_title'   => $r['name'],
				'post_content' => 'La RTB couvre la région ' . $r['name'] . ' (chef-lieu : ' . $r['city'] . '). ' . $r['role'] . '.',
				'menu_order'   => $order++,
			] );
			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, 'rtb_city', $r['city'] );
			update_post_meta( $id, 'rtb_role', $r['role'] );
			update_post_meta( $id, 'rtb_accent', $accents[ $i % 4 ] );
			rtb_seed_lang( $id );
		}
	}

	/* ---------- Stations radio ---------- */
	if ( ! get_posts( [ 'post_type' => 'rtb_station', 'numberposts' => 1, 'fields' => 'ids', 'post_status' => 'any' ] ) ) {
		$order = 0;
		foreach ( rtb_default_stations() as $s ) {
			$id = wp_insert_post( [
				'post_type'   => 'rtb_station',
				'post_status' => 'publish',
				'post_title'  => $s['name'],
				'menu_order'  => $order++,
			] );
			if ( $id && ! is_wp_error( $id ) ) {
				update_post_meta( $id, 'rtb_freq', $s['freq'] );
				update_post_meta( $id, 'rtb_tag', $s['tag'] );
			}
		}
	}

	/* ---------- Rubriques (catégories) ---------- */
	$cats = rtb_seed_categories();

	/* ---------- Émissions de démonstration (désactivées : le vrai contenu vient de rtb.bf) ---------- */
	if ( apply_filters( 'rtb_seed_demo_content', false ) && ! get_posts( [ 'post_type' => 'rtb_emission', 'numberposts' => 1, 'fields' => 'ids', 'post_status' => 'any' ] ) ) {
		foreach ( rtb_full_emissions() as $e ) {
			$args = [
				'post_type'    => 'rtb_emission',
				'post_status'  => 'publish',
				'post_title'   => $e['title'],
				'post_excerpt' => 'Revoir « ' . $e['title'] . ' » en intégralité.',
				'post_content' => 'Revoir l’intégralité de l’émission « ' . $e['title'] . ' » (' . $e['cat'] . ') diffusée par ' . $e['by'] . ' sur RTB Multimédia. Retrouvez tous les replays des journaux et magazines de la RTB.',
			];
			if ( ! empty( $e['iso'] ) ) {
				$args['post_date'] = $e['iso'];
			}
			$id = wp_insert_post( $args );
			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, 'rtb_dur', $e['dur'] );
			update_post_meta( $id, 'rtb_by', $e['by'] );
			update_post_meta( $id, 'rtb_human_date', $e['date'] );
			if ( ! empty( $e['yt'] ) ) {
				update_post_meta( $id, 'rtb_video', $e['yt'] );
			}
			wp_set_object_terms( $id, $e['cat'], 'rtb_emission_cat' );
			// Programme (grand rendez-vous) détecté depuis le titre.
			foreach ( [ 'Success', 'Questions Majeures', 'Santémag', 'Débat de presse', 'Sport Box', 'Intégral Foot' ] as $prog ) {
				if ( false !== mb_stripos( $e['title'], $prog ) ) {
					wp_set_object_terms( $id, $prog, 'rtb_programme' );
					break;
				}
			}
			rtb_seed_cover( $id, $e['cover'] );
			rtb_seed_lang( $id );
		}
	}

	/* ---------- Articles éditoriaux (corpus complet) ---------- */
	$has_seeded_posts = get_posts( [
		'post_type'   => 'post',
		'numberposts' => 1,
		'fields'      => 'ids',
		'meta_key'    => '_rtb_seeded',
		'post_status' => 'any',
	] );
	if ( apply_filters( 'rtb_seed_demo_content', false ) && empty( $has_seeded_posts ) ) {
		foreach ( rtb_full_articles() as $n ) {
			$cat_id = $cats[ $n['cat'] ] ?? 0;
			$args = [
				'post_type'     => 'post',
				'post_status'   => 'publish',
				'post_title'    => $n['title'],
				'post_excerpt'  => $n['excerpt'],
				'post_content'  => $n['excerpt'] . "\n\n" . "La rédaction de la Radiodiffusion Télévision du Burkina (RTB) revient en détail sur cette actualité. Retrouvez l'ensemble de nos reportages, analyses et éditions sur l'antenne et en ligne.",
				'post_category' => $cat_id ? [ $cat_id ] : [],
			];
			if ( ! empty( $n['iso'] ) ) {
				$args['post_date'] = $n['iso'];
			}
			$id = wp_insert_post( $args );
			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_rtb_seeded', '1' );
			rtb_seed_cover( $id, $n['cover'] );
			rtb_seed_lang( $id );
		}
	}

	/* ---------- Pages (Contact, Grille) ---------- */
	if ( ! get_page_by_path( 'contact' ) ) {
		wp_insert_post( [
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Contact',
			'post_name'    => 'contact',
			'post_content' => '',
		] );
	}
	$rtb_pages = [
		'grille'                        => 'Grille des programmes',
		'direct'                        => 'Le Direct',
		'radio'                         => 'Radio en direct',
		'a-propos'                      => 'À propos de la RTB',
		'regions'                       => 'Régions',
		'mentions-legales'              => 'Mentions légales',
		'politique-de-confidentialite'  => 'Politique de confidentialité',
		'conditions-utilisation'        => "Conditions d'utilisation",
		'accessibilite'                 => 'Accessibilité',
		'plan-du-site'                  => 'Plan du site',
	];
	foreach ( $rtb_pages as $slug => $title ) {
		if ( ! get_page_by_path( $slug ) ) {
			$pid = wp_insert_post( [
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => '',
			] );
			if ( $pid && ! is_wp_error( $pid ) ) {
				rtb_seed_lang( $pid );
			}
		}
	}
}
add_action( 'after_switch_theme', 'rtb_seed_initial_data', 20 );
