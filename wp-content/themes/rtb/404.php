<?php
/**
 * 404 — page introuvable. Pensée comme une page utile : recherche, accès rapides,
 * derniers articles et replays, pour ramener le visiteur vers le contenu.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$rtb_404_links = array(
	array( '/direct',          'fa-tower-broadcast', 'Le Direct' ),
	array( '/category/infos',  'fa-newspaper',       'Actualités' ),
	array( '/emissions',       'fa-photo-film',      'Émissions' ),
	array( '/category/sport',  'fa-futbol',          'Sport' ),
	array( '/radio',           'fa-radio',           'Radio' ),
	array( '/regions',         'fa-location-dot',    'Régions' ),
);
?>
<div class="rtb-page-head">
	<div class="rtb-container">
		<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span>ERREUR 404</span></div>
		<h1>Cette page est introuvable.</h1>
		<p style="color:var(--text-soft);max-width:600px;margin:12px 0 0">La page que vous cherchez n'existe pas, a été déplacée ou a changé d'adresse. Lancez une recherche ou explorez les rubriques ci-dessous.</p>
	</div>
</div>

<div class="rtb-404">

	<div class="rtb-404-tools">
		<?php get_search_form(); ?>
		<div class="rtb-404-cta">
			<a class="rtb-live-btn" style="background:var(--rtb-green-dark)" href="<?php echo esc_url( rtb_lurl( '/' ) ); ?>"><i class="fa-solid fa-house" aria-hidden="true"></i> Accueil</a>
			<a class="rtb-live-btn" href="<?php echo esc_url( rtb_lurl( '/direct' ) ); ?>"><span class="rtb-live-dot" aria-hidden="true"></span> En direct</a>
		</div>
	</div>

	<nav class="rtb-404-nav" aria-label="Accès rapides">
		<?php foreach ( $rtb_404_links as $l ) : ?>
			<a href="<?php echo esc_url( rtb_lurl( $l[0] ) ); ?>"><i class="fa-solid <?php echo esc_attr( $l[1] ); ?>" aria-hidden="true"></i><?php echo esc_html( $l[2] ); ?></a>
		<?php endforeach; ?>
	</nav>

	<?php
	$rtb_404_posts = new WP_Query( array(
		'post_type'           => 'post',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
	if ( $rtb_404_posts->have_posts() ) : ?>
		<div class="rtb-sec-head">
			<h2 class="rtb-eyebrow rtb-eyebrow--red"><i></i><span>DERNIERS ARTICLES</span></h2>
			<a class="rtb-link-more" href="<?php echo esc_url( rtb_lurl( '/category/infos' ) ); ?>">Toute l'actualité →</a>
		</div>
		<div class="rtb-archive-grid">
			<?php while ( $rtb_404_posts->have_posts() ) : $rtb_404_posts->the_post(); get_template_part( 'parts/card', 'post' ); endwhile; ?>
		</div>
	<?php endif; wp_reset_postdata(); ?>

	<?php
	$rtb_404_emissions = new WP_Query( array(
		'post_type'           => 'rtb_emission',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
	if ( $rtb_404_emissions->have_posts() ) : ?>
		<div class="rtb-sec-head">
			<h2 class="rtb-eyebrow rtb-eyebrow--green"><i></i><span>À REVOIR SUR RTB</span></h2>
			<a class="rtb-link-more" href="<?php echo esc_url( rtb_lurl( '/emissions' ) ); ?>">Tous les replays →</a>
		</div>
		<div class="rtb-archive-grid">
			<?php while ( $rtb_404_emissions->have_posts() ) : $rtb_404_emissions->the_post(); get_template_part( 'parts/card', 'emission' ); endwhile; ?>
		</div>
	<?php endif; wp_reset_postdata(); ?>

</div>
<?php
get_footer();
