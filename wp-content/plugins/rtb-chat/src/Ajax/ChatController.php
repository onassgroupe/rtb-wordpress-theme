<?php

namespace RTB\Chat\Ajax;

use RTB\Chat\Assistant;
use RTB\Chat\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Endpoint AJAX de l'assistant (public). Tout est calculé en local, aucune sortie réseau.
 */
final class ChatController {

	public function register(): void {
		add_action( 'wp_ajax_rtb_chat', [ $this, 'handle' ] );
		add_action( 'wp_ajax_nopriv_rtb_chat', [ $this, 'handle' ] );
	}

	public function handle(): void {
		check_ajax_referer( 'rtb_chat', 'nonce' );

		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$message = trim( mb_substr( $message, 0, 500 ) );

		if ( '' === $message ) {
			wp_send_json_error( [ 'message' => 'Message vide.' ] );
		}

		$reply = ( new Assistant() )->answer( $message );
		$html  = ( new Renderer() )->render( $reply );

		wp_send_json_success( [
			'html'   => $html,
			'intent' => $reply->intent,
		] );
	}
}
