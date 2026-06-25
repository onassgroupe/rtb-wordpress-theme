<?php
/**
 * Header — topbar, navigation, ticker.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Navigation éditoriale d'un média public national.
 * Chaque entrée peut porter un méga-menu (rubriques, chaînes, éditions).
 */
$rtb_nav = [
	'home' => [ 'label' => 'Accueil', 'url' => rtb_lurl( '/' ) ],
	'direct' => [
		'label' => 'Le Direct', 'url' => rtb_lurl( '/emissions' ),
		'menu_title' => 'Nos antennes en direct',
		'menu' => [
			[ 'RTB Télévision', rtb_lurl( '/emissions' ), 'La chaîne généraliste nationale' ],
			[ 'Télé Zénith', rtb_lurl( '/emissions' ), 'Divertissement & culture' ],
			[ 'RTB3 Langues Nationales', rtb_lurl( '/emissions' ), 'Information de proximité' ],
			[ 'RTB Guiriko', rtb_lurl( '/emissions' ), "Antenne de l'Ouest — Bobo" ],
			[ 'Radio Burkina', rtb_lurl( '/#radio' ), 'Radio nationale · 99.9 FM' ],
			[ 'Grille des programmes', rtb_lurl( '/grille' ), 'TV & radio · ce qui passe maintenant' ],
		],
	],
	'actualites' => [
		'label' => 'Actualités', 'url' => rtb_lurl( '/category/infos' ),
		'menu_title' => 'Les rubriques de la rédaction',
		'menu' => [
			[ 'Politique & Gouvernement', rtb_lurl( '/category/politique' ), 'Présidence, Conseil des ministres' ],
			[ 'Société', rtb_lurl( '/category/societe' ), 'Éducation, santé, vie nationale' ],
			[ 'Économie', rtb_lurl( '/category/economie' ), 'Croissance, agriculture, énergie' ],
			[ 'Sécurité & Défense', rtb_lurl( '/category/securite' ), 'FDS, souveraineté, AES' ],
			[ 'International', rtb_lurl( '/category/international' ), 'Afrique & monde' ],
			[ 'Culture', rtb_lurl( '/category/culture' ), 'Arts, patrimoine, SNC' ],
		],
	],
	'journal' => [
		'label' => 'Le Journal', 'url' => rtb_lurl( '/emissions' ),
		'menu_title' => 'Éditions & journaux',
		'menu' => [
			[ 'JT de 13H', rtb_lurl( '/emissions' ), 'Édition de la mi-journée' ],
			[ 'JT de 19H', rtb_lurl( '/emissions' ), 'Édition de proximité' ],
			[ 'JT de 20H', rtb_lurl( '/emissions' ), 'Grand journal du soir' ],
			[ 'Journal en langues nationales', rtb_lurl( '/emissions' ), 'Mooré · Dioula · Fulfuldé' ],
			[ 'Journaux parlés (Radio)', rtb_lurl( '/#radio' ), 'Éditions radio' ],
		],
	],
	'emissions' => [
		'label' => 'Émissions', 'url' => rtb_lurl( '/emissions' ),
		'menu_title' => 'Grands rendez-vous',
		'menu' => [
			[ 'Success', rtb_lurl( '/emissions' ), 'Le magazine de la réussite' ],
			[ 'Questions Majeures', rtb_lurl( '/emissions' ), 'Le grand débat politique' ],
			[ 'Santémag', rtb_lurl( '/emissions' ), 'Le magazine de la santé' ],
			[ 'Débat de presse', rtb_lurl( '/emissions' ), "L'analyse de l'actualité" ],
			[ 'Sport Box', rtb_lurl( '/emissions' ), "L'actualité du sport" ],
		],
	],
	'sport'   => [ 'label' => 'Sport',   'url' => rtb_lurl( '/category/sport' ) ],
	'regions' => [ 'label' => 'Régions', 'url' => rtb_lurl( '/regions' ) ],
];

