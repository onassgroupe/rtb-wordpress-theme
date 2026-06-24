<?php

namespace RTB\Chat\Responders;

use RTB\Chat\Message;
use RTB\Chat\Reply;
use RTB\Chat\Summarizer;

defined( 'ABSPATH' ) || exit;

/**
 * « Résume-moi <sujet> » : trouve l'article le plus pertinent et en fait un résumé extractif local.
 */
final class Summary implements Responder {

	public function handles( Message $msg ): bool {
		return $msg->contains( 'resume', 'resumer', 'resumé', 'synthese', 'en bref', 'explique' ) && ! empty( $msg->keywords );
	}

	public function respond( Message $msg ): Reply {
		// On retire les mots d'intention pour ne garder que le sujet.
		$topic = array_values( array_filter( $msg->keywords, static fn( $k ) => ! in_array( $k, [ 'resume', 'resumer', 'synthese', 'explique', 'bref' ], true ) ) );
		$query = $topic ? implode( ' ', $topic ) : $msg->searchTerms();

		$ids = $msg->knowledge->search( $query, 1 );
		if ( ! $ids ) {
			return Reply::make( 'summary' )
				->text( "Je n'ai pas trouvé d'article sur ce sujet à résumer. Essayez d'autres mots-clés." )
				->suggest( [ 'Dernières actualités' ] );
		}

		$id        = $ids[0];
		$card      = $msg->knowledge->card( $id );
		$plain     = $msg->knowledge->plainText( $id );
		$sentences = ( new Summarizer() )->summarize( $plain, $topic, 3 );

		$reply = Reply::make( 'summary' )->heading( $card['title'] ?? 'Résumé' );
		if ( $sentences ) {
			$reply->list( $sentences );
		} else {
			$reply->text( "Cet article n'a pas assez de texte pour un résumé, mais vous pouvez l'ouvrir directement." );
		}
		return $reply->actions( [ [ 'label' => "Lire l'article", 'url' => $card['url'] ] ] );
	}
}
