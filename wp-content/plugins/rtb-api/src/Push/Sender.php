<?php

namespace RTB\Api\Push;

defined( 'ABSPATH' ) || exit;

/**
 * Envoie des notifications via l'API Expo Push, et notifie automatiquement
 * à la publication d'un nouvel article.
 */
final class Sender {

	private const ENDPOINT  = 'https://exp.host/--/api/v2/push/send';
	private const BATCH     = 100;            // limite Expo par requête
	private const FRESH_WIN = HOUR_IN_SECONDS; // évite le flood lors des imports (dates passées)

	/** Branche le hook de publication. */
	public function register(): void {
		add_action( 'transition_post_status', [ $this, 'onPublish' ], 10, 3 );
	}

	/** @param \WP_Post $post */
	public function onPublish( string $new, string $old, $post ): void {
		if ( 'publish' !== $new || 'publish' === $old ) {
			return; // seulement les passages → publié
		}
		if ( ! $post instanceof \WP_Post || 'post' !== $post->post_type ) {
			return;
		}
		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return;
		}
		// Anti-flood import : les articles importés portent une date passée.
		if ( abs( time() - (int) get_post_time( 'U', true, $post ) ) > self::FRESH_WIN ) {
			return;
		}
		// Une seule notif par article.
		if ( get_post_meta( $post->ID, '_rtb_pushed', true ) ) {
			return;
		}
		update_post_meta( $post->ID, '_rtb_pushed', 1 );

		$cat   = get_the_category( $post->ID );
		$title = $cat ? $cat[0]->name : 'RTB';
		$this->send(
			$title,
			wp_strip_all_tags( get_the_title( $post ) ),
			[ 'articleId' => $post->ID, 'url' => get_permalink( $post ) ]
		);
	}

	/**
	 * Envoie un message à tous les appareils enregistrés (par lots).
	 * @param array<string,mixed> $data
	 */
	public function send( string $title, string $body, array $data = [] ): void {
		$tokens = TokenStore::all();
		if ( ! $tokens ) {
			return;
		}
		$invalid = [];
		foreach ( array_chunk( $tokens, self::BATCH ) as $chunk ) {
			$messages = array_map(
				static fn( string $to ) => [
					'to'        => $to,
					'title'     => $title,
					'body'      => $body,
					'data'      => $data,
					'sound'     => 'default',
					'channelId' => 'default',
					'priority'  => 'high',
				],
				$chunk
			);
			$resp = wp_remote_post( self::ENDPOINT, [
				'timeout' => 15,
				'headers' => [ 'Content-Type' => 'application/json', 'Accept' => 'application/json' ],
				'body'    => wp_json_encode( $messages ),
			] );
			$invalid = array_merge( $invalid, $this->invalidFrom( $resp, $chunk ) );
		}
		// Purge les tokens morts (app désinstallée, etc.).
		TokenStore::removeMany( array_values( array_unique( $invalid ) ) );
	}

	/**
	 * Repère les tokens « DeviceNotRegistered » dans la réponse Expo.
	 * @param array|\WP_Error $resp
	 * @param string[]        $chunk
	 * @return string[]
	 */
	private function invalidFrom( $resp, array $chunk ): array {
		if ( is_wp_error( $resp ) ) {
			return [];
		}
		$json = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		$tickets = is_array( $json['data'] ?? null ) ? $json['data'] : [];
		$dead = [];
		foreach ( $tickets as $i => $ticket ) {
			$err = $ticket['details']['error'] ?? '';
			if ( 'error' === ( $ticket['status'] ?? '' ) && 'DeviceNotRegistered' === $err && isset( $chunk[ $i ] ) ) {
				$dead[] = $chunk[ $i ];
			}
		}
		return $dead;
	}
}
