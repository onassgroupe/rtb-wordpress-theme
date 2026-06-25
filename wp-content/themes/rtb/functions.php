<?php
/**
 * RTB — Radiodiffusion Télévision du Burkina
 * functions.php
 */

defined( 'ABSPATH' ) || exit;

define( 'RTB_VERSION', '1.0.0' );

/* ============================================================
   PLUGIN FALLBACK SHIM — Onass Live Edit
   Le thème utilise onass_mod() / onass_cs_setting(), normalement
   fournis par le plugin onass-live-edit. Si le plugin est désactivé,
   on définit des fallbacks minimaux pour que le site continue de
   charger au lieu de fatal-er.
   ============================================================ */
if ( ! function_exists( 'onass_mod' ) ) {
	function onass_mod( string $key, string $default = '' ): string {
		return (string) get_theme_mod( $key, $default );
	}
}
if ( ! function_exists( 'onass_cs_setting' ) ) {
	function onass_cs_setting(
		WP_Customize_Manager $wpc,
		string $id,
		string|array $label_or_args,
		string $section = '',
		string $default = '',
		string $type    = 'text'
	): void {
		if ( is_array( $label_or_args ) ) {
			$label   = $label_or_args['label']   ?? '';
			$section = $label_or_args['section'] ?? $section;
			$default = $label_or_args['default'] ?? $default;
			$type    = $label_or_args['type']    ?? $type;
		} else {
			$label = $label_or_args;
		}

		$sanitize = match ( $type ) {
			'url'      => 'esc_url_raw',
			'email'    => 'sanitize_email',
			'textarea' => 'sanitize_textarea_field',
			default    => 'sanitize_text_field',
		};

		$wpc->add_setting( $id, [
			'default'           => $default,
			'transport'         => 'refresh',
			'sanitize_callback' => $sanitize,
		] );

		if ( 'image' === $type ) {
			$wpc->add_control( new WP_Customize_Image_Control( $wpc, $id, [
				'label'   => $label,
				'section' => $section,
			] ) );
			return;
		}

		$wpc->add_control( $id, [
			'label'   => $label,
			'section' => $section,
			'type'    => match ( $type ) {
				'textarea' => 'textarea',
				'url'      => 'url',
				'email'    => 'email',
				default    => 'text',
			},
		] );
	}
}

require_once get_template_directory() . '/inc/data.php';
require_once get_template_directory() . '/inc/seed-data.php';
require_once get_template_directory() . '/inc/i18n.php';
require_once get_template_directory() . '/inc/cpt.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/seed.php';
require_once get_template_directory() . '/inc/login.php';
require_once get_template_directory() . '/inc/import.php';
require_once get_template_directory() . '/inc/admin-meta.php';

/* ============================================================
   THEME SETUP
   ============================================================ */
function rtb_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', [
		'comment-list', 'comment-form', 'search-form',
		'gallery', 'caption', 'style', 'script',
	] );

	add_editor_style( 'assets/css/rtb.css' );

	register_nav_menus( [
		'primary' => __( 'Menu Principal', 'rtb' ),
		'footer'  => __( 'Menu Footer', 'rtb' ),
	] );

	add_image_size( 'rtb-card', 640, 360, true );
	add_image_size( 'rtb-wide', 1600, 900, true );
	add_image_size( 'rtb-thumb', 200, 120, true );
}
add_action( 'after_setup_theme', 'rtb_setup' );

/** URL du logo RTB avec cache-busting (la version change quand le fichier change). */
function rtb_logo_url(): string {
	$path = get_template_directory() . '/assets/img/rtb-logo.png';
	$ver  = file_exists( $path ) ? filemtime( $path ) : ( defined( 'RTB_VERSION' ) ? RTB_VERSION : '1' );
	return get_template_directory_uri() . '/assets/img/rtb-logo.png?v=' . $ver;
}

/* ============================================================
   ENQUEUE
   ============================================================ */
