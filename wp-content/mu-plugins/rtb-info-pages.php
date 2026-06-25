<?php
/**
 * RTB Info Pages (mu-plugin) — renseigne et publie les pages d'information liées
 * au footer (Confidentialité, Mentions légales, CGU, Accessibilité) lorsqu'elles
 * sont absentes, vides, en brouillon, ou encore au texte par défaut WordPress.
 *
 * Idempotent + une seule passe (flag d'option). Ne touche PAS aux pages qui ont
 * déjà un vrai contenu (À propos, Contact, Plan du site).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', static function (): void {
	if ( ! is_admin() && ! wp_doing_cron() ) {
		return;
	}
	if ( get_option( 'rtb_info_pages_v1' ) ) {
		return;
	}
	update_option( 'rtb_info_pages_v1', time(), false );

	foreach ( rtb_info_pages_content() as $slug => $p ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		$plain    = $existing ? trim( wp_strip_all_tags( (string) $existing->post_content ) ) : '';
		$isStub   = mb_strlen( $plain ) < 250;                                   // quasi vide
		$isWpStub = $existing && false !== strpos( (string) $existing->post_content, 'Texte suggéré' ); // gabarit WP

		if ( ! $existing ) {
			wp_insert_post( [
				'post_title'   => $p['title'],
				'post_name'    => $slug,
				'post_content' => $p['content'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
			] );
			continue;
		}

		$update = [ 'ID' => $existing->ID ];
		if ( $isStub || $isWpStub ) {
			$update['post_content'] = $p['content'];
		}
		if ( 'publish' !== $existing->post_status ) {
			$update['post_status'] = 'publish';
		}
		if ( count( $update ) > 1 ) {
			wp_update_post( $update );
		}
	}
} );

/**
 * Contenu des pages d'information (HTML simple, français).
 * @return array<string,array{title:string,content:string}>
 */
