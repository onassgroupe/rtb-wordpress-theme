<?php

namespace RTB\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Enregistre les routes REST et les en-têtes CORS du namespace mobile.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	private function __construct() {}

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		KeyStore::maybeMigrate(); // convertit l'ancienne clé unique, le cas échéant
		add_action( 'rest_api_init', [ new RestController(), 'registerRoutes' ] );
		add_filter( 'rest_pre_serve_request', [ $this, 'cors' ], 10, 4 );
		add_filter( 'rest_pre_dispatch', [ new RateLimiter(), 'maybeLimit' ], 10, 3 );

		// Notifications push : envoi auto à la publication d'un nouvel article.
		( new Push\Sender() )->register();

		if ( is_admin() ) {
			( new Admin() )->register();
		}
	}

	/** CORS permissif (lecture seule) — utile pour Expo web / debug ; sans effet sur le natif. */
	public function cors( $served, $result, $request, $server ) {
		if ( 0 === strpos( ltrim( (string) $request->get_route(), '/' ), RTB_API_NS ) ) {
			header( 'Access-Control-Allow-Origin: *' );
			header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
			header( 'Access-Control-Allow-Headers: Content-Type, X-RTB-Api-Key' );
		}
		return $served;
	}
}
