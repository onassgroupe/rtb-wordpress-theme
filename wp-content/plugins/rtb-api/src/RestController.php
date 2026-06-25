<?php

namespace RTB\Api;

use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Routes REST du namespace mobile rtb/v1 (lecture seule, public).
 */
final class RestController {

	public function registerRoutes(): void {
		$auth = [ new ApiKey(), 'authorize' ]; // clé API requise sur toutes les routes
		$ns   = RTB_API_NS;
		$list = [
			'page'     => [ 'sanitize_callback' => 'absint' ],
			'per_page' => [ 'sanitize_callback' => 'absint' ],
			'category' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			'search'   => [ 'sanitize_callback' => 'sanitize_text_field' ],
		];

		register_rest_route( $ns, '/config',     [ 'methods' => 'GET', 'callback' => [ $this, 'config' ],     'permission_callback' => $auth ] );
		register_rest_route( $ns, '/home',       [ 'methods' => 'GET', 'callback' => [ $this, 'home' ],       'permission_callback' => $auth ] );
		register_rest_route( $ns, '/channels',   [ 'methods' => 'GET', 'callback' => [ $this, 'channels' ],   'permission_callback' => $auth ] );
		register_rest_route( $ns, '/radio',      [ 'methods' => 'GET', 'callback' => [ $this, 'radio' ],      'permission_callback' => $auth ] );
		register_rest_route( $ns, '/categories', [ 'methods' => 'GET', 'callback' => [ $this, 'categories' ], 'permission_callback' => $auth ] );
		register_rest_route( $ns, '/articles',   [ 'methods' => 'GET', 'callback' => [ $this, 'articles' ],   'permission_callback' => $auth, 'args' => $list ] );
		register_rest_route( $ns, '/articles/(?P<id>\d+)', [ 'methods' => 'GET', 'callback' => [ $this, 'article' ], 'permission_callback' => $auth, 'args' => [ 'id' => [ 'sanitize_callback' => 'absint' ] ] ] );
		register_rest_route( $ns, '/emissions',  [ 'methods' => 'GET', 'callback' => [ $this, 'emissions' ],  'permission_callback' => $auth, 'args' => $list ] );
		register_rest_route( $ns, '/emissions/(?P<id>\d+)', [ 'methods' => 'GET', 'callback' => [ $this, 'emission' ], 'permission_callback' => $auth, 'args' => [ 'id' => [ 'sanitize_callback' => 'absint' ] ] ] );
		register_rest_route( $ns, '/search',     [ 'methods' => 'GET', 'callback' => [ $this, 'search' ],     'permission_callback' => $auth, 'args' => [
			'q'     => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
			'type'  => [ 'sanitize_callback' => 'sanitize_key' ],
			'limit' => [ 'sanitize_callback' => 'absint' ],
		] ] );
	}

	public function config(): WP_REST_Response {
		return $this->ok( [
			'name'        => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'logo'        => function_exists( 'rtb_logo_url' ) ? rtb_logo_url() : '',
			'languages'   => function_exists( 'rtb_languages' ) ? rtb_languages() : [],
			'contact'     => [
				'phone'   => $this->mod( 'rtb_phone' ),
				'email'   => $this->mod( 'rtb_email' ),
				'address' => $this->mod( 'rtb_address' ),
			],
			'socials'     => array_filter( [
				'facebook'  => $this->mod( 'rtb_facebook' ),
				'x'         => $this->mod( 'rtb_x' ),
				'instagram' => $this->mod( 'rtb_instagram' ),
				'linkedin'  => $this->mod( 'rtb_linkedin' ),
				'youtube'   => $this->mod( 'rtb_youtube' ),
			] ),
		] );
	}

	public function home(): WP_REST_Response {
		$latestEm = $this->recentIds( 'rtb_emission', 8 );
		return $this->ok( [
			'hero'             => $latestEm ? Transformer::emissionCard( $latestEm[0] ) : null,
			'channels'         => $this->channelsData(),
			'radio'            => $this->radioData(),
			'latest_articles'  => array_map( [ Transformer::class, 'articleCard' ], $this->recentIds( 'post', 6 ) ),
			'latest_emissions' => array_map( [ Transformer::class, 'emissionCard' ], array_slice( $latestEm, 0, 6 ) ),
		] );
	}

	public function channels(): WP_REST_Response {
		return $this->ok( [ 'items' => $this->channelsData() ] );
	}

	public function radio(): WP_REST_Response {
		return $this->ok( $this->radioData() );
	}

	public function categories(): WP_REST_Response {
		$terms = get_categories( [ 'hide_empty' => true ] );
		return $this->ok( [ 'items' => array_values( array_map( [ Transformer::class, 'category' ], $terms ) ) ] );
	}

