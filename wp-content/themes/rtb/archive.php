<?php
/**
 * Archive (catégorie, taxonomie, date, auteur).
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="rtb-page-head">
	<div class="rtb-container">
		<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span>RTB</span></div>
		<h1><?php echo wp_kses_post( get_the_archive_title() ); ?></h1>
		<?php $desc = get_the_archive_description(); if ( $desc ) : ?>
			<p style="color:var(--rtb-gray);max-width:680px;margin:10px 0 0"><?php echo wp_kses_post( $desc ); ?></p>
		<?php endif; ?>
	</div>
</div>

<?php if ( have_posts() ) : ?>
	<div class="rtb-archive-grid">
		<h2 class="rtb-visually-hidden"><?php echo esc_html( rtb_t( 'Articles de la rubrique' ) ); ?></h2>
		<?php while ( have_posts() ) : the_post(); get_template_part( 'parts/card', 'post' ); endwhile; ?>
	</div>
	<div class="rtb-pagination"><?php echo paginate_links( [ 'mid_size' => 1 ] ); ?></div>
<?php else : ?>
	<div class="rtb-archive-grid"><p><?php echo esc_html( rtb_t( 'Aucun contenu dans cette rubrique pour le moment.' ) ); ?></p></div>
<?php endif; ?>

<?php
get_footer();
