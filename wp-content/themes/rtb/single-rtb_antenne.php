<?php
/**
 * Single — page d'une chaîne / antenne (Le Direct).
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$pid    = get_the_ID();
	$mark   = get_post_meta( $pid, 'rtb_mark', true ) ?: mb_substr( get_the_title(), 0, 2 );
	$kind   = get_post_meta( $pid, 'rtb_kind', true ) ?: 'TV';
	$accent = get_post_meta( $pid, 'rtb_accent', true ) ?: '#10A653';
	$now    = get_post_meta( $pid, 'rtb_now', true );
	$freq   = get_post_meta( $pid, 'rtb_freq', true );
	$desc   = wp_strip_all_tags( get_the_content() );
	$cover  = get_the_post_thumbnail_url( $pid, 'rtb-wide' )
		?: ( rtb_cdnize( (string) get_post_meta( $pid, 'rtb_cover_url', true ) ) ?: rtb_img( 'jt-1.png' ) );
	$is_radio = ( 'RADIO' === mb_strtoupper( $kind, 'UTF-8' ) );
	// Direct de la chaîne : Infomaniak si dispo, sinon repli sur la dernière édition YouTube.
	$live_url = $is_radio ? '' : ( get_post_meta( $pid, 'rtb_live_url', true ) ?: rtb_antenne_live( get_the_title() ) );
	$yt       = rtb_latest_video_id();
	$tv_embed = $live_url ?: ( $yt ? 'https://www.youtube-nocookie.com/embed/' . $yt . '?autoplay=1&mute=1&playsinline=1&rel=0&modestbranding=1' : '' );
	?>

	<!-- HERO CHAÎNE -->
	<section class="rtb-chero" style="--ch:<?php echo esc_attr( $accent ); ?>" x-data="{ live:false, src:'' }">
		<div class="rtb-chero-bg"><span class="rtb-media" style="background-image:url('<?php echo esc_url( $cover ); ?>')"></span></div>
		<div class="rtb-container rtb-chero-inner">
			<div class="rtb-breadcrumb rtb-chero-crumb">
				<a href="<?php echo esc_url( rtb_lurl( '/' ) ); ?>"><?php echo esc_html( rtb_t( 'Accueil' ) ); ?></a> &rsaquo;
				<a href="<?php echo esc_url( rtb_lurl( '/direct' ) ); ?>"><?php echo esc_html( rtb_t( 'Le Direct' ) ); ?></a>
			</div>
			<div class="rtb-chero-badges">
				<span class="rtb-chero-mark"><?php echo esc_html( $mark ); ?></span>
				<span class="rtb-chero-kind"><?php echo esc_html( $kind ); ?></span>
				<span class="rtb-chero-live"><span class="rtb-live-dot"></span><?php pll_e( 'EN DIRECT' ); ?></span>
			</div>
			<h1 class="rtb-chero-title"><?php the_title(); ?></h1>
			<?php if ( $desc ) : ?><p class="rtb-chero-desc"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
			<?php if ( $now ) : ?>
				<div class="rtb-chero-now"><span class="rtb-chero-now-label"><?php echo esc_html( rtb_t( "À l'antenne" ) ); ?></span> <?php echo esc_html( $now ); ?></div>
			<?php endif; ?>
			<div class="rtb-chero-actions">
				<?php if ( $is_radio ) : ?>
					<a class="rtb-btn-watch" href="<?php echo esc_url( rtb_lurl( '/radio' ) ); ?>"><span class="rtb-play"><i></i></span> <?php echo esc_html( rtb_t( 'Écouter en direct' ) ); ?></a>
				<?php else : ?>
					<button type="button" class="rtb-btn-watch" @click="src='<?php echo esc_js( $tv_embed ); ?>'; live=true"><span class="rtb-play"><i></i></span> <?php echo esc_html( rtb_t( 'Regarder en direct' ) ); ?></button>
				<?php endif; ?>
				<a class="rtb-btn-ghost" href="<?php echo esc_url( rtb_lurl( '/grille' ) ); ?>"><?php pll_e( 'Guide des programmes' ); ?> <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span></a>
				<?php if ( $freq ) : ?><span class="rtb-chero-freq"><i class="fa-solid fa-radio"></i> <?php echo esc_html( $freq ); ?></span><?php endif; ?>
			</div>
		</div>
		<?php if ( ! $is_radio ) : ?>
		<div class="rtb-chero-player" x-show="live" style="display:none">
			<iframe class="rtb-video-iframe" :src="src" title="<?php echo esc_attr( get_the_title() ); ?> — Direct" allow="autoplay; fullscreen; encrypted-media" allowfullscreen></iframe>
			<button type="button" class="rtb-chero-close" @click="live=false; src=''" aria-label="<?php echo esc_attr( rtb_t( 'Fermer le direct' ) ); ?>">&times;</button>
		</div>
		<?php endif; ?>
	</section>

	<?php
	// Replays / programmes de la chaîne (émissions diffusées par cette chaîne).
	$emi = new WP_Query( [
		'post_type'      => 'rtb_emission',
		'posts_per_page' => 6,
		'meta_query'     => [ [ 'key' => 'rtb_by', 'value' => get_the_title(), 'compare' => '=' ] ],
	] );
	// fallback : si rien pour cette chaîne, derniers replays généraux
	if ( ! $emi->have_posts() ) {
		$emi = new WP_Query( [ 'post_type' => 'rtb_emission', 'posts_per_page' => 6 ] );
	}
	?>
	<section class="rtb-container rtb-section">
		<div class="rtb-sec-head">
			<h2 class="rtb-eyebrow rtb-eyebrow--red" style="margin:0"><i></i><span><?php echo esc_html( rtb_t( 'REPLAYS & PROGRAMMES' ) ); ?></span></h2>
			<a class="rtb-sec-more" href="<?php echo esc_url( rtb_lurl( '/emissions' ) ); ?>"><?php pll_e( 'Toutes les émissions' ); ?> <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span></a>
		</div>
		<div class="rtb-grid-3">
			<?php while ( $emi->have_posts() ) : $emi->the_post(); get_template_part( 'parts/card', 'emission' ); endwhile; wp_reset_postdata(); ?>
		</div>
	</section>

	<?php
	// Autres chaînes
	$others = get_posts( [ 'post_type' => 'rtb_antenne', 'numberposts' => 6, 'exclude' => [ $pid ], 'orderby' => 'menu_order', 'order' => 'ASC' ] );
	if ( $others ) :
		?>
		<section class="rtb-band">
			<div class="rtb-container rtb-section">
				<h2 class="rtb-eyebrow rtb-eyebrow--green" style="margin-top:0"><i></i><span><?php echo esc_html( rtb_t( 'AUTRES ANTENNES' ) ); ?></span></h2>
				<div class="rtb-grid-5 rtb-channels">
					<?php
					foreach ( $others as $o ) :
						$o_accent = get_post_meta( $o->ID, 'rtb_accent', true ) ?: '#10A653';
						$o_cover  = get_the_post_thumbnail_url( $o->ID, 'rtb-card' )
							?: ( rtb_cdnize( (string) get_post_meta( $o->ID, 'rtb_cover_url', true ) ) ?: rtb_img( 'jt-1.png' ) );
						?>
						<a class="rtb-chcard" href="<?php echo esc_url( get_permalink( $o ) ); ?>" style="--ch:<?php echo esc_attr( $o_accent ); ?>">
							<span class="rtb-media" style="background-image:url('<?php echo esc_url( $o_cover ); ?>')"></span>
							<span class="rtb-chcard-grad"></span>
							<span class="rtb-chcard-head">
								<span class="rtb-chcard-mark"><?php echo esc_html( get_post_meta( $o->ID, 'rtb_mark', true ) ); ?></span>
								<span class="rtb-chcard-kind"><?php echo esc_html( get_post_meta( $o->ID, 'rtb_kind', true ) ); ?></span>
							</span>
							<span class="rtb-chcard-foot">
								<span class="rtb-chcard-live"><span class="rtb-live-dot"></span><?php pll_e( 'EN DIRECT' ); ?></span>
								<span class="rtb-chcard-name"><?php echo esc_html( $o->post_title ); ?></span>
								<span class="rtb-chcard-now"><?php echo esc_html( get_post_meta( $o->ID, 'rtb_now', true ) ); ?></span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
endwhile;

get_footer();
