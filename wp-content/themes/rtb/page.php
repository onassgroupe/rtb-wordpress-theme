<?php
/**
 * Page générique.
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="rtb-page-head">
		<div class="rtb-container">
			<div class="rtb-eyebrow rtb-eyebrow--green"><i></i><span>RTB</span></div>
			<h1><?php the_title(); ?></h1>
		</div>
	</div>
	<article class="rtb-article">
		<div class="rtb-article-body"><?php the_content(); ?></div>
	</article>
	<?php
endwhile;

get_footer();
