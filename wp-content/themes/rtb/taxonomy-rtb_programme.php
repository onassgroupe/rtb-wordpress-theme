<?php
/**
 * Archive d'un programme (grand rendez-vous).
 */
defined( 'ABSPATH' ) || exit;

get_header();

$term = get_queried_object();
get_template_part( 'parts/emissions-archive', null, [
	'eyebrow' => rtb_t( 'GRAND RENDEZ-VOUS' ),
	'title'   => $term ? $term->name : rtb_t( 'Programme' ),
] );

get_footer();
