<?php
/**
 * Archive — Émissions / vidéos.
 */
defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'parts/emissions-archive', null, [
	'eyebrow' => 'TÉLÉVISION',
	'title'   => 'Émissions & vidéos',
] );

get_footer();
