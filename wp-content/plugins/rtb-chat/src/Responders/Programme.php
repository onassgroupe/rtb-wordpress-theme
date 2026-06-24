<?php

namespace RTB\Chat\Responders;

use RTB\Chat\Message;
use RTB\Chat\Reply;

defined( 'ABSPATH' ) || exit;

/**
 * Programmes / grille des émissions.
 */
final class Programme implements Responder {

	public function handles( Message $msg ): bool {
		return $msg->contains(
			'programme', 'grille', 'horaire', 'quand passe', 'a quelle heure passe', 'au programme',
			'emission', 'emissions', 'video', 'videos', 'replay', 'replays', 'diffusion'
		);
	}

	public function respond( Message $msg ): Reply {
		// Demande de vidéos/émissions → on liste les dernières émissions (lisibles dans le chat).
		$listing = $msg->contains( 'video', 'videos', 'emission', 'emissions', 'replay', 'replays' );

		if ( $listing ) {
			$ids = $msg->knowledge->recent( 6, 'rtb_emission' );
			return Reply::make( 'programme' )
				->text( 'Voici les dernières émissions et JT de la RTB :' )
				->articles( array_map( [ $msg->knowledge, 'card' ], $ids ) )
				->actions( [ [ 'label' => 'Toutes les émissions', 'url' => home_url( '/emissions' ) ] ] );
		}

		$ids = $msg->knowledge->recent( 3, 'rtb_emission' );
		return Reply::make( 'programme' )
			->text( 'Retrouvez la grille complète des programmes TV et radio de la RTB.' )
			->actions( [
				[ 'label' => 'Voir la grille', 'url' => home_url( '/grille' ) ],
				[ 'label' => 'Les émissions', 'url' => home_url( '/emissions' ) ],
			] )
			->articles( array_map( [ $msg->knowledge, 'card' ], $ids ) );
	}
}
