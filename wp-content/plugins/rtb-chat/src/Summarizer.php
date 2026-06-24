<?php

namespace RTB\Chat;

use RTB\Chat\Nlp\Normalizer;

defined( 'ABSPATH' ) || exit;

/**
 * Résumé extractif local : sélectionne les phrases les plus représentatives
 * (fréquence des mots significatifs + bonus pour les mots-clés de la question).
 * Aucune IA, aucun appel réseau.
 */
final class Summarizer {

	public function summarize( string $text, array $queryKeywords = [], int $max = 3 ): array {
		$text = trim( preg_replace( '/\s+/', ' ', $text ) );
		if ( '' === $text ) {
			return [];
		}
		$sentences = $this->splitSentences( $text );
		if ( count( $sentences ) <= $max ) {
			return $sentences;
		}

		// Fréquence des mots significatifs sur tout le texte.
		$freq = [];
		foreach ( Normalizer::keywords( $text ) as $w ) {
			$freq[ $w ] = ( $freq[ $w ] ?? 0 ) + 1;
		}

		$scored = [];
		foreach ( $sentences as $idx => $s ) {
			$words = Normalizer::keywords( $s );
			if ( ! $words ) {
				continue;
			}
			$score = 0;
			foreach ( $words as $w ) {
				$score += $freq[ $w ] ?? 0;
				if ( in_array( $w, $queryKeywords, true ) ) {
					$score += 5; // bonus pertinence question
				}
			}
			$score      = $score / max( 1, sqrt( count( $words ) ) ); // normalise la longueur
			$scored[]   = [ 'i' => $idx, 's' => $s, 'score' => $score ];
		}

		usort( $scored, static fn( $a, $b ) => $b['score'] <=> $a['score'] );
		$top = array_slice( $scored, 0, $max );
		usort( $top, static fn( $a, $b ) => $a['i'] <=> $b['i'] ); // remet dans l'ordre du texte

		return array_map( static fn( $x ) => $x['s'], $top );
	}

	/** @return string[] */
	private function splitSentences( string $text ): array {
		$parts = preg_split( '/(?<=[.!?…])\s+(?=[A-ZÀ-Ý0-9«"])/u', $text ) ?: [];
		$out   = [];
		foreach ( $parts as $p ) {
			$p = trim( $p );
			if ( mb_strlen( $p ) >= 30 ) {
				$out[] = $p;
			}
		}
		return $out;
	}
}
