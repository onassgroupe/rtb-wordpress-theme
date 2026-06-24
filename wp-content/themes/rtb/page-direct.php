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
?>
<div class="rtb-page-head">
	<div class="rtb-container">
		<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span><?php pll_e( 'EN DIRECT' ); ?></span></div>
		<h1>Le Direct</h1>
		<p class="rtb-page-lead">Suivez en direct toutes les antennes de la RTB — télévision et radio — où que vous soyez.</p>
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
					title="RTB — Direct" allow="autoplay; fullscreen; encrypted-media" allowfullscreen></iframe>
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
			<a class="rtb-live-radio-link" href="<?php echo esc_url( home_url( '/radio' ) ); ?>"><i class="fa-solid fa-radio"></i> Écouter la radio en direct</a>
		</aside>
	</div>
</section>

<?php
get_footer();
