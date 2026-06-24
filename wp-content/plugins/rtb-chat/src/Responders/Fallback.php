<?php

namespace RTB\Chat\Responders;

use RTB\Chat\Message;
use RTB\Chat\Reply;

defined( 'ABSPATH' ) || exit;

/**
 * Filet de sécurité : aucune intention reconnue.
 */
final class Fallback implements Responder {

	public function handles( Message $msg ): bool {
		return true;
	}

	public function respond( Message $msg ): Reply {
		return Reply::make( 'fallback' )
			->text( "Je ne suis pas sûr d'avoir bien compris. Je peux **chercher** des articles, vous donner les **dernières actualités**, **résumer** un sujet, ou vous orienter vers le **direct** et le **contact** de la RTB." )
			->text( 'Reformulez avec quelques mots-clés, ou choisissez :' )
			->suggest( [ 'Dernières actualités', 'Conseil des ministres', 'Le direct', 'Que peux-tu faire ?' ] );
	}
}
