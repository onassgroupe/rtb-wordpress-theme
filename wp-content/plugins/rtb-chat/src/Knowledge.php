<?php

namespace RTB\Chat;

use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Accès au contenu RTB pour l'assistant : recherche d'articles/émissions, récents, comptages.
 * Réutilise le moteur de pertinence du plugin rtb-search s'il est actif (DRY), sinon WP_Query.
 */
final class Knowledge {

	private const TYPES = [ 'post', 'rtb_emission' ];

	/**
	 * Recherche par mots-clés, classée par pertinence.
	 * @return int[] IDs
	 */
	public function search( string $query, int $limit = 6, ?int $hours = null ): array {
		// Si rtb-search est actif et qu'on n'a pas de fenêtre temporelle → on délègue le ranking.
		if ( null === $hours && class_exists( \RTB\Search\Search\Engine::class ) ) {
			return ( new \RTB\Search\Search\Engine() )->search( $query, $limit, 'all' );
		}

		$args = [
			's'                   => $query,
			'post_type'           => self::TYPES,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		];
		if ( class_exists( \RTB\Search\Search\Engine::class ) ) {
			$args['rtb_ranked'] = true;
			$args['rtb_sort']   = 'relevant';
		}
		if ( $hours ) {
			$args['date_query'] = [ [ 'after' => $hours . ' hours ago' ] ];
		}
		$q = new WP_Query( $args );
		return array_map( 'intval', wp_list_pluck( $q->posts, 'ID' ) );
	}

	/** Derniers contenus publiés. @return int[] */
	public function recent( int $limit = 6, ?string $type = null ): array {
		$q = new WP_Query( [
			'post_type'           => $type && in_array( $type, self::TYPES, true ) ? [ $type ] : self::TYPES,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		] );
		return array_map( 'intval', wp_list_pluck( $q->posts, 'ID' ) );
	}

	public function countArticles(): int {
		return (int) wp_count_posts( 'post' )->publish;
	}

	public function countEmissions(): int {
		$c = wp_count_posts( 'rtb_emission' );
		return (int) ( $c->publish ?? 0 );
	}

	/** Carte normalisée d'un contenu pour l'affichage. @return array<string,mixed> */
	public function card( int $id ): array {
		if ( class_exists( \RTB\Search\Search\Results::class ) ) {
			$card = \RTB\Search\Search\Results::format( $id );
		} else {
			$is_emission = ( 'rtb_emission' === get_post_type( $id ) );
			$thumb       = get_the_post_thumbnail_url( $id, 'rtb-card' );
			if ( ! $thumb && function_exists( 'rtb_post_cover' ) ) {
				$thumb = rtb_post_cover( $id );
			}
			$card = [
				'id'    => $id,
				'title' => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
				'url'   => get_permalink( $id ),
				'date'  => get_the_date( 'j M Y', $id ),
				'type'  => $is_emission ? 'Émission' : 'Article',
				'kind'  => $is_emission ? 'emission' : 'post',
				'thumb' => $thumb ?: '',
			];
		}

		// Vidéo YouTube des émissions → lecture directe dans le chat.
		if ( ( $card['kind'] ?? '' ) === 'emission' ) {
			$vid = (string) get_post_meta( $id, 'rtb_video', true );
			if ( '' !== $vid ) {
				$card['video'] = $vid;
			}
		}
		return $card;
	}

	/** Texte nettoyé d'un article (pour le résumé extractif). */
	public function plainText( int $id ): string {
		$post = get_post( $id );
		if ( ! $post ) {
			return '';
		}
		$raw = $post->post_content ?: $post->post_excerpt;
		$raw = wp_strip_all_tags( strip_shortcodes( $raw ) );
		return trim( preg_replace( '/\s+/', ' ', $raw ) );
	}
}
