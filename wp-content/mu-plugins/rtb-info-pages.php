<?php
/**
 * RTB Info Pages (mu-plugin) — pages d'information du footer (Confidentialité,
 * Mentions légales, CGU, Accessibilité, Support).
 *
 * - Crée/publie les pages manquantes, vides, en brouillon ou au gabarit WP par défaut.
 * - Le contenu et les titres sont RENDUS DYNAMIQUEMENT et passent par le système
 *   de traduction maison rtb_t() (donc disponibles dans les 6 langues, éditables
 *   via l'admin « Traductions RTB »). Aucune chaîne ni coordonnée n'est figée :
 *   les coordonnées proviennent des réglages du site.
 */

defined( 'ABSPATH' ) || exit;

/** Traduction (repli FR si plugin i18n absent). */
function rtb_info_t( string $s ): string {
	return function_exists( 'rtb_t' ) ? rtb_t( $s ) : $s;
}

/** Coordonnées issues des réglages du site (jamais figées). */
function rtb_info_contact(): array {
	$mod = static function ( string $k, string $d ): string {
		$v = function_exists( 'onass_mod' ) ? (string) onass_mod( $k, '' ) : '';
		return '' !== $v ? $v : $d;
	};
	return [
		'email' => $mod( 'rtb_email', 'info@rtb.bf' ),
		'tel'   => $mod( 'rtb_phone', '+226 25 31 83 53' ),
		'addr'  => $mod( 'rtb_address', '01 BP 2530 Ouagadougou 01, Burkina Faso' ),
	];
}

/** Slugs gérés → titre (source FR, traduit à l'affichage). */
function rtb_info_pages(): array {
	return [
		'politique-de-confidentialite' => 'Politique de confidentialité',
		'mentions-legales'             => 'Mentions légales',
		'conditions-utilisation'       => "Conditions d'utilisation",
		'accessibilite'                => 'Accessibilité',
		'support'                      => 'Support',
	];
}

/** Contenu HTML traduit d'une page d'info (chaque chaîne passe par rtb_t). */
function rtb_info_page_html( string $slug ): string {
	$t = 'rtb_info_t';
	[ 'email' => $email, 'tel' => $tel, 'addr' => $addr ] = rtb_info_contact();
	$mail = '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
	$contact = '<p>' . $t( 'Contact :' ) . ' ' . $mail . ' · ' . esc_html( $tel ) . ' · ' . esc_html( $addr ) . '</p>';
	$h = static fn( string $s ): string => '<h2>' . esc_html( rtb_info_t( $s ) ) . '</h2>';
	$p = static fn( string $s ): string => '<p>' . esc_html( rtb_info_t( $s ) ) . '</p>';

	switch ( $slug ) {
		case 'support':
			return $p( "Besoin d'aide avec le site ou l'application RTB ? Nous sommes là pour vous." )
				. $h( 'Nous contacter' ) . $contact
				. $h( 'Questions fréquentes' )
				. $p( "La télévision ou la radio ne se lance pas : vérifiez votre connexion. Le direct et les vidéos nécessitent un accès Internet actif." )
				. $p( "Changer de langue : le site et l'application sont disponibles en six langues (français, anglais, mooré, dioula, fulfuldé, gulmancéma)." )
				. $p( "Enregistrer un contenu : dans l'application, appuyez sur le cœur pour retrouver vos favoris, même hors-ligne." )
				. $h( 'Signaler un problème' )
				. $p( "Décrivez votre souci (appareil, navigateur ou version de l'application) et écrivez-nous." ) . $contact;

		case 'politique-de-confidentialite':
			return $p( "La Radiodiffusion Télévision du Burkina (RTB) attache une grande importance à la protection de vos données personnelles." )
				. $h( 'Données que nous collectons' )
				. $p( "Statistiques de fréquentation anonymes pour améliorer le site ; informations du formulaire de contact pour traiter votre demande ; cookies techniques et, avec votre consentement, cookies de mesure d'audience." )
				. $h( 'Utilisation des données' )
				. $p( "Vos données servent uniquement à fournir et améliorer nos services. Elles ne sont ni vendues ni cédées à des tiers à des fins commerciales." )
				. $h( 'Cookies et consentement' )
				. $p( "Un bandeau vous permet d'accepter, refuser ou paramétrer les cookies par catégorie. Vous pouvez modifier votre choix à tout moment." )
				. $h( 'Vos droits' )
				. $p( "Vous disposez d'un droit d'accès, de rectification et de suppression de vos données." )
				. $h( 'Contact' ) . $contact;

		case 'mentions-legales':
			return $h( 'Éditeur du site' )
				. $p( "Radiodiffusion Télévision du Burkina (RTB)." ) . $contact
				. $h( 'Directeur de la publication' )
				. $p( "Le Directeur Général de la RTB." )
				. $h( 'Hébergement' )
				. $p( "Le site est hébergé sur une infrastructure sécurisée ; les coordonnées de l'hébergeur sont disponibles sur demande." )
				. $h( 'Propriété intellectuelle' )
				. $p( "L'ensemble des contenus est protégé par le droit d'auteur. Toute reproduction sans autorisation de la RTB est interdite." );

		case 'conditions-utilisation':
			return $p( "En accédant à ce site, vous acceptez les présentes conditions d'utilisation." )
				. $h( 'Objet' )
				. $p( "Ce site diffuse l'information de la RTB : télévision et radio en direct, journaux télévisés, émissions et articles d'actualité." )
				. $h( 'Utilisation des contenus' )
				. $p( "Les contenus sont mis à disposition pour un usage personnel et non commercial. Toute rediffusion sans autorisation est interdite." )
				. $h( 'Responsabilité' )
				. $p( "La RTB s'efforce d'assurer la disponibilité du service mais ne garantit pas une accessibilité ininterrompue." )
				. $h( 'Contact' ) . $contact;

		case 'accessibilite':
			return $p( "La RTB s'engage à rendre son site accessible au plus grand nombre, y compris aux personnes en situation de handicap." )
				. $h( 'Notre engagement' )
				. $p( "Structure de page claire, navigation au clavier, textes alternatifs, contrastes suffisants et compatibilité avec les lecteurs d'écran." )
				. $h( 'Multilinguisme' )
				. $p( "Pour une meilleure inclusion, le site est disponible en six langues : français, anglais, mooré, dioula, fulfuldé et gulmancéma." )
				. $h( 'Signaler un problème' )
				. $p( "Si vous rencontrez une difficulté d'accès, écrivez-nous : nous nous efforcerons d'y remédier rapidement." ) . $contact;
	}
	return '';
}

