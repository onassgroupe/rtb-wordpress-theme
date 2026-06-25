<?php
/**
 * Plugin Name:       RTB i18n
 * Description:       Multilingue léger par préfixe d'URL (/{locale}/path). L'interface est traduite via un dictionnaire et la locale est conservée sur TOUS les liens (navigation, articles, catégories, pagination). Indépendant de Polylang.
 * Version:           1.0.0
 * Author:            ONASS GROUPE
 * Text Domain:       rtb-i18n
 *
 * @package RTB\I18n
 */

defined( 'ABSPATH' ) || exit;

// Autoloader PSR-4 (RTB\I18n\ → src/).
spl_autoload_register( static function ( $class ) {
	$prefix = 'RTB\\I18n\\';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}
	$rel  = substr( $class, strlen( $prefix ) );
	$file = __DIR__ . '/src/' . str_replace( '\\', '/', $rel ) . '.php';
	if ( is_file( $file ) ) {
		require $file;
	}
} );

require_once __DIR__ . '/inc/api.php';

// La détection de la locale doit avoir lieu AVANT que WordPress ne parse la
// requête : on l'exécute dès l'inclusion du plugin (les plugins sont chargés
// avant le thème et avant wp()).
\RTB\I18n\Plugin::instance()->bootLocale();
\RTB\I18n\Plugin::instance()->registerHooks();
