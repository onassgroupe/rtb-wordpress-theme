<?php
/**
 * RTB — Synchronisation du contenu réel depuis rtb.bf (API REST).
 *
 * - Importe les JT/émissions (replays, avec vidéo YouTube) et les articles éditoriaux.
 * - Dédoublonne (id source + titre), affecte rubrique + langue FR, cover via CDN.
 * - Tourne automatiquement (WP-Cron, 2×/jour) + bouton manuel (Outils → Sync RTB).
 *
 * Tout vient de la BD : une fois importé, l'accueil/Le Journal/émissions/ticker s'actualisent.
 */

defined( 'ABSPATH' ) || exit;

const RTB_SYNC_HOOK = 'rtb_sync_rtbbf';
/**
 * URL de l'API REST source pour l'import (ex. https://exemple.bf/wp-json/wp/v2/posts).
 * Configurée dans Outils → Sync RTB et stockée en base — JAMAIS versionnée dans le dépôt.
 */
function rtb_sync_endpoint(): string {
	return trim( (string) get_option( 'rtb_sync_endpoint', '' ) );
}

/** Existe déjà ? (id source rtb.bf, sinon titre exact). */
function rtb_sync_exists( int $src_id, string $title ): int {
	global $wpdb;
	$by_src = $wpdb->get_var( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_rtb_src_id' AND meta_value=%d LIMIT 1", $src_id ) );
	if ( $by_src ) {
		return (int) $by_src;
	}
	$by_title = $wpdb->get_var( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_title=%s AND post_type IN ('post','rtb_emission') AND post_status='publish' LIMIT 1", $title ) );
	return $by_title ? (int) $by_title : 0;
}

/** Rubrique rtb.bf → slug de catégorie du thème. */
function rtb_sync_map_cat( array $names ): string {
	$n = mb_strtolower( implode( ' ', $names ), 'UTF-8' );
	if ( false !== strpos( $n, 'sport' ) ) return 'sport';
	if ( false !== strpos( $n, 'cultur' ) || false !== strpos( $n, 'art' ) ) return 'culture';
	if ( false !== strpos( $n, 'econom' ) || false !== strpos( $n, 'écono' ) ) return 'economie';
	if ( false !== strpos( $n, 'sécur' ) || false !== strpos( $n, 'secur' ) || false !== strpos( $n, 'défense' ) ) return 'securite';
	if ( false !== strpos( $n, 'internat' ) || false !== strpos( $n, 'monde' ) || false !== strpos( $n, 'afriq' ) ) return 'international';
	if ( false !== strpos( $n, 'politi' ) || false !== strpos( $n, 'gouvern' ) ) return 'politique';
	if ( false !== strpos( $n, 'sant' ) ) return 'sante';
	if ( false !== strpos( $n, 'soci' ) || false !== strpos( $n, 'éduc' ) || false !== strpos( $n, 'educ' ) ) return 'societe';
	return 'societe';
}

/** ID YouTube depuis le HTML. */
function rtb_sync_youtube( string $html ): string {
	if ( preg_match( '#(?:youtube(?:-nocookie)?\.com/embed/|youtu\.be/|youtube\.com/watch\?v=)([A-Za-z0-9_-]{11})#', $html, $m ) ) {
		return $m[1];
	}
	return '';
}

/** Un titre = émission (JT / magazine nommé) ? Sinon article. */
function rtb_sync_is_show( string $title ): bool {
	return (bool) preg_match( '#^\s*JT\b|journal t[ée]l[ée]vis|d[ée]bat de presse|\bsuccess\b|questions majeures|sant[ée]mag|sport box|int[ée]gral foot|journal parl#i', $title );
}

/** Nettoie le corps d'article rtb.bf (retire scripts/styles, route les images via le CDN). */
function rtb_sync_clean_content( string $html ): string {
	$html = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', (string) $html );
	$html = preg_replace( '#<style\b[^>]*>.*?</style>#is', '', $html );
	// Images www.rtb.bf (hotlink bloqué) → CDN i0.wp.com
	$html = preg_replace( '#//www\.rtb\.bf/wp-content/#i', '//i0.wp.com/www.rtb.bf/wp-content/', $html );
	$html = wp_kses_post( $html );
	$html = trim( $html );
	// Beaucoup d'articles rtb.bf sont des DOCUMENTS embarqués (visionneuse) → le texte
	// réel n'est pas dans l'API, juste un placeholder. On le rejette (on gardera l'extrait).
	$text = trim( wp_strip_all_tags( $html ) );
	if ( mb_strlen( $text ) < 220 || preg_match( '#En cours de chargement|Recharger le document|Cela prend trop de temps#i', $text ) ) {
		return '';
	}
	return $html;
}

/* ============================================================
   Articles = DOCUMENTS PDF (visionneuse rtb.bf)
   Beaucoup d'articles rtb.bf (comptes rendus du Conseil des ministres, communiqués…)
   ne contiennent pas de texte : juste un PDF affiché dans une visionneuse. On télécharge
   ce PDF et on en extrait le texte (pdftotext / poppler) pour l'afficher sur NOTRE site.
   ============================================================ */

/** URL du PDF embarqué dans le contenu rtb.bf, le cas échéant. */
function rtb_extract_doc_url( string $html ): string {
	if ( preg_match( '#https?://[^\s"\'<>]+?\.pdf#i', $html, $m ) ) {
		return html_entity_decode( $m[0], ENT_QUOTES, 'UTF-8' );
	}
	return '';
}

/** Chemin du binaire pdftotext (poppler-utils), ou '' s'il est absent. */
function rtb_pdftotext_bin(): string {
	static $bin = null;
	if ( null !== $bin ) { return $bin; }
	foreach ( array( '/usr/bin/pdftotext', '/usr/local/bin/pdftotext', '/opt/homebrew/bin/pdftotext' ) as $p ) {
		if ( @is_executable( $p ) ) { return $bin = $p; }
	}
	$w = function_exists( 'shell_exec' ) ? trim( (string) @shell_exec( 'command -v pdftotext 2>/dev/null' ) ) : '';
	return $bin = ( $w && @is_executable( $w ) ) ? $w : '';
}

/** Valide (anti-SSRF) + encode les octets non-ASCII d'une URL pour wp_remote_get. */
function rtb_safe_url( string $url ): string {
	$url = trim( $url );

	// Anti-SSRF : n'autoriser que http/https et refuser les hôtes internes / IP privées.
	$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
	if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
		return '';
	}
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	if ( '' === $host ) {
		return '';
	}
	$ip = filter_var( $host, FILTER_VALIDATE_IP ) ? $host : gethostbyname( $host );
	if ( filter_var( $ip, FILTER_VALIDATE_IP )
		&& ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
		return ''; // boucle locale, réseau privé, link-local… → refusé
	}

	// Encode les octets non-ASCII (ex. « ° ») pour wp_remote_get.
	return preg_replace_callback( '#[^\x21-\x7E]#', static function ( $m ) {
		return rawurlencode( $m[0] );
	}, $url );
}

/**
 * Télécharge UNE fois le PDF rtb.bf et en tire :
 *  - le texte formaté en paragraphes (accessibilité, malvoyants, SEO) ;
 *  - une copie locale dans /uploads (visualiseur intégré au navigateur, sans hotlink rtb.bf).
 * @return array{text:string,url:string}
 */
function rtb_ingest_pdf( string $pdf_url ): array {
	$out = array( 'text' => '', 'url' => '' );
	if ( '' === $pdf_url ) { return $out; }

	$resp = wp_remote_get( rtb_safe_url( $pdf_url ), array( 'timeout' => 60 ) );
	if ( is_wp_error( $resp ) || 200 !== wp_remote_retrieve_response_code( $resp ) ) { return $out; }
	$data = (string) wp_remote_retrieve_body( $resp );
	if ( strlen( $data ) < 1000 || strlen( $data ) > 12 * MB_IN_BYTES || '%PDF' !== substr( $data, 0, 4 ) ) { return $out; }

	// 1) Copie locale (le navigateur l'affiche nativement, document téléchargeable).
	//    Nom de fichier ASCII (évite « ° », accents… qui cassent l'URL sur certains serveurs).
	$base = rawurldecode( (string) basename( (string) wp_parse_url( $pdf_url, PHP_URL_PATH ) ) );
	$base = remove_accents( $base );
	$base = preg_replace( '#[^A-Za-z0-9._-]+#', '-', $base );
	$name = sanitize_file_name( trim( $base, '-' ) );
	if ( '' === $name || ! preg_match( '#\.pdf$#i', $name ) ) {
		$name = 'rtb-document-' . substr( md5( $pdf_url ), 0, 8 ) . '.pdf';
	}
	$up = wp_upload_bits( $name, null, $data );
	$out['url'] = ( empty( $up['error'] ) && ! empty( $up['url'] ) ) ? $up['url'] : $pdf_url;

	// 2) Texte (pdftotext / poppler) si disponible.
	$bin = rtb_pdftotext_bin();
	if ( $bin ) {
		$tmp = wp_tempnam( 'rtb-doc.pdf' );
		if ( $tmp ) {
			file_put_contents( $tmp, $data );
			$raw = shell_exec( escapeshellarg( $bin ) . ' -enc UTF-8 -nopgbrk ' . escapeshellarg( $tmp ) . ' - 2>/dev/null' );
			@unlink( $tmp );
			if ( is_string( $raw ) ) { $out['text'] = rtb_format_pdf_text( $raw ); }
		}
	}
	return $out;
}

/** Nettoie la sortie pdftotext (entêtes officiels, n° de page) et la met en paragraphes. */
function rtb_format_pdf_text( string $txt ): string {
	$txt   = str_replace( "\r", '', $txt );
	$drop  = '#^(Page\s+\d+\s+sur\s+\d+|PP-G\s*N°|PRIMATURE|BURKINA FASO|PORTE-PAROLE DU GOUVERNEMENT|La Patrie ou la Mort|-{3,}|\d+)\b#iu';
	$lines = explode( "\n", $txt );
	$clean = array();
	foreach ( $lines as $ln ) {
		$t = trim( preg_replace( '/[ \t]+/', ' ', $ln ) );
		if ( '' === $t ) { $clean[] = ''; continue; }
		if ( preg_match( $drop, $t ) ) { continue; }
		$clean[] = $t;
	}
	// Regroupe les lignes en paragraphes (séparés par des lignes vides).
	$blocks = array();
	$cur    = '';
	foreach ( $clean as $t ) {
		if ( '' === $t ) {
			if ( '' !== trim( $cur ) ) { $blocks[] = trim( $cur ); }
			$cur = '';
			continue;
		}
		$cur .= ( '' !== $cur ? ' ' : '' ) . $t;
	}
	if ( '' !== trim( $cur ) ) { $blocks[] = trim( $cur ); }

	$blocks = array_values( array_filter( $blocks, static function ( $b ) {
		return mb_strlen( $b ) > 2;
	} ) );
	if ( count( $blocks ) < 2 ) { return ''; }

	$html = '';
	foreach ( $blocks as $b ) {
		$html .= '<p>' . esc_html( $b ) . "</p>\n";
	}
	return $html;
}

/**
 * Importe les N derniers contenus de rtb.bf. Idempotent.
 * @return array{articles:int,emissions:int,skipped:int,errors:int}
 */
/**
 * Sources d'import (libellé + URL), dans l'ordre de traitement.
 * Cat. éditoriales rtb.bf : 149 Conseil des Ministres, 11 Politique, 15 Société, 24 Économie,
 * 8 International, 22 Culture, 14 Santé, 16 Sports, 4 Communiqués, 150 Chroniques gouv., 25 Éducation, 28 Environnement.
 */
function rtb_sync_sources( int $limit = 40 ): array {
	$ep = rtb_sync_endpoint();
	if ( '' === $ep ) {
		return array(); // source non configurée → aucun import
	}
	$eds = '149,11,15,24,8,22,14,16,4,150,25,28';
	return array(
		array( 'Derniers contenus (JT, magazines…)', add_query_arg( array( 'per_page' => $limit, '_embed' => 1 ), $ep ) ),
		// Conseil des ministres (149) à part : sinon noyé par les catégories à fort débit.
		array( 'Conseil des ministres · page 1', add_query_arg( array( 'per_page' => 100, '_embed' => 1, 'categories' => '149', 'page' => 1 ), $ep ) ),
		array( 'Conseil des ministres · page 2', add_query_arg( array( 'per_page' => 100, '_embed' => 1, 'categories' => '149', 'page' => 2 ), $ep ) ),
		array( 'Articles éditoriaux · page 1', add_query_arg( array( 'per_page' => 100, '_embed' => 1, 'categories' => $eds, 'page' => 1 ), $ep ) ),
		array( 'Articles éditoriaux · page 2', add_query_arg( array( 'per_page' => 100, '_embed' => 1, 'categories' => $eds, 'page' => 2 ), $ep ) ),
	);
}

/** Importe une seule source (URL). @return array compteurs (+ 'msg' explicite si erreur) */
function rtb_import_source( string $url ): array {
	$out = array( 'articles' => 0, 'emissions' => 0, 'skipped' => 0, 'errors' => 0, 'msg' => '' );
	$resp = wp_remote_get( $url, array( 'timeout' => 60 ) );
	if ( is_wp_error( $resp ) ) {
		$out['errors'] = 1;
		$out['msg']    = 'Connexion impossible : ' . $resp->get_error_message();
		return $out;
	}
	$code  = (int) wp_remote_retrieve_response_code( $resp );
	$items = json_decode( wp_remote_retrieve_body( $resp ) );
	// Pagination dépassée (catégorie avec moins de pages) → ce n'est pas une erreur, juste vide.
	if ( 400 === $code && is_object( $items ) && isset( $items->code ) && 'rest_post_invalid_page_number' === $items->code ) {
		return $out;
	}
	if ( 200 !== $code ) {
		$out['errors'] = 1;
		$rc            = ( is_object( $items ) && isset( $items->code ) ) ? ' (' . $items->code . ')' : '';
		$out['msg']    = 'HTTP ' . $code . $rc
			. ( 404 === $code ? " — API REST introuvable. Vérifiez l'URL de la source (essayez la forme ?rest_route=/wp/v2/posts)." : '' );
		return $out;
	}
	if ( ! is_array( $items ) ) {
		$out['errors'] = 1;
		$out['msg']    = 'Réponse inattendue (le JSON renvoyé n\'est pas une liste d\'articles).';
		return $out;
	}

	$shows = [ 'Success', 'Questions Majeures', 'Santémag', 'Débat de presse', 'Sport Box', 'Intégral Foot' ];

	foreach ( $items as $p ) {
		$src_id = (int) ( $p->id ?? 0 );
		$title  = trim( html_entity_decode( wp_strip_all_tags( $p->title->rendered ?? '' ), ENT_QUOTES, 'UTF-8' ) );
		if ( ! $src_id || '' === $title ) { $out['errors']++; continue; }
		$content_raw = (string) ( $p->content->rendered ?? '' );
		$src_url = isset( $p->link ) ? esc_url_raw( (string) $p->link ) : '';

		$excerpt = trim( html_entity_decode( wp_strip_all_tags( $p->excerpt->rendered ?? '' ), ENT_QUOTES, 'UTF-8' ) );
		$excerpt = preg_replace( '/\s+/', ' ', $excerpt );
		if ( mb_strlen( $excerpt ) > 600 ) { $excerpt = mb_substr( $excerpt, 0, 597 ) . '…'; }
		$existing = rtb_sync_exists( $src_id, $title );

		// Corps "texte" direct s'il existe (sinon '' = document embarqué).
		$full = rtb_sync_clean_content( $content_raw );

		// L'article déjà en base a-t-il déjà un vrai corps ? (évite de re-télécharger le PDF à chaque sync)
		$has_good = false;
		if ( $existing && 'post' === get_post_type( $existing ) ) {
			$cur_chk  = (string) get_post_field( 'post_content', $existing );
			$has_good = ( mb_strlen( $cur_chk ) > 500 && ! preg_match( '#En cours de chargement|Recharger le document#i', $cur_chk ) );
		}

		// Document embarqué (PDF) ? On télécharge UNE fois : copie locale (visualiseur) + texte.
		$doc_src = rtb_extract_doc_url( $content_raw );
		$doc_url = $existing ? (string) get_post_meta( $existing, '_rtb_doc_url', true ) : '';
		if ( $doc_src && '' === $doc_url ) { // pas encore de copie locale → on l'ingère
			$ing = rtb_ingest_pdf( $doc_src );
			if ( '' !== $ing['url'] )  { $doc_url = $ing['url']; }
			if ( '' !== $ing['text'] && '' === $full ) { $full = $ing['text']; }
		}

		$body = $full ?: ( ( $excerpt ? $excerpt . "\n\n" : '' ) . "La rédaction de la Radiodiffusion Télévision du Burkina (RTB) revient en détail sur cette actualité." );

		if ( $existing ) {
			if ( 'post' === get_post_type( $existing ) ) {
				// Complète/corrige le corps des articles déjà importés (sauf s'ils ont déjà un bon texte).
				if ( ! $has_good ) {
					$cur  = (string) get_post_field( 'post_content', $existing );
					$junk = (bool) preg_match( '#En cours de chargement|Recharger le document#i', $cur );
					if ( $junk || mb_strlen( $body ) > mb_strlen( $cur ) ) {
						wp_update_post( array( 'ID' => $existing, 'post_content' => $body ) );
					}
				}
				if ( $doc_url ) { update_post_meta( $existing, '_rtb_doc_url', $doc_url ); }
			}
			$out['skipped']++;
			continue;
		}
		$ts = ! empty( $p->date ) ? strtotime( $p->date ) : time();

		$cover = isset( $p->_embedded->{'wp:featuredmedia'}[0]->source_url ) ? (string) $p->_embedded->{'wp:featuredmedia'}[0]->source_url : '';
		$term_names = [];
		if ( isset( $p->_embedded->{'wp:term'}[0] ) && is_array( $p->_embedded->{'wp:term'}[0] ) ) {
			foreach ( $p->_embedded->{'wp:term'}[0] as $t ) {
				if ( isset( $t->name ) ) { $term_names[] = $t->name; }
			}
		}

		if ( rtb_sync_is_show( $title ) ) {
			// ---- ÉMISSION / REPLAY ----
			if ( preg_match( '#13\s*h#i', $title ) )      { $cat = 'JT 13H'; $dur = '24 min'; }
			elseif ( preg_match( '#19\s*h#i', $title ) )  { $cat = 'JT 19H'; $dur = '19 min'; }
			elseif ( preg_match( '#20\s*h#i', $title ) )  { $cat = 'JT 20H'; $dur = '38 min'; }
			else                                          { $cat = 'Magazine'; $dur = '26 min'; }

			$id = wp_insert_post( [
				'post_type'    => 'rtb_emission',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_excerpt' => $excerpt ?: ( 'Revoir « ' . $title . ' » en intégralité.' ),
				'post_content' => $excerpt . "\n\nRevoir l’intégralité sur RTB Multimédia.",
				'post_date'    => gmdate( 'Y-m-d H:i:s', $ts ),
			], true );
			if ( is_wp_error( $id ) || ! $id ) { $out['errors']++; continue; }

			update_post_meta( $id, 'rtb_dur', $dur );
			update_post_meta( $id, 'rtb_by', 'RTB Télévision' );
			update_post_meta( $id, 'rtb_human_date', date_i18n( 'j M Y', $ts ) );
			$yt = rtb_sync_youtube( $content_raw );
			if ( $yt ) { update_post_meta( $id, 'rtb_video', $yt ); }
			wp_set_object_terms( $id, $cat, 'rtb_emission_cat' );
			foreach ( $shows as $prog ) {
				if ( false !== mb_stripos( $title, $prog ) ) { wp_set_object_terms( $id, $prog, 'rtb_programme' ); break; }
			}
			$out['emissions']++;
		} else {
			// ---- ARTICLE ----
			$term   = get_term_by( 'slug', rtb_sync_map_cat( $term_names ), 'category' );
			$cat_id = $term ? (int) $term->term_id : 0;
			$id = wp_insert_post( [
				'post_type'     => 'post',
				'post_status'   => 'publish',
				'post_title'    => $title,
				'post_excerpt'  => $excerpt,
				'post_content'  => $body,
				'post_category' => $cat_id ? [ $cat_id ] : [],
				'post_date'     => gmdate( 'Y-m-d H:i:s', $ts ),
			], true );
			if ( is_wp_error( $id ) || ! $id ) { $out['errors']++; continue; }
			update_post_meta( $id, '_rtb_seeded', '1' );
			$out['articles']++;
		}

		update_post_meta( $id, '_rtb_src_id', $src_id );
		update_post_meta( $id, '_rtb_imported', '1' );
		if ( $doc_url ) { update_post_meta( $id, '_rtb_doc_url', $doc_url ); }
		if ( $cover ) { update_post_meta( $id, 'rtb_cover_url', $cover ); }
		if ( function_exists( 'pll_set_post_language' ) ) { pll_set_post_language( $id, 'fr' ); }
	}
	return $out;
}

/** Import complet (toutes les sources). Idempotent. */
function rtb_import_from_rtbbf( int $limit = 40 ): array {
	$out  = array( 'articles' => 0, 'emissions' => 0, 'skipped' => 0, 'errors' => 0 );
	$msgs = array();
	foreach ( rtb_sync_sources( $limit ) as $src ) {
		$r = rtb_import_source( $src[1] );
		foreach ( array( 'articles', 'emissions', 'skipped', 'errors' ) as $k ) { $out[ $k ] += (int) ( $r[ $k ] ?? 0 ); }
		if ( ! empty( $r['msg'] ) ) { $msgs[] = $src[0] . ' : ' . $r['msg']; }
	}
	$out['messages'] = $msgs;
	update_option( 'rtb_last_sync', array( 'time' => time(), 'result' => $out ) );
	if ( function_exists( 'rtb_cache_clear' ) ) { rtb_cache_clear(); }
	return $out;
}

/* ---------------- WP-Cron : avant chaque JT (13h / 19h / 20h — Ouaga = UTC) ---------------- */
add_action( 'init', function () {
	// Nettoie l'ancien event 2×/jour (installs antérieures).
	$old = wp_next_scheduled( RTB_SYNC_HOOK );
	if ( $old ) { wp_unschedule_event( $old, RTB_SYNC_HOOK ); }
	// Synchros calées juste avant les éditions (12:50, 18:50, 19:50 UTC).
	foreach ( [ [ 12, 50, 'avant-13h' ], [ 18, 50, 'avant-19h' ], [ 19, 50, 'avant-20h' ] ] as $s ) {
		$args = [ $s[2] ];
		if ( ! wp_next_scheduled( RTB_SYNC_HOOK, $args ) ) {
			$ts = gmmktime( $s[0], $s[1], 0 );
			if ( $ts <= time() ) { $ts += DAY_IN_SECONDS; }
			wp_schedule_event( $ts, 'daily', RTB_SYNC_HOOK, $args );
		}
	}
} );
add_action( RTB_SYNC_HOOK, function () {
	rtb_import_from_rtbbf( 40 );
} );
add_action( 'switch_theme', function () {
	wp_clear_scheduled_hook( RTB_SYNC_HOOK );
} );

/* Première synchro à l'activation du thème → le site a du vrai contenu tout de suite. */
add_action( 'after_switch_theme', function () {
	rtb_import_from_rtbbf( 40 );
}, 30 );

/**
 * Supprime le contenu de démonstration (articles + émissions NON importés de rtb.bf).
 * Conserve tout ce qui a un _rtb_src_id (donc le vrai contenu rtb.bf) et la structure
 * (antennes, stations, régions, pages — autres types).
 * @return int nombre d'éléments supprimés
 */
function rtb_purge_demo_content(): int {
	$ids = get_posts( [
		'post_type'   => [ 'post', 'rtb_emission' ],
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
		'meta_query'  => [ [ 'key' => '_rtb_src_id', 'compare' => 'NOT EXISTS' ] ],
	] );
	$n = 0;
	foreach ( $ids as $id ) {
		if ( wp_delete_post( (int) $id, true ) ) { $n++; }
	}
	return $n;
}

/* ---------------- Bouton manuel : Outils → Sync RTB ---------------- */
add_action( 'admin_menu', function () {
	add_management_page(
		'Synchronisation RTB',
		'Sync RTB',
		'manage_options',
		'rtb-sync',
		'rtb_sync_admin_page'
	);
} );

/* Enregistre l'URL de la source d'import (option en base, hors dépôt). */
add_action( 'admin_post_rtb_sync_save_url', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'rtb_sync_url' ) ) {
		wp_die( 'Action non autorisée.' );
	}
	update_option( 'rtb_sync_endpoint', esc_url_raw( wp_unslash( $_POST['rtb_sync_endpoint'] ?? '' ) ) );
	wp_safe_redirect( add_query_arg( [ 'page' => 'rtb-sync', 'urlsaved' => 1 ], admin_url( 'tools.php' ) ) );
	exit;
} );

