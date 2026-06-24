<?php
/**
 * Corps d'archive Émissions (partagé archive + taxonomie).
 * Attend $rtb_eyebrow et $rtb_title définis avant l'inclusion.
 */
defined( 'ABSPATH' ) || exit;

$rtb_eyebrow = $args['eyebrow'] ?? 'TÉLÉVISION';
$rtb_title   = $args['title'] ?? 'Émissions & vidéos';

// Filtre programme (?prog=Success)
$rtb_prog = isset( $_GET['prog'] ) ? sanitize_text_field( wp_unslash( $_GET['prog'] ) ) : '';
if ( $rtb_prog ) {
	$rtb_eyebrow = 'GRAND RENDEZ-VOUS';
	$rtb_title   = $rtb_prog;
}

$rtb_terms      = get_terms( [ 'taxonomy' => 'rtb_emission_cat', 'hide_empty' => true ] );
$rtb_current_id = ( is_tax( 'rtb_emission_cat' ) ) ? (int) get_queried_object_id() : 0;
?>
<div class="rtb-page-head">
	<div class="rtb-container">
		<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span><?php echo esc_html( $rtb_eyebrow ); ?></span></div>
		<h1><?php echo esc_html( $rtb_title ); ?></h1>
		<p class="rtb-page-lead">
			<?php echo $rtb_prog
				? esc_html( 'Tous les épisodes et replays de « ' . $rtb_prog .' ».' )
				: 'Revivez les journaux, magazines et grands rendez-vous de la RTB, en replay et à la demande.'; ?>
		</p>
	</div>
</div>

<section class="rtb-container rtb-section">
	<h2 class="rtb-visually-hidden"><?php echo esc_html( $rtb_title ); ?></h2>
	<?php if ( $rtb_terms && ! is_wp_error( $rtb_terms ) ) : ?>
		<div class="rtb-tabs rtb-emissions-filter">
			<a class="rtb-tab<?php echo $rtb_current_id ? '' : ' is-on'; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'rtb_emission' ) ); ?>">Tout</a>
			<?php foreach ( $rtb_terms as $t ) : ?>
				<a class="rtb-tab<?php echo $rtb_current_id === (int) $t->term_id ? ' is-on' : ''; ?>" href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php echo esc_html( $t->name ); ?></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<div class="rtb-grid-3">
			<?php while ( have_posts() ) : the_post(); get_template_part( 'parts/card', 'emission' ); endwhile; ?>
		</div>
		<div class="rtb-pagination"><?php echo paginate_links( [ 'mid_size' => 1 ] ); ?></div>
	<?php else : ?>
		<p style="color:var(--text-muted)">Aucune émission disponible pour le moment.</p>
	<?php endif; ?>
</section>