/* Création/publication des pages (idempotent, une passe). */
add_action( 'init', static function (): void {
	if ( ! is_admin() && ! wp_doing_cron() ) {
		return;
	}
	if ( get_option( 'rtb_info_pages_v3' ) ) {
		return;
	}
	update_option( 'rtb_info_pages_v3', time(), false );

	foreach ( rtb_info_pages() as $slug => $title ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		$plain    = $existing ? trim( wp_strip_all_tags( (string) $existing->post_content ) ) : '';
		$isStub   = mb_strlen( $plain ) < 250;
		$isWpStub = $existing && false !== strpos( (string) $existing->post_content, 'Texte suggéré' );
		$body     = rtb_info_page_html( $slug ); // contenu de secours (le filtre ci-dessous prime à l'affichage)

		if ( ! $existing ) {
			wp_insert_post( [ 'post_title' => $title, 'post_name' => $slug, 'post_content' => $body, 'post_status' => 'publish', 'post_type' => 'page' ] );
			continue;
		}
		$update = [ 'ID' => $existing->ID ];
		if ( $isStub || $isWpStub ) {
			$update['post_content'] = $body;
		}
		if ( 'publish' !== $existing->post_status ) {
			$update['post_status'] = 'publish';
		}
		if ( count( $update ) > 1 ) {
			wp_update_post( $update );
		}
	}
} );

/* Rendu traduit à l'affichage : le corps et le titre suivent la langue courante. */
add_filter( 'the_content', static function ( string $content ): string {
	if ( ! is_page() ) {
		return $content;
	}
	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	return array_key_exists( $slug, rtb_info_pages() ) ? rtb_info_page_html( $slug ) : $content;
} );

add_filter( 'the_title', static function ( string $title, int $post_id = 0 ): string {
	if ( ! $post_id ) {
		return $title;
	}
	$slug = (string) get_post_field( 'post_name', $post_id );
	$pages = rtb_info_pages();
	return isset( $pages[ $slug ] ) ? rtb_info_t( $pages[ $slug ] ) : $title;
}, 10, 2 );
