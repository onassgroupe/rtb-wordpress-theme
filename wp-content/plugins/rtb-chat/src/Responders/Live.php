<?php

namespace RTB\Chat\Responders;

use RTB\Chat\Message;
use RTB\Chat\Reply;

defined( 'ABSPATH' ) || exit;

/**
 * Direct / radio en ligne.
 */
final class Live implements Responder {

	public function handles( Message $msg ): bool {
		return $msg->contains( 'direct', 'en live', 'live', 'regarder', 'antenne', 'streaming', 'radio', 'ecouter', 'chaine', 'chaines' );
	}

	public function respond( Message $msg ): Reply {
		$reply = Reply::make( 'live' );

		if ( $msg->contains( 'radio', 'ecouter' ) ) {
			$reply->text( 'Écoutez les radios de la RTB en direct.' )
				->actions( [ [ 'label' => 'Ouvrir la radio', 'url' => home_url( '/radio' ) ] ] );
		} else {
			$reply->text( 'Regardez la RTB en direct (RTB Télévision, Télé Zénith, RTB Guiriko) — et la radio.' )
				->actions( [
					[ 'label' => 'Le direct TV', 'url' => home_url( '/direct' ) ],
					[ 'label' => 'La radio', 'url' => home_url( '/radio' ) ],
				] );
		}
		return $reply;
	}
}
