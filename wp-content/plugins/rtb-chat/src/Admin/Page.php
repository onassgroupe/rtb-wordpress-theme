<?php

namespace RTB\Chat\Admin;

use RTB\Chat\Learning\Learner;
use RTB\Chat\Nlp\Lexicon;

defined( 'ABSPATH' ) || exit;

/**
 * Page d'admin « Outils → Assistant RTB » : apprendre le contenu (lexique) depuis l'interface.
 */
final class Page {

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'menu' ] );
		add_action( 'admin_post_rtb_chat_learn', [ $this, 'handleLearn' ] );
		add_action( 'admin_notices', [ $this, 'notice' ] );
	}

	public function menu(): void {
		add_management_page(
			'Assistant RTB',
			'Assistant RTB',
			'manage_options',
			'rtb-chat',
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		$last  = Learner::last();
		$count = ( new Lexicon() )->count();
		$post  = esc_url( admin_url( 'admin-post.php' ) );
		echo '<div class="wrap"><h1>Assistant RTB</h1>';
		echo '<p>L\'assistant répond aux visiteurs à partir du contenu de la RTB (articles, JT, émissions). '
			. 'L\'<strong>apprentissage</strong> construit un lexique du contenu pour corriger les fautes de frappe des questions '
			. '(ex. « conseille » → « conseil »). À relancer après un gros import de contenu.</p>';

		echo '<table class="widefat striped" style="max-width:560px;margin:14px 0"><tbody>';
		printf( '<tr><td><strong>Mots au lexique</strong></td><td>%s</td></tr>', esc_html( number_format_i18n( $count ) ) );
		if ( $last ) {
			printf(
				'<tr><td><strong>Dernier apprentissage</strong></td><td>%s — %s mots traités</td></tr>',
				esc_html( date_i18n( 'j M Y H:i', (int) $last['time'] ) ),
				esc_html( number_format_i18n( (int) $last['processed'] ) )
			);
		} else {
			echo '<tr><td><strong>Dernier apprentissage</strong></td><td>Jamais lancé</td></tr>';
		}
		echo '</tbody></table>';

		echo '<form method="post" action="' . $post . '">';
		echo '<input type="hidden" name="action" value="rtb_chat_learn">';
		wp_nonce_field( 'rtb_chat_learn' );
		echo '<p><label><input type="checkbox" name="fresh" value="1"> Repartir de zéro (réinitialise le lexique)</label></p>';
		submit_button( 'Apprendre le contenu maintenant', 'primary', 'submit', false );
		echo ' <span class="description" style="margin-left:8px">Quelques secondes selon le volume.</span>';
		echo '</form>';

		echo '</div>';
	}

	public function handleLearn(): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'rtb_chat_learn' ) ) {
			wp_die( 'Action non autorisée.' );
		}
		$fresh = ! empty( $_POST['fresh'] );
		$stats = ( new Learner() )->run( $fresh );
		wp_safe_redirect( add_query_arg(
			[ 'page' => 'rtb-chat', 'learned' => (int) $stats['unique'] ],
			admin_url( 'tools.php' )
		) );
		exit;
	}

	public function notice(): void {
		if ( ! isset( $_GET['page'], $_GET['learned'] ) || 'rtb-chat' !== $_GET['page'] ) {
			return;
		}
		printf(
			'<div class="notice notice-success is-dismissible"><p>Apprentissage terminé : %s mots au lexique.</p></div>',
			esc_html( number_format_i18n( (int) $_GET['learned'] ) )
		);
	}
}
