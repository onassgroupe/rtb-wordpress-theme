<?php

namespace RTB\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * Sélecteur de langue : chaque entrée pointe vers la page courante dans la
 * langue cible (même chemin, préfixe échangé) → on reste sur place.
 */
final class Switcher {

	/** @return array<int,array{name:string,slug:string,flag:string,url:string,current:bool}> */
	public static function languages(): array {
		$cur   = Locale::current();
		$inner = Locale::inner();
		$qs    = ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '';
		$out   = [];
		foreach ( Locale::all() as $d ) {
			$out[] = [
				'name'    => $d['name'],
				'slug'    => $d['slug'],
				'flag'    => $d['flag'],
				'url'     => home_url( '/' . $d['slug'] . $inner ) . $qs,
				'current' => $d['slug'] === $cur,
			];
		}
		return $out;
	}
}
