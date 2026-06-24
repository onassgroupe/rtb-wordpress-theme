<?php

namespace RTB\Search\Search;

defined( 'ABSPATH' ) || exit;

/**
 * Met en forme un article/émission pour l'affichage (instantané + API).
 */
final class Results {

	/** @return array<string,mixed> */
	public static function format( int $id ): array {
		$is_emission = ( 'rtb_emission' === get_post_type( $id ) );

		$thumb = get_the_post_thumbnail_url( $id, 'rtb-card' );
		if ( ! $thumb && function_exists( 'rtb_post_cover' ) ) {
			$thumb = rtb_post_cover( $id );
		}

		if ( $is_emission ) {
			$terms = get_the_terms( $id, 'rtb_emission_cat' );
			$cat   = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'Émission';
		} else {
			$cats = get_the_category( $id );
			$cat  = $cats ? $cats[0]->name : 'Actualité';
		}

		return [
			'id'    => $id,
			'title' => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
			'url'   => get_permalink( $id ),
			'date'  => get_the_date( 'j M Y', $id ),
			'type'  => $is_emission ? 'Émission' : 'Article',
			'kind'  => $is_emission ? 'emission' : 'post',
			'cat'   => $cat,
			'thumb' => $thumb ?: '',
		];
	}
}
