<?php
/**
 * Carte émission / vidéo (archive émissions).
 */
defined( 'ABSPATH' ) || exit;

$terms = get_the_terms( get_the_ID(), 'rtb_emission_cat' );
$cat   = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'Vidéo';
$dur   = get_post_meta( get_the_ID(), 'rtb_dur', true );
$by    = get_post_meta( get_the_ID(), 'rtb_by', true ) ?: 'RTB Multimédia';
$date  = get_post_meta( get_the_ID(), 'rtb_human_date', true ) ?: get_the_date( 'j M Y' );
$cover = rtb_post_cover( get_the_ID() );
?>
<a class="rtb-videocard" href="<?php the_permalink(); ?>">
	<span class="rtb-vc-thumb rtb-media-wrap">
		<span class="rtb-media" style="background-image:url('<?php echo esc_url( $cover ); ?>')"></span>
		<span class="rtb-vc-cat"><?php echo esc_html( $cat ); ?></span>
		<?php if ( $dur ) : ?><span class="rtb-vc-dur"><?php echo esc_html( $dur ); ?></span><?php endif; ?>
		<span class="rtb-play rtb-play--sm"><i></i></span>
	</span>
	<h3><?php the_title(); ?></h3>
	<span class="rtb-vc-meta"><b><?php echo esc_html( $by ); ?></b><span>·</span><span><?php echo esc_html( $date ); ?></span></span>
</a>
