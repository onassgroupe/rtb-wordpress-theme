<?php
/**
 * Index / blog fallback.
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="rtb-page-head">
	<div class="rtb-container">
		<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span>ACTUALITÉS</span></div>
		<h1><?php is_home() ? bloginfo( 'name' ) : single_post_title(); ?></h1>
	</div>
</div>

<?php if ( have_posts() ) : ?>
	<div class="rtb-archive-grid">
		<h2 class="rtb-visually-hidden">Articles</h2>
		<?php while ( have_posts() ) : the_post(); get_template_part( 'parts/card', 'post' ); endwhile; ?>
	</div>
	<div class="rtb-pagination"><?php echo paginate_links( [ 'mid_size' => 1 ] ); ?></div>
<?php else : ?>
	<div class="rtb-archive-grid"><p>Aucun article pour le moment.</p></div>
<?php endif; ?>

<?php
get_footer();
