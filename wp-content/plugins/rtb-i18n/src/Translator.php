<?php

namespace RTB\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * Traduction de l'interface — modèle « source → traduction » (style gettext) :
 * la chaîne française passée à rtb_t() est la clé ; on renvoie sa traduction
 * dans la langue courante, ou la source française par défaut/à défaut.
 *
 * Anglais : complet. Langues nationales : BROUILLON à valider par la RTB
 * (les chaînes non traduites retombent sur le français).
 */
final class Translator {

	public static function t( string $s ): string {
		if ( Locale::isDefault() ) {
			return $s;
		}
		$map  = self::strings();
		$lang = Locale::current();
		return $map[ $lang ][ $s ] ?? $s;
	}

	/** @return array<string,array<string,string>> langue => [ source FR => traduction ] */
	public static function strings(): array {
		return [
			'en'  => [
				// Navigation & sections
				'Accueil' => 'Home', 'Le Direct' => 'Live', 'Actualités' => 'News', 'Le Journal' => 'Newscast',
				'Émissions' => 'Shows', 'Sport' => 'Sports', 'Régions' => 'Regions', 'Contact' => 'Contact',
				'À propos' => 'About', 'Radio' => 'Radio', 'Grille' => 'Schedule', 'En direct' => 'Live',
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
				'DERNIERS ARTICLES' => 'LATEST ARTICLES', 'À REVOIR SUR RTB' => 'WATCH AGAIN ON RTB',
				'Tous les replays' => 'All replays', 'Accès rapides' => 'Quick access',
				// Footer
				'Tél.' => 'Tel.', 'Contact :' => 'Contact:',
				"La Radiodiffusion Télévision du Burkina (RTB) est la société publique de radiotélévision du Burkina Faso, au service de l'information et de la proximité." => "The Radiodiffusion Télévision du Burkina (RTB) is the public broadcasting company of Burkina Faso, dedicated to information and local proximity.",
				'Mentions légales' => 'Legal notice', 'Confidentialité' => 'Privacy', 'CGU' => 'Terms of use',
				'Accessibilité' => 'Accessibility', 'Plan du site' => 'Site map',
				// Cookies
				'Consentement aux cookies' => 'Cookie consent', 'Cookies & confidentialité' => 'Cookies & privacy',
				'La RTB utilise des cookies pour assurer le bon fonctionnement du site, mesurer son audience et améliorer votre expérience. Vous pouvez tout accepter, tout refuser ou choisir par catégorie.' => 'RTB uses cookies to ensure the proper functioning of the site, measure its audience and improve your experience. You can accept all, reject all or choose by category.',
				'En savoir plus' => 'Learn more', 'Nécessaires' => 'Essential',
				'Indispensables au fonctionnement du site (session, sécurité, consentement). Toujours actifs.' => "Essential to the site's operation (session, security, consent). Always active.",
				'Préférences' => 'Preferences',
				'Mémorisent vos choix : langue, mode clair/sombre, réglages d’affichage.' => 'Remember your choices: language, light/dark mode, display settings.',
				'Mesure d’audience' => 'Audience measurement',
				'Statistiques de fréquentation anonymes (pages vues, durée) pour améliorer le site.' => 'Anonymous traffic statistics (page views, duration) to improve the site.',
				'Publicité & marketing' => 'Advertising & marketing',
				'Personnalisation des annonces et mesure des campagnes.' => 'Ad personalization and campaign measurement.',
				'Réseaux sociaux & vidéos' => 'Social networks & videos',
				'Lecteurs et boutons de partage tiers (YouTube, Facebook, X…).' => 'Third-party players and share buttons (YouTube, Facebook, X…).',
				'Géolocalisation' => 'Geolocation',
				'Contenus et actualités adaptés à votre région.' => 'Content and news tailored to your region.',
				'Personnaliser' => 'Customize', 'Réduire' => 'Collapse', 'Tout refuser' => 'Reject all',
				'Enregistrer mes choix' => 'Save my choices', 'Tout accepter' => 'Accept all',
				// 404
				'ERREUR 404' => 'ERROR 404', 'Cette page est introuvable.' => 'This page cannot be found.',
				"La page que vous cherchez n'existe pas, a été déplacée ou a changé d'adresse. Lancez une recherche ou explorez les rubriques ci-dessous." => 'The page you are looking for does not exist, has been moved or has changed address. Run a search or explore the sections below.',
				// Recherche & article
				'Rechercher sur RTB…' => 'Search RTB…', 'RECHERCHE' => 'SEARCH', 'Résultats pour' => 'Results for',
				'Aucun résultat. Essayez d’autres mots-clés.' => 'No results. Try other keywords.',
				'Articles' => 'Articles', 'Pertinence' => 'Relevance', 'Récents' => 'Newest', 'Anciens' => 'Oldest',
				'Type' => 'Type', 'Trier' => 'Sort', 'Résultats de recherche' => 'Search results',
				'Relancer une recherche' => 'Start a new search',
				'Vérifiez l’orthographe ou utilisez des termes plus généraux.' => 'Check the spelling or use more general terms.',
				'Par la' => 'By the', 'Rédaction RTB' => 'RTB Editorial Team',
				'Document officiel' => 'Official document', 'Télécharger le PDF' => 'Download the PDF',
				// Direct / Radio / Régions
				'Suivez en direct toutes les antennes de la RTB — télévision et radio — où que vous soyez.' => 'Watch all RTB channels live — television and radio — wherever you are.',
				'RTB — Direct' => 'RTB — Live', 'Écouter la radio en direct' => 'Listen to the radio live',
				'Radio en direct' => 'Live radio',
				'Écoutez les stations de la RTB en direct, où que vous soyez.' => 'Listen to RTB stations live, wherever you are.',
				'PROGRAMMES RADIO' => 'RADIO PROGRAMS',
				'Retrouvez la grille complète des programmes radio de la RTB.' => 'Find the full schedule of RTB radio programs.',
				'Voir la grille des programmes' => 'View the program schedule',
				"Présente sur l'ensemble du territoire, la RTB couvre l'actualité de toutes les régions du Burkina Faso et porte la voix de la proximité." => 'Present throughout the country, RTB covers news from every region of Burkina Faso and gives voice to local communities.',
				"ANTENNE DE L'OUEST" => 'WESTERN CHANNEL',
				'RTB Guiriko, la proximité au cœur des Hauts-Bassins' => 'RTB Guiriko, local coverage at the heart of the Hauts-Bassins',
				"Depuis Bobo-Dioulasso, RTB Guiriko informe et accompagne les populations de l'Ouest du pays." => "From Bobo-Dioulasso, RTB Guiriko informs and supports the people of the country's western region.",
				'Découvrir RTB Guiriko' => 'Discover RTB Guiriko',
				// À propos
				'Antennes TV & radio' => 'TV & radio channels', 'Régions couvertes' => 'Regions covered',
				'Diffusion en continu' => 'Round-the-clock broadcasting', 'Année de création' => 'Year founded',
				'À PROPOS' => 'ABOUT', 'La Radiodiffusion Télévision du Burkina' => 'The Radiodiffusion Télévision du Burkina',
				'Notre mission' => 'Our mission', 'Nos valeurs' => 'Our values', 'NOTRE HISTOIRE' => 'OUR HISTORY',
				'Plus de 60 ans au service du public' => 'Over 60 years of public service',
				'RÉCOMPENSES & DISTINCTIONS' => 'AWARDS & DISTINCTIONS', 'Un travail reconnu' => 'Recognized work',
				'LA DIRECTION' => 'MANAGEMENT', 'Organisation de la RTB' => 'RTB organization',
				'Une question, un partenariat ?' => 'A question, a partnership?',
				'La rédaction et les services de la RTB sont à votre écoute.' => 'The RTB newsroom and departments are here to help.',
				'Nous contacter' => 'Contact us',
				// Jours / grille
				'Lun' => 'Mon', 'Mar' => 'Tue', 'Mer' => 'Wed', 'Jeu' => 'Thu', 'Ven' => 'Fri', 'Sam' => 'Sat', 'Dim' => 'Sun',
				'Grille des programmes' => 'Program schedule',
				'Retrouvez les programmes TV et radio de toutes les antennes de la RTB, et ce qui passe en ce moment.' => 'Find the TV and radio programs of all RTB channels, and what is on right now.',
				'Auj.' => 'Today', 'En ce moment sur' => 'Now on', 'EN COURS' => 'ON AIR', 'À SUIVRE' => 'UP NEXT',
				'Les horaires sont donnés à titre indicatif et peuvent être modifiés.' => 'Schedule times are indicative and subject to change.',
				// Contact
				'Téléphone' => 'Phone', 'E-mail' => 'Email', 'Adresse' => 'Address', 'NOUS CONTACTER' => 'CONTACT US',
				'Contactez la RTB' => 'Contact RTB',
				'Une question, une information à transmettre à la rédaction, un partenariat ? Nos équipes vous répondent.' => 'A question, information to share with the newsroom, a partnership? Our teams are here to answer.',
				'Écrire à la rédaction' => 'Write to the newsroom',
				"Les champs marqués d'un" => 'Fields marked with a', 'sont obligatoires.' => 'are required.',
				'Nom complet *' => 'Full name *', 'E-mail *' => 'Email *', 'Sujet' => 'Subject',
				'Objet de votre message' => 'Subject of your message', 'Message *' => 'Message *',
				'Envoyer le message' => 'Send message', 'Localisation RTB Ouagadougou' => 'RTB Ouagadougou location',
				"Horaires d'accueil" => 'Reception hours', 'Lundi – Vendredi' => 'Monday – Friday',
				'07h30 – 17h30' => '7:30 AM – 5:30 PM', 'Samedi' => 'Saturday', '08h00 – 12h00' => '8:00 AM – 12:00 PM',
				'Dimanche' => 'Sunday', 'Fermé · rédaction en direct' => 'Closed · live newsroom',
				// Archives & CPT
				'Articles de la rubrique' => 'Articles in this section',
				'Aucun contenu dans cette rubrique pour le moment.' => 'No content in this section yet.',
				'TÉLÉVISION' => 'TELEVISION', 'Émissions & vidéos' => 'Shows & videos',
				'Regarder la vidéo' => 'Watch the video', 'Programme :' => 'Program:',
				'Dans le même programme' => 'In the same program', 'Autres éditions' => 'Other editions',
				'À voir aussi' => 'See also', "À l'antenne" => 'On air', 'Écouter en direct' => 'Listen live',
				'Fermer le direct' => 'Close live stream', 'REPLAYS & PROGRAMMES' => 'REPLAYS & PROGRAMS',
				'AUTRES ANTENNES' => 'OTHER CHANNELS', 'RÉGION' => 'REGION', 'Chef-lieu :' => 'Regional capital:',
				'Suivre' => 'Follow', 'Actualité régionale' => 'Regional news', 'La RTB dans la région' => 'RTB in the region',
				"La rédaction de la RTB assure une couverture de proximité de l'actualité régionale : vie locale, développement, culture et événements, en français et dans les langues nationales." => 'The RTB newsroom provides local coverage of regional news: community life, development, culture and events, in French and in the national languages.',
				'Chef-lieu' => 'Regional capital', 'Couverture TV & radio' => 'TV & radio coverage',
				'AUTRES RÉGIONS' => 'OTHER REGIONS', 'ÉMISSIONS' => 'SHOWS', 'GRAND RENDEZ-VOUS' => 'FEATURED PROGRAM',
				'Programme' => 'Program', 'PLAN DU SITE' => 'SITE MAP',
				"Toutes les sections du site de la RTB en un coup d'œil." => 'All sections of the RTB website at a glance.',
				'Le site' => 'The site', 'Nos antennes' => 'Our channels', 'Rubriques' => 'Categories',
				'Journaux & émissions' => 'News & shows', 'Informations légales' => 'Legal information',
				'Politique de confidentialité' => 'Privacy policy', "Conditions d'utilisation" => 'Terms of use',
			],
			'mos' => [
				'Accueil' => 'Yiri', 'Le Direct' => 'Sasa', 'Actualités' => 'Kibaya', 'Le Journal' => 'Kibar-kãsenga',
				'Émissions' => 'Yɛlsgo', 'Sport' => 'Sport', 'Régions' => 'Tẽnsã', 'Contact' => 'Kɛɛnse',
				'EN DIRECT' => 'SASA', 'Rechercher' => 'Bao', 'Tout' => 'Fãa', 'Toutes les chaînes' => 'Sɛkã fãa',
				'NOS ANTENNES' => 'TÕND ANTENNÃ', 'À LA UNE' => 'PĨNDÃ', 'RADIO EN DIRECT' => 'RADIO SASA',
				'À propos' => 'Tõnd yelle', 'Radio' => 'Radio',
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
