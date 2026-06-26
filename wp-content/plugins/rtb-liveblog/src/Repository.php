<?php

namespace RTB\LiveBlog;

defined( 'ABSPATH' ) || exit;

/** Accès aux données du direct (statut + entrées) stockées en meta de l'article. */
final class Repository {

	public const STATUS  = '_rtb_live_status';   // '' | 'open' | 'closed'
	public const ENTRIES = '_rtb_live_entries';  // [ ['id','t','label','html','text'], ... ]
	public const SEQ     = '_rtb_live_seq';
	public const UPDATED = '_rtb_live_updated';

	public static function status( int $post_id ): string {
		$s = (string) get_post_meta( $post_id, self::STATUS, true );
		return in_array( $s, [ 'open', 'closed' ], true ) ? $s : '';
	}

	public static function isLive( int $post_id ): bool {
		return '' !== self::status( $post_id );
	}

	public static function setStatus( int $post_id, string $status ): void {
		if ( in_array( $status, [ 'open', 'closed' ], true ) ) {
			update_post_meta( $post_id, self::STATUS, $status );
		} else {
			delete_post_meta( $post_id, self::STATUS );
		}
		delete_transient( 'rtb_live_index' ); // la liste des directs a changé
	}

	/** @return array<int,array<string,mixed>> entrées triées (plus récente d'abord) */
	public static function entries( int $post_id ): array {
		$e = get_post_meta( $post_id, self::ENTRIES, true );
		$e = is_array( $e ) ? $e : [];
		usort( $e, static fn( $a, $b ) => ( (int) $b['id'] ) <=> ( (int) $a['id'] ) );
		return $e;
	}

	public static function updated( int $post_id ): int {
		return (int) get_post_meta( $post_id, self::UPDATED, true );
	}

	/** Ajoute une entrée et renvoie la liste à jour. */
	public static function add( int $post_id, string $text, string $label, string $image = '', bool $key = false ): array {
		$seq     = (int) get_post_meta( $post_id, self::SEQ, true ) + 1;
		$entries = get_post_meta( $post_id, self::ENTRIES, true );
		$entries = is_array( $entries ) ? $entries : [];
		$entries[] = [
			'id'    => $seq,
			't'     => time(),
			'label' => sanitize_key( $label ),
			'html'  => wp_kses_post( wpautop( $text ) ),
			'text'  => wp_strip_all_tags( $text ),
			'img'   => $image ? esc_url_raw( $image ) : '',
			'key'   => $key ? 1 : 0,
		];
		update_post_meta( $post_id, self::SEQ, $seq );
		update_post_meta( $post_id, self::ENTRIES, $entries );
		self::touch( $post_id );
		return self::entries( $post_id );
	}

	/** Points clés (entrées épinglées par la rédaction), plus récents d'abord. */
	public static function keyPoints( int $post_id, int $limit = 6 ): array {
		$keys = array_filter( self::entries( $post_id ), static fn( $e ) => ! empty( $e['key'] ) );
		return array_slice( array_values( $keys ), 0, $limit );
	}

	public static function delete( int $post_id, int $entry_id ): array {
		$entries = array_values( array_filter( self::entries( $post_id ), static fn( $e ) => (int) $e['id'] !== $entry_id ) );
		update_post_meta( $post_id, self::ENTRIES, $entries );
		self::touch( $post_id );
		return self::entries( $post_id );
	}

	private static function touch( int $post_id ): void {
		update_post_meta( $post_id, self::UPDATED, time() );
		delete_transient( 'rtb_live_feed_' . $post_id );
		delete_transient( 'rtb_live_index' );
	}

	public static function labelText( string $label ): string {
		$t = static fn( string $s ): string => function_exists( 'rtb_t' ) ? rtb_t( $s ) : $s;
		$map = [ 'flash' => $t( 'FLASH' ), 'urgent' => $t( 'URGENT' ), 'important' => $t( 'IMPORTANT' ) ];
		return $map[ $label ] ?? '';
	}
}
