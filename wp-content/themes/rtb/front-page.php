<?php
/**
 * Front page — accueil RTB.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$channels  = rtb_get_antennes();
$emissions = rtb_get_emissions( 6 );
$cats      = rtb_get_emission_cats();
$stations  = rtb_get_stations();

/* Hero featured : valeurs du Customizer prioritaires, sinon 1re antenne. */
$first = $channels[0] ?? [];
if ( onass_mod( 'rtb_hero_headline' ) ) {
	$channels[0]['hero'] = [
		'kicker'   => onass_mod( 'rtb_hero_kicker', $first['hero']['kicker'] ?? '' ),
		'name'     => onass_mod( 'rtb_hero_name', $first['name'] ?? '' ),
		'headline' => onass_mod( 'rtb_hero_headline', $first['hero']['headline'] ?? '' ),
		'meta'     => onass_mod( 'rtb_hero_meta', $first['hero']['meta'] ?? '' ),
		'dur'      => onass_mod( 'rtb_hero_dur', $first['hero']['dur'] ?? '' ),
	];
	$channels[0]['name'] = onass_mod( 'rtb_hero_name', $first['name'] ?? '' );
	$hero_cover = onass_mod( 'rtb_hero_cover' );
	if ( $hero_cover ) {
		$channels[0]['cover'] = $hero_cover;
	}
} elseif ( ! empty( $emissions ) && ! empty( $channels ) ) {
	/* Aucun hero figé via Customizer → tout vient de la BD : on met en avant la
	   dernière émission, avec ses champs réels (catégorie, date, durée, cover). */
	$latest = $emissions[0];
	$channels[0]['now'] = $latest['title'] ?? ( $channels[0]['now'] ?? '' );
	$channels[0]['hero'] = [
		'kicker'   => mb_strtoupper( (string) ( $latest['cat'] ?? '' ), 'UTF-8' ),
		'name'     => $channels[0]['name'] ?? '',
		'headline' => $latest['title'] ?? '',
		'meta'     => $latest['date'] ?? '',
		'dur'      => $latest['dur'] ?? '',
	];
	if ( ! empty( $latest['cover'] ) ) {
		$channels[0]['cover'] = $latest['cover'];
	}
}

/* Données pour Alpine */
$rtb_payload = [
	'channels' => array_map( function ( $c ) {
		return [
			'name'  => $c['name'],
			'now'   => $c['now'] ?? '',
			'prog'  => $c['prog'] ?? 40,
			'cover' => $c['cover'],
			'hero'  => $c['hero'],
		];
	}, $channels ),
	'editions' => array_map( function ( $e ) {
		return [
			'title' => $e['title'],
			'cat'   => $e['cat'],
			'dur'   => $e['dur'],
			'by'    => $e['by'],
			'date'  => $e['date'],
			'cover' => $e['cover'],
			'url'   => $e['permalink'] ?? '#',
		];
	}, $emissions ),
	'cats'     => array_values( $cats ),
	'stations' => $stations,
];
?>

