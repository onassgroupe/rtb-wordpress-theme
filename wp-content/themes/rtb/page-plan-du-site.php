<?php
/**
 * Page « Plan du site » — généré depuis la BD.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$cats  = get_categories( [ 'hide_empty' => false, 'orderby' => 'name', 'exclude' => [ (int) get_option( 'default_category' ) ] ] );
$ann   = rtb_get_antennes();
$eterms = get_terms( [ 'taxonomy' => 'rtb_emission_cat', 'hide_empty' => true ] );
?>
<div class="rtb-page-head">
	<div class="rtb-container">
		<div class="rtb-eyebrow rtb-eyebrow--green"><i></i><span><?php echo esc_html( rtb_t( 'PLAN DU SITE' ) ); ?></span></div>
		<h1><?php echo esc_html( rtb_t( 'Plan du site' ) ); ?></h1>
		<p class="rtb-page-lead"><?php echo esc_html( rtb_t( "Toutes les sections du site de la RTB en un coup d'œil." ) ); ?></p>
	</div>
</div>

<section class="rtb-container rtb-section">
	<div class="rtb-sitemap">

		<div class="rtb-sitemap-col">
			<h2><?php echo esc_html( rtb_t( 'Le site' ) ); ?></h2>
			<ul>
				<li><a href="<?php echo esc_url( rtb_lurl( '/' ) ); ?>"><?php echo esc_html( rtb_t( 'Accueil' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( rtb_lurl( '/direct' ) ); ?>"><?php echo esc_html( rtb_t( 'Le Direct' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( rtb_lurl( '/radio' ) ); ?>"><?php echo esc_html( rtb_t( 'Radio en direct' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( rtb_lurl( '/grille' ) ); ?>"><?php echo esc_html( rtb_t( 'Grille des programmes' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'rtb_emission' ) ); ?>"><?php echo esc_html( rtb_t( 'Émissions & vidéos' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( rtb_lurl( '/a-propos' ) ); ?>"><?php echo esc_html( rtb_t( 'À propos' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( rtb_lurl( '/contact' ) ); ?>"><?php echo esc_html( rtb_t( 'Contact' ) ); ?></a></li>
			</ul>
		</div>

		<div class="rtb-sitemap-col">
			<h2><?php echo esc_html( rtb_t( 'Nos antennes' ) ); ?></h2>
			<ul>
				<?php foreach ( $ann as $a ) : ?>
					<li><a href="<?php echo esc_url( $a['permalink'] ?? rtb_lurl( '/direct' ) ); ?>"><?php echo esc_html( $a['name'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="rtb-sitemap-col">
			<h2><?php echo esc_html( rtb_t( 'Rubriques' ) ); ?></h2>
			<ul>
				<?php foreach ( $cats as $c ) : ?>
					<li><a href="<?php echo esc_url( get_category_link( $c->term_id ) ); ?>"><?php echo esc_html( $c->name ); ?> <span class="rtb-sitemap-count"><?php echo esc_html( number_format_i18n( $c->count ) ); ?></span></a></li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="rtb-sitemap-col">
			<h2><?php echo esc_html( rtb_t( 'Journaux & émissions' ) ); ?></h2>
			<ul>
				<?php if ( $eterms && ! is_wp_error( $eterms ) ) : foreach ( $eterms as $t ) : ?>
					<li><a href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php echo esc_html( $t->name ); ?> <span class="rtb-sitemap-count"><?php echo esc_html( number_format_i18n( $t->count ) ); ?></span></a></li>
				<?php endforeach; endif; ?>
			</ul>
			<h2 style="margin-top:24px"><?php echo esc_html( rtb_t( 'Informations légales' ) ); ?></h2>
			<ul>
				<li><a href="<?php echo esc_url( rtb_lurl( '/mentions-legales' ) ); ?>"><?php echo esc_html( rtb_t( 'Mentions légales' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( rtb_lurl( '/politique-de-confidentialite' ) ); ?>"><?php echo esc_html( rtb_t( 'Politique de confidentialité' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( rtb_lurl( '/conditions-utilisation' ) ); ?>"><?php echo esc_html( rtb_t( "Conditions d'utilisation" ) ); ?></a></li>
				<li><a href="<?php echo esc_url( rtb_lurl( '/accessibilite' ) ); ?>"><?php echo esc_html( rtb_t( 'Accessibilité' ) ); ?></a></li>
			</ul>
		</div>

	</div>
</section>

<?php
get_footer();
