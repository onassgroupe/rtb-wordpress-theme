<?php
/**
 * RTB — Customizer (live-edit via plugin onass-live-edit).
 * Toutes les zones texte sont en transport postMessage (édition inline).
 */

defined( 'ABSPATH' ) || exit;

function rtb_customize_register( WP_Customize_Manager $wpc ): void {

	$wpc->add_panel( 'rtb_panel', [
		'title'    => 'RTB — Contenus du site',
		'priority' => 10,
	] );

	/* ---- Section helper : image en postMessage->refresh ---- */
	$add_image = function ( string $id, string $label, string $section ) use ( $wpc ): void {
		$wpc->add_setting( $id, [
			'default'           => '',
			'transport'         => 'refresh',
			'sanitize_callback' => 'esc_url_raw',
		] );
		$wpc->add_control( new WP_Customize_Image_Control( $wpc, $id, [
			'label'   => $label,
			'section' => $section,
		] ) );
	};

	/* =====================================================
	   1. IDENTITÉ
	   ===================================================== */
	$wpc->add_section( 'rtb_identity', [ 'title' => 'Identité', 'panel' => 'rtb_panel', 'priority' => 10 ] );
	onass_cs_setting( $wpc, 'rtb_brand_l1', [ 'label' => 'Wordmark — ligne 1', 'section' => 'rtb_identity', 'default' => 'Radiodiffusion Télévision' ] );
	onass_cs_setting( $wpc, 'rtb_brand_l2', [ 'label' => 'Wordmark — ligne 2', 'section' => 'rtb_identity', 'default' => 'DU BURKINA FASO' ] );
	onass_cs_setting( $wpc, 'rtb_baseline', [ 'label' => 'Baseline', 'section' => 'rtb_identity', 'default' => "Au service de l'information et de la proximité." ] );

	/* =====================================================
	   2. CONTACT & RÉSEAUX
	   ===================================================== */
	$wpc->add_section( 'rtb_contact', [ 'title' => 'Contact & réseaux', 'panel' => 'rtb_panel', 'priority' => 20 ] );
	onass_cs_setting( $wpc, 'rtb_phone',   [ 'label' => 'Téléphone', 'section' => 'rtb_contact', 'default' => '(+226) 25 31 83 53 / 63' ] );
	onass_cs_setting( $wpc, 'rtb_email',   [ 'label' => 'E-mail', 'section' => 'rtb_contact', 'default' => 'info@rtb.bf', 'type' => 'text' ] );
	onass_cs_setting( $wpc, 'rtb_address', [ 'label' => 'Adresse', 'section' => 'rtb_contact', 'default' => '01 BP 2530 Ouagadougou 01, Burkina Faso' ] );
	onass_cs_setting( $wpc, 'rtb_facebook',  [ 'label' => 'Facebook (URL)',  'section' => 'rtb_contact', 'default' => 'https://facebook.com/rtb.bf', 'type' => 'url' ] );
	onass_cs_setting( $wpc, 'rtb_x',         [ 'label' => 'X / Twitter (URL)', 'section' => 'rtb_contact', 'default' => 'https://x.com/rtb_bf', 'type' => 'url' ] );
	onass_cs_setting( $wpc, 'rtb_instagram', [ 'label' => 'Instagram (URL)', 'section' => 'rtb_contact', 'default' => 'https://instagram.com/rtb.bf', 'type' => 'url' ] );
	onass_cs_setting( $wpc, 'rtb_linkedin',  [ 'label' => 'LinkedIn (URL)',  'section' => 'rtb_contact', 'default' => 'https://www.linkedin.com/company/rtb-bf', 'type' => 'url' ] );
	onass_cs_setting( $wpc, 'rtb_youtube',   [ 'label' => 'YouTube (URL)',   'section' => 'rtb_contact', 'default' => 'https://youtube.com/@rtbbf', 'type' => 'url' ] );

	/* =====================================================
	   3. TICKER — DERNIÈRE MINUTE
	   ===================================================== */
	$wpc->add_section( 'rtb_ticker_sec', [ 'title' => 'Ticker — Dernière minute', 'panel' => 'rtb_panel', 'priority' => 30 ] );
	onass_cs_setting( $wpc, 'rtb_ticker', [
		'label'   => 'Messages (un par ligne)',
		'section' => 'rtb_ticker_sec',
		'default' => implode( "\n", rtb_default_tickers() ),
		'type'    => 'textarea',
	] );

	/* =====================================================
	   4. HERO — VIDÉO À LA UNE
	   ===================================================== */
	$wpc->add_section( 'rtb_hero', [ 'title' => 'Hero — Vidéo à la une', 'panel' => 'rtb_panel', 'priority' => 40 ] );
	onass_cs_setting( $wpc, 'rtb_hero_kicker',   [ 'label' => 'Kicker', 'section' => 'rtb_hero', 'default' => 'EN DIRECT · JOURNAL' ] );
	onass_cs_setting( $wpc, 'rtb_hero_headline', [ 'label' => 'Titre', 'section' => 'rtb_hero', 'default' => 'JT de 20H du 21 juin 2026' ] );
	onass_cs_setting( $wpc, 'rtb_hero_meta',     [ 'label' => 'Sous-titre', 'section' => 'rtb_hero', 'default' => "Édition du soir · l'essentiel de l'actualité nationale" ] );
	onass_cs_setting( $wpc, 'rtb_hero_name',     [ 'label' => 'Nom chaîne (badge)', 'section' => 'rtb_hero', 'default' => 'RTB Télévision' ] );
	onass_cs_setting( $wpc, 'rtb_hero_dur',      [ 'label' => 'Durée / statut', 'section' => 'rtb_hero', 'default' => 'En direct · 20H00' ] );
	$add_image( 'rtb_hero_cover', 'Image de couverture', 'rtb_hero' );

	/* =====================================================
	   5. À LA UNE — ARTICLE PRINCIPAL
	   ===================================================== */
	$wpc->add_section( 'rtb_aune', [ 'title' => 'À la Une — Article lead', 'panel' => 'rtb_panel', 'priority' => 50 ] );
	onass_cs_setting( $wpc, 'rtb_aune_cat',     [ 'label' => 'Catégorie (badge)', 'section' => 'rtb_aune', 'default' => 'SOCIÉTÉ' ] );
	onass_cs_setting( $wpc, 'rtb_aune_title',   [ 'label' => 'Titre', 'section' => 'rtb_aune', 'default' => "Nuit de l'arbre 2026 : cérémonie de récompense" ] );
	onass_cs_setting( $wpc, 'rtb_aune_excerpt', [ 'label' => 'Chapô', 'section' => 'rtb_aune', 'default' => "La cérémonie a distingué les acteurs engagés pour le reboisement et la préservation de l'environnement au Burkina Faso.", 'type' => 'textarea' ] );
	onass_cs_setting( $wpc, 'rtb_aune_meta',    [ 'label' => 'Signature / date', 'section' => 'rtb_aune', 'default' => 'RTB Télévision · 20 juin 2026' ] );
	$add_image( 'rtb_aune_cover', 'Image', 'rtb_aune' );

	/* =====================================================
	   6. RADIO
	   ===================================================== */
	$wpc->add_section( 'rtb_radio', [ 'title' => 'Radio en direct', 'panel' => 'rtb_panel', 'priority' => 60 ] );
	onass_cs_setting( $wpc, 'rtb_radio_eyebrow', [ 'label' => 'Sur-titre', 'section' => 'rtb_radio', 'default' => 'RADIO EN DIRECT' ] );
	onass_cs_setting( $wpc, 'rtb_radio_title',   [ 'label' => 'Titre', 'section' => 'rtb_radio', 'default' => 'Écoutez nos stations, où que vous soyez.' ] );

	/* =====================================================
	   7. À PROPOS  (page /a-propos — tout éditable)
	   ===================================================== */
	$wpc->add_section( 'rtb_about', [ 'title' => 'À propos', 'panel' => 'rtb_panel', 'priority' => 70 ] );
	onass_cs_setting( $wpc, 'rtb_about_mission', [ 'label' => 'Mission (paragraphes, 1 par ligne)', 'section' => 'rtb_about', 'type' => 'textarea', 'default' => "La RTB est le service public audiovisuel du Burkina Faso. Sa mission : garantir un accès équitable à une information fiable, valoriser les cultures nationales et accompagner le développement du pays à travers la télévision et la radio.\nPrésente sur l'ensemble du territoire et dans les principales langues nationales — mooré, dioula, fulfuldé, gulmancéma — la RTB porte la voix de toutes les régions et de la diaspora." ] );
	onass_cs_setting( $wpc, 'rtb_about_values', [ 'label' => 'Valeurs (1 par ligne)', 'section' => 'rtb_about', 'type' => 'textarea', 'default' => "Indépendance éditoriale\nRigueur et déontologie\nProximité avec les populations\nService public pour tous" ] );
	onass_cs_setting( $wpc, 'rtb_about_history', [ 'label' => 'Histoire — 1 jalon par ligne : « Année | texte »', 'section' => 'rtb_about', 'type' => 'textarea', 'default' => "1963 | Naissance de la radiodiffusion nationale.\n1974 | Lancement de la télévision nationale.\n1995 | Modernisation et extension de la couverture sur tout le territoire.\n2010 | Passage progressif à la diffusion numérique.\n2020 | Essor des plateformes web et réseaux sociaux.\n2026 | Refonte numérique : direct, replays, multilingue, accessibilité." ] );
	onass_cs_setting( $wpc, 'rtb_about_awards', [ 'label' => 'Récompenses — 1 par ligne : « Année | Titre | Description »', 'section' => 'rtb_about', 'type' => 'textarea', 'default' => "2024 | Nuit des Galian | Distinctions de la presse burkinabè décernées à des productions de la RTB.\n2022 | Couverture nationale | Reconnaissance pour la couverture des grands rendez-vous du pays.\n2019 | Langues nationales | Saluée pour la promotion des langues nationales à l'antenne." ] );
	onass_cs_setting( $wpc, 'rtb_about_team', [ 'label' => 'Direction (organigramme) — 1ʳᵉ ligne = sommet, puis branches : « Nom | Rôle »', 'section' => 'rtb_about', 'type' => 'textarea', 'default' => "Direction Générale | Pilotage stratégique de la RTB\nDirection de la Télévision | RTB Télé · Télé Zénith · RTB3\nDirection de la Radio | Radio Burkina & stations\nDirection de l'Information | Rédactions TV & radio\nDirection Technique | Diffusion & numérique\nDirections Régionales | Présence dans les 13 régions" ] );
}
add_action( 'customize_register', 'rtb_customize_register' );
