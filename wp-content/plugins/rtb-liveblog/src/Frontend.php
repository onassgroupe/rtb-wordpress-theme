<?php

namespace RTB\LiveBlog;

defined( 'ABSPATH' ) || exit;

/** Rendu front du direct : fil initial, assets de polling, schema SEO. */
final class Frontend {

	public function register(): void {
		add_filter( 'the_content', [ $this, 'prependFeed' ], 9 );
		add_action( 'wp_enqueue_scripts', [ $this, 'assets' ] );
		add_action( 'wp_head', [ $this, 'schema' ] );
	}

	private function t( string $s ): string {
		return function_exists( 'rtb_t' ) ? rtb_t( $s ) : $s;
	}

	public function prependFeed( string $content ): string {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$id = (int) get_the_ID();
		if ( ! $id || ! Repository::isLive( $id ) ) {
			return $content;
		}
		return $this->render( $id ) . $content;
	}

	private function render( int $post_id ): string {
		$status  = Repository::status( $post_id );
		$entries = Repository::entries( $post_id );
		$last    = $entries ? (int) $entries[0]['id'] : 0;
		$badge   = 'open' === $status
			? '<span class="rtb-live-post-badge"><span class="rtb-live-post-dot"></span>' . esc_html( $this->t( 'EN DIRECT' ) ) . '</span>'
			: '<span class="rtb-live-post-badge rtb-live-post-badge--ended">' . esc_html( $this->t( 'Direct terminé' ) ) . '</span>';

		$items = '';
		foreach ( $entries as $e ) {
			$items .= $this->entryHtml( $e );
		}

		// Rail gauche : badge + points clés épinglés + partage.
		$keys    = Repository::keyPoints( $post_id );
		$keyHtml = '';
		foreach ( $keys as $k ) {
			$keyHtml .= '<li><a href="#rtb-live-post-e' . (int) $k['id'] . '">' . esc_html( $k['text'] ) . '</a></li>';
		}
		$keyBlock = '<div class="rtb-live-post-key"' . ( $keys ? '' : ' hidden' ) . '>'
			. '<h3>' . esc_html( $this->t( 'Les points clés' ) ) . '</h3>'
			. '<ul class="rtb-live-post-keys">' . $keyHtml . '</ul></div>';

		$rail = '<aside class="rtb-live-post-rail">'
			. '<div class="rtb-live-post-railhead">' . $badge . '<span class="rtb-live-post-updated" aria-live="polite"></span></div>'
			. $keyBlock
			. '<button type="button" class="rtb-live-post-share">' . esc_html( $this->t( 'Partager le direct' ) ) . '</button>'
			. '</aside>';

		return '<section class="rtb-live-post" id="rtb-live-post" data-live-id="' . $post_id . '" data-after="' . $last . '" data-status="' . esc_attr( $status ) . '">'
			. $rail
			. '<div class="rtb-live-post-main"><div class="rtb-live-post-stream">' . $items . '</div></div>'
			. '</section>';
	}

	/** @param array<string,mixed> $e */
	private function entryHtml( array $e ): string {
		$lbl  = Repository::labelText( (string) $e['label'] );
		$time = esc_html( wp_date( 'H\hi', (int) $e['t'] ) );
		$img  = ! empty( $e['img'] )
			? '<figure class="rtb-live-post-figure"><img src="' . esc_url( (string) $e['img'] ) . '" alt="" loading="lazy"></figure>'
			: '';
		$cls  = 'rtb-live-post-entry' . ( ! empty( $e['key'] ) ? ' rtb-live-post-entry--key' : '' );
		return '<article class="' . $cls . '" id="rtb-live-post-e' . (int) $e['id'] . '" data-id="' . (int) $e['id'] . '">'
			. '<div class="rtb-live-post-meta"><time>' . $time . '</time>'
			. ( $lbl ? '<span class="rtb-live-post-tag rtb-live-post-tag--' . esc_attr( $e['label'] ) . '">' . esc_html( $lbl ) . '</span>' : '' )
			. '</div>' . $img . '<div class="rtb-live-post-body">' . wp_kses_post( $e['html'] ) . '</div></article>';
	}

	public function assets(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		$id = (int) get_queried_object_id();
		if ( ! Repository::isLive( $id ) ) {
			return;
		}
		wp_enqueue_style( 'rtb-liveblog', RTB_LIVEBLOG_URL . 'assets/live.css', [], RTB_LIVEBLOG_VER );
		wp_enqueue_script( 'rtb-liveblog', RTB_LIVEBLOG_URL . 'assets/live.js', [], RTB_LIVEBLOG_VER, true );
		wp_localize_script( 'rtb-liveblog', 'rtbLive', [
			'endpoint' => esc_url_raw( rest_url( 'rtb/v1/live/' ) ),
			// Front même-origine : nonce REST (pas de secret exposé dans le JS).
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			// Clé API optionnelle (même mécanisme que rtb-api) si définie.
			'apiKey'   => (string) apply_filters( 'rtb_api_public_key', defined( 'RTB_API_KEY' ) ? RTB_API_KEY : '' ),
			'interval' => 8000,
			'i18n'     => [
				'updated' => $this->t( 'Mis à jour à' ),
				'new'     => $this->t( 'Nouvelle(s) mise(s) à jour' ),
				'ended'   => $this->t( 'Direct terminé' ),
				'live'    => $this->t( 'EN DIRECT' ),
				'copied'  => $this->t( 'Lien copié' ),
			],
		] );
	}

	/** Schema.org LiveBlogPosting (SEO) pour les directs ouverts. */
	public function schema(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		$id = (int) get_queried_object_id();
		if ( 'open' !== Repository::status( $id ) ) {
			return;
		}
		$entries = Repository::entries( $id );
		$updates = [];
		foreach ( array_slice( $entries, 0, 25 ) as $e ) {
			$updates[] = [
				'@type'         => 'BlogPosting',
				'headline'      => wp_strip_all_tags( (string) $e['text'] ),
				'datePublished' => wp_date( 'c', (int) $e['t'] ),
			];
		}
		$data = [
			'@context'           => 'https://schema.org',
			'@type'              => 'LiveBlogPosting',
			'headline'           => wp_strip_all_tags( get_the_title( $id ) ),
			'url'                => get_permalink( $id ),
			'coverageStartTime'  => get_post_time( 'c', true, $id ),
			'liveBlogUpdate'     => $updates,
		];
		echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}
