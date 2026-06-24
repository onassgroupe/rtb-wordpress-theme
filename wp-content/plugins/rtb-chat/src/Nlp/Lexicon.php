<?php

namespace RTB\Chat\Nlp;

defined( 'ABSPATH' ) || exit;

/**
 * Vocabulaire appris du contenu RTB → correction orthographique (distance de Levenshtein).
 * Stocké en option WordPress. Alimenté par « wp rtb-chat learn ».
 */
final class Lexicon {

	private const OPTION = 'rtb_chat_lexicon';

	/** @var array<string,int> mot → fréquence */
	private array $words;

	public function __construct( ?array $words = null ) {
		$this->words = $words ?? (array) get_option( self::OPTION, [] );
	}

	public function count(): int {
		return count( $this->words );
	}

	public function has( string $word ): bool {
		return isset( $this->words[ $word ] );
	}

	/** Apprend une liste de mots (incrémente les fréquences). */
	public function learn( array $words ): void {
		foreach ( $words as $w ) {
			if ( '' === $w ) {
				continue;
			}
			$this->words[ $w ] = ( $this->words[ $w ] ?? 0 ) + 1;
		}
	}

	public function persist(): void {
		// Garde les 8000 mots les plus fréquents (borne la taille de l'option).
		arsort( $this->words );
		$this->words = array_slice( $this->words, 0, 8000, true );
		update_option( self::OPTION, $this->words, false );
	}

	public function reset(): void {
		$this->words = [];
	}

	/**
	 * Corrige un mot UNIQUEMENT en cas de faute évidente : une seule lettre de différence
	 * ET même première lettre (sinon on garde le mot tel quel). Évite « videos → vibes ».
	 */
	public function correct( string $word ): string {
		$word = mb_strtolower( $word, 'UTF-8' );
		if ( mb_strlen( $word ) < 5 || $this->has( $word ) || ! $this->words ) {
			return $word;
		}
		$first    = $word[0];
		$best     = '';
		$bestFreq = 0;
		foreach ( $this->words as $cand => $freq ) {
			$cand = (string) $cand;
			if ( '' === $cand || $cand[0] !== $first ) {        // même première lettre
				continue;
			}
			if ( abs( strlen( $cand ) - strlen( $word ) ) > 1 ) { // longueur quasi identique
				continue;
			}
			if ( 1 !== levenshtein( $word, $cand ) ) {            // exactement 1 édition
				continue;
			}
			if ( $freq > $bestFreq ) {
				$bestFreq = $freq;
				$best     = $cand;
			}
		}
		return '' !== $best ? $best : $word;
	}
}
