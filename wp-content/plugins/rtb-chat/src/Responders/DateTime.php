<?php

namespace RTB\Chat\Responders;

use RTB\Chat\Message;
use RTB\Chat\Reply;

defined( 'ABSPATH' ) || exit;

/**
 * Heure et date courantes (petite utilité conversationnelle).
 */
final class DateTime implements Responder {

	public function handles( Message $msg ): bool {
		return $msg->contains(
			'quelle heure', 'il est quelle heure', 'heure est', 'quel jour', 'on est quel',
			'quelle date', 'date du jour', 'quel mois', 'quelle annee', 'nous sommes quel'
		);
	}

	public function respond( Message $msg ): Reply {
		$wantsTime = $msg->contains( 'heure' );
		if ( $wantsTime ) {
			return Reply::make( 'datetime' )->text( sprintf(
				'Il est **%s** (heure de Ouagadougou), nous sommes le %s.',
				date_i18n( 'H\hi' ),
				date_i18n( 'l j F Y' )
			) );
		}
		return Reply::make( 'datetime' )->text( sprintf( 'Nous sommes le **%s**.', date_i18n( 'l j F Y' ) ) );
	}
}
