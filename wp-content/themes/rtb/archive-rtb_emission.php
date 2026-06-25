<?php
/**
 * Archive — Émissions / vidéos.
 */
defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'parts/emissions-archive', null, [
	'eyebrow' => rtb_t( 'TÉLÉVISION' ),
	'title'   => rtb_t( 'Émissions & vidéos' ),
] );

get_footer();
