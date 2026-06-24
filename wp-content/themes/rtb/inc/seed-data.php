<?php
/**
 * RTB — corpus de contenu réel pour le seeder (tout finit en BD → recherche).
 * Données issues de rtb.bf (juin 2026).
 */

defined( 'ABSPATH' ) || exit;

/** Rubriques de la rédaction avec slugs alignés sur le menu. */
function rtb_seed_categories_map(): array {
	return [
		'Infos'              => 'infos',
		'Politique'          => 'politique',
		'Société'            => 'societe',
		'Économie'           => 'economie',
		'Sécurité & Défense' => 'securite',
		'International'      => 'international',
		'Culture'            => 'culture',
		'Sport'              => 'sport',
	];
}

/** Émissions / vidéos (JT + magazines) — ~26 entrées réelles. */
function rtb_full_emissions(): array {
	$u    = 'https://www.rtb.bf/wp-content/uploads/';
	$jt13 = $u . '2026/06/vlcsnap-2026-06-15-13h05m32s345.png';
	$jt19 = $u . '2026/06/vlcsnap-2026-06-15-19h26m07s146.png';
	$jt20 = $u . '2020/04/vlcsnap-2020-02-18-20h44m13s684.png';
	// Vrais ID YouTube (chaîne RTB), représentatifs par édition.
	$yt13 = 'N__9uQOxBhg';
	$yt19 = '9q_h8X82JlA';
	$yt20 = '_0WZRxGApjA';

	$out = [];

	$jt_days = [
		[ '21 juin 2026', '2026-06-21' ],
		[ '20 juin 2026', '2026-06-20' ],
		[ '19 juin 2026', '2026-06-19' ],
		[ '18 juin 2026', '2026-06-18' ],
		[ '17 juin 2026', '2026-06-17' ],
		[ '16 juin 2026', '2026-06-16' ],
		[ '15 juin 2026', '2026-06-15' ],
	];
	foreach ( $jt_days as $d ) {
		[ $label, $iso ] = $d;
		$out[] = [ 'title' => "JT de 13H du $label", 'cat' => 'JT 13H', 'dur' => '24 min', 'by' => 'RTB Télévision', 'date' => $label, 'iso' => "$iso 13:05:00", 'cover' => $jt13, 'yt' => $yt13 ];
		$out[] = [ 'title' => "JT de 19H du $label", 'cat' => 'JT 19H', 'dur' => '19 min', 'by' => 'RTB Télévision', 'date' => $label, 'iso' => "$iso 19:00:00", 'cover' => $jt19, 'yt' => $yt19 ];
		$out[] = [ 'title' => "JT de 20H du $label", 'cat' => 'JT 20H', 'dur' => '38 min', 'by' => 'RTB Télévision', 'date' => $label, 'iso' => "$iso 20:00:00", 'cover' => $jt20, 'yt' => $yt20 ];
	}

	// Magazines & grands rendez-vous (vrais ID YouTube)
	$out[] = [ 'title' => 'Success du 17 juin 2026', 'cat' => 'Magazine', 'dur' => '21:39', 'by' => 'Télé Zénith', 'date' => '17 juin 2026', 'iso' => '2026-06-17 21:39:00', 'cover' => $u . '2026/06/vlcsnap-2026-06-17-21h44m25s931.png', 'yt' => 'olaGnIXy94I' ];
	$out[] = [ 'title' => 'Débat de presse du 21 juin 2026', 'cat' => 'Magazine', 'dur' => '52 min', 'by' => 'RTB Télévision', 'date' => '21 juin 2026', 'iso' => '2026-06-21 11:05:00', 'cover' => $u . '2026/06/vlcsnap-2026-06-14-11h05m37s084.png', 'yt' => 'tGKtp_UNeak' ];
	$out[] = [ 'title' => 'Questions Majeures du 14 juin 2026', 'cat' => 'Magazine', 'dur' => '47 min', 'by' => 'RTB Télévision', 'date' => '14 juin 2026', 'iso' => '2026-06-14 21:00:00', 'cover' => $u . '2026/06/questions-majeures.png', 'yt' => 'SMF0A8N2ZAw' ];
	$out[] = [ 'title' => "Santémag du 18 juin 2026 : l'acné chez les adultes et les adolescents", 'cat' => 'Magazine', 'dur' => '26 min', 'by' => 'RTB Télévision', 'date' => '18 juin 2026', 'iso' => '2026-06-18 18:30:00', 'cover' => $u . '2026/06/santemag.png', 'yt' => 'y3p2wSeYb1o' ];
	$out[] = [ 'title' => 'Sport Box du 03 juin 2026', 'cat' => 'Magazine', 'dur' => '18 min', 'by' => 'RTB Télévision', 'date' => '3 juin 2026', 'iso' => '2026-06-03 18:00:00', 'cover' => $jt19, 'yt' => 'FAtK9SLqwbE' ];
	$out[] = [ 'title' => 'Intégral Foot du 25 mai 2026', 'cat' => 'Magazine', 'dur' => '44 min', 'by' => 'RTB Télévision', 'date' => '25 mai 2026', 'iso' => '2026-05-25 22:00:00', 'cover' => $u . '2025/03/integral-foot.png', 'yt' => 'ZoEfqmbGu4I' ];

	return $out;
}

