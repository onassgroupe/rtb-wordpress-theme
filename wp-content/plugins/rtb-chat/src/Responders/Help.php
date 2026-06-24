<?php

namespace RTB\Chat\Responders;

use RTB\Chat\Message;
use RTB\Chat\Reply;

defined( 'ABSPATH' ) || exit;

/**
 * « Aide / que peux-tu faire / comment ça marche ».
 */
final class Help implements Responder {

	public function handles( Message $msg ): bool {
		if ( $msg->contains( 'aide', 'aider', 'help', 'besoin d aide' ) ) {
			return true;
		}
		$f = $msg->flat;
		// « que sais-tu / peux-tu faire », « tu sais faire quoi », « que fais-tu »…
		if ( preg_match( '/\b(sais|sait|peux|peut|saurais|pourrais|fais|fait)\b.{0,12}\bfaire\b/', $f ) ) {
			return true;
		}
		if ( preg_match( '/\bfaire\b.{0,10}(quoi|pour moi)/', $f ) ) {
			return true;
		}
		return $msg->contains(
			'que peux tu', 'que peux-tu', 'tu peux faire', 'tu sais faire', 'fais quoi', 'fait quoi',
			'capacite', 'capable', 'tes fonctions', 'comment ca marche', 'comment tu marche',
			'comment ca fonctionne', 'comment utiliser', 'a quoi tu sers', 'tu sers a quoi',
			'tu fais quoi', 'quoi faire', 'sers a quoi', 'role'
		);
	}

	public function respond( Message $msg ): Reply {
		return Reply::make( 'help' )
			->text( "Bien sûr ! Voici ce que je peux faire pour vous :" )
			->list( [
				'**Rechercher** des articles, JT et émissions (ex. « finale Coupe du Faso »)',
				'**Résumer** un sujet (ex. « résume-moi le conseil des ministres »)',
				'Donner les **dernières actualités**',
				'Vous orienter vers le **direct** et la **radio**',
				'Répondre sur les **programmes** et le **contact** de la RTB',
			] )
			->text( 'Astuce : quelques mots-clés suffisent. Essayez :' )
			->suggest( [ 'Dernières actualités', 'Résume le conseil des ministres', 'Le direct', 'Contact RTB' ] );
	}
}
