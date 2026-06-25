<?php

namespace RTB\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Réglages → RTB API : gérer dynamiquement les clés (créer, lister, révoquer, supprimer).
 */
final class Admin {

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'menu' ] );
		add_action( 'admin_post_rtb_api_key_create', [ $this, 'create' ] );
		add_action( 'admin_post_rtb_api_key_revoke', [ $this, 'revoke' ] );
		add_action( 'admin_post_rtb_api_key_delete', [ $this, 'delete' ] );
	}

	public function menu(): void {
		add_options_page( 'RTB API', 'RTB API', 'manage_options', 'rtb-api', [ $this, 'render' ] );
	}

	public function render(): void {
		$base = esc_url( rest_url( RTB_API_NS ) );
		$post = esc_url( admin_url( 'admin-post.php' ) );
		$new  = get_transient( 'rtb_api_new_key_' . get_current_user_id() );
		if ( $new ) {
			delete_transient( 'rtb_api_new_key_' . get_current_user_id() );
		}

		echo '<div class="wrap"><h1>RTB API — clés de l\'application mobile</h1>';
		echo '<p>Clé requise pour consommer l\'API <code>' . esc_html( RTB_API_NS ) . '</code> via l\'en-tête <code>X-RTB-Api-Key</code>. Base : <code>' . $base . '</code></p>';

		if ( $new ) {
			echo '<div class="notice notice-success"><p><strong>Nouvelle clé créée — copiez-la maintenant, elle ne sera plus affichée :</strong></p>'
				. '<p><code style="user-select:all;font-size:15px;padding:6px 10px;background:#f0f0f1;display:inline-block">' . esc_html( $new ) . '</code></p></div>';
		}

		// Formulaire de création
		echo '<h2>Créer une clé</h2><form method="post" action="' . $post . '">';
		echo '<input type="hidden" name="action" value="rtb_api_key_create">';
		wp_nonce_field( 'rtb_api_key_create' );
		echo '<input type="text" name="label" class="regular-text" placeholder="Nom (ex. App iOS, App Android)" required> ';
		submit_button( 'Générer une clé', 'primary', 'submit', false );
		echo '</form>';

		// Liste
		echo '<h2 style="margin-top:28px">Clés existantes</h2>';
		$keys = KeyStore::all();
		if ( ! $keys ) {
			echo '<p>Aucune clé. Créez-en une ci-dessus, ou définissez la constante d\'env <code>RTB_API_KEY</code>.</p>';
		} else {
			echo '<table class="widefat striped" style="max-width:820px"><thead><tr>'
				. '<th>Nom</th><th>Préfixe</th><th>Créée</th><th>Dernier usage</th><th>Statut</th><th>Actions</th>'
				. '</tr></thead><tbody>';
			foreach ( $keys as $k ) {
				$revoked = ! empty( $k['revoked'] );
				$used    = ! empty( $k['last_used'] ) ? date_i18n( 'j M Y H:i', (int) $k['last_used'] ) : '—';
				echo '<tr>';
				echo '<td><strong>' . esc_html( $k['label'] ?? '' ) . '</strong></td>';
				echo '<td><code>' . esc_html( $k['prefix'] ?? '' ) . '…</code></td>';
				echo '<td>' . esc_html( date_i18n( 'j M Y', (int) ( $k['created'] ?? 0 ) ) ) . '</td>';
				echo '<td>' . esc_html( $used ) . '</td>';
				echo '<td>' . ( $revoked ? '<span style="color:#b32d2e">Révoquée</span>' : '<span style="color:#1a7f37">Active</span>' ) . '</td>';
				echo '<td>' . $this->actionButtons( (string) ( $k['id'] ?? '' ), $revoked, $post ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}
		echo '</div>';
	}

	private function actionButtons( string $id, bool $revoked, string $post ): string {
		$out = '';
		if ( ! $revoked ) {
			$out .= '<form method="post" action="' . $post . '" style="display:inline" onsubmit="return confirm(\'Révoquer cette clé ? Les apps qui l\\\'utilisent perdront l\\\'accès.\');">'
				. '<input type="hidden" name="action" value="rtb_api_key_revoke"><input type="hidden" name="id" value="' . esc_attr( $id ) . '">'
				. wp_nonce_field( 'rtb_api_key_revoke_' . $id, '_wpnonce', true, false )
				. '<button class="button button-secondary">Révoquer</button></form> ';
		}
		$out .= '<form method="post" action="' . $post . '" style="display:inline" onsubmit="return confirm(\'Supprimer définitivement cette clé ?\');">'
			. '<input type="hidden" name="action" value="rtb_api_key_delete"><input type="hidden" name="id" value="' . esc_attr( $id ) . '">'
			. wp_nonce_field( 'rtb_api_key_delete_' . $id, '_wpnonce', true, false )
			. '<button class="button-link delete" style="color:#b32d2e">Supprimer</button></form>';
		return $out;
	}

	public function create(): void {
		$this->guard( 'rtb_api_key_create' );
		$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
		$plain = KeyStore::create( $label );
		set_transient( 'rtb_api_new_key_' . get_current_user_id(), $plain, 60 );
		$this->back();
	}

	public function revoke(): void {
		$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$this->guard( 'rtb_api_key_revoke_' . $id );
		KeyStore::revoke( $id );
		$this->back();
	}

	public function delete(): void {
		$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$this->guard( 'rtb_api_key_delete_' . $id );
		KeyStore::delete( $id );
		$this->back();
	}

	private function guard( string $nonce ): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $nonce ) ) {
			wp_die( 'Action non autorisée.' );
		}
	}

	private function back(): void {
		wp_safe_redirect( admin_url( 'options-general.php?page=rtb-api' ) );
		exit;
	}
}