function rtb_info_pages_content(): array {
	$email = 'info@rtb.bf';
	$tel   = '+226 25 31 83 53';
	$addr  = '01 BP 2530 Ouagadougou 01, Burkina Faso';

	$privacy = <<<HTML
<p><em>Dernière mise à jour : juin 2026.</em></p>
<p>La Radiodiffusion Télévision du Burkina (RTB) attache une grande importance à la protection de vos données personnelles. Cette politique explique quelles informations sont collectées sur ce site et comment elles sont utilisées.</p>
<h2>Données que nous collectons</h2>
<ul>
<li><strong>Navigation</strong> : des statistiques de fréquentation anonymes (pages vues, durée de visite) pour améliorer le site.</li>
<li><strong>Formulaire de contact</strong> : les informations que vous saisissez (nom, e-mail, message) sont utilisées uniquement pour traiter votre demande.</li>
<li><strong>Cookies</strong> : des cookies techniques assurent le bon fonctionnement du site ; des cookies de mesure d'audience, soumis à votre consentement, nous aident à comprendre son utilisation.</li>
</ul>
<h2>Utilisation des données</h2>
<p>Vos données servent exclusivement à fournir et améliorer nos services d'information. Elles ne sont ni vendues, ni louées, ni cédées à des tiers à des fins commerciales.</p>
<h2>Cookies et consentement</h2>
<p>Lors de votre première visite, un bandeau vous permet d'accepter, de refuser ou de paramétrer les cookies par catégorie. Vous pouvez modifier votre choix à tout moment.</p>
<h2>Vos droits</h2>
<p>Vous disposez d'un droit d'accès, de rectification et de suppression de vos données. Pour l'exercer, écrivez-nous à <a href="mailto:{$email}">{$email}</a>.</p>
<h2>Sécurité</h2>
<p>Les échanges avec le site sont chiffrés (HTTPS). Nous mettons en œuvre des mesures techniques pour protéger vos informations.</p>
<h2>Contact</h2>
<p>Pour toute question relative à cette politique : <a href="mailto:{$email}">{$email}</a> · {$tel} · {$addr}.</p>
HTML;

	$legal = <<<HTML
<h2>Éditeur du site</h2>
<p><strong>Radiodiffusion Télévision du Burkina (RTB)</strong><br>{$addr}<br>Téléphone : {$tel}<br>E-mail : <a href="mailto:{$email}">{$email}</a></p>
<h2>Directeur de la publication</h2>
<p>Le Directeur Général de la RTB.</p>
<h2>Hébergement</h2>
<p>Le site est hébergé sur une infrastructure sécurisée. Les coordonnées de l'hébergeur sont disponibles sur demande auprès de l'éditeur.</p>
<h2>Propriété intellectuelle</h2>
<p>L'ensemble des contenus (textes, images, vidéos, sons, logos) présents sur ce site est protégé par le droit d'auteur. Toute reproduction ou diffusion, totale ou partielle, sans autorisation préalable de la RTB est interdite.</p>
<h2>Responsabilité</h2>
<p>La RTB s'efforce d'assurer l'exactitude des informations publiées mais ne saurait être tenue responsable d'éventuelles erreurs ou indisponibilités du service.</p>
<h2>Contact</h2>
<p>Pour toute question : <a href="mailto:{$email}">{$email}</a>.</p>
HTML;

	$terms = <<<HTML
<p><em>En accédant à ce site, vous acceptez les présentes conditions d'utilisation.</em></p>
<h2>Objet du site</h2>
<p>Ce site a pour objet de diffuser l'information de la Radiodiffusion Télévision du Burkina : télévision et radio en direct, journaux télévisés, émissions, articles d'actualité et services associés.</p>
<h2>Accès au service</h2>
<p>L'accès au site est gratuit. Les coûts de connexion et d'équipement restent à la charge de l'utilisateur. La RTB s'efforce d'assurer la disponibilité du service mais ne garantit pas une accessibilité ininterrompue.</p>
<h2>Utilisation des contenus</h2>
<p>Les contenus sont mis à disposition pour un usage personnel et non commercial. Toute reproduction ou rediffusion sans autorisation est interdite.</p>
<h2>Comportement de l'utilisateur</h2>
<p>L'utilisateur s'engage à ne pas perturber le fonctionnement du site ni à porter atteinte aux droits de la RTB ou de tiers.</p>
<h2>Liens externes</h2>
<p>Le site peut renvoyer vers des services tiers (plateformes vidéo, réseaux sociaux) régis par leurs propres conditions.</p>
<h2>Modification</h2>
<p>La RTB peut modifier ces conditions à tout moment. La version en vigueur est celle publiée sur cette page.</p>
<h2>Contact</h2>
<p><a href="mailto:{$email}">{$email}</a>.</p>
HTML;

	$a11y = <<<HTML
<p>La Radiodiffusion Télévision du Burkina s'engage à rendre son site accessible au plus grand nombre, y compris aux personnes en situation de handicap.</p>
<h2>Notre engagement</h2>
<p>Le site est conçu selon les bonnes pratiques d'accessibilité : structure de page claire, navigation au clavier, textes alternatifs sur les images, contrastes suffisants et compatibilité avec les lecteurs d'écran.</p>
<h2>Niveau de conformité</h2>
<p>Le site vise les critères des standards internationaux d'accessibilité (WCAG). Les audits techniques automatisés obtiennent une note d'accessibilité de 100/100, et nous poursuivons nos efforts d'amélioration continue.</p>
<h2>Multilinguisme</h2>
<p>Pour une meilleure inclusion, le site est disponible en six langues : français, anglais, mooré, dioula, fulfuldé et gulmancéma.</p>
<h2>Signaler un problème</h2>
<p>Si vous rencontrez une difficulté d'accès à un contenu, écrivez-nous à <a href="mailto:{$email}">{$email}</a> : nous nous efforcerons d'y remédier dans les meilleurs délais.</p>
HTML;

	return [
		'politique-de-confidentialite' => [ 'title' => 'Politique de confidentialité', 'content' => $privacy ],
		'mentions-legales'             => [ 'title' => 'Mentions légales',             'content' => $legal ],
		'conditions-utilisation'       => [ 'title' => "Conditions d'utilisation",      'content' => $terms ],
		'accessibilite'                => [ 'title' => 'Accessibilité',                 'content' => $a11y ],
	];
}
