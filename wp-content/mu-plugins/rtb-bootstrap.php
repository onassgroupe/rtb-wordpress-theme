<?php
/**
 * RTB Bootstrap (mu-plugin) — automatise la mise en route après un (re)déploiement.
 *
 * - Active automatiquement le thème RTB + les extensions du projet si elles sont présentes
 *   mais inactives (idempotent : ne fait rien si déjà actif).
 * - Planifie une première synchronisation du contenu + l'apprentissage du lexique de
 *   l'assistant, UNE SEULE FOIS (via un événement cron non bloquant).
 *
 * Auto-chargé (mu-plugin) → aucune action manuelle nécessaire après redeploy.
 * N'agit qu'en contexte admin ou cron, pour ne pas peser sur les pages publiques.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', static function (): void {
	if ( ! is_admin() && ! wp_doing_cron() ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	// 1) Active les extensions présentes mais inactives (idempotent).
	$plugins = [
		'rtb-search/rtb-search.php',
		'rtb-chat/rtb-chat.php',
		'rtb-seo/rtb-seo.php',
		'onass-live-edit/onass-live-edit.php',
		'polylang/polylang.php',
	];
	foreach ( $plugins as $plugin ) {
		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin ) && ! is_plugin_active( $plugin ) ) {
			activate_plugin( $plugin ); // déclenche les hooks d'activation (tables, etc.)
		}
	}

	// 2) Active le thème RTB s'il n'est pas le thème courant.
	if ( wp_get_theme()->get_stylesheet() !== 'rtb' && wp_get_theme( 'rtb' )->exists() ) {
		switch_theme( 'rtb' );
	}

	// 3) Première synchro + apprentissage, une seule fois (différé, non bloquant).
	if ( ! get_option( 'rtb_bootstrapped' ) ) {
		update_option( 'rtb_bootstrapped', time(), false );
		if ( ! wp_next_scheduled( 'rtb_bootstrap_seed' ) ) {
			wp_schedule_single_event( time() + 60, 'rtb_bootstrap_seed' );
		}
	}
} );

/* Tâche différée : importe le contenu rtb.bf puis apprend le lexique de l'assistant. */
add_action( 'rtb_bootstrap_seed', static function (): void {
	if ( function_exists( 'rtb_import_from_rtbbf' ) ) {
		rtb_import_from_rtbbf( 40 );
	}
	if ( class_exists( \RTB\Chat\Learning\Learner::class ) ) {
		( new \RTB\Chat\Learning\Learner() )->run();
	}
} );
