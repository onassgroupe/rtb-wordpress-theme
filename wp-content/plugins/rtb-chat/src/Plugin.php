<?php

namespace RTB\Chat;

use RTB\Chat\Admin\Page;
use RTB\Chat\Ajax\ChatController;
use RTB\Chat\Console\LearnCommand;
use RTB\Chat\Frontend\Assets;
use RTB\Chat\Frontend\AssistantPage;

defined( 'ABSPATH' ) || exit;

/**
 * Câble l'assistant : endpoint AJAX, widget front, commande WP-CLI d'apprentissage.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	private function __construct() {}

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		( new ChatController() )->register();
		( new Assets() )->register();
		( new AssistantPage() )->register();

		if ( is_admin() ) {
			( new Page() )->register();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'rtb-chat learn', LearnCommand::class );
		}
	}
}
