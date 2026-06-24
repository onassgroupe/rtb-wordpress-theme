<?php

namespace RTB\Chat\Responders;

use RTB\Chat\Message;
use RTB\Chat\Reply;

defined( 'ABSPATH' ) || exit;

/**
 * Salutations, remerciements, « qui es-tu ». Détection par mots entiers (anti faux-positifs)
 * et seulement si le message est « essentiellement » une politesse (sinon on laisse chercher).
 */
final class SmallTalk implements Responder {

	/** Salutations en 1 mot (FR + abrégés + langues nationales). Comparées comme mots entiers. */
	private const GREET_WORDS = [
		'bonjour', 'bonsoir', 'bondjour', 'salut', 'coucou', 'cc', 'slt', 'bjr', 'bsr',
		'hello', 'hi', 'hey', 'yo', 'wesh', 'hola', 'salam', 'salamou', 're',
		'sogoma', 'wula', 'sɔgɔma', 'yibeoogo', 'zaabre', 'neyibeoogo',
	];

	/** Salutations / politesses en plusieurs mots. */
	private const GREET_PHRASES = [
		'ca va', 'comment ca va', 'comment allez', 'comment vas', 'comment tu vas',
		'vous allez bien', 'tu vas bien', 'quoi de neuf', 'ca roule', 'la forme',
		'bonne journee', 'bonne soiree', 'bonne nuit', 'enchante',
		'i ni sogoma', 'i ni ce', 'i ni tile', 'i ni wula', 'ne y yibeoogo', 'ne y zaabre',
	];

	/** Mots « de remplissage » tolérés autour d'une salutation (ne comptent pas comme un sujet). */
	private const FILLER = [
		'comment', 'vous', 'allez', 'bien', 'neuf', 'quoi', 'bonne', 'journee', 'soiree',
		'nuit', 'roule', 'forme', 'matin', 'jour', 'cher', 'chere', 'svp', 'stp',
	];

	private const THANKS = [ 'merci', 'mercii', 'thanks', 'thank', 'nice', 'parfait', 'super', 'genial', 'top', 'cool' ];

	public function handles( Message $msg ): bool {
		return $this->isThanks( $msg ) || $this->isIdentity( $msg ) || $this->isPureGreeting( $msg );
	}

	public function respond( Message $msg ): Reply {
		if ( $this->isThanks( $msg ) ) {
			return Reply::make( 'smalltalk' )->text( 'Avec plaisir ! Je reste à votre disposition pour toute info sur la RTB.' );
		}

		// « Comment ça va ? »
		if ( $this->isHowAreYou( $msg ) ) {
			return Reply::make( 'smalltalk' )
				->text( 'Tout va bien, merci ! Toujours prêt à vous aider. Que souhaitez-vous savoir sur la RTB ?' )
				->suggest( [ 'Dernières actualités', 'Le direct', 'JT de 20H' ] );
		}

		if ( $this->isIdentity( $msg ) ) {
			return Reply::make( 'smalltalk' )
				->text( "Je suis l'assistant de la **RTB** (Radiodiffusion Télévision du Burkina). Je réponds à partir du contenu du site : articles, JT, émissions, direct…" )
				->suggest( [ 'Dernières actualités', 'Le direct', 'Conseil des ministres' ] );
		}

		// Bonjour vs bonsoir selon le moment / le mot employé.
		$evening = $msg->contains( 'bonsoir', 'bsr', 'bonne soiree', 'bonne nuit', 'zaabre' ) || (int) current_time( 'G' ) >= 18;
		$hello   = $evening ? 'Bonsoir' : 'Bonjour';

		return Reply::make( 'smalltalk' )
			->text( $hello . " ! Je suis l'assistant de la RTB. Posez-moi une question sur l'actualité, les JT, les émissions ou le direct." )
			->suggest( [ 'Dernières actualités', 'JT de 20H', 'Le direct', 'Conseil des ministres' ] );
	}

	private function isThanks( Message $msg ): bool {
		foreach ( $this->tokens( $msg ) as $t ) {
			if ( in_array( $t, self::THANKS, true ) ) {
				return true;
			}
		}
		return false;
	}

	private function isIdentity( Message $msg ): bool {
		return $msg->contains( 'qui es tu', 'qui es-tu', 'tu es qui', 'ton nom', 'tu t appelles', 'comment tu t', 'tu fais quoi', 'tu sers a quoi' );
	}

	/** « comment (tu/vous) va/vas/allez », « ça va », « tu vas bien », « la forme »… */
	private function isHowAreYou( Message $msg ): bool {
		$f = $msg->flat;
		if ( preg_match( '/\b(comment|cmt)\s+(tu\s+|vous\s+)?(va|vas|vont|allez|aller|vais)\b/', $f ) ) {
			return true;
		}
		if ( preg_match( '/\bca\s+va\b/', $f ) ) {
			return true;
		}
		return $msg->contains( 'va bien', 'vas bien', 'allez bien', 'la forme', 'quoi de neuf', 'ca roule' );
	}

	/** Vrai si le message est essentiellement une salutation. */
	private function isPureGreeting( Message $msg ): bool {
		// Une phrase de politesse (« comment tu vas », « ça va »…) est sans ambiguïté.
		if ( $this->isHowAreYou( $msg ) || $msg->contains( ...self::GREET_PHRASES ) ) {
			return true;
		}
		// Sinon : une salutation en un mot, et rien d'autre de substantiel
		// (« bonjour le sport » → ce n'est PAS qu'une salutation → on laissera chercher).
		$tokens = $this->tokens( $msg );
		if ( ! array_intersect( $tokens, self::GREET_WORDS ) ) {
			return false;
		}
		foreach ( $tokens as $t ) {
			if ( mb_strlen( $t ) >= 3
				&& ! in_array( $t, self::GREET_WORDS, true )
				&& ! in_array( $t, self::FILLER, true ) ) {
				return false;
			}
		}
		return true;
	}

	/** @return string[] */
	private function tokens( Message $msg ): array {
		return preg_split( '/\s+/', $msg->flat, -1, PREG_SPLIT_NO_EMPTY ) ?: [];
	}
}