function rtb_scripts(): void {
	wp_enqueue_style(
		'rtb-fonts',
		'https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800;900&family=Libre+Franklin:wght@400;500;600;700&display=optional',
		[],
		null
	);

	// Font Awesome : cœur + solid + brands uniquement (évite les webfonts "regular" et "v4" inutiles).
	$fa = get_template_directory_uri() . '/assets/fontawesome/css/';
	wp_enqueue_style( 'font-awesome', $fa . 'fontawesome.min.css', [], '6.5.1' );
	wp_enqueue_style( 'fa-solid', $fa . 'solid.min.css', [ 'font-awesome' ], '6.5.1' );
	wp_enqueue_style( 'fa-brands', $fa . 'brands.min.css', [ 'font-awesome' ], '6.5.1' );

	wp_enqueue_style(
		'rtb-style',
		get_template_directory_uri() . '/assets/css/rtb.css',
		[ 'rtb-fonts', 'font-awesome' ],
		RTB_VERSION
	);

	// Alpine.js auto-hébergé (aucune dépendance CDN) — version figée 3.14.8.
	wp_enqueue_script(
		'alpinejs',
		get_template_directory_uri() . '/assets/js/alpine.min.js',
		[],
		'3.14.8',
		[ 'strategy' => 'defer', 'in_footer' => true ]
	);

	// PAS de defer : rtb.js doit enregistrer ses composants Alpine (sur alpine:init)
	// AVANT qu'Alpine (deferé) ne démarre. Le mettre en defer casse tout Alpine.
	wp_enqueue_script(
		'rtb-js',
		get_template_directory_uri() . '/assets/js/rtb.js',
		[],
		RTB_VERSION,
		[ 'in_footer' => true ]
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	wp_localize_script( 'rtb-js', 'rtbData', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'rtb_contact' ),
	] );
}
add_action( 'wp_enqueue_scripts', 'rtb_scripts' );

/**
 * Charge les CSS non critiques en ASYNCHRONE (non bloquantes pour le rendu) :
 * FontAwesome, polices Google, CSS des widgets chat/recherche. Seul rtb.css
 * (critique) reste bloquant → meilleur FCP/LCP, surtout sur mobile.
 */
function rtb_defer_styles( string $tag, string $handle, string $href, string $media ): string {
	if ( is_admin() ) {
		return $tag;
	}
	$defer_handles = [ 'rtb-fonts', 'font-awesome', 'fa-solid', 'fa-brands' ];
	$defer         = in_array( $handle, $defer_handles, true );
	if ( ! $defer ) {
		foreach ( [ 'fontawesome', '/chat.css', '/instant.css', 'fonts.googleapis.com' ] as $needle ) {
			if ( false !== strpos( $href, $needle ) ) {
				$defer = true;
				break;
			}
		}
	}
	if ( ! $defer ) {
		return $tag;
	}
	return '<link rel="stylesheet" href="' . esc_url( $href ) . '" media="print" onload="this.media=\'all\';this.onload=null;">'
		. '<noscript><link rel="stylesheet" href="' . esc_url( $href ) . '"></noscript>' . "\n";
}
add_filter( 'style_loader_tag', 'rtb_defer_styles', 10, 4 );

/* Pas d'avatars Gravatar : requêtes externes inutiles (contenu institutionnel). */
add_filter( 'pre_option_show_avatars', '__return_zero' );
add_filter( 'get_avatar', '__return_empty_string', 99 );
add_filter( 'get_avatar_url', '__return_empty_string', 99 );

/* Resource hints : connexions anticipées vers fonts, CDN images, YouTube, Alpine. */
add_filter( 'wp_resource_hints', function ( $hints, $relation ) {
	if ( 'preconnect' === $relation ) {
		$hints[] = [ 'href' => 'https://fonts.gstatic.com', 'crossorigin' ];
		$hints[] = 'https://i0.wp.com';
	}
	if ( 'dns-prefetch' === $relation ) {
		$hints[] = 'https://i.ytimg.com';
		$hints[] = 'https://i0.wp.com';
	}
	return $hints;
}, 10, 2 );

