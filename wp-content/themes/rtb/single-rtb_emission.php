<?php
/**
 * Single — émission / replay vidéo (contextualisé par type).
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$pid = get_the_ID();

	$catterms = get_the_terms( $pid, 'rtb_emission_cat' );
	$catterm  = ( $catterms && ! is_wp_error( $catterms ) ) ? $catterms[0] : null;
	$cat      = $catterm ? $catterm->name : 'Vidéo';

	$progterms = get_the_terms( $pid, 'rtb_programme' );
	$prog      = ( $progterms && ! is_wp_error( $progterms ) ) ? $progterms[0] : null;

	$dur   = get_post_meta( $pid, 'rtb_dur', true );
	$by    = get_post_meta( $pid, 'rtb_by', true ) ?: 'RTB Multimédia';
	$date  = get_post_meta( $pid, 'rtb_human_date', true ) ?: get_the_date( 'j F Y' );
	$cover = rtb_post_cover( $pid, 'rtb-wide' );
	$video = get_post_meta( $pid, 'rtb_video', true );

	// Type → accent + libellé + couleur d'eyebrow.
	$is_jt  = ( false !== mb_stripos( $cat, 'JT' ) || false !== mb_stripos( $cat, 'journal' ) );
	$is_mag = ( false !== mb_stripos( $cat, 'Magazine' ) );
	$accent = $is_jt ? '#E70C2F' : ( $is_mag ? '#C9A227' : '#10A653' );
	$type   = $is_jt ? 'Journal télévisé' : ( $is_mag ? 'Magazine' : 'Émission' );
	?>
	<div class="rtb-page-head" style="border-bottom:3px solid <?php echo esc_attr( $accent ); ?>">
		<div class="rtb-container">
			<div class="rtb-breadcrumb">
				<a href="<?php echo esc_url( rtb_lurl( '/' ) ); ?>"><?php echo esc_html( rtb_t( 'Accueil' ) ); ?></a> &rsaquo;
				<a href="<?php echo esc_url( get_post_type_archive_link( 'rtb_emission' ) ); ?>"><?php echo esc_html( rtb_t( 'Émissions' ) ); ?></a>
				<?php if ( $catterm ) : ?> &rsaquo; <a href="<?php echo esc_url( get_term_link( $catterm ) ); ?>"><?php echo esc_html( $cat ); ?></a><?php endif; ?>
			</div>
			<div class="rtb-eyebrow"><i style="background:<?php echo esc_attr( $accent ); ?>"></i><span style="color:<?php echo esc_attr( $accent ); ?>"><?php echo esc_html( mb_strtoupper( $type, 'UTF-8' ) ); ?></span></div>
			<h1><?php the_title(); ?></h1>
		</div>
	</div>

	<article class="rtb-article">
		<?php if ( $video ) : ?>
			<div class="rtb-video" x-data="{ play: false }">
				<button type="button" class="rtb-video-facade" :class="{ 'is-playing': play }" @click="play = true" style="background-image:url('<?php echo esc_url( $cover ); ?>')" aria-label="<?php echo esc_attr( rtb_t( 'Regarder la vidéo' ) ); ?>">
					<span class="rtb-play rtb-play--lg" style="background:<?php echo esc_attr( $accent ); ?>;border-color:rgba(255,255,255,.7)"><i></i></span>
					<span class="rtb-video-yt"><i class="fa-brands fa-youtube" aria-hidden="true"></i> <?php echo esc_html( rtb_t( 'Regarder la vidéo' ) ); ?></span>
				</button>
				<template x-if="play">
					<iframe class="rtb-video-iframe" src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr( $video ); ?>?autoplay=1&rel=0&modestbranding=1" title="<?php echo esc_attr( get_the_title() ); ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>
				</template>
			</div>
		<?php else : ?>
			<div class="rtb-article-cover" style="background-image:url('<?php echo esc_url( $cover ); ?>')">
				<span class="rtb-play rtb-play--lg" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:<?php echo esc_attr( $accent ); ?>"><i></i></span>
			</div>
		<?php endif; ?>
		<div class="rtb-article-meta">
			<span class="rtb-chip" style="background:<?php echo esc_attr( $accent ); ?>;color:<?php echo $is_mag ? '#161310' : '#fff'; ?>"><?php echo esc_html( $cat ); ?></span>
			<span style="color:var(--green-text);font-weight:600"><?php echo esc_html( $by ); ?></span>
			· <?php echo esc_html( $date ); ?>
			<?php if ( $dur ) : ?> · <?php echo esc_html( $dur ); ?><?php endif; ?>
			<?php if ( $prog ) : ?> · <?php echo esc_html( rtb_t( 'Programme :' ) ); ?> <a href="<?php echo esc_url( get_term_link( $prog ) ); ?>"><strong><?php echo esc_html( $prog->name ); ?></strong></a><?php endif; ?>
		</div>
		<div class="rtb-article-body"><?php the_content(); ?></div>
	</article>

	<?php
	// À voir aussi — priorité : même programme, sinon même édition, sinon récents.
	$rel_args = [ 'post_type' => 'rtb_emission', 'posts_per_page' => 3, 'post__not_in' => [ $pid ] ];
	if ( $prog ) {
		$rel_args['tax_query'] = [ [ 'taxonomy' => 'rtb_programme', 'field' => 'term_id', 'terms' => $prog->term_id ] ];
		$rel_title = rtb_t( 'Dans le même programme' );
	} elseif ( $catterm ) {
		$rel_args['tax_query'] = [ [ 'taxonomy' => 'rtb_emission_cat', 'field' => 'term_id', 'terms' => $catterm->term_id ] ];
		$rel_title = $is_jt ? rtb_t( 'Autres éditions' ) : rtb_t( 'À voir aussi' );
	} else {
		$rel_title = rtb_t( 'À voir aussi' );
	}
	$rtb_related = new WP_Query( $rel_args );
	if ( ! $rtb_related->have_posts() ) {
		wp_reset_postdata();
		$rtb_related = new WP_Query( [ 'post_type' => 'rtb_emission', 'posts_per_page' => 3, 'post__not_in' => [ $pid ] ] );
		$rel_title = rtb_t( 'À voir aussi' );
	}
	if ( $rtb_related->have_posts() ) :
		?>
		<section class="rtb-container rtb-section" style="padding-top:8px">
			<h2 class="rtb-eyebrow rtb-eyebrow--green" style="margin-top:0"><i></i><span><?php echo esc_html( mb_strtoupper( $rel_title, 'UTF-8' ) ); ?></span></h2>
			<div class="rtb-grid-3">
				<?php while ( $rtb_related->have_posts() ) : $rtb_related->the_post(); get_template_part( 'parts/card', 'emission' ); endwhile; ?>
			</div>
		</section>
		<?php
		wp_reset_postdata();
	endif;
	?>
	<?php
endwhile;

get_footer();
