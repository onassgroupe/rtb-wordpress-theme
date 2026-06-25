<?php
/**
 * Page « À propos de la RTB ». Contenu éditable via le Customizer (section « À propos »).
 */
defined( 'ABSPATH' ) || exit;

get_header();

$figures = [
	[ 'n' => '4',    'l' => 'Antennes TV & radio', 'i' => 'fa-tower-broadcast', 'c' => '#E70C2F' ],
	[ 'n' => '13',   'l' => 'Régions couvertes',    'i' => 'fa-location-dot',    'c' => '#0B7A3B' ],
	[ 'n' => '24/7', 'l' => 'Diffusion en continu', 'i' => 'fa-satellite-dish',  'c' => '#C9A227' ],
	[ 'n' => '1963', 'l' => 'Année de création',    'i' => 'fa-flag',            'c' => '#E70C2F' ],
];
$antennes = rtb_get_antennes();

/** Découpe un réglage multi-lignes en tableau de colonnes (séparateur « | »). */
function rtb_about_lines( string $key, string $default = '' ): array {
	$raw  = (string) onass_mod( $key, $default );
	$rows = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) { continue; }
		$rows[] = array_map( 'trim', explode( '|', $line ) );
	}
	return $rows;
}

$rtb_def     = function_exists( 'rtb_about_defaults' ) ? rtb_about_defaults() : array();
$rtb_mission = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) onass_mod( 'rtb_about_mission', $rtb_def['mission'] ?? '' ) ) ) );
$rtb_values  = rtb_about_lines( 'rtb_about_values', $rtb_def['values'] ?? '' );
$rtb_history = rtb_about_lines( 'rtb_about_history', $rtb_def['history'] ?? '' );
$rtb_awards  = rtb_about_lines( 'rtb_about_awards', $rtb_def['awards'] ?? '' );
$rtb_team    = rtb_about_lines( 'rtb_about_team', $rtb_def['team'] ?? '' );
?>
<div class="rtb-page-head">
	<div class="rtb-container">
		<div class="rtb-eyebrow rtb-eyebrow--green"><i></i><span>À PROPOS</span></div>
		<h1>La Radiodiffusion Télévision du Burkina</h1>
		<p class="rtb-page-lead"><?php echo esc_html( onass_mod( 'rtb_baseline', "Société publique de radiotélévision du Burkina Faso, la RTB informe, éduque et divertit, au service de l'information et de la proximité." ) ); ?></p>
	</div>
</div>

