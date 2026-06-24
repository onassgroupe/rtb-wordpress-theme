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
		<div class="rtb-eyebrow rtb-eyebrow--green"><i></i><span>PLAN DU SITE</span></div>
		<h1>Plan du site</h1>
		<p class="rtb-page-lead">Toutes les sections du site de la RTB en un coup d'œil.</p>
	</div>
</div>

<section class="rtb-container rtb-section">
	<div class="rtb-sitemap">

		<div class="rtb-sitemap-col">
			<h2>Le site</h2>
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li>
				<li><a href="<?php echo esc_url( home_url( '/direct' ) ); ?>">Le Direct</a></li>
				<li><a href="<?php echo esc_url( home_url( '/radio' ) ); ?>">Radio en direct</a></li>
				<li><a href="<?php echo esc_url( home_url( '/grille' ) ); ?>">Grille des programmes</a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'rtb_emission' ) ); ?>">Émissions & vidéos</a></li>
				<li><a href="<?php echo esc_url( home_url( '/a-propos' ) ); ?>">À propos</a></li>
				<li><a href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Contact</a></li>
			</ul>
		</div>

		<div class="rtb-sitemap-col">
			<h2>Nos antennes</h2>
			<ul>
				<?php foreach ( $ann as $a ) : ?>
					<li><a href="<?php echo esc_url( $a['permalink'] ?? home_url( '/direct' ) ); ?>"><?php echo esc_html( $a['name'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="rtb-sitemap-col">
			<h2>Rubriques</h2>
			<ul>
				<?php foreach ( $cats as $c ) : ?>
					<li><a href="<?php echo esc_url( get_category_link( $c->term_id ) ); ?>"><?php echo esc_html( $c->name ); ?> <span class="rtb-sitemap-count"><?php echo esc_html( number_format_i18n( $c->count ) ); ?></span></a></li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="rtb-sitemap-col">
			<h2>Journaux & émissions</h2>
			<ul>
				<?php if ( $eterms && ! is_wp_error( $eterms ) ) : foreach ( $eterms as $t ) : ?>
					<li><a href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php echo esc_html( $t->name ); ?> <span class="rtb-sitemap-count"><?php echo esc_html( number_format_i18n( $t->count ) ); ?></span></a></li>
				<?php endforeach; endif; ?>
			</ul>
			<h2 style="margin-top:24px">Informations légales</h2>
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/mentions-legales' ) ); ?>">Mentions légales</a></li>
				<li><a href="<?php echo esc_url( home_url( '/politique-de-confidentialite' ) ); ?>">Politique de confidentialité</a></li>
				<li><a href="<?php echo esc_url( home_url( '/conditions-utilisation' ) ); ?>">Conditions d'utilisation</a></li>
				<li><a href="<?php echo esc_url( home_url( '/accessibilite' ) ); ?>">Accessibilité</a></li>
			</ul>
		</div>

	</div>
</section>

<?php
get_footer();
