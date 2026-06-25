<?php
/**
 * Résultats de recherche.
 */
defined( 'ABSPATH' ) || exit;

get_header();

global $wp_query;
$total = (int) $wp_query->found_posts;
?>
<div class="rtb-page-head">
	<div class="rtb-container">
		<div class="rtb-eyebrow rtb-eyebrow--red"><i></i><span><?php echo esc_html( rtb_t( 'RECHERCHE' ) ); ?></span></div>
		<h1><?php echo esc_html( rtb_t( 'Résultats pour' ) ); ?> «&nbsp;<?php echo esc_html( get_search_query() ); ?>&nbsp;»</h1>
		<p class="rtb-page-lead">
			<?php
			echo $total > 0
				? esc_html( sprintf( _n( '%s résultat trouvé', '%s résultats trouvés', $total, 'rtb' ), number_format_i18n( $total ) ) )
				: esc_html( rtb_t( "Aucun résultat. Essayez d’autres mots-clés." ) );
			?>
		</p>
	</div>
</div>

<?php
$rtb_q = get_search_query();
if ( '' !== $rtb_q ) :
	$rtb_type  = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'all';
	$rtb_sort  = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'relevant';
	$rtb_link  = static function ( array $args ) use ( $rtb_q ) {
		return esc_url( add_query_arg( array_merge( [ 's' => $rtb_q ], $args ), rtb_lurl( '/' ) ) );
	};
	$rtb_types = [ 'all' => rtb_t( 'Tout' ), 'post' => rtb_t( 'Articles' ), 'rtb_emission' => rtb_t( 'Émissions' ) ];
	$rtb_sorts = [ 'relevant' => rtb_t( 'Pertinence' ), 'recent' => rtb_t( 'Récents' ), 'oldest' => rtb_t( 'Anciens' ) ];
	?>
	<div class="rtb-container">
		<div class="rtb-search-filters">
			<div class="rtb-sf-group">
				<span class="rtb-sf-label"><?php echo esc_html( rtb_t( 'Type' ) ); ?></span>
				<?php foreach ( $rtb_types as $k => $lbl ) : ?>
					<a class="rtb-sf-chip <?php echo $rtb_type === $k ? 'is-active' : ''; ?>" href="<?php echo $rtb_link( [ 'type' => $k, 'sort' => $rtb_sort ] ); ?>"><?php echo esc_html( $lbl ); ?></a>
				<?php endforeach; ?>
			</div>
			<div class="rtb-sf-group">
				<span class="rtb-sf-label"><?php echo esc_html( rtb_t( 'Trier' ) ); ?></span>
				<?php foreach ( $rtb_sorts as $k => $lbl ) : ?>
					<a class="rtb-sf-chip <?php echo $rtb_sort === $k ? 'is-active' : ''; ?>" href="<?php echo $rtb_link( [ 'type' => $rtb_type, 'sort' => $k ] ); ?>"><?php echo esc_html( $lbl ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php if ( have_posts() ) : ?>
	<div class="rtb-archive-grid">
		<h2 class="rtb-visually-hidden"><?php echo esc_html( rtb_t( 'Résultats de recherche' ) ); ?></h2>
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'parts/card', 'rtb_emission' === get_post_type() ? 'emission' : 'post' );
		endwhile;
		?>
	</div>
	<div class="rtb-pagination"><?php echo paginate_links( [ 'mid_size' => 1 ] ); ?></div>
<?php else : ?>
	<div class="rtb-container" style="padding:40px 28px 70px">
		<div class="rtb-emptystate">
			<h2><?php echo esc_html( rtb_t( 'Relancer une recherche' ) ); ?></h2>
			<p><?php echo esc_html( rtb_t( "Vérifiez l’orthographe ou utilisez des termes plus généraux." ) ); ?></p>
			<?php get_search_form(); ?>
			<div class="rtb-emptystate-links">
				<?php foreach ( [ 'Actualités' => '/category/infos', 'Le Journal' => '/emissions', 'Sport' => '/category/sport' ] as $label => $url ) : ?>
					<a class="rtb-tab" href="<?php echo esc_url( rtb_lurl( $url ) ); ?>"><?php echo esc_html( rtb_t( $label ) ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php
get_footer();
