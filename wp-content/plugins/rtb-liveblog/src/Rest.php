<?php

namespace RTB\LiveBlog;

defined( 'ABSPATH' ) || exit;

/**
 * Endpoint du fil de direct, sous le namespace rtb/v1 (mêmes règles que rtb-api) :
 * authentification par clé API (ApiKey) + rate-limiter du namespace, lecture seule,
 * réponse mise en cache 3 s.
 */
final class Rest {

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	/**
	 * Autorisation, deux voies :
	 *  1. Front du site (même-origine) → nonce REST standard `X-WP-Nonce`
	 *     (valable aussi pour un visiteur anonyme). Le fil est déjà public dans
	 *     la page : pas de secret à exposer dans le JS.
	 *  2. App mobile / intégrations → clé API de rtb-api (`X-RTB-Api-Key`).
	 */
	public function authorize( \WP_REST_Request $req ) {
		$nonce = $req->get_header( 'X-WP-Nonce' );
		if ( $nonce && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return true;
		}
		if ( class_exists( \RTB\Api\ApiKey::class ) ) {
			return ( new \RTB\Api\ApiKey() )->authorize( $req );
		}
		return true; // rtb-api absent : endpoint public en lecture seule.
	}

	public function routes(): void {
		register_rest_route( 'rtb/v1', '/live/(?P<id>\d+)', [
			'methods'             => 'GET',
			'permission_callback' => [ $this, 'authorize' ],
			'args'                => [
				'id'    => [ 'sanitize_callback' => 'absint' ],
				'after' => [ 'sanitize_callback' => 'absint' ],
			],
			'callback'            => [ $this, 'feed' ],
		] );
	}

	public function feed( \WP_REST_Request $req ): \WP_REST_Response {
		$id   = (int) $req['id'];
		$post = get_post( $id );
		if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new \WP_REST_Response( [ 'error' => 'not_found' ], 404 );
		}
		$key = 'rtb_live_feed_' . $id;
		$all = get_transient( $key );
		if ( false === $all ) {
			$all = [
				'status'  => Repository::status( $id ),
				'updated' => Repository::updated( $id ),
				'entries' => Repository::entries( $id ),
			];
			set_transient( $key, $all, 3 );
		}
		$after   = (int) $req->get_param( 'after' );
		$entries = array_values( array_filter( $all['entries'], static fn( $e ) => (int) $e['id'] > $after ) );

		$resp = new \WP_REST_Response( [
			'status'  => $all['status'],
			'updated' => $all['updated'],
			'now'     => time(),
			'entries' => $entries,
		], 200 );
		$resp->header( 'Cache-Control', 'public, max-age=3' );
		return $resp;
	}
}
