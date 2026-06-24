<?php

namespace RTB\Search;

use RTB\Search\Analytics\Store;
use RTB\Search\Frontend\Assets;
use RTB\Search\Search\Engine;
use RTB\Search\Search\InstantController;

defined( 'ABSPATH' ) || exit;

/**
 * Point d'entrée du plugin : instancie et câble les services.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	private function __construct() {}

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		$engine = new Engine();
		$store  = new Store();

		$engine->register();
		( new InstantController( $engine, $store ) )->register();
		( new Assets() )->register();

		$store->maybeInstall();

		// Journalise les recherches (page de résultats, 1re page) → alimente tendances/récents.
		add_action( 'template_redirect', static function () use ( $store ): void {
			if ( ! is_search() || ! is_main_query() ) {
				return;
			}
			$term  = (string) get_search_query();
			$paged = max( 1, (int) get_query_var( 'paged' ) );
			if ( '' !== $term && 1 === $paged ) {
				$store->record( $term );
			}
		} );
	}
}
