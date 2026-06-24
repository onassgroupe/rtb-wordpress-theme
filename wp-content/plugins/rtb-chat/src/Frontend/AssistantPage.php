<?php

namespace RTB\Chat\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Page dédiée /assistant : interface de chat plein écran (comme le « kit » starter-media),
 * rendue avec l'en-tête/pied du thème. Route propre via règle de réécriture.
 */
final class AssistantPage {

	private const QV  = 'rtb_assistant';
	private const VER = '1';

	public function register(): void {
		add_action( 'init', [ $this, 'rewrite' ] );
		add_filter( 'query_vars', [ $this, 'queryVar' ] );
		add_action( 'template_redirect', [ $this, 'maybeRender' ] );
	}

	public function rewrite(): void {
		add_rewrite_rule( '^assistant/?$', 'index.php?' . self::QV . '=1', 'top' );
		// Flush unique (au 1er chargement après déploiement/activation).
		if ( get_option( 'rtb_chat_rewrite_v' ) !== self::VER ) {
			flush_rewrite_rules( false );
			update_option( 'rtb_chat_rewrite_v', self::VER );
		}
	}

	public function queryVar( array $vars ): array {
		$vars[] = self::QV;
		return $vars;
	}

	public function maybeRender(): void {
		if ( ! get_query_var( self::QV ) ) {
			return;
		}
		status_header( 200 );
		add_filter( 'pre_get_document_title', static fn() => 'Assistant RTB' );

		$prompts = [
			[ 'Dernières actualités', 'fa-newspaper' ],
			[ 'Conseil des ministres', 'fa-landmark' ],
			[ 'Le direct', 'fa-tower-broadcast' ],
			[ 'JT de 20H', 'fa-tv' ],
			[ 'Résultats sportifs', 'fa-futbol' ],
			[ 'La radio en ligne', 'fa-radio' ],
		];

		$tips = [
			[ 'fa-bullseye', 'Soyez précis', 'Indiquez un sujet clair : « conseil des ministres mai », « finale Coupe du Faso ».' ],
			[ 'fa-key', 'Mots-clés', 'Pas besoin de phrases complètes — quelques mots suffisent.' ],
			[ 'fa-wand-magic-sparkles', 'Demandez un résumé', 'Commencez par « résume-moi… » pour une synthèse de l\'article.' ],
			[ 'fa-clock', 'Actu récente', 'Tapez « dernières actualités » pour les publications du moment.' ],
		];
		$can = [
			[ 'fa-magnifying-glass', 'Rechercher des articles & JT' ],
			[ 'fa-file-lines', 'Résumer un sujet' ],
			[ 'fa-tower-broadcast', 'Orienter vers le direct & la radio' ],
			[ 'fa-newspaper', 'Donner les dernières actualités' ],
		];

		get_header();
		?>
		<div class="rtb-assist-page">
			<div class="rtb-container rtb-assist-layout">
				<div class="rtb-assist" data-rtb-chat-inline>
					<button class="rtb-bot-clear rtb-assist-clear" type="button" aria-label="Effacer la conversation" title="Nouvelle conversation"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
					<div class="rtb-bot-log rtb-assist-log" aria-live="polite">
						<div class="rtb-assist-intro">
							<span class="rtb-assist-mark"><i class="fa-solid fa-headset" aria-hidden="true"></i></span>
							<h1>Comment puis-je vous aider ?</h1>
							<p>Posez vos questions sur l'actualité, les JT, les émissions ou le direct — je réponds à partir du contenu de la RTB.</p>
							<div class="rtb-assist-prompts">
								<?php foreach ( $prompts as $p ) : ?>
									<button type="button" class="rtb-assist-prompt" data-rtb-ask="<?php echo esc_attr( $p[0] ); ?>">
										<i class="fa-solid <?php echo esc_attr( $p[1] ); ?>" aria-hidden="true"></i>
										<span><?php echo esc_html( $p[0] ); ?></span>
									</button>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
					<form class="rtb-bot-form rtb-assist-form">
						<input type="text" class="rtb-bot-input" placeholder="Écrivez votre message…" autocomplete="off" maxlength="500">
						<button type="submit" aria-label="Envoyer"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button>
					</form>
				</div>

				<aside class="rtb-assist-side">
					<div class="rtb-assist-side-card">
						<h2 class="rtb-assist-side-h"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> Tirer le meilleur</h2>
						<ul class="rtb-assist-tips">
							<?php foreach ( $tips as $t ) : ?>
								<li>
									<span class="rtb-assist-tip-ico"><i class="fa-solid <?php echo esc_attr( $t[0] ); ?>" aria-hidden="true"></i></span>
									<span><strong><?php echo esc_html( $t[1] ); ?></strong><br><?php echo esc_html( $t[2] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<div class="rtb-assist-side-card">
						<h2 class="rtb-assist-side-h"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Ce que je peux faire</h2>
						<ul class="rtb-assist-can">
							<?php foreach ( $can as $c ) : ?>
								<li><i class="fa-solid <?php echo esc_attr( $c[0] ); ?>" aria-hidden="true"></i> <?php echo esc_html( $c[1] ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				</aside>
			</div>
		</div>
		<?php
		get_footer();
		exit;
	}
}