/* Préchargement des webfonts d'icônes (Font Awesome) → icônes affichées tout de suite. */
add_action( 'wp_head', function () {
	$fa = get_template_directory_uri() . '/assets/fontawesome/webfonts/';
	echo '<link rel="preload" href="' . esc_url( $fa . 'fa-solid-900.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<link rel="preload" href="' . esc_url( $fa . 'fa-brands-400.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}, 1 );

/* Préchargement de l'image LCP (cover du hero) sur l'accueil → meilleur Largest Contentful Paint. */
add_action( 'wp_head', function () {
	if ( ! is_front_page() || ! function_exists( 'rtb_get_emissions' ) ) {
		return;
	}
	$e = rtb_get_emissions( 1 );
	$cover = $e[0]['cover'] ?? '';
	if ( $cover ) {
		echo '<link rel="preload" as="image" href="' . esc_url( $cover ) . '" fetchpriority="high">' . "\n";
	}
}, 2 );

/* ============================================================
   AJAX — FORMULAIRE CONTACT
   ============================================================ */
function rtb_handle_contact(): void {
	check_ajax_referer( 'rtb_contact', 'nonce' );

	// Anti-spam : 1 envoi / 30 s par IP (léger, sans dépendance).
	$ip     = (string) ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
	$bucket = 'rtb_contact_rl_' . md5( $ip );
	if ( get_transient( $bucket ) ) {
		wp_send_json_error( [ 'message' => 'Merci de patienter un instant avant de renvoyer un message.' ] );
	}
	set_transient( $bucket, 1, 30 );

	$nom   = sanitize_text_field( $_POST['nom'] ?? '' );
	$email = sanitize_email( $_POST['email'] ?? '' );
	$sujet = sanitize_text_field( $_POST['sujet'] ?? '' );
	$msg   = sanitize_textarea_field( $_POST['message'] ?? '' );

	if ( ! $nom || ! $email || ! $msg ) {
		wp_send_json_error( [ 'message' => 'Merci de remplir tous les champs obligatoires.' ] );
	}
	if ( ! is_email( $email ) ) {
		wp_send_json_error( [ 'message' => 'Adresse e-mail invalide.' ] );
	}

	$to = onass_mod( 'rtb_email', get_option( 'admin_email' ) );
	wp_mail(
		$to,
		sprintf( '[RTB] %s — %s', $sujet ?: 'Contact', $nom ),
		sprintf( "Message via le formulaire de contact RTB.\n\nNom : %s\nE-mail : %s\nSujet : %s\n\n%s", $nom, $email, $sujet, $msg )
	);

	wp_send_json_success( [ 'message' => 'Merci ! Votre message a bien été envoyé à la rédaction.' ] );
}
add_action( 'wp_ajax_rtb_contact', 'rtb_handle_contact' );
add_action( 'wp_ajax_nopriv_rtb_contact', 'rtb_handle_contact' );

/* ============================================================
   ICÔNES RÉSEAUX SOCIAUX (SVG inline, currentColor)
   ============================================================ */
function rtb_social_svg( string $key ): string {
	// Icônes de marque officielles via Font Awesome (brands).
	$icons = [
		'facebook'  => 'fa-facebook-f',
		'x'         => 'fa-x-twitter',
		'twitter'   => 'fa-x-twitter',
		'instagram' => 'fa-instagram',
		'linkedin'  => 'fa-linkedin-in',
		'youtube'   => 'fa-youtube',
		'whatsapp'  => 'fa-whatsapp',
	];
	$class = $icons[ $key ] ?? '';
	if ( ! $class ) {
		return '';
	}
	return '<i class="fa-brands ' . esc_attr( $class ) . '" aria-hidden="true"></i>';
}

/* ============================================================
   FILTRE PROGRAMME — /emissions/?prog=Success → épisodes du programme
   ============================================================ */
function rtb_filter_programme( WP_Query $q ): void {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( 'rtb_emission' ) && ! empty( $_GET['prog'] ) ) {
		$q->set( 'rtb_prog', sanitize_text_field( wp_unslash( $_GET['prog'] ) ) );
	}
}
add_action( 'pre_get_posts', 'rtb_filter_programme' );

function rtb_programme_where( string $where, WP_Query $q ): string {
	$prog = $q->get( 'rtb_prog' );
	if ( $prog ) {
		global $wpdb;
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like( $prog ) . '%' );
	}
	return $where;
}
add_filter( 'posts_where', 'rtb_programme_where', 10, 2 );

/* ============================================================
   NAV ACTIVE HELPER
   ============================================================ */
function rtb_active( string $slug ): string {
	if ( 'home' === $slug && is_front_page() ) {
		return 'is-active';
	}
	if ( is_page( $slug ) ) {
		return 'is-active';
	}
	return '';
}

/* ============================================================
   TICKER — liste de messages (1 par ligne dans le Customizer)
   ============================================================ */
function rtb_ticker_messages(): array {
	$raw = onass_mod( 'rtb_ticker', '' );
	$lines = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
	if ( empty( $lines ) ) {
		$lines = rtb_default_tickers();
	}
	return array_values( $lines );
}

/* ============================================================
   META DESCRIPTION (SEO)
   ============================================================ */
function rtb_meta_description(): void {
	$desc = '';
	if ( is_singular() ) {
		$desc = get_the_excerpt();
		if ( ! $desc ) {
			$desc = wp_strip_all_tags( get_post_field( 'post_content', get_queried_object_id() ) );
		}
	} elseif ( is_category() || is_tax() || is_tag() ) {
		$desc = term_description() ?: single_term_title( '', false );
	}
	if ( ! $desc ) {
		$desc = get_bloginfo( 'description' );
	}
	$desc = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $desc ) ) );
	if ( '' === $desc ) {
		return;
	}
	$desc = mb_substr( $desc, 0, 160 );
	echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
}
add_action( 'wp_head', 'rtb_meta_description', 2 );

