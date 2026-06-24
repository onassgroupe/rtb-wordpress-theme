<?php

namespace RTB\Chat\Nlp;

defined( 'ABSPATH' ) || exit;

/**
 * Normalisation conversationnelle : minuscules + sans accents, gestion de l'élision
 * française (l'article → article), extraction de mots-clés sans mots-vides.
 */
final class Normalizer {

	private const STOPWORDS = [
		'de', 'des', 'du', 'la', 'le', 'les', 'un', 'une', 'et', 'en', 'au', 'aux',
		'pour', 'sur', 'dans', 'par', 'avec', 'ce', 'ces', 'cet', 'cette', 'qui',
		'que', 'quoi', 'est', 'ses', 'son', 'sa', 'ou', 'a', 'as', 'ai', 'je', 'tu',
		'il', 'elle', 'on', 'nous', 'vous', 'ils', 'me', 'te', 'se', 'mon', 'ma',
		'mes', 'ton', 'ta', 'tes', 'plus', 'moi', 'toi', 'donne', 'donnez', 'montre',
		'montrez', 'dis', 'dites', 'parle', 'parler', 'veux', 'peux', 'quel', 'quels',
		'quelle', 'quelles', 'the', 'of', 'and', 'aujourd', 'hui', 'svp',
		// Verbes de commande (intention, pas contenu) → ne doivent pas devenir des mots requis.
		'trouve', 'trouver', 'trouvez', 'cherche', 'chercher', 'cherchez', 'recherche',
		'rechercher', 'affiche', 'afficher', 'voir', 'liste', 'lister', 'propose',
		'proposer', 'envoie', 'envoyer', 'sors', 'sortir', 'connais', 'connaitre', 'sais',
		'faire', 'fais', 'fait', 'sait', 'peut', 'savoir', 'pouvoir',
	];

	/** Tokens courts (< 3) mais signifiants à conserver. */
	private const KEEP_SHORT = [ 'jt', 'tv' ];

	/** Élisions françaises retirées en début de mot (l', d', qu', j', n', s', c', m', t'). */
	private const ELISIONS = [ 'l', 'd', 'qu', 'j', 'n', 's', 'c', 'm', 't', 'jusqu', 'lorsqu', 'puisqu' ];

	public static function flat( string $text ): string {
		$text = remove_accents( mb_strtolower( $text, 'UTF-8' ) );
		$text = preg_replace( "/[’'`]/u", "'", $text );
		$text = preg_replace( '/[-_]+/', ' ', $text ); // « vas-tu » → « vas tu », « allez-vous » → « allez vous »
		return trim( preg_replace( '/\s+/', ' ', $text ) );
	}

	/** @return string[] mots-clés significatifs (élisions retirées, mots-vides exclus). */
	public static function keywords( string $text ): array {
		$flat   = self::flat( $text );
		$tokens = preg_split( '/[^a-z0-9\']+/', $flat, -1, PREG_SPLIT_NO_EMPTY ) ?: [];

		$out = [];
		foreach ( $tokens as $tok ) {
			$tok = self::deElide( $tok );
			$tok = trim( $tok, "'" );
			if ( in_array( $tok, self::STOPWORDS, true ) ) {
				continue;
			}
			$keep = mb_strlen( $tok ) >= 3
				|| ctype_digit( $tok )                       // nombres : 23, 13…
				|| in_array( $tok, self::KEEP_SHORT, true );  // jt, tv
			if ( ! $keep ) {
				continue;
			}
			$out[] = $tok;
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Retire l'élision UNIQUEMENT en présence d'une apostrophe : « l'article » → « article ».
	 * (On ne devine PAS l'élision sans apostrophe : « donne », « lundi », « demain » resteraient
	 *  amputés à tort en « onne », « undi », « emain ».)
	 */
	public static function deElide( string $word ): string {
		if ( str_contains( $word, "'" ) ) {
			$parts = array_filter( explode( "'", $word ) );
			$last  = end( $parts );
			// Si le segment avant l'apostrophe est une élision connue, on garde le segment d'après.
			return $last !== false ? $last : $word;
		}
		return $word;
	}
}
