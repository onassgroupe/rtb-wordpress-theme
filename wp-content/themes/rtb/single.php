<?php
/**
 * Single — article.
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$cat   = get_the_category();
	$cname = $cat ? $cat[0]->name : 'Actualité';
	$cover = get_the_post_thumbnail_url( get_the_ID(), 'rtb-wide' )
		?: rtb_cdnize( (string) get_post_meta( get_the_ID(), 'rtb_cover_url', true ) );
	$rtb_doc = get_post_meta( get_the_ID(), '_rtb_doc_url', true );
	?>
	<div class="rtb-page-head">
		<div class="rtb-container">
			<div class="rtb-breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a>
				<?php if ( $cat ) : ?> &rsaquo; <a href="<?php echo esc_url( get_category_link( $cat[0]->term_id ) ); ?>"><?php echo esc_html( $cname ); ?></a><?php endif; ?>
			</div>
			<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span><?php echo esc_html( mb_strtoupper( $cname, 'UTF-8' ) ); ?></span></div>
			<h1><?php the_title(); ?></h1>
		</div>
	</div>

	<div class="rtb-single rtb-container">
		<article class="rtb-article rtb-article--main">
			<div class="rtb-article-meta">
				Par la <strong>Rédaction RTB</strong> · <?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
			</div>
			<?php if ( $cover && ! $rtb_doc ) : ?>
				<div class="rtb-article-cover" style="background-image:url('<?php echo esc_url( $cover ); ?>')"></div>
			<?php endif; ?>
			<?php if ( $rtb_doc ) : ?>
				<?php $rtb_lead = trim( (string) get_post_field( 'post_excerpt', get_the_ID() ) ); ?>
				<?php if ( '' !== $rtb_lead ) : ?>
					<div class="rtb-article-body"><p class="rtb-doc-lead"><?php echo esc_html( $rtb_lead ); ?></p></div>
				<?php endif; ?>
				<figure class="rtb-doc">
					<figcaption class="rtb-doc-head">
						<span class="rtb-doc-title"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Document officiel</span>
						<a class="rtb-doc-dl" href="<?php echo esc_url( $rtb_doc ); ?>" download><i class="fa-solid fa-download" aria-hidden="true"></i> Télécharger le PDF</a>
					</figcaption>
					<object class="rtb-doc-view" data="<?php echo esc_url( $rtb_doc ); ?>#view=FitH" type="application/pdf">
						<iframe class="rtb-doc-view" src="<?php echo esc_url( $rtb_doc ); ?>" title="<?php the_title_attribute(); ?> — document PDF" loading="lazy"></iframe>
					</object>
				</figure>
			<?php else : ?>
				<div class="rtb-article-body"><?php the_content(); ?></div>
			<?php endif; ?>

			<?php
			$tags = get_the_tags();
			if ( $tags ) : ?>
				<div class="rtb-article-tags">
					<?php foreach ( $tags as $t ) : ?>
						<a href="<?php echo esc_url( get_tag_link( $t->term_id ) ); ?>" class="rtb-tab"><?php echo esc_html( $t->name ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</article>

		<?php get_template_part( 'parts/sidebar' ); ?>
	</div>
	<?php
endwhile;

get_footer();
