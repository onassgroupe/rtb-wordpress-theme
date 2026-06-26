<?php

namespace RTB\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Met en forme les entités RTB pour l'API mobile (sortie JSON propre et stable).
 */
final class Transformer {

	/** @return array<string,mixed> */
	public static function articleCard( int $id ): array {
		$cats = get_the_category( $id );
		return [
			'id'           => $id,
			'title'        => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
			'excerpt'      => self::excerpt( $id ),
			'date'         => get_post_time( 'c', true, $id ),
			'date_human'   => get_the_date( 'j F Y', $id ),
			'category'     => $cats ? [ 'id' => $cats[0]->term_id, 'name' => $cats[0]->name, 'slug' => $cats[0]->slug ] : null,
			'cover'        => self::cover( $id ),
			'has_document' => (bool) get_post_meta( $id, '_rtb_doc_url', true ),
			'live'         => 'open' === get_post_meta( $id, '_rtb_live_status', true ),
			'url'          => get_permalink( $id ),
		];
	}

	/** @return array<string,mixed> */
	public static function articleFull( int $id ): array {
		$card = self::articleCard( $id );
		$doc  = (string) get_post_meta( $id, '_rtb_doc_url', true );
		$card['content']  = apply_filters( 'the_content', get_post_field( 'post_content', $id ) );
		$card['document'] = $doc ?: null;
		$tags             = get_the_tags( $id );
		$card['tags']     = ( $tags && ! is_wp_error( $tags ) )
			? array_map( static fn( $t ) => [ 'name' => $t->name, 'slug' => $t->slug ], $tags )
			: [];
		return $card;
	}

	/** @return array<string,mixed> */
	public static function emissionCard( int $id ): array {
		$terms = get_the_terms( $id, 'rtb_emission_cat' );
		return [
			'id'         => $id,
			'title'      => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
			'excerpt'    => self::excerpt( $id ),
			'date'       => get_post_time( 'c', true, $id ),
			'date_human' => get_post_meta( $id, 'rtb_human_date', true ) ?: get_the_date( 'j M Y', $id ),
			'category'   => ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'Émission',
			'duration'   => get_post_meta( $id, 'rtb_dur', true ) ?: null,
			'video'      => get_post_meta( $id, 'rtb_video', true ) ?: null,
			'cover'      => self::cover( $id ),
			'url'        => get_permalink( $id ),
		];
	}

	/** Carte générique selon le type (post|rtb_emission). @return array<string,mixed> */
	public static function card( int $id ): array {
		return 'rtb_emission' === get_post_type( $id ) ? self::emissionCard( $id ) : self::articleCard( $id );
	}

	/** @param \WP_Term $term @return array<string,mixed> */
	public static function category( $term ): array {
		return [ 'id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug, 'count' => (int) $term->count ];
	}

	private static function excerpt( int $id ): string {
		$e = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) get_the_excerpt( $id ) ) ) );
		return mb_substr( $e, 0, 220 );
	}

	private static function cover( int $id ): string {
		$url = get_the_post_thumbnail_url( $id, 'rtb-wide' );
		if ( ! $url && function_exists( 'rtb_post_cover' ) ) {
			$url = rtb_post_cover( $id );
		}
		if ( ! $url ) {
			$meta = (string) get_post_meta( $id, 'rtb_cover_url', true );
			if ( $meta && function_exists( 'rtb_cdnize' ) ) {
				$url = rtb_cdnize( $meta, 800, 450 );
			}
		}
		return $url ?: '';
	}
}
