<?php

namespace RTB\Chat\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Charge le widget de chat (CSS/JS) et l'injecte dans le pied de page.
 */
final class Assets {

	/** Traduction sûre du chrome d'UI (rtb_t fourni par le plugin rtb-i18n, peut être absent). */
	private static function t( string $fr ): string {
		return function_exists( 'rtb_t' ) ? rtb_t( $fr ) : $fr;
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'wp_footer', [ $this, 'widget' ] );
	}

	public function enqueue(): void {
		if ( is_admin() ) {
			return;
		}
		$css = RTB_CHAT_DIR . 'assets/chat.css';
		$js  = RTB_CHAT_DIR . 'assets/chat.js';
		wp_enqueue_style( 'rtb-chat', RTB_CHAT_URL . 'assets/chat.css', [], is_file( $css ) ? (string) filemtime( $css ) : RTB_CHAT_VER );
		wp_enqueue_script( 'rtb-chat', RTB_CHAT_URL . 'assets/chat.js', [], is_file( $js ) ? (string) filemtime( $js ) : RTB_CHAT_VER, true );
		wp_localize_script( 'rtb-chat', 'RTB_CHAT', [
			'ajax'        => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'rtb_chat' ),
			'suggestions' => [ 'Dernières actualités', 'Conseil des ministres', 'Le direct', 'JT de 20H' ],
		] );
	}

	public function widget(): void {
		// Pas de bulle flottante dans l'admin ni sur la page /assistant (déjà en plein écran).
		if ( is_admin() || get_query_var( 'rtb_assistant' ) ) {
			return;
		}
		?>
		<div class="rtb-bot" id="rtb-bot" data-open="false">
			<button class="rtb-bot-toggle" type="button" aria-label="<?php echo esc_attr( self::t( "Ouvrir l'assistant RTB" ) ); ?>" aria-expanded="false">
				<i class="fa-solid fa-comment-dots rtb-bot-toggle-open" aria-hidden="true"></i>
				<i class="fa-solid fa-xmark rtb-bot-toggle-close" aria-hidden="true"></i>
			</button>
			<div class="rtb-bot-panel" role="dialog" aria-label="<?php echo esc_attr( self::t( 'Assistant RTB' ) ); ?>">
				<div class="rtb-bot-header">
					<span class="rtb-bot-avatar"><i class="fa-solid fa-headset" aria-hidden="true"></i></span>
					<span class="rtb-bot-head-txt">
						<strong><?php echo esc_html( self::t( 'Assistant RTB' ) ); ?></strong>
						<small><?php echo esc_html( self::t( 'Réponses à partir du contenu de la RTB' ) ); ?></small>
					</span>
					<button class="rtb-bot-clear" type="button" aria-label="<?php echo esc_attr( self::t( 'Effacer la conversation' ) ); ?>" title="<?php echo esc_attr( self::t( 'Nouvelle conversation' ) ); ?>"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
					<a class="rtb-bot-expand" href="<?php echo esc_url( home_url( '/assistant' ) ); ?>" aria-label="<?php echo esc_attr( self::t( 'Ouvrir en plein écran' ) ); ?>" title="<?php echo esc_attr( self::t( 'Plein écran' ) ); ?>"><i class="fa-solid fa-up-right-and-down-left-from-center" aria-hidden="true"></i></a>
					<button class="rtb-bot-min" type="button" aria-label="<?php echo esc_attr( self::t( 'Fermer' ) ); ?>">&times;</button>
				</div>
				<div class="rtb-bot-log" id="rtb-bot-log" aria-live="polite"></div>
				<form class="rtb-bot-form" id="rtb-bot-form">
					<input type="text" class="rtb-bot-input" id="rtb-bot-input" placeholder="<?php echo esc_attr( self::t( 'Posez votre question…' ) ); ?>" autocomplete="off" maxlength="500">
					<button type="submit" aria-label="<?php echo esc_attr( self::t( 'Envoyer' ) ); ?>"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button>
				</form>
			</div>
		</div>
		<?php
	}
}
