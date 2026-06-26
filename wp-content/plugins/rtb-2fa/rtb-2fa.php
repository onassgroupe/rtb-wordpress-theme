<?php
/**
 * Plugin Name:       RTB 2FA
 * Description:        Double authentification (OTP par e-mail + SMS) à la connexion WordPress.
 *                     SMTP et passerelle SMS (API HTTP) configurables dans les réglages du plugin. POO.
 * Version:           1.0.0
 * Author:            Onass Groupe
 * Requires PHP:      8.1
 */

defined( 'ABSPATH' ) || exit;

define( 'RTB_2FA_FILE', __FILE__ );
define( 'RTB_2FA_DIR', plugin_dir_path( __FILE__ ) );
define( 'RTB_2FA_VER', '1.0.0' );

spl_autoload_register( static function ( string $class ): void {
	$prefix = 'RTB\\TwoFA\\';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}
	$file = RTB_2FA_DIR . 'src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
	if ( is_file( $file ) ) {
		require $file;
	}
} );

add_action( 'plugins_loaded', static function (): void {
	\RTB\TwoFA\Plugin::instance()->boot();
} );
