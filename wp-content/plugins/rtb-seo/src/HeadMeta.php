<?php

namespace RTB\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * Balises <head> : description, canonical, robots, Open Graph, Twitter Cards.
 */
final class HeadMeta {

	public function render(): void {
		$c    = new Context();
		$desc = $c->description();
		$url  = $c->url();
		$img  = $c->image();
		$title = $c->title();
		$site = get_bloginfo( 'name' );

		$out = "\n<!-- RTB SEO -->\n";

		if ( '' !== $desc ) {
			$out .= $this->tag( 'meta', [ 'name' => 'description', 'content' => $desc ] );
		}
		$out .= '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
		if ( $c->isNoindex() ) {
			$out .= $this->tag( 'meta', [ 'name' => 'robots', 'content' => 'noindex,follow' ] );
		}

		/* ---- Open Graph ---- */
		$out .= $this->prop( 'og:locale', $this->locale() );
		$out .= $this->prop( 'og:site_name', $site );
		$out .= $this->prop( 'og:type', $c->ogType() );
		$out .= $this->prop( 'og:title', $title );
		if ( '' !== $desc ) {
			$out .= $this->prop( 'og:description', $desc );
		}
		$out .= $this->prop( 'og:url', $url );
		if ( '' !== $img ) {
			$out .= $this->prop( 'og:image', $img );
			$out .= $this->prop( 'og:image:width', '1200' );
			$out .= $this->prop( 'og:image:height', '630' );
		}

		if ( $c->isArticle() || $c->isVideo() ) {
			$id   = $c->postId();
			$out .= $this->prop( 'article:published_time', get_post_time( 'c', true, $id ) );
			$out .= $this->prop( 'article:modified_time', get_post_modified_time( 'c', true, $id ) );
			$cats = get_the_category( $id );
			if ( $cats ) {
				$out .= $this->prop( 'article:section', $cats[0]->name );
			}
			$tags = get_the_tags( $id );
			if ( $tags ) {
				foreach ( $tags as $t ) {
					$out .= $this->prop( 'article:tag', $t->name );
				}
			}
		}

		/* ---- Twitter Cards ---- */
		$out .= $this->tag( 'meta', [ 'name' => 'twitter:card', 'content' => '' !== $img ? 'summary_large_image' : 'summary' ] );
		$out .= $this->tag( 'meta', [ 'name' => 'twitter:title', 'content' => $title ] );
		if ( '' !== $desc ) {
			$out .= $this->tag( 'meta', [ 'name' => 'twitter:description', 'content' => $desc ] );
		}
		if ( '' !== $img ) {
			$out .= $this->tag( 'meta', [ 'name' => 'twitter:image', 'content' => $img ] );
		}
		$handle = $this->twitterHandle();
		if ( '' !== $handle ) {
			$out .= $this->tag( 'meta', [ 'name' => 'twitter:site', 'content' => $handle ] );
		}

		echo $out . "<!-- /RTB SEO -->\n";
	}

	private function locale(): string {
		return str_replace( '-', '_', get_locale() ) ?: 'fr_FR';
	}

	private function twitterHandle(): string {
		$x = function_exists( 'onass_mod' ) ? (string) onass_mod( 'rtb_x', '' ) : '';
		if ( '' === $x ) {
			return '';
		}
		$slug = trim( (string) wp_parse_url( $x, PHP_URL_PATH ), '/' );
		return '' !== $slug ? '@' . $slug : '';
	}

	/** @param array<string,string> $attrs */
	private function tag( string $name, array $attrs ): string {
		$html = '<' . $name;
		foreach ( $attrs as $k => $v ) {
			$html .= ' ' . $k . '="' . esc_attr( $v ) . '"';
		}
		return $html . ">\n";
	}

	private function prop( string $property, string $content ): string {
		if ( '' === $content ) {
			return '';
		}
		return '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( $content ) . '">' . "\n";
	}
}
