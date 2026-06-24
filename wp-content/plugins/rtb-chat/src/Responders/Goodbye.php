<?php

namespace RTB\Chat\Responders;

use RTB\Chat\Message;
use RTB\Chat\Reply;

defined( 'ABSPATH' ) || exit;

/**
 * Prise de congé.
 */
final class Goodbye implements Responder {

	public function handles( Message $msg ): bool {
		return $msg->contains(
			'au revoir', 'aurevoir', 'a bientot', 'a plus', 'a la prochaine', 'a tantot',
			'bye', 'ciao', 'adieu', 'tchao', 'bonne continuation', 'a demain'
		);
	}

	public function respond( Message $msg ): Reply {
		return Reply::make( 'goodbye' )
			->text( 'Au revoir ! Merci de votre visite. Revenez quand vous voulez pour suivre l\'actualité de la RTB.' );
	}
}