/* Visuels "à la une" affichés dans chaque méga-menu (plus parlant). */
$rtb_cdn_u = 'https://www.rtb.bf/wp-content/uploads/';
// Covers des méga-menus — défauts statiques, puis remplacés par le dernier contenu (suit chaque synchro).
$rtb_features = [
	'direct'     => [ 'label' => 'EN DIRECT', 'title' => 'RTB Télévision', 'cover' => $rtb_cdn_u . '2026/05/Capture-decran-2026-02-25-201146.png' ],
	'actualites' => [ 'label' => 'À LA UNE', 'title' => 'Actualités', 'cover' => $rtb_cdn_u . '2026/06/vlcsnap-2026-06-20-21h18m47s439.png' ],
	'journal'    => [ 'label' => 'DERNIÈRE ÉDITION', 'title' => 'Le Journal', 'cover' => $rtb_cdn_u . '2026/06/vlcsnap-2026-06-15-19h26m07s146.png' ],
	'emissions'  => [ 'label' => 'MAGAZINE', 'title' => 'Magazines', 'cover' => $rtb_cdn_u . '2026/06/vlcsnap-2026-06-17-21h44m25s931.png' ],
];
// Dernière émission → Le Direct + Le Journal
$rtb_f_last = rtb_get_emissions( 1 );
if ( ! empty( $rtb_f_last[0]['title'] ) ) {
	$rtb_features['direct']['title']  = $rtb_f_last[0]['title'];
	$rtb_features['direct']['cover']  = $rtb_f_last[0]['cover'];
	$rtb_features['journal']['title'] = $rtb_f_last[0]['title'];
	$rtb_features['journal']['cover'] = $rtb_f_last[0]['cover'];
}
// Dernier magazine → Émissions
$rtb_f_mag = rtb_get_emissions_in_cat( 'Magazine', 1 );
if ( ! empty( $rtb_f_mag[0]['title'] ) ) {
	$rtb_features['emissions']['title'] = $rtb_f_mag[0]['title'];
	$rtb_features['emissions']['cover'] = $rtb_f_mag[0]['cover'];
}
// Dernier article → Actualités
$rtb_f_art = get_posts( [ 'post_type' => 'post', 'numberposts' => 1 ] );
if ( ! empty( $rtb_f_art[0] ) ) {
	$rtb_features['actualites']['title'] = get_the_title( $rtb_f_art[0] );
	$rtb_features['actualites']['cover'] = rtb_post_cover( $rtb_f_art[0]->ID, 'rtb-card', 'aune-culture.png' );
}

$rtb_socials = [
	'facebook'  => onass_mod( 'rtb_facebook', '#' ),
	'x'         => onass_mod( 'rtb_x', '#' ),
	'instagram' => onass_mod( 'rtb_instagram', '#' ),
	'linkedin'  => onass_mod( 'rtb_linkedin', '#' ),
	'youtube'   => onass_mod( 'rtb_youtube', '#' ),
];

/* Menu « Le Direct » dynamique : une entrée par chaîne (page dédiée) + radio + grille. */
$rtb_nav['direct']['url'] = rtb_lurl( '/direct' );
$rtb_direct_menu = [];
foreach ( rtb_get_antennes() as $a ) {
	$rtb_direct_menu[] = [ $a['name'], $a['permalink'] ?? rtb_lurl( '/direct' ), $a['desc'] ?: 'En direct' ];
}
$rtb_direct_menu[] = [ 'Radio en direct', rtb_lurl( '/radio' ), 'Toutes les stations de la RTB' ];
$rtb_direct_menu[] = [ 'Grille des programmes', rtb_lurl( '/grille' ), 'TV & radio · ce qui passe maintenant' ];
$rtb_nav['direct']['menu'] = $rtb_direct_menu;

