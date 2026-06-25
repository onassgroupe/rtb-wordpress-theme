<?php
/**
 * Sidebar d'article : partage, réseaux (social proof), liés, récents.
 */
defined( 'ABSPATH' ) || exit;

$rtb_pid   = get_the_ID();
$rtb_url   = rawurlencode( get_permalink( $rtb_pid ) );
$rtb_title = rawurlencode( get_the_title( $rtb_pid ) );

$rtb_shares = [
	'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $rtb_url,
	'x'        => 'https://twitter.com/intent/tweet?url=' . $rtb_url . '&text=' . $rtb_title,
	'whatsapp' => 'https://wa.me/?text=' . $rtb_title . '%20' . $rtb_url,
	'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $rtb_url,
];

$rtb_follow = [
	'facebook'  => onass_mod( 'rtb_facebook', '#' ),
	'x'         => onass_mod( 'rtb_x', '#' ),
	'instagram' => onass_mod( 'rtb_instagram', '#' ),
	'linkedin'  => onass_mod( 'rtb_linkedin', '#' ),
	'youtube'   => onass_mod( 'rtb_youtube', '#' ),
];
?>
<aside class="rtb-sidebar">

	<!-- Partage -->
	<div class="rtb-widget">
		<div class="rtb-widget-title"><?php echo esc_html( rtb_t( 'Partager' ) ); ?></div>
		<div class="rtb-share">
			<?php foreach ( $rtb_shares as $key => $href ) : ?>
				<a class="rtb-share-btn rtb-soc--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $href ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( rtb_t( 'Partager sur' ) ) . ' ' . esc_attr( ucfirst( $key ) ); ?>"><?php echo rtb_social_svg( $key ); // phpcs:ignore ?></a>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Suivre la RTB (social proof) -->
	<div class="rtb-widget rtb-widget--follow">
		<div class="rtb-widget-title"><?php echo esc_html( rtb_t( 'Suivez la RTB' ) ); ?></div>
		<p class="rtb-widget-sub"><?php echo esc_html( rtb_t( 'Rejoignez notre communauté sur les réseaux et ne manquez aucune actualité.' ) ); ?></p>
		<div class="rtb-follow">
			<?php foreach ( $rtb_follow as $key => $href ) : ?>
				<a class="rtb-soc rtb-soc--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $href ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $key ) ); ?>"><?php echo rtb_social_svg( $key ); // phpcs:ignore ?></a>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Articles liés (même rubrique) -->
	<?php
	$rtb_cats = wp_get_post_categories( $rtb_pid );
	if ( $rtb_cats ) :
		$rtb_related = new WP_Query( [
			'post_type'           => 'post',
			'posts_per_page'      => 4,
			'post__not_in'        => [ $rtb_pid ],
			'category__in'        => $rtb_cats,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		] );
		if ( $rtb_related->have_posts() ) :
			?>
			<div class="rtb-widget">
				<div class="rtb-widget-title"><?php echo esc_html( rtb_t( 'Articles liés' ) ); ?></div>
				<div class="rtb-widget-list">
					<?php while ( $rtb_related->have_posts() ) : $rtb_related->the_post(); ?>
						<a class="rtb-mini" href="<?php the_permalink(); ?>">
							<span class="rtb-mini-thumb" style="background-image:url('<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'rtb-thumb' ) ?: ( rtb_cdnize( (string) get_post_meta( get_the_ID(), 'rtb_cover_url', true ) ) ?: rtb_img( 'aune-culture.png' ) ) ); ?>')"></span>
							<span class="rtb-mini-body">
								<span class="rtb-mini-title"><?php the_title(); ?></span>
								<span class="rtb-mini-date"><?php echo esc_html( get_the_date( 'j M Y' ) ); ?></span>
							</span>
						</a>
					<?php endwhile; ?>
				</div>
			</div>
			<?php
			wp_reset_postdata();
		endif;
	endif;
	?>

	<!-- Récents -->
	<?php
	$rtb_recent = new WP_Query( [
		'post_type'           => 'post',
		'posts_per_page'      => 5,
		'post__not_in'        => [ $rtb_pid ],
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	] );
	if ( $rtb_recent->have_posts() ) :
		?>
		<div class="rtb-widget">
			<div class="rtb-widget-title"><?php echo esc_html( rtb_t( 'Les plus récents' ) ); ?></div>
			<div class="rtb-widget-list">
				<?php $rn = 0; while ( $rtb_recent->have_posts() ) : $rtb_recent->the_post(); $rn++; ?>
					<a class="rtb-recent" href="<?php the_permalink(); ?>">
						<span class="rtb-recent-num"><?php echo esc_html( sprintf( '%02d', $rn ) ); ?></span>
						<span class="rtb-recent-title"><?php the_title(); ?></span>
					</a>
				<?php endwhile; ?>
			</div>
		</div>
		<?php
		wp_reset_postdata();
	endif;
	?>

</aside>
