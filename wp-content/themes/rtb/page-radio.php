<?php
/**
 * Page « Radio en direct » — lecteur radio + stations.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$stations = rtb_get_stations();
$payload  = [ 'channels' => [], 'editions' => [], 'cats' => [], 'stations' => $stations, 'radioStream' => rtb_radio_stream() ];
?>
<div class="rtb-page-head">
	<div class="rtb-container">
		<div class="rtb-eyebrow rtb-eyebrow--yellow"><i></i><span><?php pll_e( 'RADIO EN DIRECT' ); ?></span></div>
		<h1>Radio en direct</h1>
		<p class="rtb-page-lead">Écoutez les stations de la RTB en direct, où que vous soyez.</p>
	</div>
</div>

<script>window.__RTB = <?php echo wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;</script>

<section class="rtb-radio rtb-radio--page" id="radio" x-data="rtbHome()">
	<div class="rtb-container">
		<div>
			<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span x-text="station.tag || 'NATIONALE'"></span></div>
			<h2 x-text="station.name"></h2>
			<div class="rtb-radio-player">
				<button type="button" class="rtb-radio-toggle" @click="toggleRadio()" :aria-label="radioPlaying ? 'Pause' : 'Lecture'">
					<span class="pause" x-show="radioPlaying"><span></span><span></span></span>
					<span class="play" x-show="!radioPlaying"></span>
				</button>
				<div class="rtb-radio-now">
					<b x-text="station.freq"></b>
					<small><span x-text="radioPlaying ? 'En direct' : 'En pause'"></span></small>
				</div>
				<div class="rtb-eq" :class="{ 'is-paused': !radioPlaying }">
					<span></span><span></span><span></span><span></span><span></span><span></span><span></span>
				</div>
				<audio x-ref="rtbAudio" preload="none"></audio>
			</div>
		</div>
		<div class="rtb-radio-cards">
			<template x-for="(st, i) in data.stations" :key="i">
				<button type="button" class="rtb-radiocard" :class="{ 'is-on': radioStation === i }" @click="selectStation(i)">
					<span>
						<b x-text="st.name"></b>
						<small x-text="st.freq"></small>
					</span>
					<span class="tag" x-text="st.tag"></span>
				</button>
			</template>
		</div>
	</div>
</section>

<section class="rtb-container rtb-section">
	<div class="rtb-eyebrow rtb-eyebrow--green"><i></i><span>PROGRAMMES RADIO</span></div>
	<p class="rtb-page-lead" style="margin-bottom:22px">Retrouvez la grille complète des programmes radio de la RTB.</p>
	<a class="rtb-btn-watch" href="<?php echo esc_url( rtb_lurl( '/grille' ) ); ?>" style="text-decoration:none"><i class="fa-solid fa-calendar-days" style="margin-right:4px"></i> Voir la grille des programmes</a>
</section>

<?php
get_footer();
