<?php
/**
 * Plugin Name: RTB Admin Recovery
 * Description: Réinitialise le mot de passe du premier administrateur via une URL secrète.
 *              Strictement gatée par la variable d'environnement RTB_RECOVERY_TOKEN
 *              (ou la constante PHP du même nom). Désactivée sans token configuré.
 * Version:     1.0.0
 *
 * Usage (Coolify) :
 *   1. Définir RTB_RECOVERY_TOKEN=<chaine-aléatoire-≥24-car> dans les env vars de l'app
 *   2. Redéployer (ou redémarrer le conteneur)
 *   3. Visiter https://<domaine>/?rtb-recover=<le-même-token>
 *   4. Lire le couple login / mot de passe affiché en clair
 *   5. SUPPRIMER RTB_RECOVERY_TOKEN dans Coolify + redéployer pour désactiver
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'rtb_admin_recovery_handle', 0 );

function rtb_admin_recovery_handle(): void {
	if ( ! isset( $_GET['rtb-recover'] ) ) {
		return;
	}

	$configured = (string) getenv( 'RTB_RECOVERY_TOKEN' );
	if ( '' === $configured && defined( 'RTB_RECOVERY_TOKEN' ) ) {
		$configured = (string) constant( 'RTB_RECOVERY_TOKEN' );
	}

	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );

	if ( '' === $configured ) {
		status_header( 404 );
		echo "Recovery désactivée. Pour l'activer : définir RTB_RECOVERY_TOKEN dans Coolify et redéployer.\n";
		exit;
	}

	if ( strlen( $configured ) < 24 ) {
		status_header( 500 );
		echo "Recovery refusée : RTB_RECOVERY_TOKEN doit faire au moins 24 caractères.\n";
		exit;
	}

	$provided = (string) wp_unslash( $_GET['rtb-recover'] );

	if ( ! hash_equals( $configured, $provided ) ) {
		usleep( 1500000 ); // throttle anti-bruteforce
		status_header( 403 );
		echo "Token invalide.\n";
		exit;
	}

	$is_https = is_ssl() || ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] );
	if ( ! $is_https ) {
		status_header( 400 );
		echo "Recovery exige HTTPS.\n";
		exit;
	}

	$admins = get_users( [
		'role'    => 'administrator',
		'number'  => 10,
		'orderby' => 'ID',
		'order'   => 'ASC',
	] );

	if ( empty( $admins ) ) {
		status_header( 404 );
		echo "Aucun compte administrateur trouvé.\n";
		exit;
	}

	$user_arg = isset( $_GET['user'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['user'] ) ) : '';
	$target   = null;
	if ( $user_arg ) {
		foreach ( $admins as $a ) {
			if ( (string) $a->ID === $user_arg || $a->user_login === $user_arg ) {
				$target = $a;
				break;
			}
		}
	}
	if ( ! $target ) {
		$target = $admins[0];
	}

	$new_pass = 'RTB-' . wp_generate_password( 16, false, false );
	wp_set_password( $new_pass, $target->ID );

	echo "✅ Mot de passe administrateur réinitialisé.\n\n";
	echo "  Login    : {$target->user_login}\n";
	echo "  Email    : {$target->user_email}\n";
	echo "  Password : {$new_pass}\n";
	echo "  Login URL: " . home_url( '/wp-login.php' ) . "\n\n";
	echo "Comptes administrateurs (utilise ?user=<login|ID> pour en cibler un autre) :\n";
	foreach ( $admins as $a ) {
		echo "  - ID={$a->ID}  login={$a->user_login}  email={$a->user_email}\n";
	}
	echo "\n⚠ À FAIRE IMMÉDIATEMENT :\n";
	echo "  1. Connecte-toi avec les identifiants ci-dessus\n";
	echo "  2. Change le mot de passe via Utilisateurs → Profil\n";
	echo "  3. Supprime RTB_RECOVERY_TOKEN dans Coolify\n";
	echo "  4. Redéploie pour désactiver complètement la recovery\n";
	exit;
}
