<?php

namespace RTB\Search\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Normalisation des requêtes : minuscules, sans accents, sans mots-vides,
 * pluriels réduits à leur radical (conseils → conseil, ministres → ministre).
 */
final class Normalizer {

	/** Mots-vides français + quelques anglais courants. */
	private const STOPWORDS = [
		'de', 'des', 'du', 'la', 'le', 'les', 'un', 'une', 'et', 'en', 'au', 'aux',
		'pour', 'sur', 'dans', 'par', 'avec', 'ce', 'ces', 'cet', 'cette', 'qui',
		'que', 'quoi', 'est', 'ses', 'son', 'sa', 'ou', 'a', 'the', 'of', 'and',
	];

	/** Découpe une requête en radicaux significatifs, dédoublonnés. */
	public static function terms( string $query ): array {
		$query = remove_accents( mb_strtolower( $query, 'UTF-8' ) );
		$parts = preg_split( '/[^a-z0-9]+/', $query, -1, PREG_SPLIT_NO_EMPTY ) ?: [];

		$out = [];
		foreach ( $parts as $word ) {
			if ( mb_strlen( $word ) < 2 || in_array( $word, self::STOPWORDS, true ) ) {
				continue;
			}
			$out[] = self::stem( $word );
		}
		return array_values( array_unique( array_filter( $out ) ) );
	}

	/** Radical : retire le « s »/« x » du pluriel (en gardant ≥ 3 lettres). */
	public static function stem( string $word ): string {
		if ( mb_strlen( $word ) > 3 && preg_match( '/(s|x)$/', $word ) ) {
			return mb_substr( $word, 0, -1 );
		}
		return $word;
	}

	/** Phrase complète normalisée (pour le bonus « titre contient la phrase exacte »). */
	public static function phrase( string $query ): string {
		return trim( preg_replace( '/\s+/', ' ', remove_accents( mb_strtolower( $query, 'UTF-8' ) ) ) );
	}
}
