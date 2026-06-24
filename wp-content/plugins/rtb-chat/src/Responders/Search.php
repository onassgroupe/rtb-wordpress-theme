<?php

namespace RTB\Chat\Responders;

use RTB\Chat\Message;
use RTB\Chat\Reply;

defined( 'ABSPATH' ) || exit;

/**
 * Recherche libre : le cœur RAG-léger. Renvoie les articles/JT les plus pertinents.
 * Gère aussi « dernières actualités » (sans mots-clés → contenus récents).
 */
final class Search implements Responder {

	public function handles( Message $msg ): bool {
		// Demande de récents OU présence de mots-clés exploitables.
		return $msg->contains( 'actualite', 'actualites', 'dernier', 'derniere', 'dernieres', 'recent', 'nouvelles', 'info', 'infos', 'news' )
			|| ! empty( $msg->keywords );
	}

	public function respond( Message $msg ): Reply {
		// Mots génériques « récents » (sans sujet précis).
		$recentWords = [ 'actualite', 'actualites', 'actu', 'dernier', 'derniere', 'dernieres', 'derniers',
			'recent', 'recente', 'recents', 'nouvelle', 'nouvelles', 'info', 'infos', 'news', 'jour' ];
		$topic       = array_values( array_diff( $msg->keywords, $recentWords ) );
		$wantsRecent = (bool) array_intersect( $msg->keywords, $recentWords ) || $msg->contains( 'quoi de neuf' );

		// Demande de récents sans sujet précis (ou message sans mot-clé) → contenus récents.
		if ( ( $wantsRecent && ! $topic ) || ! $msg->keywords ) {
			$ids = $msg->knowledge->recent( 5 );
			return Reply::make( 'recent' )
				->text( 'Voici les dernières publications de la RTB :' )
				->articles( array_map( [ $msg->knowledge, 'card' ], $ids ) )
				->suggest( [ 'JT de 20H', 'Sport', 'Le direct' ] );
		}

		// Sujet précis (on ignore les mots « récents » génériques).
		$query = implode( ' ', $topic ?: $msg->keywords );
		$ids   = $msg->knowledge->search( $query, 5 );
		if ( ! $ids ) {
			return Reply::make( 'search-empty' )
				->text( sprintf( "Je n'ai rien trouvé pour « **%s** ». Essayez d'autres mots-clés.", $query ) )
				->suggest( [ 'Dernières actualités', 'Conseil des ministres', 'Sport' ] );
		}

		return Reply::make( 'search' )
			->text( sprintf( 'Voici ce que j\'ai trouvé sur « **%s** » :', $query ) )
			->articles( array_map( [ $msg->knowledge, 'card' ], $ids ) );
	}
}
