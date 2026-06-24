<?php
/**
 * Page Grille des programmes (EPG TV & Radio).
 */
defined( 'ABSPATH' ) || exit;

get_header();

$schedule = rtb_get_schedule();

// Jours de la semaine (aujourd'hui actif).
$days    = [ 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim' ];
$today_i = (int) ( new DateTime( 'now', new DateTimeZone( 'Africa/Ouagadougou' ) ) )->format( 'N' ) - 1;
?>
<div class="rtb-page-head">
	<div class="rtb-container">
		<div class="rtb-eyebrow rtb-eyebrow--green"><i></i><span>PROGRAMMES</span></div>
		<h1>Grille des programmes</h1>
		<p class="rtb-page-lead">Retrouvez les programmes TV et radio de toutes les antennes de la RTB, et ce qui passe en ce moment.</p>
	</div>
</div>

<section class="rtb-container rtb-section" x-data="rtbGrid()">

	<!-- Jours -->
	<div class="rtb-grid-days">
		<?php foreach ( $days as $i => $d ) : ?>
			<button type="button" class="rtb-grid-day<?php echo $i === $today_i ? ' is-today' : ''; ?>"
				:class="{ 'is-on': day === <?php echo (int) $i; ?> }" @click="day = <?php echo (int) $i; ?>">
				<?php echo esc_html( $d ); ?><?php echo $i === $today_i ? '<span>·  Auj.</span>' : ''; ?>
			</button>
		<?php endforeach; ?>
	</div>

	<!-- Chaînes -->
	<div class="rtb-grid-chans">
		<template x-for="(ch, i) in data" :key="i">
			<button type="button" class="rtb-grid-chan" :class="{ 'is-on': chan === i }" @click="chan = i">
				<span class="rtb-grid-chan-dot" :style="'background:' + ch.accent"></span>
				<span x-text="ch.name"></span>
				<span class="rtb-grid-chan-kind" x-text="ch.kind"></span>
			</button>
		</template>
	</div>

	<!-- Timeline -->
	<div class="rtb-grid-now-note" x-show="day === <?php echo (int) $today_i; ?>">
		<span class="rtb-live-dot" style="background:var(--rtb-red)"></span>
		En ce moment sur <strong x-text="data[chan].name"></strong> · <span x-text="nowLabel"></span>
	</div>

	<div class="rtb-grid-timeline">
		<template x-for="(s, i) in data[chan].slots" :key="i">
			<div class="rtb-slot" :class="{ 'is-now': isNow(i), 'is-past': isPast(i) }">
				<div class="rtb-slot-time" x-text="s[0]"></div>
				<div class="rtb-slot-line"><span class="rtb-slot-dot"></span></div>
				<div class="rtb-slot-body">
					<div class="rtb-slot-title" x-text="s[1]"></div>
					<div class="rtb-slot-cat">
						<span class="rtb-slot-tag" x-text="s[2]"></span>
						<span class="rtb-slot-badge" x-show="isNow(i)">EN COURS</span>
						<span class="rtb-slot-upnext" x-show="isNext(i)">À SUIVRE</span>
					</div>
				</div>
			</div>
		</template>
	</div>

	<p class="rtb-grid-foot">Les horaires sont donnés à titre indicatif et peuvent être modifiés.</p>
</section>

<script>
window.__RTB_GRID = <?php echo wp_json_encode( $schedule, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;
window.__RTB_TODAY = <?php echo (int) $today_i; ?>;
</script>

<?php
get_footer();
