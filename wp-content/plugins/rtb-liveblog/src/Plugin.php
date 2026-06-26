<?php

namespace RTB\LiveBlog;

defined( 'ABSPATH' ) || exit;

/** Câble les composants du live blog. */
final class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		( new Rest() )->register();
		( new Frontend() )->register();
		if ( is_admin() ) {
			( new Admin() )->register();
		}
	}
}