<script>window.__RTB = <?php echo wp_json_encode( $rtb_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;</script>

<div x-data="rtbHome()">

	<!-- HERO STAGE -->
	<section class="rtb-stage">
		<div class="rtb-stage-bg">
			<span class="rtb-media" :style="'background-image:url(\'' + heroCover + '\')'"></span>
		</div>
		<div class="rtb-container rtb-stage-inner">
			<div class="rtb-stage-lead">
				<div class="rtb-stage-eyebrow"><span class="rtb-live-dot"></span><span x-text="hero.kicker"></span></div>
				<h1 class="rtb-stage-title" x-text="hero.headline"></h1>
				<p class="rtb-stage-meta"><strong x-text="heroName"></strong> — <span x-text="hero.meta"></span> · <span x-text="hero.dur"></span></p>
				<div class="rtb-stage-actions">
					<a class="rtb-btn-watch" href="<?php echo esc_url( rtb_lurl( '/direct' ) ); ?>">
						<span class="rtb-play"><i></i></span> <?php pll_e( 'Regarder en direct' ); ?>
					</a>
					<a class="rtb-btn-ghost" href="<?php echo esc_url( rtb_lurl( '/grille' ) ); ?>"><?php pll_e( 'Guide des programmes' ); ?> <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span></a>
				</div>
			</div>

			<div class="rtb-stage-channels">
				<div class="rtb-stage-channels-head"><span class="rtb-live-dot"></span> <?php pll_e( "À L'ANTENNE MAINTENANT" ); ?></div>
				<div class="rtb-stage-strip">
					<template x-for="(ch, i) in data.channels" :key="i">
						<button type="button" class="rtb-chan" :class="{ 'is-on': active === i }" :aria-pressed="(active === i).toString()" @click="active = i">
							<span class="rtb-chan-thumb rtb-media-wrap">
								<span class="rtb-media" :style="'background-image:url(\'' + ch.cover + '\')'"></span>
								<span class="rtb-chan-live">LIVE</span>
							</span>
							<span class="rtb-chan-name" x-text="ch.name"></span>
							<span class="rtb-chan-now" x-text="ch.now"></span>
							<span class="rtb-chan-prog"><i :style="'width:' + ch.prog + '%'"></i></span>
						</button>
					</template>
				</div>
			</div>
		</div>
	</section>

	<!-- À LA UNE — GROS TITRES (priorité éditoriale n°1) -->
	<section class="rtb-container rtb-section rtb-section--top">
		<div class="rtb-sec-head">
			<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span><?php pll_e( 'À LA UNE' ); ?></span></div>
			<a class="rtb-sec-more" href="<?php echo esc_url( rtb_lurl( '/category/infos' ) ); ?>"><?php pll_e( "Toute l'actualité" ); ?> <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span></a>
		</div>

		<div class="rtb-headlines">
			<!-- Lead éditable (Customizer) -->
			<?php
			$aune_cover = onass_mod( 'rtb_aune_cover' ) ?: rtb_cdnize( 'https://www.rtb.bf/wp-content/uploads/2026/06/vlcsnap-2026-06-20-21h18m47s439.png' );
			?>
			<a class="rtb-lead" href="<?php echo esc_url( rtb_lurl( '/category/societe' ) ); ?>" data-cs="rtb_aune">
				<span class="rtb-lead-img rtb-media-wrap">
					<span class="rtb-media" style="background-image:url('<?php echo esc_url( $aune_cover ); ?>')"></span>
					<span class="rtb-lead-grad"></span>
					<span class="rtb-lead-tag" data-live="rtb_aune_cat"><?php echo esc_html( onass_mod( 'rtb_aune_cat', 'SOCIÉTÉ' ) ); ?></span>
					<span class="rtb-lead-overlay">
						<h2 data-live="rtb_aune_title"><?php echo esc_html( onass_mod( 'rtb_aune_title', "Nuit de l'arbre 2026 : cérémonie de récompense" ) ); ?></h2>
						<span class="rtb-lead-meta" data-live="rtb_aune_meta"><?php echo esc_html( onass_mod( 'rtb_aune_meta', 'RTB Télévision · 20 juin 2026' ) ); ?></span>
					</span>
				</span>
				<p class="rtb-lead-excerpt" data-live="rtb_aune_excerpt"><?php echo esc_html( onass_mod( 'rtb_aune_excerpt', "La cérémonie a distingué les acteurs engagés pour le reboisement et la préservation de l'environnement au Burkina Faso." ) ); ?></p>
			</a>

			<!-- Les gros titres (numérotés) -->
			<div class="rtb-titres">
				<div class="rtb-titres-head"><?php pll_e( 'LES GROS TITRES' ); ?></div>
				<?php
				$aune_q = new WP_Query( [
					'post_type'           => 'post',
					'posts_per_page'      => 5,
					'ignore_sticky_posts' => true,
				] );
				$n = 0;
				if ( $aune_q->have_posts() ) :
					while ( $aune_q->have_posts() ) :
						$aune_q->the_post();
						$n++;
						$cat   = get_the_category();
						$cname = $cat ? $cat[0]->name : 'Actualité';
						?>
						<a class="rtb-titre" href="<?php the_permalink(); ?>">
							<span class="rtb-titre-num"><?php echo esc_html( sprintf( '%02d', $n ) ); ?></span>
							<span class="rtb-titre-body">
								<span class="rtb-titre-cat"><?php echo esc_html( mb_strtoupper( $cname, 'UTF-8' ) ); ?></span>
								<span class="rtb-titre-title"><?php the_title(); ?></span>
								<span class="rtb-titre-date"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></span>
							</span>
						</a>
						<?php
					endwhile;
					wp_reset_postdata();
				endif;
				?>
			</div>
		</div>
	</section>

	<!-- LE JOURNAL TÉLÉVISÉ -->
	<section class="rtb-band">
		<div class="rtb-container rtb-section">
			<div class="rtb-jt-head">
				<div>
					<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span><?php pll_e( 'INFORMATION' ); ?></span></div>
					<h2><?php pll_e( 'Le Journal Télévisé' ); ?></h2>
				</div>
				<div class="rtb-tabs">
					<template x-for="t in tabs" :key="t">
						<button type="button" class="rtb-tab" :class="{ 'is-on': jtTab === t }" :aria-pressed="(jtTab === t).toString()" @click="jtTab = t" x-text="t"></button>
					</template>
				</div>
			</div>
			<div class="rtb-grid-3">
				<template x-for="(ed, i) in editions" :key="i">
					<a class="rtb-videocard" :href="ed.url">
						<span class="rtb-vc-thumb rtb-media-wrap">
							<span class="rtb-media" :style="'background-image:url(\'' + ed.cover + '\')'"></span>
							<span class="rtb-vc-cat" x-text="ed.cat"></span>
							<span class="rtb-vc-dur" x-text="ed.dur"></span>
							<span class="rtb-play rtb-play--sm"><i></i></span>
						</span>
						<h3 x-text="ed.title"></h3>
						<span class="rtb-vc-meta"><b x-text="ed.by"></b><span>·</span><span x-text="ed.date"></span></span>
					</a>
				</template>
			</div>
		</div>
	</section>

	<!-- NOS ANTENNES -->
	<section class="rtb-container rtb-section">
		<div class="rtb-sec-head">
			<div class="rtb-eyebrow rtb-eyebrow--green"><i></i><span><?php pll_e( 'NOS ANTENNES' ); ?></span></div>
			<a class="rtb-sec-more" href="<?php echo esc_url( rtb_lurl( '/emissions' ) ); ?>"><?php pll_e( 'Toutes les chaînes' ); ?> <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span></a>
		</div>
		<div class="rtb-grid-5 rtb-channels">
			<?php foreach ( $channels as $c ) : ?>
				<a class="rtb-chcard" href="<?php echo esc_url( $c['permalink'] ?? rtb_lurl( '/direct' ) ); ?>" style="--ch:<?php echo esc_attr( $c['accent'] ); ?>">
					<span class="rtb-media" style="background-image:url('<?php echo esc_url( $c['cover'] ); ?>')"></span>
					<span class="rtb-chcard-grad"></span>
					<span class="rtb-chcard-head">
						<span class="rtb-chcard-mark"><?php echo esc_html( $c['mark'] ); ?></span>
						<span class="rtb-chcard-kind"><?php echo esc_html( $c['kind'] ); ?></span>
					</span>
					<span class="rtb-chcard-foot">
						<span class="rtb-chcard-live"><span class="rtb-live-dot"></span><?php pll_e( 'EN DIRECT' ); ?></span>
						<span class="rtb-chcard-name"><?php echo esc_html( $c['name'] ); ?></span>
						<?php if ( ! empty( $c['now'] ) ) : ?>
							<span class="rtb-chcard-now"><?php echo esc_html( $c['now'] ); ?></span>
						<?php endif; ?>
						<span class="rtb-chcard-cta"><?php pll_e( 'Regarder la chaîne' ); ?> <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- GRANDS RENDEZ-VOUS (magazines — depuis la BD) -->
	<?php
	$rtb_mags    = rtb_get_emissions_in_cat( 'Magazine', 4 );
	$rtb_accents = [ '#F5DE00', '#E70C2F', '#10A653', '#0B7A3B' ];
	$rtb_mag_tags = [
		'Success'            => 'Le magazine de la réussite',
		'Questions Majeures' => 'Le grand débat politique',
		'Santémag'           => 'Le magazine de la santé',
		'Débat de presse'    => "L'analyse de l'actualité",
		'Sport Box'          => "L'actualité du sport",
		'Intégral Foot'      => 'Le football national',
	];
	if ( $rtb_mags ) :
		?>
		<section class="rtb-band">
			<div class="rtb-container rtb-section">
				<div class="rtb-sec-head">
					<div class="rtb-eyebrow rtb-eyebrow--yellow"><i></i><span><?php pll_e( 'GRANDS RENDEZ-VOUS' ); ?></span></div>
					<a class="rtb-sec-more" href="<?php echo esc_url( rtb_lurl( '/emissions' ) ); ?>"><?php pll_e( 'Toutes les émissions' ); ?> <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span></a>
				</div>
				<div class="rtb-mags">
					<?php foreach ( $rtb_mags as $i => $m ) :
						$accent = $rtb_accents[ $i % count( $rtb_accents ) ];
						$tag    = 'Grand rendez-vous';
						foreach ( $rtb_mag_tags as $kw => $t ) {
							if ( false !== mb_stripos( $m['title'], $kw ) ) {
								$tag = $t;
								break;
							}
						}
						?>
						<a class="rtb-mag" href="<?php echo esc_url( $m['permalink'] ); ?>">
							<span class="rtb-mag-img rtb-media-wrap">
								<span class="rtb-media" style="background-image:url('<?php echo esc_url( rtb_cdnize( $m['cover'], 440, 560 ) ); ?>')"></span>
								<span class="rtb-mag-grad"></span>
								<span class="rtb-mag-overlay">
									<span class="rtb-mag-chan" style="color:<?php echo esc_attr( $accent ); ?>"><?php echo esc_html( mb_strtoupper( $m['by'], 'UTF-8' ) ); ?></span>
									<span class="rtb-mag-name"><?php echo esc_html( $m['title'] ); ?></span>
									<span class="rtb-mag-tag"><?php echo esc_html( $tag ); ?></span>
								</span>
								<span class="rtb-play rtb-play--sm"><i></i></span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<!-- RÉGIONS (proximité) -->
	<?php $rtb_regions = array_slice( rtb_get_regions(), 0, 6 ); ?>
	<section class="rtb-container rtb-section">
		<div class="rtb-sec-head">
			<div class="rtb-eyebrow rtb-eyebrow--green"><i></i><span><?php pll_e( 'PROXIMITÉ' ); ?></span></div>
			<a class="rtb-sec-more" href="<?php echo esc_url( rtb_lurl( '/regions' ) ); ?>"><?php pll_e( "L'info des régions" ); ?> <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span></a>
		</div>
		<h2 class="rtb-regions-title"><?php pll_e( 'La RTB en régions' ); ?></h2>
		<div class="rtb-regions">
			<?php foreach ( $rtb_regions as $r ) : ?>
				<a class="rtb-region" href="<?php echo esc_url( $r['permalink'] ?? rtb_lurl( '/regions' ) ); ?>" style="border-left-color:<?php echo esc_attr( $r['accent'] ?? 'var(--rtb-green)' ); ?>">
					<span class="rtb-region-name"><?php echo esc_html( $r['name'] ); ?></span>
					<span class="rtb-region-city"><?php echo esc_html( $r['city'] ); ?></span>
					<span class="rtb-region-role"><?php echo esc_html( $r['role'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- RADIO -->
	<section class="rtb-radio" id="radio">
		<div class="rtb-container">
			<div data-cs="rtb_radio">
				<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span data-live="rtb_radio_eyebrow"><?php echo esc_html( onass_mod( 'rtb_radio_eyebrow', 'RADIO EN DIRECT' ) ); ?></span></div>
				<h2 data-live="rtb_radio_title"><?php echo esc_html( onass_mod( 'rtb_radio_title', 'Écoutez nos stations, où que vous soyez.' ) ); ?></h2>
				<div class="rtb-radio-player">
					<button type="button" class="rtb-radio-toggle" @click="toggleRadio()" :aria-label="radioPlaying ? 'Pause' : 'Lecture'">
						<span class="pause" x-show="radioPlaying"><span></span><span></span></span>
						<span class="play" x-show="!radioPlaying"></span>
					</button>
					<div class="rtb-radio-now">
						<b x-text="station.name"></b>
						<small><span x-text="station.freq"></span> · <span x-text="radioPlaying ? 'En direct' : 'En pause'"></span></small>
					</div>
					<div class="rtb-eq" :class="{ 'is-paused': !radioPlaying }">
						<span></span><span></span><span></span><span></span><span></span>
					</div>
				</div>
			</div>
			<div class="rtb-radio-cards">
				<template x-for="(st, i) in data.stations" :key="i">
					<button type="button" class="rtb-radiocard" :class="{ 'is-on': radioStation === i }" :aria-pressed="(radioStation === i).toString()" @click="selectStation(i)">
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

	<!-- BARRE LIVE FLOTTANTE -->
	<div class="rtb-livebar" :class="{ 'is-up': pastHero && !footerVisible }">
		<div class="rtb-container">
			<button type="button" class="rtb-livebar-play" aria-label="Regarder"><i></i></button>
			<span class="rtb-livebar-tag"><span class="rtb-live-dot"></span>EN DIRECT</span>
			<div class="rtb-livebar-info">
				<b x-text="heroName"></b>
				<small x-text="channel.now"></small>
			</div>
			<div class="rtb-eq"><span></span><span></span><span></span><span></span><span></span></div>
			<a class="rtb-livebar-cta" href="<?php echo esc_url( rtb_lurl( '/emissions' ) ); ?>">Regarder</a>
		</div>
	</div>

</div><!-- /rtbHome -->

<?php
get_footer();
