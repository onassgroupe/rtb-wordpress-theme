<?php
/**
 * Plugin Name: RTB Page Cache
 * Description: Cache de page disque pour visiteurs anonymes (écrit le HTML, purge auto).
 *              Le service est fait par wp-content/advanced-cache.php (WP_CACHE).
 * Version:     1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'RTB_CACHE_DIR' ) ) {
	define( 'RTB_CACHE_DIR', WP_CONTENT_DIR . '/cache/rtb' );
}

/** Cette requête est-elle cachable ? (mêmes garde-fous que le lecteur) */
function rtb_cache_cacheable() {
	if ( is_user_logged_in() || is_admin() ) {
		return false;
	}
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
		return false;
	}
	if ( ! empty( $_GET ) ) {
		return false;
	}
	if ( defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) || defined( 'REST_REQUEST' ) ) {
		return false;
	}
	return true;
}

/** Chemin du fichier de cache pour l'URL courante. */
function rtb_cache_file() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
	$path = strtok( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/', '?' );
	return RTB_CACHE_DIR . '/' . md5( $host . '|' . $path ) . '.html';
}

/** Démarre la capture du HTML sur le front. */
add_action( 'template_redirect', function () {
	if ( ! rtb_cache_cacheable() ) {
		return;
	}
	ob_start( 'rtb_cache_save' );
}, 0 );

/** Callback de fin de buffer : écrit le HTML si la page est « propre ». */
function rtb_cache_save( $html ) {
	if ( strlen( $html ) < 500 ) {
		return $html;
	}
	if ( is_404() || is_search() || is_feed() || is_preview() || is_trackback() || post_password_required() ) {
		return $html;
	}
	if ( function_exists( 'http_response_code' ) && 200 !== http_response_code() ) {
		return $html;
	}
	if ( ! is_dir( RTB_CACHE_DIR ) ) {
		wp_mkdir_p( RTB_CACHE_DIR );
	}
	if ( is_writable( RTB_CACHE_DIR ) ) {
		@file_put_contents( rtb_cache_file(), $html, LOCK_EX );
	}
	return $html . "\n<!-- RTB cache " . gmdate( 'c' ) . " -->";
}

/** Vide tout le cache. */
function rtb_cache_clear() {
	if ( ! is_dir( RTB_CACHE_DIR ) ) {
		return 0;
	}
	$n = 0;
	foreach ( glob( RTB_CACHE_DIR . '/*.html' ) ?: array() as $f ) {
		if ( @unlink( $f ) ) {
			$n++;
		}
	}
	return $n;
}

/* Purge automatique sur les changements de contenu + synchro rtb.bf. */
add_action( 'save_post', 'rtb_cache_clear' );
add_action( 'deleted_post', 'rtb_cache_clear' );
add_action( 'comment_post', 'rtb_cache_clear' );
add_action( 'switch_theme', 'rtb_cache_clear' );
add_action( 'customize_save_after', 'rtb_cache_clear' );
add_action( 'rtb_cache_clear', 'rtb_cache_clear' ); // déclenchable ailleurs (ex. après synchro)
