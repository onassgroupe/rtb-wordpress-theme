<?php
/**
 * Page « Régions » — la RTB sur le territoire.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$regions = rtb_get_regions();

// Lien vers la chaîne RTB Guiriko si disponible.
$guiriko = home_url( '/direct' );
foreach ( rtb_get_antennes() as $a ) {
	if ( false !== mb_stripos( $a['name'], 'Guiriko' ) ) {
		$guiriko = $a['permalink'] ?? $guiriko;
	}
}
?>
<div class="rtb-page-head">
	<div class="rtb-container">
		<div class="rtb-eyebrow rtb-eyebrow--green"><i></i><span>PROXIMITÉ</span></div>
		<h1>La RTB en régions</h1>
		<p class="rtb-page-lead">Présente sur l'ensemble du territoire, la RTB couvre l'actualité de toutes les régions du Burkina Faso et porte la voix de la proximité.</p>
	</div>
</div>

<section class="rtb-container rtb-section">
	<div class="rtb-regions">
		<?php foreach ( $regions as $r ) : ?>
			<a class="rtb-region" href="<?php echo esc_url( $r['permalink'] ?? home_url( '/regions' ) ); ?>" style="border-left-color:<?php echo esc_attr( $r['accent'] ?? 'var(--rtb-green)' ); ?>">
				<span class="rtb-region-name"><?php echo esc_html( $r['name'] ); ?></span>
				<span class="rtb-region-city"><?php echo esc_html( $r['city'] ); ?></span>
				<span class="rtb-region-role"><?php echo esc_html( $r['role'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>

<section class="rtb-band">
	<div class="rtb-container rtb-section" style="text-align:center">
		<div class="rtb-eyebrow rtb-eyebrow--red" style="justify-content:center"><i></i><span>ANTENNE DE L'OUEST</span></div>
		<h2 style="font-size:28px;margin:6px 0 12px">RTB Guiriko, la proximité au cœur des Hauts-Bassins</h2>
		<p class="rtb-page-lead" style="margin:0 auto 24px">Depuis Bobo-Dioulasso, RTB Guiriko informe et accompagne les populations de l'Ouest du pays.</p>
		<a class="rtb-btn-watch" href="<?php echo esc_url( $guiriko ); ?>" style="text-decoration:none"><span class="rtb-play"><i></i></span> Découvrir RTB Guiriko</a>
	</div>
</section>

<?php
get_footer();
