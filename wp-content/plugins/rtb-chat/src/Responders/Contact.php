<?php

namespace RTB\Chat\Responders;

use RTB\Chat\Message;
use RTB\Chat\Reply;

defined( 'ABSPATH' ) || exit;

/**
 * Coordonnées de la RTB (téléphone, e-mail, adresse) + lien vers la page contact.
 * Les valeurs proviennent du Customizer du thème (onass_mod) si disponibles.
 */
final class Contact implements Responder {

	public function handles( Message $msg ): bool {
		return $msg->contains(
			'contact', 'contacter', 'joindre', 'adresse', 'telephone', 'numero', 'appeler',
			'email', 'e mail', 'mail', 'ecrire', 'siege', 'localisation', 'ou se trouve', 'ou est la rtb'
		);
	}

	public function respond( Message $msg ): Reply {
		$phone = $this->mod( 'rtb_phone', '(+226) 25 31 83 53 / 63' );
		$email = $this->mod( 'rtb_email', 'info@rtb.bf' );
		$addr  = $this->mod( 'rtb_address', '01 BP 2530 Ouagadougou 01, Burkina Faso' );

		return Reply::make( 'contact' )
			->heading( 'Contacter la RTB' )
			->list( [
				'Téléphone : **' . $phone . '**',
				'E-mail : **' . $email . '**',
				'Adresse : ' . $addr,
			] )
			->actions( [ [ 'label' => 'Page contact', 'url' => home_url( '/contact' ) ] ] );
	}

	private function mod( string $key, string $default ): string {
		$v = function_exists( 'onass_mod' ) ? (string) onass_mod( $key, $default ) : $default;
		return '' !== $v ? $v : $default;
	}
}
