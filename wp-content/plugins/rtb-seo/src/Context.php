<?php

namespace RTB\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * Résout les métadonnées SEO de la requête courante (titre, description, image, URL, type).
 */
final class Context {

	public function isArticle(): bool {
		return is_singular( 'post' );
	}

	public function isVideo(): bool {
		return is_singular( 'rtb_emission' );
	}

	public function postId(): int {
		return is_singular() ? (int) get_queried_object_id() : 0;
	}

	/** URL canonique. */
	public function url(): string {
		return $this->localize( $this->rawUrl() );
	}

	private function rawUrl(): string {
		if ( is_singular() ) {
			return (string) get_permalink();
		}
		if ( is_front_page() ) {
			return home_url( '/' );
		}
		if ( is_category() || is_tag() || is_tax() ) {
			$link = get_term_link( get_queried_object() );
			return is_wp_error( $link ) ? home_url( '/' ) : (string) $link;
		}
		if ( is_post_type_archive() ) {
			$link = get_post_type_archive_link( (string) get_query_var( 'post_type' ) );
			return $link ?: home_url( '/' );
		}
		if ( is_search() ) {
			return home_url( '/?s=' . rawurlencode( get_search_query() ) );
		}
		global $wp;
		return home_url( add_query_arg( [], $wp->request ?? '' ) );
	}

	/**
	 * Garantit que l'URL canonique porte le préfixe de langue courant
	 * (rtb-i18n), pour éviter une chaîne canonical → redirection. Idempotent.
	 */
	private function localize( string $url ): string {
		if ( ! function_exists( 'rtb_current_lang' ) ) {
			return $url;
		}
		$home = home_url( '/' );
		if ( 0 !== strpos( $url, $home ) ) {
			return $url;
		}
		$rest = ltrim( substr( $url, strlen( $home ) ), '/' );
		if ( preg_match( '#^(fr|en|mos|dyu|ff|gux)(/|$|\?)#', $rest ) ) {
			return $url; // déjà préfixé
		}
		if ( preg_match( '#^(wp-|xmlrpc|feed)#', $rest ) ) {
			return $url;
		}
		return $home . rtb_current_lang() . '/' . $rest;
	}

	public function title(): string {
		$t = wp_get_document_title();
		return trim( wp_strip_all_tags( (string) $t ) );
	}

	public function description(): string {
		$desc = '';
		if ( is_singular() ) {
			$desc = get_the_excerpt( $this->postId() );
			if ( '' === trim( (string) $desc ) ) {
				$desc = wp_strip_all_tags( (string) get_post_field( 'post_content', $this->postId() ) );
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$desc = term_description() ?: single_term_title( '', false );
		} elseif ( is_search() ) {
			$desc = sprintf( 'Résultats de recherche pour « %s » sur la RTB.', get_search_query() );
		}
		// Accueil : slogan du site (Réglages → Général), sinon description de marque.
		if ( '' === trim( (string) $desc ) && ( is_front_page() || is_home() ) ) {
			$desc = (string) get_bloginfo( 'description' );
			if ( '' === trim( $desc ) ) {
				$desc = 'Radiodiffusion Télévision du Burkina, télévision, radio et actualités au cœur du pays.';
			}
		}
		if ( '' === trim( (string) $desc ) ) {
			$desc = get_bloginfo( 'description' );
		}
		// Localise une description issue d'une option texte (slogan) dans la langue courante.
		if ( '' !== trim( (string) $desc ) && function_exists( 'rtb_t' ) ) {
			$desc = rtb_t( (string) $desc );
		}
		$desc = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $desc ) ) );
		return '' === $desc ? '' : mb_substr( $desc, 0, 200 );
	}

	/** Image de partage (Open Graph / Twitter / schema). */
	public function image(): string {
		$id = $this->postId();
		if ( $id ) {
			$thumb = get_the_post_thumbnail_url( $id, 'rtb-wide' ) ?: get_the_post_thumbnail_url( $id, 'full' );
			if ( ! $thumb && function_exists( 'rtb_post_cover' ) ) {
				$thumb = rtb_post_cover( $id, 'rtb-wide' );
			}
			if ( ! $thumb ) {
				$cover = (string) get_post_meta( $id, 'rtb_cover_url', true );
				if ( $cover && function_exists( 'rtb_cdnize' ) ) {
					$thumb = rtb_cdnize( $cover, 1200, 630 );
				}
			}
			if ( $thumb ) {
				return $thumb;
			}
		}
		return function_exists( 'rtb_logo_url' ) ? rtb_logo_url() : '';
	}

	public function ogType(): string {
		if ( $this->isVideo() ) {
			return 'video.other';
		}
		if ( $this->isArticle() ) {
			return 'article';
		}
		return 'website';
	}

	/** Pages à ne pas indexer (résultats de recherche, 404). */
	public function isNoindex(): bool {
		return is_search() || is_404();
	}

	/** ID vidéo YouTube d'une émission, le cas échéant. */
	public function youtubeId(): string {
		return $this->isVideo() ? (string) get_post_meta( $this->postId(), 'rtb_video', true ) : '';
	}
}
