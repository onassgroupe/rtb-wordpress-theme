<?php
/**
 * Carte article (grille archives/index/search).
 */
defined( 'ABSPATH' ) || exit;

$cat   = get_the_category();
$cname = $cat ? $cat[0]->name : 'Actualité';
$thumb = get_the_post_thumbnail_url( get_the_ID(), 'rtb-card' )
	?: ( rtb_cdnize( (string) get_post_meta( get_the_ID(), 'rtb_cover_url', true ) ) ?: rtb_img( 'aune-culture.png' ) );
?>
<a class="rtb-videocard" href="<?php the_permalink(); ?>">
	<span class="rtb-vc-thumb" style="background-image:url('<?php echo esc_url( $thumb ); ?>')">
		<span class="rtb-vc-cat"><?php echo esc_html( mb_strtoupper( $cname, 'UTF-8' ) ); ?></span>
	</span>
	<h3><?php the_title(); ?></h3>
	<span class="rtb-vc-meta"><b>Rédaction RTB</b><span>·</span><span><?php echo esc_html( get_the_date( 'j M Y' ) ); ?></span></span>
</a>
