<?php
/**
 * RTB — réglages wp-config supplémentaires injectés au build Docker.
 * Requis depuis wp-config.php (cf. sed du Dockerfile sur wp-config-docker.php).
 */

defined( 'ABSPATH' ) || exit;

define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
define( 'FORCE_SSL_ADMIN', true );

// Cache de page disque (wp-content/advanced-cache.php + mu-plugins/rtb-page-cache.php)
if ( ! defined( 'WP_CACHE' ) ) {
	define( 'WP_CACHE', true );
}

// WP-Cron ne se déclenche plus sur chaque visite (évite toute latence/boucle réseau).
// → prévoir une tâche planifiée Coolify : curl https://<domaine>/wp-cron.php?doing_wp_cron
if ( ! defined( 'DISABLE_WP_CRON' ) ) {
	define( 'DISABLE_WP_CRON', true );
}

// HTTPS derrière un reverse-proxy / load-balancer (Traefik, Nginx, Cloudflare…)
if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
	$_SERVER['HTTPS'] = 'on';
}
