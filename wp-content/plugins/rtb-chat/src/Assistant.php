<?php

namespace RTB\Chat;

use RTB\Chat\Nlp\Lexicon;
use RTB\Chat\Responders\Contact;
use RTB\Chat\Responders\Count;
use RTB\Chat\Responders\DateTime;
use RTB\Chat\Responders\Fallback;
use RTB\Chat\Responders\Goodbye;
use RTB\Chat\Responders\Help;
use RTB\Chat\Responders\Live;
use RTB\Chat\Responders\Programme;
use RTB\Chat\Responders\Responder;
use RTB\Chat\Responders\Search;
use RTB\Chat\Responders\SmallTalk;
use RTB\Chat\Responders\Summary;

defined( 'ABSPATH' ) || exit;

/**
 * Assistant local : analyse le message, choisit le premier responder qui le gère,
 * renvoie une Reply. Ordre du pipeline = priorité des intentions.
 */
final class Assistant {

	private Knowledge $knowledge;
	private Lexicon $lexicon;

	/** @var class-string<Responder>[] */
	private array $pipeline = [
		SmallTalk::class,
		Help::class,
		Goodbye::class,
		DateTime::class,
		Contact::class,
		Programme::class,
		Count::class,
		Live::class,
		Summary::class,
		Search::class,
		Fallback::class,
	];

	public function __construct( ?Knowledge $knowledge = null, ?Lexicon $lexicon = null ) {
		$this->knowledge = $knowledge ?? new Knowledge();
		$this->lexicon   = $lexicon ?? new Lexicon();
	}

	public function answer( string $text ): Reply {
		$msg = new Message( $text, $this->knowledge, $this->lexicon );

		if ( $msg->isEmpty() ) {
			return ( new Fallback() )->respond( $msg );
		}

		foreach ( $this->pipeline as $class ) {
			$responder = new $class();
			if ( $responder->handles( $msg ) ) {
				return $responder->respond( $msg );
			}
		}
		return ( new Fallback() )->respond( $msg );
	}
}