/* Le Journal : éditions (catégories JT) + langues nationales (RTB3) + journaux parlés (radio). */
$rtb_rtb3_url = rtb_lurl( '/emissions' );
foreach ( rtb_get_antennes() as $a ) {
	if ( false !== mb_stripos( $a['name'], 'RTB3' ) || false !== mb_stripos( $a['name'], 'Langues' ) ) {
		$rtb_rtb3_url = $a['permalink'] ?? $rtb_rtb3_url;
	}
}
$rtb_jt_desc = [ 'JT 13H' => 'Édition de la mi-journée', 'JT 19H' => 'Édition de proximité', 'JT 20H' => 'Grand journal du soir' ];
$rtb_journal_menu = [];
foreach ( [ 'jt-13h', 'jt-19h', 'jt-20h' ] as $jt_slug ) {
	$term = get_term_by( 'slug', $jt_slug, 'rtb_emission_cat' );
	if ( $term ) {
		$rtb_journal_menu[] = [ $term->name, get_term_link( $term ), $rtb_jt_desc[ $term->name ] ?? 'Journal télévisé' ];
	}
}
$rtb_journal_menu[] = [ 'Journal en langues nationales', $rtb_rtb3_url, 'Mooré · Dioula · Fulfuldé · Gulmancéma' ];
$rtb_journal_menu[] = [ 'Journaux parlés (Radio)', rtb_lurl( '/radio' ), 'Éditions radio' ];
$rtb_nav['journal']['menu'] = $rtb_journal_menu;
$rtb_nav['journal']['url']  = get_post_type_archive_link( 'rtb_emission' ) ?: rtb_lurl( '/emissions' );

