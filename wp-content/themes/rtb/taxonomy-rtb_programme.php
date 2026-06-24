<?php
/**
 * Archive d'un programme (grand rendez-vous).
 */
defined( 'ABSPATH' ) || exit;

get_header();

$term = get_queried_object();
get_template_part( 'parts/emissions-archive', null, [
	'eyebrow' => 'GRAND RENDEZ-VOUS',
	'title'   => $term ? $term->name : 'Programme',
] );

get_footer();
