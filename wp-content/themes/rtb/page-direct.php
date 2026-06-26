<?php
/**
 * Page « Le Direct » — hub live TV (lecteur + sélecteur de chaînes).
 */
defined( 'ABSPATH' ) || exit;

get_header();

$channels = rtb_get_antennes();
// La chaîne principale (TV) affiche la dernière édition réelle (BD), pas une méta figée.
$rtb_latest = rtb_get_emissions( 1 );
if ( ! empty( $rtb_latest ) && ! empty( $channels ) ) {
	$channels[0]['now'] = $rtb_latest[0]['title'];
	if ( ! empty( $rtb_latest[0]['cover'] ) ) {
		$channels[0]['cover'] = $rtb_latest[0]['cover'];
	}
}
// Direct par chaîne (Infomaniak) + repli YouTube dernière édition si pas de live.
$payload = [
	'channels' => array_map( function ( $c ) {
		return [
			'name'  => $c['name'],
			'now'   => $c['now'] ?? '',
			'kind'  => $c['kind'] ?? 'TV',
			'cover' => $c['cover'],
			'url'   => $c['permalink'] ?? '#',
			'live'  => $c['live'] ?? '',
			'hero'  => $c['hero'],
		];
	}, $channels ),
	'ytId'     => rtb_latest_video_id(),
	'editions' => [],
	'cats'     => [],
	'stations' => [],
];

// Articles en direct (live blogs ouverts) — affichés seulement s'il y en a.
$rtb_live_posts = get_posts( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 8,
	'meta_key'       => '_rtb_live_status',
	'meta_value'     => 'open',
	'no_found_rows'  => true,
	'orderby'        => 'modified',
	'order'          => 'DESC',
] );
?>
<div class="rtb-page-head">
	<div class="rtb-container">
		<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span><?php pll_e( 'EN DIRECT' ); ?></span></div>
		<h1><?php echo esc_html( rtb_t( 'Le Direct' ) ); ?></h1>
		<p class="rtb-page-lead"><?php echo esc_html( rtb_t( 'Suivez en direct toutes les antennes de la RTB — télévision et radio — où que vous soyez.' ) ); ?></p>
	</div>
</div>

<script>window.__RTB = <?php echo wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;</script>

<section class="rtb-container rtb-section" x-data="rtbHome()" style="padding-top:8px">
	<div class="rtb-live">
		<!-- Lecteur -->
		<div class="rtb-live-player">
			<div class="rtb-live-screen rtb-media-wrap">
				<!-- Direct de la chaîne (Infomaniak, auto_start) ; si pas de live → dernière édition YouTube -->
				<iframe class="rtb-video-iframe"
					:src="(channel && channel.live) ? channel.live : ('https://www.youtube-nocookie.com/embed/' + data.ytId + '?autoplay=1&mute=1&playsinline=1&rel=0&modestbranding=1')"
					title="<?php echo esc_attr( rtb_t( 'RTB — Direct' ) ); ?>" allow="autoplay; fullscreen; encrypted-media" allowfullscreen></iframe>
				<span class="rtb-badge-live rtb-badge-over">
					<span class="rtb-live-dot"></span>
					<span x-text="(channel && channel.live) ? 'EN DIRECT' : 'DERNIÈRE ÉDITION'"></span>
				</span>
			</div>
			<div class="rtb-live-meta">
				<div>
					<div class="rtb-live-meta-name" x-text="heroName"></div>
					<div class="rtb-live-meta-now"><span class="rtb-live-dot" style="background:var(--rtb-red)"></span> <span x-text="channel.now"></span></div>
				</div>
				<a class="rtb-btn-ghost" :href="channel.url"><?php pll_e( 'Voir la chaîne' ); ?> <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span></a>
			</div>
		</div>

		<!-- Sélecteur de chaînes -->
		<aside class="rtb-live-list">
			<div class="rtb-live-list-title"><span class="rtb-live-dot" style="background:var(--rtb-red)"></span> <?php pll_e( "À L'ANTENNE MAINTENANT" ); ?></div>
			<template x-for="(ch, i) in data.channels" :key="i">
				<button type="button" class="rtb-live-row" :class="{ 'is-on': active === i }" @click="active = i">
					<span class="rtb-live-row-thumb rtb-media-wrap">
						<span class="rtb-media" :style="'background-image:url(\'' + ch.cover + '\')'"></span>
						<span class="rtb-live-row-badge" x-text="ch.kind"></span>
					</span>
					<span class="rtb-live-row-body">
						<span class="rtb-live-row-name" x-text="ch.name"></span>
						<span class="rtb-live-row-now" x-text="ch.now"></span>
					</span>
					<i class="fa-solid fa-play rtb-live-row-ico"></i>
				</button>
			</template>
			<a class="rtb-live-radio-link" href="<?php echo esc_url( rtb_lurl( '/radio' ) ); ?>"><i class="fa-solid fa-radio"></i> <?php echo esc_html( rtb_t( 'Écouter la radio en direct' ) ); ?></a>
		</aside>
	</div>
</section>

<?php if ( ! empty( $rtb_live_posts ) ) : ?>
<section class="rtb-container rtb-section rtb-livearts">
	<div class="rtb-sec-head">
		<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span><?php echo esc_html( rtb_t( 'ARTICLES EN DIRECT' ) ); ?></span></div>
		<p class="rtb-sec-sub"><?php echo esc_html( rtb_t( 'Suivez nos directs écrits, mis à jour en temps réel.' ) ); ?></p>
	</div>
	<div class="rtb-archive-grid">
		<?php foreach ( $rtb_live_posts as $rtb_lp ) :
			$rtb_lp_id    = (int) $rtb_lp->ID;
			$rtb_lp_thumb = get_the_post_thumbnail_url( $rtb_lp_id, 'rtb-card' )
				?: ( rtb_cdnize( (string) get_post_meta( $rtb_lp_id, 'rtb_cover_url', true ) ) ?: rtb_img( 'aune-culture.png' ) );
			$rtb_lp_cat   = get_the_category( $rtb_lp_id );
			$rtb_lp_cname = $rtb_lp_cat ? $rtb_lp_cat[0]->name : rtb_t( 'Actualité' );
			$rtb_lp_count = class_exists( '\RTB\LiveBlog\Repository' ) ? count( \RTB\LiveBlog\Repository::entries( $rtb_lp_id ) ) : 0;
			?>
			<a class="rtb-videocard" href="<?php echo esc_url( get_permalink( $rtb_lp_id ) ); ?>">
				<span class="rtb-vc-thumb" style="background-image:url('<?php echo esc_url( $rtb_lp_thumb ); ?>')">
					<span class="rtb-vc-cat"><?php echo esc_html( mb_strtoupper( (string) $rtb_lp_cname, 'UTF-8' ) ); ?></span>
					<span class="rtb-vc-live"><span class="rtb-live-dot"></span><?php echo esc_html( rtb_t( 'EN DIRECT' ) ); ?></span>
				</span>
				<h3><?php echo esc_html( get_the_title( $rtb_lp_id ) ); ?></h3>
				<span class="rtb-vc-meta">
					<b><?php echo esc_html( rtb_t( 'Rédaction RTB' ) ); ?></b><span>·</span>
					<span><?php echo (int) $rtb_lp_count . ' ' . esc_html( rtb_t( $rtb_lp_count > 1 ? 'mises à jour' : 'mise à jour' ) ); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<?php
get_footer();