	public function articles( WP_REST_Request $req ): WP_REST_Response {
		[ $page, $per ] = $this->paging( $req );
		$args = [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'paged'          => $page,
			'posts_per_page' => $per,
			'ignore_sticky_posts' => true,
		];
		$cat = sanitize_title( (string) $req->get_param( 'category' ) );
		if ( $cat ) {
			$args['category_name'] = $cat;
		}
		$s = sanitize_text_field( (string) $req->get_param( 'search' ) );
		if ( $s ) {
			$args['s'] = $s;
			if ( class_exists( \RTB\Search\Search\Engine::class ) ) {
				$args['rtb_ranked'] = true;
				$args['rtb_sort']   = 'relevant';
			}
		}
		$q = new WP_Query( $args );
		return $this->paginated( $q, [ Transformer::class, 'articleCard' ], $page, $per );
	}

	public function article( WP_REST_Request $req ) {
		$id = absint( $req->get_param( 'id' ) );
		$p  = get_post( $id );
		if ( ! $p || 'publish' !== $p->post_status || 'post' !== $p->post_type ) {
			return new WP_Error( 'rtb_not_found', 'Article introuvable.', [ 'status' => 404 ] );
		}
		return $this->ok( Transformer::articleFull( $id ) );
	}

	public function emissions( WP_REST_Request $req ): WP_REST_Response {
		[ $page, $per ] = $this->paging( $req );
		$args = [
			'post_type'      => 'rtb_emission',
			'post_status'    => 'publish',
			'paged'          => $page,
			'posts_per_page' => $per,
			'ignore_sticky_posts' => true,
		];
		$cat = sanitize_text_field( (string) $req->get_param( 'category' ) );
		if ( $cat ) {
			$args['tax_query'] = [ [ 'taxonomy' => 'rtb_emission_cat', 'field' => 'slug', 'terms' => sanitize_title( $cat ) ] ];
		}
		$q = new WP_Query( $args );
		return $this->paginated( $q, [ Transformer::class, 'emissionCard' ], $page, $per );
	}

	public function emission( WP_REST_Request $req ) {
		$id = absint( $req->get_param( 'id' ) );
		$p  = get_post( $id );
		if ( ! $p || 'publish' !== $p->post_status || 'rtb_emission' !== $p->post_type ) {
			return new WP_Error( 'rtb_not_found', 'Émission introuvable.', [ 'status' => 404 ] );
		}
		return $this->ok( Transformer::emissionCard( $id ) );
	}

	public function search( WP_REST_Request $req ): WP_REST_Response {
		$q = trim( sanitize_text_field( (string) $req->get_param( 'q' ) ) );
		if ( mb_strlen( $q ) < 2 ) {
			return $this->ok( [ 'items' => [], 'total' => 0, 'query' => $q ] );
		}
		$type  = sanitize_key( (string) $req->get_param( 'type' ) );
		$type  = in_array( $type, [ 'post', 'rtb_emission' ], true ) ? $type : 'all';
		$limit = min( 50, max( 1, absint( $req->get_param( 'limit' ) ?: 20 ) ) );

		$ids = [];
		if ( class_exists( \RTB\Search\Search\Engine::class ) ) {
			$ids = ( new \RTB\Search\Search\Engine() )->search( $q, $limit, $type );
		} else {
			$wpq = new WP_Query( [
				's'              => $q,
				'post_type'      => 'all' === $type ? [ 'post', 'rtb_emission' ] : [ $type ],
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'no_found_rows'  => true,
			] );
			$ids = wp_list_pluck( $wpq->posts, 'ID' );
		}
		$items = array_map( [ Transformer::class, 'card' ], array_map( 'intval', $ids ) );
		return $this->ok( [ 'items' => $items, 'total' => count( $items ), 'query' => $q ] );
	}

	/* ---------------- helpers ---------------- */

	private function channelsData(): array {
		return function_exists( 'rtb_get_antennes' ) ? array_values( rtb_get_antennes() ) : [];
	}

	private function radioData(): array {
		return [
			'stream'   => function_exists( 'rtb_radio_stream' ) ? rtb_radio_stream() : '',
			'stations' => function_exists( 'rtb_get_stations' ) ? array_values( rtb_get_stations() ) : [],
		];
	}

	/** @return int[] */
	private function recentIds( string $type, int $limit ): array {
		$q = new WP_Query( [
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );
		return array_map( 'intval', $q->posts );
	}

	/** @return array{0:int,1:int} */
	private function paging( WP_REST_Request $req ): array {
		$page = max( 1, absint( $req->get_param( 'page' ) ?: 1 ) );
		$per  = min( 50, max( 1, absint( $req->get_param( 'per_page' ) ?: 12 ) ) );
		return [ $page, $per ];
	}

	private function paginated( WP_Query $q, callable $fmt, int $page, int $per ): WP_REST_Response {
		return $this->ok( [
			'items'    => array_map( $fmt, array_map( 'intval', wp_list_pluck( $q->posts, 'ID' ) ) ),
			'page'     => $page,
			'per_page' => $per,
			'total'    => (int) $q->found_posts,
			'pages'    => (int) $q->max_num_pages,
		] );
	}

	private function ok( array $data ): WP_REST_Response {
		return new WP_REST_Response( $data, 200 );
	}

	private function mod( string $key ): string {
		return function_exists( 'onass_mod' ) ? (string) onass_mod( $key, '' ) : '';
	}
}
