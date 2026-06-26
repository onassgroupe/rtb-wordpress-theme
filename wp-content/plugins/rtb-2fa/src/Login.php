<?php

namespace RTB\TwoFA;

defined( 'ABSPATH' ) || exit;

/** Intercepte la connexion pour exiger un OTP, puis finalise après vérification. */
final class Login {

	private static ?Login $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function register(): void {
		add_filter( 'authenticate', [ $this, 'challenge' ], 100, 3 );
		add_action( 'login_form_rtb2fa', [ $this, 'handleVerify' ] );
		add_action( 'login_form_rtb2fa_resend', [ $this, 'handleResend' ] );
	}

	private function required( \WP_User $user ): bool {
		if ( 'all' === Settings::get( 'enforce' ) ) {
			return true;
		}
		return user_can( $user, 'manage_options' );
	}

	/**
	 * Après vérification du mot de passe (priorité 100), bloque la connexion
	 * interactive et affiche l'écran OTP. N'interfère pas avec REST / mots de
	 * passe d'application (pagenow != wp-login.php) ni les connexions par cookie.
	 *
	 * @param \WP_User|\WP_Error|null $user
	 * @return \WP_User|\WP_Error|null
	 */
	public function challenge( $user, $username, $password ) {
		if ( ! Settings::enabled() || ! ( $user instanceof \WP_User ) ) {
			return $user;
		}
		if ( ( $GLOBALS['pagenow'] ?? '' ) !== 'wp-login.php' || empty( $password ) ) {
			return $user;
		}
		if ( ! Settings::get( 'channel_email' ) && ! Settings::get( 'channel_sms' ) ) {
			return $user;
		}
		if ( ! $this->required( $user ) ) {
			return $user;
		}
		$remember = ! empty( $_POST['rememberme'] );
		$redirect = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : admin_url();
		$token    = Otp::start( $user, $remember, $redirect );
		$this->renderForm( $token, $user, '' );
		exit;
	}

	public function handleVerify(): void {
		$token = isset( $_POST['rtb_2fa_token'] ) ? sanitize_text_field( wp_unslash( $_POST['rtb_2fa_token'] ) ) : '';
		if ( '' === $token || ! check_admin_referer( 'rtb_2fa_verify', 'rtb_2fa_nonce' ) ) {
			wp_safe_redirect( wp_login_url() );
			exit;
		}
		$data = Otp::peek( $token );
		if ( ! $data ) {
			wp_safe_redirect( add_query_arg( 'rtb_2fa', 'expired', wp_login_url() ) );
			exit;
		}
		$code         = isset( $_POST['rtb_2fa_code'] ) ? sanitize_text_field( wp_unslash( $_POST['rtb_2fa_code'] ) ) : '';
		[ $ok, $msg ] = Otp::verify( $token, $code );
		$user         = get_user_by( 'id', (int) $data['user'] );
		if ( ! $ok || ! $user ) {
			$this->renderForm( $token, $user ?: null, $msg ?: 'Erreur.' );
			exit;
		}
		wp_set_auth_cookie( $user->ID, ! empty( $data['remember'] ) );
		do_action( 'wp_login', $user->user_login, $user );
		wp_safe_redirect( ! empty( $data['redirect'] ) ? $data['redirect'] : admin_url() );
		exit;
	}

	public function handleResend(): void {
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		$data  = $token ? Otp::peek( $token ) : null;
		$user  = $data ? get_user_by( 'id', (int) $data['user'] ) : null;
		if ( ! $data || ! $user ) {
			wp_safe_redirect( wp_login_url() );
			exit;
		}
		$new = Otp::start( $user, ! empty( $data['remember'] ), (string) $data['redirect'] );
		Otp::consume( $token );
		$this->renderForm( $new, $user, 'Un nouveau code vient d\'être envoyé.' );
		exit;
	}

	private function maskEmail( string $email ): string {
		$parts = explode( '@', $email );
		if ( count( $parts ) !== 2 ) {
			return $email;
		}
		$n = $parts[0];
		$masked = mb_substr( $n, 0, 1 ) . str_repeat( '•', max( 1, mb_strlen( $n ) - 2 ) ) . mb_substr( $n, -1 );
		return $masked . '@' . $parts[1];
	}

	private function maskPhone( string $phone ): string {
		$c = preg_replace( '/[^0-9]/', '', $phone );
		return str_repeat( '•', max( 0, strlen( $c ) - 2 ) ) . substr( $c, -2 );
	}

	private function renderForm( string $token, ?\WP_User $user, string $message ): void {
		$len     = (int) Settings::get( 'otp_length' );
		$targets = [];
		if ( $user && Settings::get( 'channel_email' ) ) {
			$targets[] = $this->maskEmail( $user->user_email );
		}
		if ( $user && Settings::get( 'channel_sms' ) ) {
			$ph = (string) get_user_meta( $user->ID, 'rtb_2fa_phone', true );
			if ( '' !== $ph ) {
				$targets[] = $this->maskPhone( $ph );
			}
		}
		login_header( 'Vérification en deux étapes' );
		?>
		<form name="rtb2fa" id="loginform" action="<?php echo esc_url( site_url( 'wp-login.php?action=rtb2fa', 'login_post' ) ); ?>" method="post">
			<p style="margin-bottom:14px">Un code à <?php echo (int) $len; ?> chiffres a été envoyé à&nbsp;: <strong><?php echo esc_html( implode( ' · ', $targets ) ); ?></strong>.</p>
			<?php if ( '' !== $message ) : ?>
				<div id="login_error" style="margin-bottom:14px"><?php echo esc_html( $message ); ?></div>
			<?php endif; ?>
			<p>
				<label for="rtb_2fa_code">Code de connexion</label>
				<input type="text" name="rtb_2fa_code" id="rtb_2fa_code" class="input" inputmode="numeric" autocomplete="one-time-code" maxlength="<?php echo (int) $len; ?>" autofocus style="letter-spacing:.4em;font-size:20px;text-align:center">
			</p>
			<input type="hidden" name="rtb_2fa_token" value="<?php echo esc_attr( $token ); ?>">
			<?php wp_nonce_field( 'rtb_2fa_verify', 'rtb_2fa_nonce' ); ?>
			<p class="submit">
				<input type="submit" class="button button-primary button-large" value="Valider" style="width:100%">
			</p>
		</form>
		<p id="nav" style="text-align:center">
			<a href="<?php echo esc_url( add_query_arg( [ 'action' => 'rtb2fa_resend', 'token' => $token ], wp_login_url() ) ); ?>">Renvoyer le code</a>
			&nbsp;·&nbsp;
			<a href="<?php echo esc_url( wp_login_url() ); ?>">Annuler</a>
		</p>
		<?php
		login_footer( 'rtb_2fa_code' );
		exit;
	}
}
