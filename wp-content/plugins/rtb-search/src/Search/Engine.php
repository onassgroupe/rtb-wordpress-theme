<?php

namespace RTB\Search\Search;

use RTB\Search\Support\Normalizer;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Moteur de pertinence : remplace la recherche WordPress native par un classement
 * pondéré (titre ≫ extrait ≫ corps) avec mots-vides et pluriels gérés.
 *
 * Activé uniquement quand la query porte la var « rtb_ranked » (recherche principale
 * ou recherche programmatique de l'instantané) → n'impacte aucune autre requête.
 */
final class Engine {

	public const POST_TYPES = [ 'post', 'rtb_emission' ];
	public const PER_PAGE   = 12;

	public function register(): void {
		add_action( 'pre_get_posts', [ $this, 'onPreGetPosts' ] );
		add_filter( 'posts_search', [ $this, 'filterSearch' ], 10, 2 );
		add_filter( 'posts_orderby', [ $this, 'filterOrderby' ], 10, 2 );
	}

	/** Configure la recherche principale (types, pagination, filtres type/tri). */
	public function onPreGetPosts( WP_Query $q ): void {
		if ( is_admin() || ! $q->is_search() || ! $q->is_main_query() ) {
			return;
		}
		$q->set( 'rtb_ranked', true );
		$q->set( 'posts_per_page', self::PER_PAGE );

		$type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'all';
		$q->set( 'post_type', in_array( $type, self::POST_TYPES, true ) ? [ $type ] : self::POST_TYPES );

		$sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'relevant';
		$q->set( 'rtb_sort', $sort );
	}

	/** WHERE : chaque radical doit apparaître dans titre OU extrait OU corps. */
	public function filterSearch( string $search, WP_Query $q ): string {
		if ( ! $q->get( 'rtb_ranked' ) ) {
			return $search;
		}
		$terms = Normalizer::terms( (string) $q->get( 's' ) );
		if ( ! $terms ) {
			return $search;
		}
		global $wpdb;
		$clauses = [];
		foreach ( $terms as $t ) {
			$like      = '%' . $wpdb->esc_like( $t ) . '%';
			$clauses[] = $wpdb->prepare(
				"({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s OR {$wpdb->posts}.post_content LIKE %s)",
				$like,
				$like,
				$like
			);
		}
		return ' AND (' . implode( ' AND ', $clauses ) . ') ';
	}

	/** ORDER BY : score de pertinence (titre pondéré) puis date, sauf tri explicite. */
	public function filterOrderby( string $orderby, WP_Query $q ): string {
		if ( ! $q->get( 'rtb_ranked' ) ) {
			return $orderby;
		}
		global $wpdb;

		$sort = (string) ( $q->get( 'rtb_sort' ) ?: 'relevant' );
		if ( 'recent' === $sort ) {
			return "{$wpdb->posts}.post_date DESC";
		}
		if ( 'oldest' === $sort ) {
			return "{$wpdb->posts}.post_date ASC";
		}

		$terms = Normalizer::terms( (string) $q->get( 's' ) );
		if ( ! $terms ) {
			return $orderby;
		}

		// Score basé sur le TITRE (phrase exacte > radicaux). À score égal, le plus récent
		// gagne (UX actu). Le corps/extrait servent au filtrage (WHERE), pas au tri.
		$score  = [];
		$phrase = Normalizer::phrase( (string) $q->get( 's' ) );
		if ( '' !== $phrase ) {
			$like    = '%' . $wpdb->esc_like( $phrase ) . '%';
			$score[] = $wpdb->prepare( "(CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN 200 ELSE 0 END)", $like );
		}
		foreach ( $terms as $t ) {
			$like    = '%' . $wpdb->esc_like( $t ) . '%';
			$score[] = $wpdb->prepare( "(CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN 10 ELSE 0 END)", $like );
		}
		$expr = implode( ' + ', $score );
		return "($expr) DESC, {$wpdb->posts}.post_date DESC";
	}

	/**
	 * Recherche programmatique (utilisée par l'instantané). Renvoie des IDs classés.
	 * @return int[]
	 */
	public function search( string $query, int $limit = 6, string $type = 'all' ): array {
		$wpq = new WP_Query( [
			's'                   => $query,
			'rtb_ranked'          => true,
			'rtb_sort'            => 'relevant',
			'post_type'           => in_array( $type, self::POST_TYPES, true ) ? [ $type ] : self::POST_TYPES,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		] );
		return array_map( 'intval', wp_list_pluck( $wpq->posts, 'ID' ) );
	}
}