/** Articles éditoriaux par rubrique — ~15 entrées réelles. */
function rtb_full_articles(): array {
	$u = 'https://www.rtb.bf/wp-content/uploads/';
	return [
		[ 'title' => "Nuit de l'arbre 2026 : cérémonie de récompense", 'cat' => 'Société', 'date' => '20 juin 2026', 'iso' => '2026-06-20 21:18:00', 'cover' => $u . '2026/06/vlcsnap-2026-06-20-21h18m47s439.png', 'excerpt' => "La cérémonie a distingué les acteurs engagés pour le reboisement et la préservation de l'environnement au Burkina Faso." ],
		[ 'title' => 'Nuit de récompense des prix Galian, édition 2026', 'cat' => 'Culture', 'date' => '19 juin 2026', 'iso' => '2026-06-19 22:58:00', 'cover' => $u . '2026/06/Capture-decran-2026-06-19-225812.png', 'excerpt' => 'Les meilleures productions de la presse burkinabè ont été primées lors de cette grande soirée des médias.' ],
		[ 'title' => "Santémag : l'acné chez les adultes et les adolescents", 'cat' => 'Société', 'date' => '18 juin 2026', 'iso' => '2026-06-18 19:30:00', 'cover' => $u . '2026/06/santemag.png', 'excerpt' => "Le magazine de la santé revient sur les causes, la prévention et les traitements de l'acné." ],
		[ 'title' => 'Compte rendu du Conseil des ministres du 21 mai 2026', 'cat' => 'Politique', 'date' => '21 mai 2026', 'iso' => '2026-05-21 18:00:00', 'cover' => $u . '2026/05/Capture-decran-2026-02-25-201146.png', 'excerpt' => 'Le Gouvernement a examiné plusieurs dossiers relatifs au développement économique et social de la Nation.' ],
		[ 'title' => 'Compte rendu du Conseil des ministres du 13 mai 2026', 'cat' => 'Politique', 'date' => '13 mai 2026', 'iso' => '2026-05-13 18:00:00', 'cover' => $u . '2026/05/Capture-decran-2026-02-25-201146.png', 'excerpt' => 'Plusieurs textes ont été adoptés en faveur de la souveraineté et de la résilience nationale.' ],
		[ 'title' => "Conseil des ministres : un économat pour renforcer l'élan de solidarité avec les FDS", 'cat' => 'Sécurité & Défense', 'date' => '12 mars 2026', 'iso' => '2026-03-12 18:00:00', 'cover' => 'aune-societe.png', 'excerpt' => "La mesure vise à soutenir les Forces de défense et de sécurité engagées pour la reconquête du territoire." ],
		[ 'title' => "Le Burkina, le Mali et le Niger créent l'Alliance des États du Sahel", 'cat' => 'International', 'date' => '16 sept. 2023', 'iso' => '2023-09-16 12:00:00', 'cover' => 'aune-societe.png', 'excerpt' => 'Les trois chefs d’État scellent une alliance de défense collective au sein de l’espace sahélien.' ],
		[ 'title' => 'Gabon : Raymond Ndong Sima nommé Premier ministre', 'cat' => 'International', 'date' => '7 sept. 2023', 'iso' => '2023-09-07 12:00:00', 'cover' => 'aune-culture.png', 'excerpt' => "L'opposant est désigné à la tête du gouvernement de transition." ],
		[ 'title' => 'Finale de la Coupe du Faso : Vitesse FC face à Sporting FC', 'cat' => 'Sport', 'date' => '31 mai 2026', 'iso' => '2026-05-31 17:00:00', 'cover' => 'aune-sport.png', 'excerpt' => 'La grande finale de la Coupe nationale a tenu toutes ses promesses devant un public venu nombreux.' ],
		[ 'title' => 'Intégral Foot : retour sur la journée de championnat', 'cat' => 'Sport', 'date' => '25 mai 2026', 'iso' => '2026-05-25 22:00:00', 'cover' => $u . '2025/03/integral-foot.png', 'excerpt' => "L'émission décrypte les résultats et les temps forts du football national." ],
		[ 'title' => 'Éducation : 240 nouvelles salles de classe livrées avant la rentrée', 'cat' => 'Société', 'date' => '15 juin 2026', 'iso' => '2026-06-15 10:00:00', 'cover' => 'aune-societe.png', 'excerpt' => 'Les infrastructures seront opérationnelles dès la prochaine rentrée scolaire sur tout le territoire.' ],
		[ 'title' => "Énergie : un nouveau parc solaire renforce l'accès à l'électricité", 'cat' => 'Économie', 'date' => '17 juin 2026', 'iso' => '2026-06-17 09:00:00', 'cover' => 'lead-solaire.png', 'excerpt' => "Le projet améliore l'accès à l'électricité dans les zones rurales et soutient la transition énergétique." ],
		[ 'title' => "Agriculture : adoption d'un plan national d'investissement", 'cat' => 'Économie', 'date' => '10 juin 2026', 'iso' => '2026-06-10 09:00:00', 'cover' => 'aune-culture.png', 'excerpt' => "Le plan vise à moderniser les filières et à renforcer la sécurité alimentaire." ],
		[ 'title' => 'SNC de Bobo-Dioulasso : le programme de la prochaine édition dévoilé', 'cat' => 'Culture', 'date' => '8 juin 2026', 'iso' => '2026-06-08 11:00:00', 'cover' => 'aune-culture.png', 'excerpt' => 'La Semaine nationale de la culture promet une riche programmation artistique et patrimoniale.' ],
		[ 'title' => 'Femmes de valeur : portrait d’une entrepreneure du secteur agricole', 'cat' => 'Société', 'date' => '20 févr. 2026', 'iso' => '2026-02-20 14:00:00', 'cover' => 'aune-culture.png', 'excerpt' => "Le magazine met en lumière le parcours d'une femme qui transforme l'agriculture locale." ],
	];
}
