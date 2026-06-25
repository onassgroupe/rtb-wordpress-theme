<?php

namespace RTB\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * Point d'entrée du plugin : détecte la locale tôt et enregistre les hooks.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Détection de la locale — à exécuter avant que WP ne parse la requête. */
	public function bootLocale(): void {
		Locale::detect();
	}

	public function registerHooks(): void {
		// Pas de redirection canonique sur les requêtes préfixées (REQUEST_URI réécrit).
		add_filter( 'redirect_canonical', static function ( $url ) {
			return Locale::isPrefixed() ? false : $url;
		} );

		// Préfixer tous les permaliens internes générés par WordPress.
		foreach ( [ 'post_link', 'page_link', 'post_type_link', 'term_link', 'get_pagenum_link', 'attachment_link' ] as $hook ) {
			add_filter( $hook, [ Links::class, 'prefix' ] );
		}

		// Rediriger les URLs front non préfixées vers la locale par défaut.
		add_action( 'template_redirect', [ Links::class, 'maybeRedirect' ] );

		// Attribut lang du <html>.
		add_filter( 'language_attributes', static function ( $output ) {
			return 'lang="' . esc_attr( Locale::current() ) . '"';
		} );
	}
}