<section class="rtb-container rtb-section">
	<div class="rtb-about-grid">
		<div class="rtb-article-body" data-cs="rtb_about">
			<h2>Notre mission</h2>
			<?php foreach ( $rtb_mission as $para ) : ?>
				<p><?php echo esc_html( $para ); ?></p>
			<?php endforeach; ?>
			<h2>Nos valeurs</h2>
			<ul class="rtb-values">
				<?php foreach ( $rtb_values as $v ) : ?>
					<li><i class="fa-solid fa-check" aria-hidden="true"></i> <?php echo esc_html( $v[0] ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<aside class="rtb-about-figures">
			<?php foreach ( $figures as $f ) : ?>
				<div class="rtb-figure" style="--fc:<?php echo esc_attr( $f['c'] ); ?>">
					<span class="rtb-figure-ico"><i class="fa-solid <?php echo esc_attr( $f['i'] ); ?>" aria-hidden="true"></i></span>
					<span class="rtb-figure-n"><?php echo esc_html( $f['n'] ); ?></span>
					<span class="rtb-figure-l"><?php echo esc_html( $f['l'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</aside>
	</div>
</section>

<?php if ( $rtb_history ) : ?>
<section class="rtb-band">
	<div class="rtb-container rtb-section" data-cs="rtb_about">
		<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span>NOTRE HISTOIRE</span></div>
		<h2 class="rtb-sec-h">Plus de 60 ans au service du public</h2>
		<ol class="rtb-timeline">
			<?php foreach ( $rtb_history as $h ) : ?>
				<li class="rtb-tl-item">
					<span class="rtb-tl-year"><?php echo esc_html( $h[0] ); ?></span>
					<span class="rtb-tl-text"><?php echo esc_html( $h[1] ?? '' ); ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
<?php endif; ?>

<?php if ( $rtb_awards ) : ?>
<section class="rtb-container rtb-section" data-cs="rtb_about">
	<div class="rtb-eyebrow rtb-eyebrow--yellow"><i></i><span>RÉCOMPENSES & DISTINCTIONS</span></div>
	<h2 class="rtb-sec-h">Un travail reconnu</h2>
	<div class="rtb-awards">
		<?php foreach ( $rtb_awards as $a ) : ?>
			<div class="rtb-award">
				<span class="rtb-award-ico"><i class="fa-solid fa-trophy" aria-hidden="true"></i></span>
				<span class="rtb-award-year"><?php echo esc_html( $a[0] ); ?></span>
				<h3><?php echo esc_html( $a[1] ?? '' ); ?></h3>
				<p><?php echo esc_html( $a[2] ?? '' ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<?php if ( $rtb_team ) :
	$rtb_root     = $rtb_team[0];
	$rtb_branches = array_slice( $rtb_team, 1 );
	?>
<section class="rtb-band">
	<div class="rtb-container rtb-section" data-cs="rtb_about" style="text-align:center">
		<div class="rtb-eyebrow rtb-eyebrow--green" style="justify-content:center"><i></i><span>LA DIRECTION</span></div>
		<h2 class="rtb-sec-h">Organisation de la RTB</h2>
		<div class="rtb-org">
			<div class="rtb-org-root">
				<b><?php echo esc_html( $rtb_root[0] ); ?></b>
				<?php if ( ! empty( $rtb_root[1] ) ) : ?><span><?php echo esc_html( $rtb_root[1] ); ?></span><?php endif; ?>
			</div>
			<?php if ( $rtb_branches ) : ?>
				<div class="rtb-org-trunk"></div>
				<div class="rtb-org-branches">
					<?php foreach ( $rtb_branches as $b ) : ?>
						<div class="rtb-org-node">
							<b><?php echo esc_html( $b[0] ); ?></b>
							<?php if ( ! empty( $b[1] ) ) : ?><span><?php echo esc_html( $b[1] ); ?></span><?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="rtb-band">
	<div class="rtb-container rtb-section">
		<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span>NOS ANTENNES</span></div>
		<div class="rtb-grid-5 rtb-channels">
			<?php foreach ( $antennes as $c ) : ?>
				<a class="rtb-chcard" href="<?php echo esc_url( $c['permalink'] ?? rtb_lurl( '/direct' ) ); ?>" style="--ch:<?php echo esc_attr( $c['accent'] ); ?>">
					<span class="rtb-media" style="background-image:url('<?php echo esc_url( $c['cover'] ); ?>')"></span>
					<span class="rtb-chcard-grad"></span>
					<span class="rtb-chcard-head">
						<span class="rtb-chcard-mark"><?php echo esc_html( $c['mark'] ); ?></span>
						<span class="rtb-chcard-kind"><?php echo esc_html( $c['kind'] ); ?></span>
					</span>
					<span class="rtb-chcard-foot">
						<span class="rtb-chcard-name"><?php echo esc_html( $c['name'] ); ?></span>
						<span class="rtb-chcard-now"><?php echo esc_html( $c['desc'] ); ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="rtb-container rtb-section" style="text-align:center">
	<h2 style="font-size:28px;margin-bottom:12px">Une question, un partenariat ?</h2>
	<p class="rtb-page-lead" style="margin:0 auto 24px">La rédaction et les services de la RTB sont à votre écoute.</p>
	<a class="rtb-btn-watch" href="<?php echo esc_url( rtb_lurl( '/contact' ) ); ?>" style="text-decoration:none"><i class="fa-solid fa-envelope" style="margin-right:4px"></i> Nous contacter</a>
</section>

<?php
get_footer();
