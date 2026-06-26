<?php

namespace RTB\TwoFA;

defined( 'ABSPATH' ) || exit;

/** Champ « Téléphone (2FA) » sur la fiche de profil utilisateur. */
final class Profile {

	public const META = 'rtb_2fa_phone';

	public static function register(): void {
		add_action( 'show_user_profile', [ self::class, 'field' ] );
		add_action( 'edit_user_profile', [ self::class, 'field' ] );
		add_action( 'personal_options_update', [ self::class, 'save' ] );
		add_action( 'edit_user_profile_update', [ self::class, 'save' ] );
	}

	/** @param \WP_User $user */
	public static function field( $user ): void {
		$val = (string) get_user_meta( $user->ID, self::META, true );
		?>
		<h2>Sécurité — double authentification</h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="rtb_2fa_phone">Téléphone (2FA)</label></th>
				<td>
					<input type="text" id="rtb_2fa_phone" name="rtb_2fa_phone" value="<?php echo esc_attr( $val ); ?>" class="regular-text" placeholder="+226 70 00 00 00">
					<p class="description">Numéro pour recevoir le code de connexion par SMS (format Burkina, ex. 70000000 ou +22670000000).</p>
				</td>
			</tr>
		</table>
		<?php
	}

	public static function save( int $user_id ): void {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		if ( isset( $_POST['rtb_2fa_phone'] ) ) {
			update_user_meta( $user_id, self::META, sanitize_text_field( wp_unslash( $_POST['rtb_2fa_phone'] ) ) );
		}
	}
}
