<?php
/**
 * Plugin Name:       RTB Assistant
 * Description:        Assistant conversationnel 100 % local (sans IA externe, sans clé API, sans CDN) — répond à partir du contenu de la RTB : recherche d'articles/JT, résumé extractif, direct, comptages. Inspiré de l'assistant local de laravel-starter-media. POO.
 * Version:           1.0.0
 * Author:            Onass Groupe
 * Requires PHP:      8.1
 * Text Domain:       rtb-chat
 */

defined( 'ABSPATH' ) || exit;

define( 'RTB_CHAT_FILE', __FILE__ );
define( 'RTB_CHAT_DIR', plugin_dir_path( __FILE__ ) );
define( 'RTB_CHAT_URL', plugin_dir_url( __FILE__ ) );
define( 'RTB_CHAT_VER', '1.0.0' );

/* Autoload PSR-4 : RTB\Chat\Foo\Bar → src/Foo/Bar.php */
spl_autoload_register( static function ( string $class ): void {
	$prefix = 'RTB\\Chat\\';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}
	$file = RTB_CHAT_DIR . 'src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
	if ( is_file( $file ) ) {
		require $file;
	}
} );

add_action( 'plugins_loaded', static function (): void {
	\RTB\Chat\Plugin::instance()->boot();
} );
