<?php

namespace RTB\Chat\Responders;

use RTB\Chat\Message;
use RTB\Chat\Reply;

defined( 'ABSPATH' ) || exit;

/**
 * « Combien d'articles / d'émissions ? »
 */
final class Count implements Responder {

	public function handles( Message $msg ): bool {
		return $msg->contains( 'combien' ) && $msg->contains( 'article', 'articles', 'emission', 'emissions', 'jt', 'contenu', 'video', 'videos' );
	}

	public function respond( Message $msg ): Reply {
		$a = $msg->knowledge->countArticles();
		$e = $msg->knowledge->countEmissions();
		return Reply::make( 'count' )
			->text( sprintf( 'Le site de la RTB compte actuellement **%s** articles et **%s** émissions/JT publiés.', number_format_i18n( $a ), number_format_i18n( $e ) ) )
			->suggest( [ 'Dernières actualités', 'JT de 20H' ] );
	}
}
