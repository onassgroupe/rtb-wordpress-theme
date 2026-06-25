<?php
/**
 * Single — page d'une région.
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$pid    = get_the_ID();
	$city   = get_post_meta( $pid, 'rtb_city', true );
	$role   = get_post_meta( $pid, 'rtb_role', true );
	$accent = get_post_meta( $pid, 'rtb_accent', true ) ?: '#10A653';

	// Antenne liée (RTB Guiriko pour l'Ouest, sinon RTB Télévision).
	$ant_url = rtb_lurl( '/direct' );
	$ant_name = 'RTB Télévision';
	foreach ( rtb_get_antennes() as $a ) {
		if ( false !== mb_stripos( get_the_title(), 'Hauts-Bassins' ) && false !== mb_stripos( $a['name'], 'Guiriko' ) ) {
			$ant_url = $a['permalink'] ?? $ant_url; $ant_name = $a['name'];
		}
	}
	?>
	<section class="rtb-chero" style="--ch:<?php echo esc_attr( $accent ); ?>">
		<div class="rtb-chero-bg"><span class="rtb-media" style="background-image:url('<?php echo esc_url( rtb_cdnize( 'https://www.rtb.bf/wp-content/uploads/2026/06/vlcsnap-2026-06-20-21h18m47s439.png' ) ); ?>')"></span></div>
		<div class="rtb-container rtb-chero-inner">
			<div class="rtb-breadcrumb rtb-chero-crumb">
				<a href="<?php echo esc_url( rtb_lurl( '/' ) ); ?>"><?php echo esc_html( rtb_t( 'Accueil' ) ); ?></a> &rsaquo;
				<a href="<?php echo esc_url( rtb_lurl( '/regions' ) ); ?>"><?php echo esc_html( rtb_t( 'Régions' ) ); ?></a>
			</div>
			<div class="rtb-chero-badges">
				<span class="rtb-chero-kind"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?php echo esc_html( rtb_t( 'RÉGION' ) ); ?></span>
			</div>
			<h1 class="rtb-chero-title"><?php the_title(); ?></h1>
			<p class="rtb-chero-desc"><strong><?php echo esc_html( rtb_t( 'Chef-lieu :' ) ); ?> <?php echo esc_html( $city ); ?></strong> — <?php echo esc_html( $role ); ?></p>
			<div class="rtb-chero-actions">
				<a class="rtb-btn-watch" href="<?php echo esc_url( $ant_url ); ?>"><span class="rtb-play"><i></i></span> <?php echo esc_html( rtb_t( 'Suivre' ) ); ?> <?php echo esc_html( $ant_name ); ?></a>
				<a class="rtb-btn-ghost" href="<?php echo esc_url( rtb_lurl( '/category/societe' ) ); ?>"><?php echo esc_html( rtb_t( 'Actualité régionale' ) ); ?> <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span></a>
			</div>
		</div>
	</section>

	<section class="rtb-container rtb-section">
		<div class="rtb-about-grid">
			<div class="rtb-article-body">
				<h2><?php echo esc_html( rtb_t( 'La RTB dans la région' ) ); ?></h2>
				<?php the_content(); ?>
				<p><?php echo esc_html( rtb_t( "La rédaction de la RTB assure une couverture de proximité de l'actualité régionale : vie locale, développement, culture et événements, en français et dans les langues nationales." ) ); ?></p>
			</div>
			<aside class="rtb-about-figures">
				<div class="rtb-figure"><span class="rtb-figure-n" style="font-size:18px;line-height:1.2"><?php echo esc_html( $city ); ?></span><span class="rtb-figure-l"><?php echo esc_html( rtb_t( 'Chef-lieu' ) ); ?></span></div>
				<div class="rtb-figure"><span class="rtb-figure-n"><i class="fa-solid fa-tower-broadcast"></i></span><span class="rtb-figure-l"><?php echo esc_html( rtb_t( 'Couverture TV & radio' ) ); ?></span></div>
			</aside>
		</div>
	</section>

	<?php
	$others = get_posts( [ 'post_type' => 'rtb_region', 'numberposts' => 12, 'exclude' => [ $pid ], 'orderby' => 'menu_order', 'order' => 'ASC' ] );
	if ( $others ) :
		?>
		<section class="rtb-band">
			<div class="rtb-container rtb-section">
				<div class="rtb-eyebrow rtb-eyebrow--green"><i></i><span><?php echo esc_html( rtb_t( 'AUTRES RÉGIONS' ) ); ?></span></div>
				<div class="rtb-regions">
					<?php
					$acc = [ '#10A653', '#F5DE00', '#E70C2F', '#0B7A3B' ];
					foreach ( $others as $i => $o ) :
						?>
						<a class="rtb-region" href="<?php echo esc_url( get_permalink( $o ) ); ?>" style="border-left-color:<?php echo esc_attr( get_post_meta( $o->ID, 'rtb_accent', true ) ?: $acc[ $i % 4 ] ); ?>">
							<span class="rtb-region-name"><?php echo esc_html( $o->post_title ); ?></span>
							<span class="rtb-region-city"><?php echo esc_html( get_post_meta( $o->ID, 'rtb_city', true ) ); ?></span>
							<span class="rtb-region-role"><?php echo esc_html( get_post_meta( $o->ID, 'rtb_role', true ) ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
endwhile;

get_footer();