/* ============================================================
   FAVICON
   ============================================================ */
function rtb_favicon(): void {
	$url = rtb_logo_url();
	echo '<link rel="icon" type="image/png" href="' . esc_url( $url ) . '">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $url ) . '">' . "\n";
}
add_action( 'wp_head', 'rtb_favicon', 1 );

/* ============================================================
   SCHEMA.ORG — NewsMediaOrganization
   ============================================================ */
function rtb_schema_json_ld(): void {
	if ( ! is_front_page() ) {
		return;
	}
	$schema = [
		'@context'    => 'https://schema.org',
		'@type'       => 'NewsMediaOrganization',
		'name'        => get_bloginfo( 'name' ),
		'description' => get_bloginfo( 'description' ),
		'url'         => home_url(),
		'logo'        => rtb_logo_url(),
		'email'       => onass_mod( 'rtb_email', 'info@rtb.bf' ),
		'telephone'   => onass_mod( 'rtb_phone', '+226 25 31 83 53' ),
		'address'     => [
			'@type'          => 'PostalAddress',
			'streetAddress'  => onass_mod( 'rtb_address', '01 BP 2530 Ouagadougou 01' ),
			'addressCountry' => 'BF',
		],
	];
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'rtb_schema_json_ld' );

/* ============================================================
   EXCERPT
   ============================================================ */
function rtb_excerpt_length( int $length ): int {
	return 24;
}
add_filter( 'excerpt_length', 'rtb_excerpt_length' );

function rtb_excerpt_more( string $more ): string {
	return '…';
}
add_filter( 'excerpt_more', 'rtb_excerpt_more' );

/* ============================================================
   FLUSH REWRITE RULES ON ACTIVATION
   ============================================================ */
function rtb_flush_rewrites(): void {
	if ( function_exists( 'rtb_register_cpts' ) ) {
		rtb_register_cpts();
	}
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'rtb_flush_rewrites' );
