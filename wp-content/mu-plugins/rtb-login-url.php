<?php
/**
 * Plugin Name: RTB Login URL
 * Description: Fait pointer les liens de connexion générés par WordPress (wp_login_url,
 *              action du formulaire, déconnexion, mot de passe oublié) vers /login.
 *              Le SERVICE de /login se fait au niveau du serveur (sans warning de scope) :
 *              .htaccess sur Apache/Coolify, router.php en local. wp-login.php reste
 *              accessible directement (aucun risque de lock-out).
 * Version:     1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'RTB_LOGIN_SLUG' ) ) {
	define( 'RTB_LOGIN_SLUG', 'login' );
}

/** wp_login_url() et redirections « vous devez vous connecter ». */
add_filter( 'login_url', function ( $login_url ) {
	return str_replace( 'wp-login.php', RTB_LOGIN_SLUG, $login_url );
}, 10, 1 );

/** Action du formulaire (login_post), déconnexion et liens construits via site_url(). */
add_filter( 'site_url', function ( $url, $path, $scheme ) {
	if ( in_array( $scheme, array( 'login', 'login_post' ), true ) && is_string( $url ) ) {
		return str_replace( 'wp-login.php', RTB_LOGIN_SLUG, $url );
	}
	return $url;
}, 10, 3 );

/** Lien « Mot de passe oublié ». */
add_filter( 'lostpassword_url', function ( $url ) {
	return str_replace( 'wp-login.php', RTB_LOGIN_SLUG, $url );
}, 10, 1 );