/* Émissions : une page par grand rendez-vous (filtre ?prog=). */
$rtb_progs = [
	'Success'            => 'Le magazine de la réussite',
	'Questions Majeures' => 'Le grand débat politique',
	'Santémag'           => 'Le magazine de la santé',
	'Débat de presse'    => "L'analyse de l'actualité",
	'Sport Box'          => "L'actualité du sport",
];
$rtb_emi_menu = [];
foreach ( $rtb_progs as $name => $desc ) {
	$term = get_term_by( 'name', $name, 'rtb_programme' );
	$url  = $term ? get_term_link( $term ) : rtb_lurl( '/programme/' . sanitize_title( $name ) );
	$rtb_emi_menu[] = [ $name, $url, $desc ];
}
$rtb_nav['emissions']['menu'] = $rtb_emi_menu;
$rtb_nav['emissions']['url']  = get_post_type_archive_link( 'rtb_emission' ) ?: rtb_lurl( '/emissions' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>
	/* Thème (clair/sombre) appliqué avant le paint pour éviter le flash. */
	(function () {
		try {
			var t = localStorage.getItem('rtb-theme');
			if (!t) { t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
			if (t === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); }
		} catch (e) {}
	})();
	</script>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="rtb-skip" href="#rtb-main">Aller au contenu principal</a>

<div x-data="{ open: false, search: false }" @keydown.escape.window="open = false; search = false">

	<!-- OVERLAY DE RECHERCHE -->
	<div class="rtb-search-overlay" :class="{ 'is-open': search }" x-cloak>
		<div class="rtb-search-overlay-bar">
			<div class="rtb-container">
				<div class="rtb-search-head">
					<span class="rtb-search-eyebrow"><?php pll_e( 'RECHERCHER SUR RTB' ); ?></span>
					<button class="rtb-search-close" @click="search = false" aria-label="Fermer la recherche">&times;</button>
				</div>
				<form role="search" method="get" action="<?php echo esc_url( rtb_lurl( '/' ) ); ?>" class="rtb-search-form">
					<i class="fa-solid fa-magnifying-glass rtb-search-ico"></i>
					<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Actualité, JT, émission, dossier…" x-ref="searchInput" x-init="$watch('search', v => v && $nextTick(() => $refs.searchInput.focus()))" autocomplete="off" data-rtb-instant>
					<button type="submit" class="rtb-search-go"><?php pll_e( 'Rechercher' ); ?></button>
				</form>
				<div class="rtb-search-suggest">
					<span class="rtb-search-suggest-label"><?php pll_e( 'Recherches fréquentes' ); ?></span>
					<?php
					$rtb_suggest = function_exists( 'rtb_search_trending' )
						? rtb_search_trending( 6 )
						: [ 'Conseil des ministres', 'JT de 20H', 'Coupe du Faso', 'Success', 'Langues nationales', 'Économie' ];
					foreach ( $rtb_suggest as $s ) :
						?>
						<a href="<?php echo esc_url( rtb_lurl( '/?s=' . rawurlencode( $s ) ) ); ?>"><?php echo esc_html( $s ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<div class="rtb-search-backdrop" @click="search = false"></div>
	</div>

	<!-- Bloc supérieur sticky (bandeau + topbar + nav restent visibles au scroll) -->
	<div class="rtb-topbox" id="rtb-topbox">

	<!-- Bandeau tricolore (motif du logo) -->
	<div class="rtb-flag"><span></span><span></span><span></span></div>

	<!-- TOPBAR -->
	<div class="rtb-topbar">
		<div class="rtb-container" x-data="rtbClock">
			<div class="rtb-topbar-left" aria-hidden="true">
				<span class="rtb-topbar-loc"><span class="rtb-dot-green"></span>Ouagadougou</span>
				<span class="rtb-topbar-sep">·</span>
				<span class="rtb-topbar-time" x-text="time">--:--:--</span>
				<span class="rtb-topbar-sep">·</span>
				<span class="rtb-topbar-date" x-text="date"></span>
			</div>
			<div class="rtb-topbar-right">
				<div class="rtb-topbar-socials" data-cs="rtb_contact">
					<?php foreach ( $rtb_socials as $key => $url ) : ?>
						<a class="rtb-soc rtb-soc--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $key ) ); ?>"><?php echo rtb_social_svg( $key ); // phpcs:ignore ?></a>
					<?php endforeach; ?>
				</div>
				<span class="rtb-topbar-sep">|</span>
				<a class="rtb-topbar-link" href="<?php echo esc_url( rtb_lurl( '/contact' ) ); ?>"><?php pll_e( 'Contact' ); ?></a>
				<span class="rtb-topbar-sep">|</span>

				<!-- Sélecteur de langue (Polylang) -->
				<?php
				$rtb_langs    = rtb_languages();
				$rtb_cur_lng  = 'FR';
				$rtb_cur_flag = '🇫🇷';
				foreach ( $rtb_langs as $rl ) {
					if ( $rl['current'] ) {
						$rtb_cur_lng  = strtoupper( $rl['slug'] );
						$rtb_cur_flag = $rl['flag'];
					}
				}
				if ( $rtb_langs ) :
					?>
					<div class="rtb-lang" x-data="{ open: false }" @click.outside="open = false">
						<button class="rtb-lang-btn" @click="open = !open" :aria-expanded="open.toString()">
							<span class="rtb-lang-flag"><?php echo esc_html( $rtb_cur_flag ); ?></span>
							<span><?php echo esc_html( $rtb_cur_lng ); ?></span><i class="fa-solid fa-chevron-down rtb-caret"></i>
						</button>
						<div class="rtb-lang-menu" x-show="open" x-transition.opacity style="display:none">
							<?php foreach ( $rtb_langs as $rl ) : ?>
								<a href="<?php echo esc_url( $rl['url'] ); ?>" class="<?php echo $rl['current'] ? 'is-on' : ''; ?>">
									<span class="rtb-lang-flag"><?php echo esc_html( $rl['flag'] ); ?></span>
									<span><?php echo esc_html( $rl['name'] ); ?></span>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Dark mode -->
				<button class="rtb-theme-btn" onclick="rtbToggleTheme()" aria-label="Changer de thème" title="Mode clair / sombre">
					<svg class="rtb-icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4.2"/><path d="M12 2v2.5M12 19.5V22M4.2 4.2l1.8 1.8M18 18l1.8 1.8M2 12h2.5M19.5 12H22M4.2 19.8 6 18M18 6l1.8-1.8"/></svg>
						<svg class="rtb-icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.8 6.8 0 0 0 9.8 9.8z"/></svg>
				</button>
			</div>
		</div>
	</div>

	<!-- NAV -->
	<header class="rtb-header" id="rtb-header">
		<div class="rtb-container">
			<nav class="rtb-nav" aria-label="Navigation principale">
				<a href="<?php echo esc_url( rtb_lurl( '/' ) ); ?>" class="rtb-logo" rel="home" data-cs="rtb_identity">
					<img src="<?php echo esc_url( rtb_logo_url() ); ?>" alt="<?php bloginfo( 'name' ); ?>">
					<span class="rtb-logo-divider"></span>
					<span class="rtb-logo-text">
						<b data-live="rtb_brand_l1"><?php echo esc_html( onass_mod( 'rtb_brand_l1', 'Radiodiffusion Télévision' ) ); ?></b>
						<span data-live="rtb_brand_l2"><?php echo esc_html( onass_mod( 'rtb_brand_l2', 'DU BURKINA FASO' ) ); ?></span>
					</span>
				</a>

				<div class="rtb-nav-links">
					<?php foreach ( $rtb_nav as $slug => $item ) : ?>
						<?php if ( ! empty( $item['menu'] ) ) : ?>
							<div class="rtb-nav-item" x-data="{ o: false, t: null }" @mouseenter="clearTimeout(t); o = true" @mouseleave="t = setTimeout(() => o = false, 260)">
								<a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo esc_attr( rtb_active( $slug ) ); ?>"<?php echo rtb_active( $slug ) ? ' aria-current="page"' : ''; ?> aria-haspopup="true" :aria-expanded="o.toString()">
									<?php echo esc_html( rtb_t( $item['label'] ) ); ?><i class="fa-solid fa-chevron-down rtb-caret" aria-hidden="true"></i>
								</a>
								<?php $feat = $rtb_features[ $slug ] ?? null; ?>
								<div class="rtb-dropdown<?php echo $feat ? ' rtb-dropdown--mega' : ''; ?>" :class="{ 'is-open': o }" @mouseenter="clearTimeout(t)" @mouseleave="t = setTimeout(() => o = false, 260)" role="menu">
									<div class="rtb-dropdown-links">
										<div class="rtb-dropdown-title"><?php echo esc_html( $item['menu_title'] ); ?></div>
										<?php foreach ( $item['menu'] as $sub ) : ?>
											<a class="rtb-dropdown-link" href="<?php echo esc_url( $sub[1] ); ?>" role="menuitem">
												<span class="rtb-dropdown-link-name"><?php echo esc_html( $sub[0] ); ?></span>
												<span class="rtb-dropdown-link-desc"><?php echo esc_html( $sub[2] ); ?></span>
											</a>
										<?php endforeach; ?>
									</div>
									<?php if ( $feat ) : ?>
										<a class="rtb-dropdown-feature rtb-media-wrap" href="<?php echo esc_url( $item['url'] ); ?>">
											<span class="rtb-media" style="background-image:url('<?php echo esc_url( rtb_cdnize( $feat['cover'], 360, 460 ) ); ?>')"></span>
											<span class="rtb-dropdown-feature-grad"></span>
											<span class="rtb-dropdown-feature-cap">
												<span class="rtb-dropdown-feature-label"><?php echo esc_html( $feat['label'] ); ?></span>
												<span class="rtb-dropdown-feature-title"><?php echo esc_html( $feat['title'] ); ?></span>
											</span>
											<span class="rtb-play rtb-play--sm"><i></i></span>
										</a>
									<?php endif; ?>
								</div>
							</div>
						<?php else : ?>
							<a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo esc_attr( rtb_active( $slug ) ); ?>"<?php echo rtb_active( $slug ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( rtb_t( $item['label'] ) ); ?></a>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>

				<div class="rtb-nav-actions">
					<button class="rtb-search-btn" aria-label="Rechercher" @click="search = true">
						<i class="fa-solid fa-magnifying-glass"></i>
					</button>
					<a href="<?php echo esc_url( rtb_lurl( '/direct' ) ); ?>" class="rtb-live-btn">
						<span class="rtb-live-dot"></span><?php pll_e( 'EN DIRECT' ); ?>
					</a>
					<button class="rtb-hamburger" @click="open = true" aria-label="Menu"><span></span><span></span><span></span></button>
				</div>
			</nav>
		</div>
	</header>

	</div><!-- /rtb-topbox -->

	<!-- TICKER -->
	<div class="rtb-ticker" data-cs="rtb_ticker_sec" role="region" aria-label="<?php echo esc_attr( rtb_t( 'DERNIÈRE MINUTE' ) ); ?>">
		<div class="rtb-ticker-label" aria-hidden="true"><span class="rtb-live-dot"></span><?php pll_e( 'DERNIÈRE MINUTE' ); ?></div>
		<div class="rtb-ticker-track-wrap">
			<div class="rtb-ticker-track">
				<?php
				// Derniers articles (titres cliquables) ; sinon, messages du Customizer.
				$rtb_ticker_items = [];
				$rtb_tq = new WP_Query( [
					'post_type'           => 'post',
					'posts_per_page'      => 6,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				] );
				if ( $rtb_tq->have_posts() ) {
					while ( $rtb_tq->have_posts() ) {
						$rtb_tq->the_post();
						$rtb_ticker_items[] = [ 'title' => get_the_title(), 'url' => get_permalink() ];
					}
					wp_reset_postdata();
				} else {
					foreach ( rtb_ticker_messages() as $msg ) {
						$rtb_ticker_items[] = [ 'title' => $msg, 'url' => '' ];
					}
				}
				// Dupliqué pour la boucle CSS infinie.
				for ( $rep = 0; $rep < 2; $rep++ ) :
					foreach ( $rtb_ticker_items as $it ) :
						if ( $it['url'] ) : ?>
							<a class="rtb-ticker-item" href="<?php echo esc_url( $it['url'] ); ?>"<?php echo $rep > 0 ? ' aria-hidden="true" tabindex="-1"' : ''; ?>><b aria-hidden="true">◆</b><?php echo esc_html( $it['title'] ); ?></a>
						<?php else : ?>
							<span class="rtb-ticker-item"<?php echo $rep > 0 ? ' aria-hidden="true"' : ''; ?>><b aria-hidden="true">◆</b><?php echo esc_html( $it['title'] ); ?></span>
						<?php endif;
					endforeach;
				endfor; ?>
			</div>
		</div>
	</div>

	<!-- DRAWER MOBILE -->
	<div class="rtb-mobile-nav" :class="{ 'is-open': open }">
		<div class="rtb-mobile-head">
			<img src="<?php echo esc_url( rtb_logo_url() ); ?>" alt="<?php bloginfo( 'name' ); ?>">
			<button class="rtb-mobile-close" @click="open = false" aria-label="Fermer">&times;</button>
		</div>
		<nav class="rtb-mobile-links">
			<?php foreach ( $rtb_nav as $slug => $item ) : ?>
				<a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo esc_attr( rtb_active( $slug ) ); ?>"><?php echo esc_html( rtb_t( $item['label'] ) ); ?></a>
			<?php endforeach; ?>
		</nav>
		<a href="<?php echo esc_url( rtb_lurl( '/direct' ) ); ?>" class="rtb-live-btn rtb-mobile-cta"><span class="rtb-live-dot"></span><?php pll_e( 'EN DIRECT' ); ?></a>
	</div>
	<div class="rtb-overlay" :class="{ 'is-visible': open }" @click="open = false"></div>

<main class="rtb-main" id="rtb-main" tabindex="-1">
