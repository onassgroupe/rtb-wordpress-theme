<?php

namespace RTB\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * Câble les sorties SEO dans <head> et neutralise les doublons du thème.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	private function __construct() {}

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		// Évite les doublons : on retire les sorties SEO inline du thème + le canonical natif
		// (on les remplace par une version unifiée et cohérente sur tous les types de pages).
		add_action( 'template_redirect', static function (): void {
			remove_action( 'wp_head', 'rtb_meta_description', 2 );
			remove_action( 'wp_head', 'rtb_schema_json_ld' );
			remove_action( 'wp_head', 'rel_canonical' );
		}, 1 );

		add_action( 'wp_head', [ new HeadMeta(), 'render' ], 3 );
		add_action( 'wp_head', [ new Hreflang(), 'render' ], 4 );
		add_action( 'wp_head', [ new Schema\JsonLd(), 'render' ], 5 );

		// L'archive des émissions (replays) dans le sitemap WordPress.
		add_filter( 'wp_sitemaps_post_types', static function ( array $types ): array {
			if ( ! isset( $types['rtb_emission'] ) && post_type_exists( 'rtb_emission' ) ) {
				$types['rtb_emission'] = get_post_type_object( 'rtb_emission' );
			}
			return $types;
		} );
	}
}
