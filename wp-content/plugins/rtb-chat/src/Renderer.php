<?php

namespace RTB\Chat;

defined( 'ABSPATH' ) || exit;

/**
 * Rend une Reply en HTML sûr, prêt à insérer dans une bulle de chat.
 */
final class Renderer {

	public function render( Reply $reply ): string {
		$html = '';
		foreach ( $reply->blocks() as $b ) {
			$html .= match ( $b['type'] ) {
				'heading'  => '<h4 class="rtb-bot-h">' . esc_html( $b['text'] ) . '</h4>',
				'text'     => '<p>' . wp_kses_post( $this->inline( $b['text'] ) ) . '</p>',
				'list'     => $this->list( $b['items'] ),
				'articles' => $this->articles( $b['cards'] ),
				'actions'  => $this->actions( $b['buttons'] ),
				'suggest'  => $this->suggest( $b['chips'] ),
				default    => '',
			};
		}
		return $html;
	}

	/** Gras léger : **texte** → <strong>. */
	private function inline( string $text ): string {
		$text = esc_html( $text );
		return preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
	}

	private function list( array $items ): string {
		$li = '';
		foreach ( $items as $i ) {
			$li .= '<li>' . wp_kses_post( $this->inline( $i ) ) . '</li>';
		}
		return '<ul class="rtb-bot-ul">' . $li . '</ul>';
	}

	private function articles( array $cards ): string {
		$out = '<div class="rtb-bot-cards">';
		foreach ( $cards as $c ) {
			$kind  = $c['kind'] ?? 'post';
			$video = $c['video'] ?? '';
			$ph    = empty( $c['thumb'] );

			$thumb = '<span class="rtb-bot-card-thumb' . ( $ph ? ' rtb-bot-card-thumb--ph' : '' ) . '"'
				. ( $ph ? '' : ' style="background-image:url(\'' . esc_url( $c['thumb'] ) . '\')"' ) . '>'
				. ( '' !== $video
					? '<span class="rtb-bot-card-play"><i class="fa-solid fa-play" aria-hidden="true"></i></span>'
					: ( $ph ? '<i class="fa-solid fa-newspaper" aria-hidden="true"></i>' : '' ) )
				. '</span>';

			$body = '<span class="rtb-bot-card-body">'
				. '<span class="rtb-bot-card-kind rtb-bot-card-kind--' . esc_attr( $kind ) . '">' . esc_html( $c['type'] ?? '' ) . '</span>'
				. '<span class="rtb-bot-card-title">' . esc_html( $c['title'] ?? '' ) . '</span>'
				. '<span class="rtb-bot-card-meta">' . esc_html( $c['date'] ?? '' ) . '</span>'
				. '</span>';

			if ( '' !== $video ) {
				$out .= '<button type="button" class="rtb-bot-card rtb-bot-card--video" data-rtb-video="' . esc_attr( $video ) . '" data-rtb-url="' . esc_url( $c['url'] ) . '" title="Lire dans le chat">' . $thumb . $body . '</button>';
			} else {
				$out .= '<a class="rtb-bot-card" href="' . esc_url( $c['url'] ) . '">' . $thumb . $body . '</a>';
			}
		}
		return $out . '</div>';
	}

	private function actions( array $buttons ): string {
		$out = '<div class="rtb-bot-actions">';
		foreach ( $buttons as $b ) {
			$out .= '<a class="rtb-bot-btn" href="' . esc_url( $b['url'] ) . '">' . esc_html( $b['label'] ) . '</a>';
		}
		return $out . '</div>';
	}

	private function suggest( array $chips ): string {
		$out = '<div class="rtb-bot-suggest">';
		foreach ( $chips as $c ) {
			$out .= '<button type="button" class="rtb-bot-chip" data-rtb-ask="' . esc_attr( $c ) . '">' . esc_html( $c ) . '</button>';
		}
		return $out . '</div>';
	}
}
