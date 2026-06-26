<?php

namespace RTB\TwoFA;

defined( 'ABSPATH' ) || exit;

/**
 * Réglages du plugin (option unique) + page d'admin + câblage SMTP.
 * Les identifiants (mot de passe SMTP, clé API SMS) sont saisis dans l'admin et
 * stockés en option — pratique pour la RTB ; ne jamais exposer cette option en public.
 */
final class Settings {

	public const OPTION = 'rtb_2fa_settings';
	private static ?Settings $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/** @return array<string,mixed> */
	public static function defaults(): array {
		return [
			'enabled'      => 0,
			'channel_email'=> 1,
			'channel_sms'  => 1,
			'enforce'      => 'administrators', // 'all' | 'administrators'
			'otp_length'   => 6,
			'otp_ttl'      => 300, // secondes
			// SMTP
			'smtp_host'    => '',
			'smtp_port'    => 587,
			'smtp_secure'  => 'tls', // 'tls' | 'ssl' | 'none'
			'smtp_user'    => '',
			'smtp_pass'    => '',
			'from_email'   => '',
			'from_name'    => '',
			// SMS (Aqilas)
			'sms_base_url' => 'https://www.aqilas.com/api/v1',
			'sms_api_key'  => '',
			'sms_sender'   => 'RTB',
		];
	}

	/** @return array<string,mixed> */
	public static function all(): array {
		$v = get_option( self::OPTION, [] );
		return array_merge( self::defaults(), is_array( $v ) ? $v : [] );
	}

	/** @return mixed */
	public static function get( string $key ) {
		return self::all()[ $key ] ?? null;
	}