function rtb_sync_admin_page() {
	$last = get_option( 'rtb_last_sync' );
	echo '<div class="wrap"><h1>Synchronisation du contenu</h1>';
	echo '<p>Importe les derniers JT, émissions et articles depuis l\'API REST source. Tout est rangé en base (dédoublonné) puis affiché automatiquement sur le site.</p>';

	// Source d'import (option en base, jamais versionnée dans le dépôt).
	$ep   = rtb_sync_endpoint();
	$psave = esc_url( admin_url( 'admin-post.php' ) );
	$nf2  = wp_nonce_field( 'rtb_sync_url', '_wpnonce', true, false );
	if ( isset( $_GET['urlsaved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Source d\'import enregistrée.</p></div>';
	}
	if ( '' === $ep ) {
		echo '<div class="notice notice-warning"><p><strong>Source non configurée.</strong> Renseignez l\'URL de l\'API REST ci-dessous pour activer la synchronisation.</p></div>';
	}
	echo '<form method="post" action="' . $psave . '" style="margin:14px 0 22px;max-width:640px">'
		. '<input type="hidden" name="action" value="rtb_sync_save_url">' . $nf2
		. '<label for="rtb_sync_endpoint"><strong>URL de l\'API REST source</strong></label><br>'
		. '<input type="url" id="rtb_sync_endpoint" name="rtb_sync_endpoint" class="regular-text" style="width:100%;max-width:560px" value="' . esc_attr( $ep ) . '" placeholder="https://exemple.bf/wp-json/wp/v2/posts">'
		. '<p class="description">Endpoint WordPress « posts ». Stocké en base, non inclus dans le code source public.</p>'
		. '<p><button type="submit" class="button">Enregistrer la source</button></p>'
		. '</form>';
	if ( is_array( $last ) && ! empty( $last['time'] ) ) {
		$r = $last['result'] ?? [];
		printf(
			'<p><strong>Dernière synchro :</strong> %s — %d articles, %d émissions, %d déjà présents, %d erreurs.</p>',
			esc_html( date_i18n( 'j M Y H:i', $last['time'] ) ),
			(int) ( $r['articles'] ?? 0 ), (int) ( $r['emissions'] ?? 0 ),
			(int) ( $r['skipped'] ?? 0 ), (int) ( $r['errors'] ?? 0 )
		);
		if ( ! empty( $r['messages'] ) && is_array( $r['messages'] ) ) {
			echo '<div class="notice notice-error inline" style="max-width:640px"><p><strong>Détail des erreurs :</strong></p><ul style="list-style:disc;margin-left:20px">';
			foreach ( $r['messages'] as $m ) {
				echo '<li>' . esc_html( (string) $m ) . '</li>';
			}
			echo '</ul></div>';
		}
	}
	$ajax  = esc_url( admin_url( 'admin-ajax.php' ) );
	$nonce = esc_js( wp_create_nonce( 'rtb_sync_ajax' ) );
	$post  = esc_url( admin_url( 'admin-post.php' ) );
	$nf    = wp_nonce_field( 'rtb_sync_now', '_wpnonce', true, false );
	echo <<<HTML
	<form method="post" id="rtb-sync-form" action="{$post}">
		<input type="hidden" name="action" value="rtb_sync_now">
		{$nf}
		<p><button type="submit" id="rtb-sync-btn" class="button button-primary button-hero">&#x21BB; Synchroniser maintenant</button></p>
	</form>
	<div id="rtb-sync-progress" style="display:none;max-width:640px;margin:6px 0 4px">
		<div style="background:#e6e6e6;border-radius:999px;height:14px;overflow:hidden">
			<div id="rtb-sync-bar" style="height:100%;width:0;background:linear-gradient(90deg,#E70C2F,#F5DE00,#10A653);transition:width .35s ease"></div>
		</div>
		<p id="rtb-sync-status" style="font-weight:600;margin:10px 0 6px"></p>
		<ul id="rtb-sync-log" style="margin:0;padding-left:18px;color:#555;font-size:13px;line-height:1.8"></ul>
	</div>
	<script>
	(function(){
		var form=document.getElementById('rtb-sync-form'),btn=document.getElementById('rtb-sync-btn'),
		box=document.getElementById('rtb-sync-progress'),bar=document.getElementById('rtb-sync-bar'),
		st=document.getElementById('rtb-sync-status'),log=document.getElementById('rtb-sync-log');
		if(!form)return;
		var AJAX='{$ajax}',NONCE='{$nonce}';
		form.addEventListener('submit',function(e){
			e.preventDefault();btn.disabled=true;box.style.display='block';log.innerHTML='';
			bar.style.width='6%';st.textContent='Connexion à rtb.bf…';
			function step(i){
				var fd=new FormData();fd.append('action','rtb_sync_step');fd.append('nonce',NONCE);fd.append('step',i);
				fetch(AJAX,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){
					if(!j||!j.success){st.textContent='Erreur de synchronisation.';btn.disabled=false;return;}
					var d=j.data,li=document.createElement('li');
					li.textContent=d.label+' — +'+d.result.articles+' articles, +'+d.result.emissions+' émissions ('+d.result.skipped+' déjà là)';
					if(d.result.msg){ li.style.color='#b32d2e'; li.textContent+=' ⚠ '+d.result.msg; }
					log.appendChild(li);
					bar.style.width=Math.round((d.step+1)/d.steps*100)+'%';
					if(d.done){st.textContent='Terminé ✅ — '+d.total.articles+' articles, '+d.total.emissions+' émissions ('+d.total.skipped+' déjà présents).';btn.disabled=false;btn.innerHTML='&#x21BB; Re-synchroniser';}
					else{st.textContent='Synchronisation… étape '+(d.step+2)+'/'+d.steps;step(i+1);}
				}).catch(function(){st.textContent='Erreur réseau.';btn.disabled=false;});
			}
			step(0);
		});
	})();
	</script>
HTML;

	echo '<hr style="margin:28px 0"><h2>Contenu de démonstration</h2>';
	echo '<p>Supprime les articles et émissions <strong>non importés de rtb.bf</strong> (le contenu de démo créé à l’installation). Le vrai contenu rtb.bf et la structure (antennes, stations, régions, pages) sont conservés.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Supprimer définitivement tout le contenu de démonstration ?\');">';
	echo '<input type="hidden" name="action" value="rtb_purge_demo">';
	wp_nonce_field( 'rtb_purge_demo' );
	submit_button( 'Supprimer le contenu de démo', 'delete' );
	echo '</form></div>';
}

/* AJAX : import par étape (barre de progression dans l'admin). */
add_action( 'wp_ajax_rtb_sync_step', function () {
	check_ajax_referer( 'rtb_sync_ajax', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'non autorisé' ) );
	}
	$step    = isset( $_POST['step'] ) ? max( 0, (int) $_POST['step'] ) : 0;
	$sources = rtb_sync_sources( 40 );
	$steps   = count( $sources );
	if ( $step >= $steps ) {
		wp_send_json_error( array( 'message' => 'étape invalide' ) );
	}
	$r = rtb_import_source( $sources[ $step ][1] );

	$acc = get_transient( 'rtb_sync_acc' );
	if ( 0 === $step || ! is_array( $acc ) ) {
		$acc = array( 'articles' => 0, 'emissions' => 0, 'skipped' => 0, 'errors' => 0 );
	}
	foreach ( array_keys( $acc ) as $k ) {
		$acc[ $k ] += (int) ( $r[ $k ] ?? 0 );
	}
	set_transient( 'rtb_sync_acc', $acc, 300 );

	$done = ( $step + 1 >= $steps );
	if ( $done ) {
		update_option( 'rtb_last_sync', array( 'time' => time(), 'result' => $acc ) );
		delete_transient( 'rtb_sync_acc' );
		if ( function_exists( 'rtb_cache_clear' ) ) { rtb_cache_clear(); }
	}
	wp_send_json_success( array(
		'label'  => $sources[ $step ][0],
		'result' => $r,
		'total'  => $acc,
		'step'   => $step,
		'steps'  => $steps,
		'done'   => $done,
	) );
} );

