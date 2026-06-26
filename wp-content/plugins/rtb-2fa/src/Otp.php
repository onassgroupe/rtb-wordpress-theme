<?php

namespace RTB\TwoFA;

defined( 'ABSPATH' ) || exit;

/** Génération, stockage (transient haché) et vérification des codes OTP. */
final class Otp {

	private const PREFIX = 'rtb_2fa_';

	public static function code( int $len ): string {
		$max = ( 10 ** $len ) - 1;
		return str_pad( (string) random_int( 0, $max ), $len, '0', STR_PAD_LEFT );
	}

	/** Crée un challenge, envoie le code par les canaux actifs, retourne le token. */
	public static function start( \WP_User $user, bool $remember, string $redirect ): string {
		$len   = (int) Settings::get( 'otp_length' );
		$ttl   = (int) Settings::get( 'otp_ttl' );
		$code  = self::code( $len );
		$token = bin2hex( random_bytes( 16 ) );
		set_transient( self::PREFIX . $token, [
			'user'     => $user->ID,
			'hash'     => hash( 'sha256', $code ),
			'remember' => $remember,
			'redirect' => $redirect,
			'tries'    => 0,
			'created'  => time(),
		], $ttl );
		self::dispatch( $user, $code, $ttl );
		return $token;
	}

	private static function dispatch( \WP_User $user, string $code, int $ttl ): void {
		$mins = max( 1, (int) round( $ttl / 60 ) );
		$site = get_bloginfo( 'name' );
		if ( Settings::get( 'channel_email' ) ) {
			wp_mail(
				$user->user_email,
				sprintf( '%s — code de connexion', $site ),
				sprintf(
					"Bonjour,\n\nVotre code de connexion est : %s\nIl expire dans %d minute(s).\n\nSi vous n'êtes pas à l'origine de cette connexion, ignorez ce message.\n\n%s",
					$code, $mins, $site
				)
			);
		}
		if ( Settings::get( 'channel_sms' ) ) {
			$phone = (string) get_user_meta( $user->ID, 'rtb_2fa_phone', true );
			if ( '' !== $phone ) {
				Sms::send( $phone, sprintf( '%s : votre code de connexion est %s (valide %d min).', $site, $code, $mins ) );
			}
		}
	}

	/** @return array<string,mixed>|null */
	public static function peek( string $token ): ?array {
		$d = get_transient( self::PREFIX . $token );
		return is_array( $d ) ? $d : null;
	}

	public static function consume( string $token ): void {
		delete_transient( self::PREFIX . $token );
	}

	/** @return array{0:bool,1:string} [ok, message] */
	public static function verify( string $token, string $code ): array {
		$key  = self::PREFIX . $token;
		$data = get_transient( $key );
		if ( ! is_array( $data ) ) {
			return [ false, 'Code expiré, veuillez recommencer la connexion.' ];
		}
		if ( (int) $data['tries'] >= 5 ) {
			delete_transient( $key );
			return [ false, 'Trop de tentatives. Recommencez la connexion.' ];
		}
		if ( hash_equals( (string) $data['hash'], hash( 'sha256', trim( $code ) ) ) ) {
			delete_transient( $key );
			return [ true, '' ];
		}
		$data['tries']++;
		set_transient( $key, $data, max( 60, (int) Settings::get( 'otp_ttl' ) ) );
		return [ false, 'Code incorrect.' ];
	}
}
