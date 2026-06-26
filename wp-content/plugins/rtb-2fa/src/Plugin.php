<?php

namespace RTB\TwoFA;

defined( 'ABSPATH' ) || exit;

/** Câble les composants du plugin 2FA. */
final class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		Settings::instance()->register();
		Profile::register();
		Login::instance()->register();
	}
}
