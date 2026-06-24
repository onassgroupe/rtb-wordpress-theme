<?php
/**
 * Plugin Name: Onass Live Edit
 * Plugin URI:  https://github.com/onassgroupe/onass-live-edit
 * Description: Inline editing engine for the WordPress Customizer — editable zones, badge navigation, postMessage channel. Drop-in for any ONASS agency theme.
 * Version:     1.0.0
 * Author:      ONASS Groupe
 * Author URI:  https://onassgroupe.com
 * License:     GPL-2.0-or-later
 * Text Domain: onass-live-edit
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ONASS_LIVE_EDIT_VERSION', '1.0.0' );
define( 'ONASS_LIVE_EDIT_URL', plugin_dir_url( __FILE__ ) );

/* ──────────────────────────────────────────────────────────
   ENQUEUE — Customizer preview iframe
   ────────────────────────────────────────────────────────── */
function onass_live_edit_preview_js(): void {
    wp_enqueue_script(
        'onass-live-edit-preview',
        ONASS_LIVE_EDIT_URL . 'assets/js/customize-preview.js',
        [ 'customize-preview', 'jquery', 'underscore' ],
        ONASS_LIVE_EDIT_VERSION,
        true
    );
}
add_action( 'customize_preview_init', 'onass_live_edit_preview_js' );

/* ──────────────────────────────────────────────────────────
   ENQUEUE — Customizer controls panel (left sidebar)
   ────────────────────────────────────────────────────────── */
function onass_live_edit_controls_js(): void {
    wp_enqueue_script(
        'onass-live-edit-controls',
        ONASS_LIVE_EDIT_URL . 'assets/js/customize-controls.js',
        [ 'customize-controls', 'jquery' ],
        ONASS_LIVE_EDIT_VERSION,
        true
    );
}
add_action( 'customize_controls_enqueue_scripts', 'onass_live_edit_controls_js' );

/* ──────────────────────────────────────────────────────────
   HELPER — onass_mod()
   Shorthand for get_theme_mod() with optional default.
   Usage: onass_mod( 'my_setting', 'Default text' )
   ────────────────────────────────────────────────────────── */
if ( ! function_exists( 'onass_mod' ) ) {
    function onass_mod( string $key, string $default = '' ): string {
        return get_theme_mod( $key, $default );
    }
}

/* ──────────────────────────────────────────────────────────
   HELPER — onass_cs_setting()
   Registers a Customizer setting + control in one call.

   Drop-in replacement for any theme-level helper with the same signature.
   Accepts both positional args OR a named-array as 3rd argument.

   Positional (easy migration from bcer_add_setting_control):
     onass_cs_setting( $wpc, 'my_id', 'My Label', 'my_section', 'Default', 'text' );

   Named array (preferred for new themes):
     onass_cs_setting( $wpc, 'my_id', [
         'label'   => 'My Label',
         'section' => 'my_section',
         'default' => 'Default',
         'type'    => 'text',
     ] );
   ────────────────────────────────────────────────────────── */
if ( ! function_exists( 'onass_cs_setting' ) ) {
    function onass_cs_setting(
        WP_Customize_Manager $wpc,
        string $id,
        string|array $label_or_args,
        string $section  = '',
        string $default  = '',
        string $type     = 'text'
    ): void {
        // Named-array calling convention
        if ( is_array( $label_or_args ) ) {
            $label   = $label_or_args['label']   ?? '';
            $section = $label_or_args['section'] ?? $section;
            $default = $label_or_args['default'] ?? $default;
            $type    = $label_or_args['type']    ?? $type;
        } else {
            $label = $label_or_args;
        }

        // URL fields need a full refresh; text/textarea use postMessage
        $transport = ( $type === 'url' ) ? 'refresh' : 'postMessage';

        $sanitize = match ( $type ) {
            'url'      => 'esc_url_raw',
            'textarea' => 'sanitize_textarea_field',
            default    => 'sanitize_text_field',
        };

        $wpc->add_setting( $id, [
            'default'           => $default,
            'transport'         => $transport,
            'sanitize_callback' => $sanitize,
        ] );

        $control_type = match ( $type ) {
            'textarea' => 'textarea',
            'url'      => 'url',
            default    => 'text',
        };

        $wpc->add_control( $id, [
            'label'   => $label,
            'section' => $section,
            'type'    => $control_type,
        ] );
    }
}