	public static function enabled(): bool {
		return (bool) self::get( 'enabled' );
	}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'menu' ] );
		add_action( 'admin_post_rtb_2fa_save', [ $this, 'save' ] );
		add_action( 'phpmailer_init', [ $this, 'applySmtp' ] );
		// Cohérence de l'expéditeur quand un SMTP/expéditeur est défini.
		add_filter( 'wp_mail_from', static function ( $email ) {
			$f = self::get( 'from_email' );
			return $f ?: $email;
		} );
		add_filter( 'wp_mail_from_name', static function ( $name ) {
			$f = self::get( 'from_name' );
			return $f ?: $name;
		} );
	}

	public function menu(): void {
		add_options_page( 'RTB 2FA', 'RTB 2FA', 'manage_options', 'rtb-2fa', [ $this, 'render' ] );
	}

	/** @param \PHPMailer\PHPMailer\PHPMailer $phpmailer */
	public function applySmtp( $phpmailer ): void {
		$s = self::all();
		if ( '' === trim( (string) $s['smtp_host'] ) ) {
			return;
		}
		$phpmailer->isSMTP();
		$phpmailer->Host       = $s['smtp_host'];
		$phpmailer->Port       = (int) $s['smtp_port'] ?: 587;
		$phpmailer->SMTPAuth   = '' !== trim( (string) $s['smtp_user'] );
		if ( $phpmailer->SMTPAuth ) {
			$phpmailer->Username = $s['smtp_user'];
			$phpmailer->Password = $s['smtp_pass'];
		}
		if ( in_array( $s['smtp_secure'], [ 'tls', 'ssl' ], true ) ) {
			$phpmailer->SMTPSecure = $s['smtp_secure'];
		}
		if ( '' !== trim( (string) $s['from_email'] ) ) {
			$phpmailer->setFrom( $s['from_email'], $s['from_name'] ?: get_bloginfo( 'name' ) );
		}
	}

	public function save(): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'rtb_2fa_save' ) ) {
			wp_die( 'Action non autorisée.' );
		}
		$in  = wp_unslash( $_POST );
		$cur = self::all();
		$out = [
			'enabled'       => empty( $in['enabled'] ) ? 0 : 1,
			'channel_email' => empty( $in['channel_email'] ) ? 0 : 1,
			'channel_sms'   => empty( $in['channel_sms'] ) ? 0 : 1,
			'enforce'       => in_array( $in['enforce'] ?? '', [ 'all', 'administrators' ], true ) ? $in['enforce'] : 'administrators',
			'otp_length'    => min( 8, max( 4, (int) ( $in['otp_length'] ?? 6 ) ) ),
			'otp_ttl'       => min( 1800, max( 60, (int) ( $in['otp_ttl'] ?? 300 ) ) ),
			'smtp_host'     => sanitize_text_field( $in['smtp_host'] ?? '' ),
			'smtp_port'     => (int) ( $in['smtp_port'] ?? 587 ),
			'smtp_secure'   => in_array( $in['smtp_secure'] ?? '', [ 'tls', 'ssl', 'none' ], true ) ? $in['smtp_secure'] : 'tls',
			'smtp_user'     => sanitize_text_field( $in['smtp_user'] ?? '' ),
			'smtp_pass'     => $this->keep( (string) ( $in['smtp_pass'] ?? '' ), (string) $cur['smtp_pass'] ),
			'from_email'    => sanitize_email( $in['from_email'] ?? '' ),
			'from_name'     => sanitize_text_field( $in['from_name'] ?? '' ),
			'sms_base_url'  => esc_url_raw( $in['sms_base_url'] ?? '' ) ?: self::defaults()['sms_base_url'],
			'sms_api_key'   => $this->keep( (string) ( $in['sms_api_key'] ?? '' ), (string) $cur['sms_api_key'] ),
			'sms_sender'    => sanitize_text_field( $in['sms_sender'] ?? 'RTB' ),
		];
		update_option( self::OPTION, $out, false );
		wp_safe_redirect( add_query_arg( [ 'page' => 'rtb-2fa', 'saved' => 1 ], admin_url( 'options-general.php' ) ) );
		exit;
	}

	/** Conserve l'ancien secret si le champ est laissé vide (placeholder ••••). */
	private function keep( string $new, string $old ): string {
		return '' === trim( $new ) ? $old : $new;
	}

	public function render(): void {
		$s = self::all();
		?>
		<div class="wrap">
			<h1>RTB 2FA — double authentification</h1>
			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Réglages enregistrés.</p></div>
			<?php endif; ?>

			<h2 class="nav-tab-wrapper rtb-2fa-tabs">
				<a href="#general" class="nav-tab" data-tab="general">⚙️ Général</a>
				<a href="#email" class="nav-tab" data-tab="email">✉️ E-mail</a>
				<a href="#sms" class="nav-tab" data-tab="sms">📱 SMS</a>
			</h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="rtb_2fa_save">
				<?php wp_nonce_field( 'rtb_2fa_save' ); ?>

				<div class="rtb-2fa-panel" data-panel="general">
					<table class="form-table" role="presentation">
						<tr><th>Activer la 2FA</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( $s['enabled'] ); ?>> Exiger un code à la connexion</label></td></tr>
						<tr><th>Canaux</th><td>
							<label><input type="checkbox" name="channel_email" value="1" <?php checked( $s['channel_email'] ); ?>> E-mail</label> &nbsp;
							<label><input type="checkbox" name="channel_sms" value="1" <?php checked( $s['channel_sms'] ); ?>> SMS</label>
							<p class="description">Configurez chaque canal dans son onglet.</p>
						</td></tr>
						<tr><th>Appliquer à</th><td>
							<select name="enforce">
								<option value="administrators" <?php selected( $s['enforce'], 'administrators' ); ?>>Administrateurs seulement</option>
								<option value="all" <?php selected( $s['enforce'], 'all' ); ?>>Tous les utilisateurs</option>
							</select>
						</td></tr>
						<tr><th>Longueur du code</th><td><input type="number" name="otp_length" min="4" max="8" value="<?php echo esc_attr( $s['otp_length'] ); ?>"></td></tr>
						<tr><th>Validité (secondes)</th><td><input type="number" name="otp_ttl" min="60" max="1800" value="<?php echo esc_attr( $s['otp_ttl'] ); ?>"></td></tr>
					</table>
				</div>

				<div class="rtb-2fa-panel" data-panel="email">
					<p class="description">Serveur d'envoi des e-mails contenant le code de connexion.</p>
					<table class="form-table" role="presentation">
						<tr><th>Hôte SMTP</th><td><input type="text" class="regular-text" name="smtp_host" value="<?php echo esc_attr( $s['smtp_host'] ); ?>" placeholder="smtp.exemple.com"></td></tr>
						<tr><th>Port</th><td><input type="number" name="smtp_port" value="<?php echo esc_attr( $s['smtp_port'] ); ?>"></td></tr>
						<tr><th>Sécurité</th><td>
							<select name="smtp_secure">
								<option value="tls" <?php selected( $s['smtp_secure'], 'tls' ); ?>>TLS</option>
								<option value="ssl" <?php selected( $s['smtp_secure'], 'ssl' ); ?>>SSL</option>
								<option value="none" <?php selected( $s['smtp_secure'], 'none' ); ?>>Aucune</option>
							</select>
						</td></tr>
						<tr><th>Utilisateur</th><td><input type="text" class="regular-text" name="smtp_user" value="<?php echo esc_attr( $s['smtp_user'] ); ?>"></td></tr>
						<tr><th>Mot de passe</th><td><input type="password" class="regular-text" name="smtp_pass" value="" placeholder="<?php echo $s['smtp_pass'] ? '•••••• (inchangé)' : ''; ?>" autocomplete="new-password"></td></tr>
						<tr><th>Expéditeur (e-mail)</th><td><input type="email" class="regular-text" name="from_email" value="<?php echo esc_attr( $s['from_email'] ); ?>" placeholder="no-reply@rtb.bf"></td></tr>
						<tr><th>Expéditeur (nom)</th><td><input type="text" class="regular-text" name="from_name" value="<?php echo esc_attr( $s['from_name'] ); ?>" placeholder="RTB"></td></tr>
					</table>
				</div>

				<div class="rtb-2fa-panel" data-panel="sms">
					<p class="description">Passerelle SMS (Aqilas) pour l'envoi du code par SMS.</p>
					<table class="form-table" role="presentation">
						<tr><th>API base URL</th><td><input type="url" class="regular-text" name="sms_base_url" value="<?php echo esc_attr( $s['sms_base_url'] ); ?>"></td></tr>
						<tr><th>Clé API</th><td><input type="password" class="regular-text" name="sms_api_key" value="" placeholder="<?php echo $s['sms_api_key'] ? '•••••• (inchangée)' : 'X-AUTH-TOKEN'; ?>" autocomplete="new-password"></td></tr>
						<tr><th>Expéditeur (sender ID)</th><td><input type="text" name="sms_sender" value="<?php echo esc_attr( $s['sms_sender'] ); ?>" maxlength="11"></td></tr>
						<tr><th>Téléphone des comptes</th><td><p class="description">Le numéro de chaque utilisateur se renseigne sur sa fiche de profil (champ « Téléphone (2FA) »).</p></td></tr>
					</table>
				</div>

				<?php submit_button( 'Enregistrer' ); ?>
			</form>
		</div>
		<style>.rtb-2fa-panel{display:none}.rtb-2fa-panel.is-active{display:block}</style>
		<script>
		(function(){
			var tabs=[].slice.call(document.querySelectorAll('.rtb-2fa-tabs .nav-tab'));
			var panels=[].slice.call(document.querySelectorAll('.rtb-2fa-panel'));
			function show(name){
				if(!panels.some(function(p){return p.getAttribute('data-panel')===name;})) name='general';
				tabs.forEach(function(t){t.classList.toggle('nav-tab-active',t.getAttribute('data-tab')===name);});
				panels.forEach(function(p){p.classList.toggle('is-active',p.getAttribute('data-panel')===name);});
				try{history.replaceState(null,'','#'+name);}catch(e){}
			}
			tabs.forEach(function(t){t.addEventListener('click',function(e){e.preventDefault();show(t.getAttribute('data-tab'));});});
			show((location.hash||'#general').slice(1));
		})();
		</script>
		<?php
	}
}