add_action( 'admin_post_rtb_sync_now', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'rtb_sync_now' ) ) {
		wp_die( 'Action non autorisée.' );
	}
	$r = rtb_import_from_rtbbf( 60 );
	wp_safe_redirect( add_query_arg( [ 'page' => 'rtb-sync', 'synced' => 1 ], admin_url( 'tools.php' ) ) );
	exit;
} );

add_action( 'admin_post_rtb_purge_demo', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'rtb_purge_demo' ) ) {
		wp_die( 'Action non autorisée.' );
	}
	$n = rtb_purge_demo_content();
	wp_safe_redirect( add_query_arg( [ 'page' => 'rtb-sync', 'purged' => (int) $n ], admin_url( 'tools.php' ) ) );
	exit;
} );

add_action( 'admin_notices', function () {
	if ( ! isset( $_GET['page'] ) || 'rtb-sync' !== $_GET['page'] ) {
		return;
	}
	if ( isset( $_GET['synced'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Synchronisation effectuée.</p></div>';
	}
	if ( isset( $_GET['purged'] ) ) {
		printf( '<div class="notice notice-success is-dismissible"><p>%d élément(s) de démo supprimé(s).</p></div>', (int) $_GET['purged'] );
	}
} );

/* WP-CLI : wp rtb sync */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'rtb sync', function () {
		$r = rtb_import_from_rtbbf( 60 );
		WP_CLI::success( sprintf( 'Articles: %d · Émissions: %d · Déjà présents: %d · Erreurs: %d', $r['articles'], $r['emissions'], $r['skipped'], $r['errors'] ) );
	} );
}
