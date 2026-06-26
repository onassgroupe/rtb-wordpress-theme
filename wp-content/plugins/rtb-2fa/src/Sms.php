<?php

namespace RTB\TwoFA;

defined( 'ABSPATH' ) || exit;

/**
 * Passerelle SMS Aqilas (API HTTP) — POST {base}/sms, en-tête X-AUTH-TOKEN,
 * corps { from, text, to:[+226XXXXXXXX] }.
 */
final class Sms {

	public static function configured(): bool {
		return '' !== trim( (string) Settings::get( 'sms_api_key' ) ) && '' !== trim( (string) Settings::get( 'sms_base_url' ) );
	}

	/** Numéro au format international Burkina (+226…). */
	public static function formatPhone( string $phone ): string {
		$clean = preg_replace( '/[^0-9]/', '', $phone );
		if ( '' === $clean ) {
			return '';
		}
		return 0 === strpos( $clean, '226' ) ? '+' . $clean : '+226' . $clean;
	}

	/** Envoie un SMS. Retourne true si accepté par la passerelle. */
	public static function send( string $phone, string $message ): bool {
		if ( ! self::configured() ) {
			return false;
		}
		$to = self::formatPhone( $phone );
		if ( '' === $to ) {
			return false;
		}
		$base = rtrim( (string) Settings::get( 'sms_base_url' ), '/' );
		$resp = wp_remote_post( $base . '/sms', [
			'timeout' => 15,
			'headers' => [
				'X-AUTH-TOKEN' => (string) Settings::get( 'sms_api_key' ),
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			],
			'body'    => wp_json_encode( [
				'from' => (string) Settings::get( 'sms_sender' ) ?: 'RTB',
				'text' => $message,
				'to'   => [ $to ],
			] ),
		] );
		if ( is_wp_error( $resp ) ) {
			return false;
		}
		$code = (int) wp_remote_retrieve_response_code( $resp );
		$data = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		return $code >= 200 && $code < 300 && ! empty( $data['success'] );
	}
}
