<?php

namespace RTB\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * Traduction des chaînes d'interface via un dictionnaire.
 * Anglais : complet. Langues nationales : BROUILLON à valider par la RTB.
 */
final class Translator {

	public static function t( string $s ): string {
		if ( Locale::isDefault() ) {
			return $s;
		}
		$map = self::strings();
		$lang = Locale::current();
		return $map[ $lang ][ $s ] ?? $s;
	}

	/** @return array<string,array<string,string>> */
	public static function strings(): array {
		return [
			'en'  => [
				'Accueil' => 'Home', 'Le Direct' => 'Live', 'Actualités' => 'News', 'Le Journal' => 'Newscast',
				'Émissions' => 'Shows', 'Sport' => 'Sports', 'Régions' => 'Regions', 'Contact' => 'Contact',
				'EN DIRECT' => 'LIVE', 'Regarder en direct' => 'Watch live', 'Guide des programmes' => 'TV guide',
				'Rechercher' => 'Search', 'RECHERCHER SUR RTB' => 'SEARCH RTB', "Toute l'actualité" => 'All the news',
				"À L'ANTENNE MAINTENANT" => 'ON AIR NOW', 'DERNIÈRE MINUTE' => 'BREAKING NEWS',
				'Recherches fréquentes' => 'Popular searches', 'Voir la chaîne' => 'View channel',
				'Regarder la chaîne' => 'Watch channel', 'Toutes les chaînes' => 'All channels',
				'Toutes les émissions' => 'All shows', "L'info des régions" => 'Regional news', 'Tout' => 'All',
				'À LA UNE' => 'TOP STORIES', 'LES GROS TITRES' => 'HEADLINES', 'INFORMATION' => 'NEWS',
				'Le Journal Télévisé' => 'TV Newscast', 'NOS ANTENNES' => 'OUR CHANNELS',
				'GRANDS RENDEZ-VOUS' => 'HIGHLIGHTS', 'PROXIMITÉ' => 'LOCAL',
				'La RTB en régions' => 'RTB across the regions', 'RADIO EN DIRECT' => 'LIVE RADIO',
				'PROGRAMMES' => 'PROGRAMS', 'PLUS DE VIDÉOS' => 'MORE VIDEOS',
				'CATÉGORIES POPULAIRES' => 'POPULAR CATEGORIES',
			],
			'mos' => [
				'Accueil' => 'Yiri', 'Le Direct' => 'Sasa', 'Actualités' => 'Kibaya', 'Le Journal' => 'Kibar-kãsenga',
				'Émissions' => 'Yɛlsgo', 'Sport' => 'Sport', 'Régions' => 'Tẽnsã', 'Contact' => 'Kɛɛnse',
				'EN DIRECT' => 'SASA', 'Rechercher' => 'Bao', 'Tout' => 'Fãa', 'Toutes les chaînes' => 'Sɛkã fãa',
				'NOS ANTENNES' => 'TÕND ANTENNÃ', 'À LA UNE' => 'PĨNDÃ', 'RADIO EN DIRECT' => 'RADIO SASA',
			],
			'dyu' => [
				'Accueil' => 'So', 'Le Direct' => 'Sisan', 'Actualités' => 'Kibaruyaw', 'Le Journal' => 'Kunnafoni',
				'Émissions' => 'Porogaramuw', 'Sport' => 'Farikoloɲɛnajɛ', 'Régions' => 'Marabolow', 'Contact' => 'Ɲɔgɔnye',
				'EN DIRECT' => 'SISAN', 'Rechercher' => 'Ɲini', 'Tout' => 'Bɛɛ',
				'NOS ANTENNES' => 'AN KA TELEYAW', 'À LA UNE' => 'KUNFƆLƆ',
			],
			'ff'  => [
				'Accueil' => 'Suudu', 'Le Direct' => 'Jooni', 'Actualités' => 'Kabaruuji', 'Le Journal' => 'Kabaaru',
				'Émissions' => 'Eɓɓooje', 'Sport' => 'Coftal ɓalli', 'Régions' => 'Diiwanuuji', 'Contact' => 'Jokkondiral',
				'EN DIRECT' => 'JOONI', 'Rechercher' => 'Ɗaɓɓude', 'Tout' => 'Fof',
				'NOS ANTENNES' => 'LAABI AMEN', 'À LA UNE' => 'KO ƁURI HIMME',
			],
			'gux' => [
				'Accueil' => 'Deni', 'Le Direct' => 'Mɔanu', 'Actualités' => 'Labaali', 'Sport' => 'Sport',
				'EN DIRECT' => 'MƆANU', 'Rechercher' => 'Lingidi', 'Tout' => 'Kuli',
				'NOS ANTENNES' => 'TI ANTENANU', 'À LA UNE' => 'YAA PUOLI',
			],
		];
	}
}
