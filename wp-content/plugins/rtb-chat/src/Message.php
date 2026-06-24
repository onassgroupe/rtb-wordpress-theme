<?php

namespace RTB\Chat;

use RTB\Chat\Nlp\Lexicon;
use RTB\Chat\Nlp\Normalizer;

defined( 'ABSPATH' ) || exit;

/**
 * Message utilisateur analysé + services partagés, passé à chaque responder.
 */
final class Message {

	public readonly string $raw;
	public readonly string $flat;
	/** @var string[] */
	public readonly array $keywords;

	public function __construct(
		string $raw,
		public readonly Knowledge $knowledge,
		public readonly Lexicon $lexicon
	) {
		$this->raw  = trim( $raw );
		$this->flat = Normalizer::flat( $raw );
		// Mots-clés tels que tapés (pas d'auto-correction : le moteur gère pluriels & accents).
		$this->keywords = Normalizer::keywords( $raw );
	}

	public function isEmpty(): bool {
		return '' === $this->flat;
	}

	/** La requête contient-elle l'un de ces fragments (déjà normalisés) ? */
	public function contains( string ...$needles ): bool {
		foreach ( $needles as $n ) {
			if ( '' !== $n && str_contains( $this->flat, $n ) ) {
				return true;
			}
		}
		return false;
	}

	/** Recherche libre (mots-clés rejoints). */
	public function searchTerms(): string {
		return implode( ' ', $this->keywords );
	}
}
