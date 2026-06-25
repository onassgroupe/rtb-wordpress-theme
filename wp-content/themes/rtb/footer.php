<?php
/**
 * Footer.
 */
defined( 'ABSPATH' ) || exit;

$rtb_phone   = onass_mod( 'rtb_phone', '(+226) 25 31 83 53 / 63' );
$rtb_email   = onass_mod( 'rtb_email', 'info@rtb.bf' );
$rtb_address = onass_mod( 'rtb_address', '01 BP 2530 Ouagadougou 01, Burkina Faso' );

$rtb_footer_socials = [
	'facebook'  => onass_mod( 'rtb_facebook', '#' ),
	'instagram' => onass_mod( 'rtb_instagram', '#' ),
	'linkedin'  => onass_mod( 'rtb_linkedin', '#' ),
	'x'         => onass_mod( 'rtb_x', '#' ),
	'youtube'   => onass_mod( 'rtb_youtube', '#' ),
];

$rtb_footer_videos = rtb_get_emissions( 3 );

/* Catégories populaires — vraies stats de la BD (articles + émissions), triées par volume. */
$rtb_footer_cats = [];
foreach ( get_categories( [ 'hide_empty' => true ] ) as $c ) {
	$rtb_footer_cats[] = [ 'name' => $c->name, 'count' => (int) $c->count, 'link' => get_category_link( $c->term_id ) ];
}
$rtb_emi_terms = get_terms( [ 'taxonomy' => 'rtb_emission_cat', 'hide_empty' => true ] );
if ( $rtb_emi_terms && ! is_wp_error( $rtb_emi_terms ) ) {
	foreach ( $rtb_emi_terms as $t ) {
		$rtb_footer_cats[] = [ 'name' => $t->name, 'count' => (int) $t->count, 'link' => get_term_link( $t ) ];
	}
}
usort( $rtb_footer_cats, fn( $a, $b ) => $b['count'] <=> $a['count'] );
$rtb_footer_cats = array_slice( $rtb_footer_cats, 0, 8 );
?>
</main>

