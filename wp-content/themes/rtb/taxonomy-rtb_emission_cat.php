<?php
/**
 * Archive par catégorie d'émission.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$term = get_queried_object();
get_template_part( 'parts/emissions-archive', null, [
	'eyebrow' => rtb_t( 'ÉMISSIONS' ),
	'title'   => $term ? $term->name : rtb_t( 'Émissions' ),
] );

get_footer();
