<?php

namespace RTB\Chat\Learning;

use RTB\Chat\Nlp\Lexicon;
use RTB\Chat\Nlp\Normalizer;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Apprend le vocabulaire du contenu RTB (titres, extraits, corps, catégories, tags)
 * → correction orthographique de l'assistant. Partagé entre WP-CLI et l'admin.
 */
final class Learner {

	private const OPTION_LAST = 'rtb_chat_last_learn';

	/**
	 * @return array{processed:int,unique:int,time:int}
	 */
	public function run( bool $fresh = false ): array {
		$lexicon = new Lexicon( $fresh ? [] : null );
		if ( $fresh ) {
			$lexicon->reset();
		}

		$total = 0;
		$paged = 1;
		do {
			$q = new WP_Query( [
				'post_type'      => [ 'post', 'rtb_emission' ],
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'paged'          => $paged,
				'fields'         => 'ids',
			] );
			foreach ( $q->posts as $id ) {
				$text  = get_the_title( $id ) . ' '
					. get_post_field( 'post_excerpt', $id ) . ' '
					. wp_strip_all_tags( (string) get_post_field( 'post_content', $id ) );
				$words = Normalizer::keywords( $text );
				$lexicon->learn( $words );
				$total += count( $words );
			}
			$paged++;
		} while ( $paged <= (int) $q->max_num_pages );

		foreach ( [ 'category', 'post_tag', 'rtb_emission_cat' ] as $tax ) {
			$terms = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false ] );
			if ( is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $t ) {
				$words = Normalizer::keywords( $t->name );
				$lexicon->learn( $words );
				$total += count( $words );
			}
		}

		$lexicon->persist();

		$stats = [ 'processed' => $total, 'unique' => $lexicon->count(), 'time' => time() ];
		update_option( self::OPTION_LAST, $stats, false );
		return $stats;
	}

	/** @return array{processed:int,unique:int,time:int}|null */
	public static function last(): ?array {
		$v = get_option( self::OPTION_LAST );
		return is_array( $v ) ? $v : null;
	}
}