<footer class="rtb-footer">
	<div class="rtb-footer-tricolor"><span></span><span></span><span></span></div>

	<div class="rtb-footer-grid">

		<!-- À propos -->
		<div data-cs="rtb_contact">
			<div class="rtb-footer-logo">
				<img src="<?php echo esc_url( rtb_logo_url() ); ?>" alt="<?php bloginfo( 'name' ); ?>">
			</div>
			<p>La Radiodiffusion Télévision du Burkina (RTB) est la société publique de radiotélévision du Burkina Faso, au service de l'information et de la proximité.</p>
			<div class="rtb-footer-contact">
				<div data-live="rtb_address"><?php echo esc_html( $rtb_address ); ?></div>
				<div>Tél. <span data-live="rtb_phone"><?php echo esc_html( $rtb_phone ); ?></span></div>
				<div style="margin-top:4px">Contact : <a href="mailto:<?php echo esc_attr( $rtb_email ); ?>" data-live-mail="rtb_email"><span data-live="rtb_email"><?php echo esc_html( $rtb_email ); ?></span></a></div>
			</div>
			<div class="rtb-footer-socials">
				<?php foreach ( $rtb_footer_socials as $key => $url ) : ?>
					<a class="rtb-soc--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $key ) ); ?>"><?php echo rtb_social_svg( $key ); // phpcs:ignore ?></a>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Plus de vidéos -->
		<div>
			<span class="rtb-footer-col-title"><?php pll_e( 'PLUS DE VIDÉOS' ); ?></span>
			<div>
				<?php foreach ( $rtb_footer_videos as $v ) : ?>
					<a class="rtb-footer-video" href="<?php echo esc_url( $v['permalink'] ); ?>">
						<span class="rtb-footer-video-thumb" style="background-image:url('<?php echo esc_url( $v['cover'] ); ?>')">
							<span class="rtb-play"><i></i></span>
						</span>
						<span>
							<b><?php echo esc_html( $v['title'] ); ?></b>
							<small><?php echo esc_html( $v['date'] ); ?></small>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Catégories populaires -->
		<div>
			<span class="rtb-footer-col-title"><?php pll_e( 'CATÉGORIES POPULAIRES' ); ?></span>
			<div>
				<?php foreach ( $rtb_footer_cats as $c ) : ?>
					<a class="rtb-footer-cat" href="<?php echo esc_url( $c['link'] ); ?>">
						<span><?php echo esc_html( $c['name'] ); ?></span>
						<span><?php echo esc_html( number_format_i18n( $c['count'] ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<div class="rtb-footer-bottom">
		<div class="rtb-container">
			<span>&copy; <?php echo esc_html( date( 'Y' ) ); ?> — Radiodiffusion Télévision du Burkina</span>
			<nav>
				<a href="<?php echo esc_url( rtb_lurl( '/a-propos' ) ); ?>">À propos</a>
				<a href="<?php echo esc_url( rtb_lurl( '/direct' ) ); ?>">Le Direct</a>
				<a href="<?php echo esc_url( rtb_lurl( '/radio' ) ); ?>">Radio</a>
				<a href="<?php echo esc_url( rtb_lurl( '/grille' ) ); ?>">Grille</a>
				<a href="<?php echo esc_url( rtb_lurl( '/contact' ) ); ?>">Contact</a>
				<a href="<?php echo esc_url( rtb_lurl( '/mentions-legales' ) ); ?>">Mentions légales</a>
				<a href="<?php echo esc_url( rtb_lurl( '/politique-de-confidentialite' ) ); ?>">Confidentialité</a>
				<a href="<?php echo esc_url( rtb_lurl( '/conditions-utilisation' ) ); ?>">CGU</a>
				<a href="<?php echo esc_url( rtb_lurl( '/accessibilite' ) ); ?>">Accessibilité</a>
				<a href="<?php echo esc_url( rtb_lurl( '/plan-du-site' ) ); ?>">Plan du site</a>
			</nav>
		</div>
	</div>
</footer>

</div><!-- /x-data wrapper (ouvert dans header.php) -->

<!-- CONSENTEMENT COOKIES — bandeau non bloquant + personnalisation -->
<div class="rtb-cookie" :class="{ 'is-custom': custom }" x-data="rtbCookie()" x-show="open" x-transition @rtb-open-cookies.window="open = true; custom = true" x-cloak role="region" aria-label="Consentement aux cookies">
	<div class="rtb-cookie-flag"><span></span><span></span><span></span></div>
	<div class="rtb-cookie-inner">
		<div class="rtb-cookie-head">
			<span class="rtb-cookie-ico"><i class="fa-solid fa-cookie-bite"></i></span>
			<h2 id="rtb-cookie-title">Cookies &amp; confidentialité</h2>
		</div>
		<p class="rtb-cookie-intro">La RTB utilise des cookies pour assurer le bon fonctionnement du site, mesurer son audience et améliorer votre expérience. Vous pouvez tout accepter, tout refuser ou choisir par catégorie. <a href="<?php echo esc_url( rtb_lurl( '/politique-de-confidentialite' ) ); ?>">En savoir plus</a>.</p>

		<!-- Panneau de personnalisation (dépliable) -->
		<div class="rtb-cookie-opts" x-show="custom" x-transition.opacity>
			<?php
			$rtb_cookie_cats = [
				[ 'key' => 'necessary', 'lock' => true,  'icon' => 'fa-shield-halved', 'name' => 'Nécessaires',            'desc' => 'Indispensables au fonctionnement du site (session, sécurité, consentement). Toujours actifs.' ],
				[ 'key' => 'prefs',     'lock' => false, 'icon' => 'fa-sliders',       'name' => 'Préférences',            'desc' => 'Mémorisent vos choix : langue, mode clair/sombre, réglages d’affichage.' ],
				[ 'key' => 'stats',     'lock' => false, 'icon' => 'fa-chart-line',     'name' => 'Mesure d’audience',      'desc' => 'Statistiques de fréquentation anonymes (pages vues, durée) pour améliorer le site.' ],
				[ 'key' => 'ads',       'lock' => false, 'icon' => 'fa-bullhorn',       'name' => 'Publicité & marketing',  'desc' => 'Personnalisation des annonces et mesure des campagnes.' ],
				[ 'key' => 'social',    'lock' => false, 'icon' => 'fa-share-nodes',    'name' => 'Réseaux sociaux & vidéos','desc' => 'Lecteurs et boutons de partage tiers (YouTube, Facebook, X…).' ],
				[ 'key' => 'geo',       'lock' => false, 'icon' => 'fa-location-dot',   'name' => 'Géolocalisation',        'desc' => 'Contenus et actualités adaptés à votre région.' ],
			];
			foreach ( $rtb_cookie_cats as $cat ) :
				?>
				<div class="rtb-cookie-opt<?php echo $cat['lock'] ? ' is-locked' : ''; ?>">
					<span class="rtb-cookie-opt-ico"><i class="fa-solid <?php echo esc_attr( $cat['icon'] ); ?>" aria-hidden="true"></i></span>
					<span class="rtb-cookie-opt-text"><strong><?php echo esc_html( $cat['name'] ); ?></strong><small><?php echo esc_html( $cat['desc'] ); ?></small></span>
					<?php if ( $cat['lock'] ) : ?>
						<span class="rtb-switch is-on is-disabled" aria-hidden="true"></span>
					<?php else : ?>
						<button type="button" class="rtb-switch" :class="{ 'is-on': <?php echo esc_attr( $cat['key'] ); ?> }" @click="<?php echo esc_attr( $cat['key'] ); ?> = !<?php echo esc_attr( $cat['key'] ); ?>" role="switch" :aria-checked="<?php echo esc_attr( $cat['key'] ); ?>.toString()" aria-label="<?php echo esc_attr( $cat['name'] ); ?>"></button>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Actions -->
		<div class="rtb-cookie-actions">
			<template x-if="!custom">
				<button type="button" class="rtb-cookie-btn rtb-cookie-ghost" @click="custom = true"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Personnaliser</button>
			</template>
			<template x-if="custom">
				<button type="button" class="rtb-cookie-btn rtb-cookie-ghost" @click="custom = false">Réduire</button>
			</template>
			<button type="button" class="rtb-cookie-btn rtb-cookie-refuse" @click="refuseAll()">Tout refuser</button>
			<template x-if="custom">
				<button type="button" class="rtb-cookie-btn rtb-cookie-save" @click="saveChoices()">Enregistrer mes choix</button>
			</template>
			<button type="button" class="rtb-cookie-btn rtb-cookie-accept" @click="acceptAll()">Tout accepter</button>
		</div>
	</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
