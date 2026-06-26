<?php
/**
 * Plugin Name:       RTB Live Blog
 * Description:        Direct écrit : un article qui se met à jour en temps quasi réel (polling micro-caché). Mises à jour horodatées, badge EN DIRECT, schema LiveBlogPosting. POO.
 * Version:           1.0.0
 * Author:            Onass Groupe
 * Requires PHP:      8.1
 */

defined( 'ABSPATH' ) || exit;

define( 'RTB_LIVEBLOG_FILE', __FILE__ );
define( 'RTB_LIVEBLOG_DIR', plugin_dir_path( __FILE__ ) );
define( 'RTB_LIVEBLOG_URL', plugin_dir_url( __FILE__ ) );
define( 'RTB_LIVEBLOG_VER', '1.0.3' );

spl_autoload_register( static function ( string $class ): void {
	$prefix = 'RTB\\LiveBlog\\';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}
	$file = RTB_LIVEBLOG_DIR . 'src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
	if ( is_file( $file ) ) {
		require $file;
	}
} );

add_action( 'plugins_loaded', static function (): void {
	\RTB\LiveBlog\Plugin::instance()->boot();
} );
