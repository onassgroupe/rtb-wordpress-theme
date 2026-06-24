<?php

namespace RTB\Chat;

defined( 'ABSPATH' ) || exit;

/**
 * Réponse de l'assistant, indépendante du format (rendue ensuite en HTML).
 * Composée de blocs : texte, titre, liste, cartes d'articles, boutons.
 */
final class Reply {

	/** @var array<int,array<string,mixed>> */
	private array $blocks = [];

	public string $intent = '';

	public static function make( string $intent = '' ): self {
		$r         = new self();
		$r->intent = $intent;
		return $r;
	}

	public function text( string $text ): self {
		$this->blocks[] = [ 'type' => 'text', 'text' => $text ];
		return $this;
	}

	public function heading( string $text ): self {
		$this->blocks[] = [ 'type' => 'heading', 'text' => $text ];
		return $this;
	}

	/** @param string[] $items */
	public function list( array $items ): self {
		$this->blocks[] = [ 'type' => 'list', 'items' => $items ];
		return $this;
	}

	/** @param array<int,array<string,mixed>> $cards */
	public function articles( array $cards ): self {
		if ( $cards ) {
			$this->blocks[] = [ 'type' => 'articles', 'cards' => $cards ];
		}
		return $this;
	}

	/** @param array<int,array{label:string,url:string}> $buttons */
	public function actions( array $buttons ): self {
		if ( $buttons ) {
			$this->blocks[] = [ 'type' => 'actions', 'buttons' => $buttons ];
		}
		return $this;
	}

	/** Questions suggérées (chips cliquables qui relancent l'assistant). @param string[] $chips */
	public function suggest( array $chips ): self {
		if ( $chips ) {
			$this->blocks[] = [ 'type' => 'suggest', 'chips' => $chips ];
		}
		return $this;
	}

	/** @return array<int,array<string,mixed>> */
	public function blocks(): array {
		return $this->blocks;
	}

	/** Version texte (mémoire de conversation). */
	public function toText(): string {
		$out = [];
		foreach ( $this->blocks as $b ) {
			if ( in_array( $b['type'], [ 'text', 'heading' ], true ) ) {
				$out[] = $b['text'];
			} elseif ( 'list' === $b['type'] ) {
				$out = array_merge( $out, $b['items'] );
			} elseif ( 'articles' === $b['type'] ) {
				foreach ( $b['cards'] as $c ) {
					$out[] = $c['title'] ?? '';
				}
			}
		}
		return trim( implode( ' ', $out ) );
	}
}
