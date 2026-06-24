<?php

namespace RTB\Seo\Schema;

use RTB\Seo\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Données structurées schema.org en un seul @graph :
 * WebSite (+SearchAction), NewsMediaOrganization, NewsArticle / VideoObject, BreadcrumbList.
 */
final class JsonLd {

	private string $home;
	private string $orgId;
	private string $siteId;

	public function __construct() {
		$this->home   = home_url( '/' );
		$this->orgId  = $this->home . '#organization';
		$this->siteId = $this->home . '#website';
	}

	public function render(): void {
		$c     = new Context();
		$graph = [ $this->website(), $this->organization() ];

		if ( $c->isArticle() ) {
			$graph[] = $this->article( $c );
		} elseif ( $c->isVideo() ) {
			$graph[] = $this->video( $c );
		}

		$crumbs = $this->breadcrumbs( $c );
		if ( $crumbs ) {
			$graph[] = $crumbs;
		}

		$data = [ '@context' => 'https://schema.org', '@graph' => array_values( array_filter( $graph ) ) ];
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}

	private function website(): array {
		return [
			'@type'           => 'WebSite',
			'@id'             => $this->siteId,
			'url'             => $this->home,
			'name'            => get_bloginfo( 'name' ),
			'description'     => get_bloginfo( 'description' ),
			'publisher'       => [ '@id' => $this->orgId ],
			'inLanguage'      => 'fr',
			'potentialAction' => [
				'@type'       => 'SearchAction',
				'target'      => [
					'@type'       => 'EntryPoint',
					'urlTemplate' => $this->home . '?s={search_term_string}',
				],
				'query-input' => 'required name=search_term_string',
			],
		];
	}

	private function organization(): array {
		$logo = function_exists( 'rtb_logo_url' ) ? rtb_logo_url() : '';
		$same = [];
		foreach ( [ 'rtb_facebook', 'rtb_x', 'rtb_instagram', 'rtb_linkedin', 'rtb_youtube' ] as $key ) {
			$u = function_exists( 'onass_mod' ) ? (string) onass_mod( $key, '' ) : '';
			if ( '' !== $u ) {
				$same[] = $u;
			}
		}

		$org = [
			'@type'       => 'NewsMediaOrganization',
			'@id'         => $this->orgId,
			'name'        => get_bloginfo( 'name' ),
			'url'         => $this->home,
			'description' => get_bloginfo( 'description' ),
			'email'       => function_exists( 'onass_mod' ) ? onass_mod( 'rtb_email', 'info@rtb.bf' ) : 'info@rtb.bf',
			'telephone'   => function_exists( 'onass_mod' ) ? onass_mod( 'rtb_phone', '+226 25 31 83 53' ) : '',
			'address'     => [
				'@type'          => 'PostalAddress',
				'streetAddress'  => function_exists( 'onass_mod' ) ? onass_mod( 'rtb_address', '01 BP 2530 Ouagadougou 01' ) : '',
				'addressCountry' => 'BF',
			],
		];
		if ( '' !== $logo ) {
			$org['logo'] = [ '@type' => 'ImageObject', 'url' => $logo ];
		}
		if ( $same ) {
			$org['sameAs'] = $same;
		}
		return $org;
	}

	private function article( Context $c ): array {
		$id  = $c->postId();
		$img = $c->image();
		return [
			'@type'            => 'NewsArticle',
			'@id'              => get_permalink( $id ) . '#article',
			'headline'         => mb_substr( get_the_title( $id ), 0, 110 ),
			'description'      => $c->description(),
			'image'            => '' !== $img ? [ $img ] : [],
			'datePublished'    => get_post_time( 'c', true, $id ),
			'dateModified'     => get_post_modified_time( 'c', true, $id ),
			'author'           => [ '@id' => $this->orgId ],
			'publisher'        => [ '@id' => $this->orgId ],
			'mainEntityOfPage' => [ '@type' => 'WebPage', '@id' => get_permalink( $id ) ],
			'articleSection'   => ( $cats = get_the_category( $id ) ) ? $cats[0]->name : 'Actualité',
			'isPartOf'         => [ '@id' => $this->siteId ],
			'inLanguage'       => 'fr',
		];
	}

	private function video( Context $c ): array {
		$id  = $c->postId();
		$yt  = $c->youtubeId();
		$img = $c->image();
		$v   = [
			'@type'         => 'VideoObject',
			'@id'           => get_permalink( $id ) . '#video',
			'name'          => get_the_title( $id ),
			'description'   => $c->description() ?: get_the_title( $id ),
			'thumbnailUrl'  => '' !== $img ? [ $img ] : [],
			'uploadDate'    => get_post_time( 'c', true, $id ),
			'publisher'     => [ '@id' => $this->orgId ],
			'isPartOf'      => [ '@id' => $this->siteId ],
			'inLanguage'    => 'fr',
		];
		if ( '' !== $yt ) {
			$v['embedUrl']   = 'https://www.youtube.com/embed/' . $yt;
			$v['contentUrl'] = 'https://www.youtube.com/watch?v=' . $yt;
			if ( empty( $v['thumbnailUrl'] ) ) {
				$v['thumbnailUrl'] = [ 'https://i.ytimg.com/vi/' . $yt . '/hqdefault.jpg' ];
			}
		}
		return $v;
	}

	private function breadcrumbs( Context $c ): ?array {
		$items = [ [ 'name' => 'Accueil', 'url' => $this->home ] ];

		if ( $c->isArticle() || $c->isVideo() ) {
			$id   = $c->postId();
			$cats = get_the_category( $id );
			if ( $cats ) {
				$items[] = [ 'name' => $cats[0]->name, 'url' => (string) get_category_link( $cats[0]->term_id ) ];
			}
			$items[] = [ 'name' => get_the_title( $id ), 'url' => (string) get_permalink( $id ) ];
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$items[] = [ 'name' => single_term_title( '', false ), 'url' => $c->url() ];
		} else {
			return null;
		}

		$list = [];
		foreach ( $items as $i => $it ) {
			$list[] = [
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $it['name'],
				'item'     => $it['url'],
			];
		}
		return [ '@type' => 'BreadcrumbList', 'itemListElement' => $list ];
	}
}
