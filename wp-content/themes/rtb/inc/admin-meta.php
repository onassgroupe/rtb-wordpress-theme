<?php
/**
 * RTB — Champs admin pour gérer le direct et les flux radio.
 *  - rtb_antenne : URL du direct (live) + « À l'antenne maintenant »
 *  - rtb_station : URL du flux audio
 * Tout est éditable depuis l'éditeur WordPress de chaque chaîne / station.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'rtb_live_box', 'Direct (live)', 'rtb_box_antenne', 'rtb_antenne', 'side', 'high' );
	add_meta_box( 'rtb_stream_box', 'Flux audio', 'rtb_box_station', 'rtb_station', 'side', 'high' );
} );

function rtb_box_antenne( $post ) {
	wp_nonce_field( 'rtb_live_save', 'rtb_live_nonce' );
	$live = (string) get_post_meta( $post->ID, 'rtb_live_url', true );
	$now  = (string) get_post_meta( $post->ID, 'rtb_now', true );
	echo '<p><label for="rtb_live_url"><strong>URL du direct (player live)</strong></label>';
	echo '<input type="url" id="rtb_live_url" name="rtb_live_url" value="' . esc_attr( $live ) . '" class="widefat" placeholder="https://player.infomaniak.com?channel=…&player=…&autoplay=1"></p>';
	echo '<p class="description">Laissez vide → repli automatique sur la dernière édition (YouTube).</p>';
	echo '<p style="margin-top:12px"><label for="rtb_now"><strong>À l\'antenne maintenant</strong></label>';
	echo '<input type="text" id="rtb_now" name="rtb_now" value="' . esc_attr( $now ) . '" class="widefat" placeholder="Ex. JT de 20H"></p>';
}

function rtb_box_station( $post ) {
	wp_nonce_field( 'rtb_live_save', 'rtb_live_nonce' );
	$stream = (string) get_post_meta( $post->ID, 'rtb_stream', true );
	echo '<p><label for="rtb_stream"><strong>URL du flux audio</strong></label>';
	echo '<input type="url" id="rtb_stream" name="rtb_stream" value="' . esc_attr( $stream ) . '" class="widefat" placeholder="https://…/stream"></p>';
	echo '<p class="description">Laissez vide → utilise le flux radio national par défaut.</p>';
}

add_action( 'save_post', function ( $post_id ) {
	if ( ! isset( $_POST['rtb_live_nonce'] ) || ! wp_verify_nonce( $_POST['rtb_live_nonce'], 'rtb_live_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$urls = array( 'rtb_live_url', 'rtb_stream' );
	foreach ( array( 'rtb_live_url', 'rtb_stream', 'rtb_now' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$val = wp_unslash( $_POST[ $key ] );
			$val = in_array( $key, $urls, true ) ? esc_url_raw( $val ) : sanitize_text_field( $val );
			update_post_meta( $post_id, $key, $val );
		}
	}
} );

/* Pré-remplissage unique des URLs connues (pour que l'admin les affiche). */
add_action( 'admin_init', function () {
	if ( get_option( 'rtb_live_meta_seeded' ) ) {
		return;
	}
	foreach ( get_posts( array( 'post_type' => 'rtb_antenne', 'numberposts' => 20, 'post_status' => 'any' ) ) as $a ) {
		if ( ! get_post_meta( $a->ID, 'rtb_live_url', true ) ) {
			$u = rtb_antenne_live( $a->post_title );
			if ( $u ) {
				update_post_meta( $a->ID, 'rtb_live_url', $u );
			}
		}
	}
	foreach ( get_posts( array( 'post_type' => 'rtb_station', 'numberposts' => 20, 'post_status' => 'any' ) ) as $s ) {
		if ( ! get_post_meta( $s->ID, 'rtb_stream', true ) && preg_match( '/burkina|national/i', $s->post_title ) ) {
			update_post_meta( $s->ID, 'rtb_stream', rtb_radio_stream() );
		}
	}
	update_option( 'rtb_live_meta_seeded', 1 );
} );
