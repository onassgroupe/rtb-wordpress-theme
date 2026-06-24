<?php

namespace RTB\Search\Analytics;

defined( 'ABSPATH' ) || exit;

/**
 * Journalise les termes recherchés → recherches tendances & récentes.
 */
final class Store {

	private const OPT_VER = 'rtb_search_db_ver';
	private const VER     = '1';

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'rtb_search_queries';
	}

	public static function installTable(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$collate = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				term VARCHAR(191) NOT NULL,
				hits BIGINT UNSIGNED NOT NULL DEFAULT 1,
				last_searched_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY term (term),
				KEY hits (hits),
				KEY last_searched_at (last_searched_at)
			) {$collate};"
		);
		update_option( self::OPT_VER, self::VER );
	}

	public function maybeInstall(): void {
		if ( get_option( self::OPT_VER ) !== self::VER ) {
			self::installTable();
		}
	}

	public function record( string $term ): void {
		$term = trim( preg_replace( '/\s+/', ' ', mb_strtolower( $term, 'UTF-8' ) ) );
		if ( mb_strlen( $term ) < 2 || mb_strlen( $term ) > 191 ) {
			return;
		}
		global $wpdb;
		$table = self::table();
		$now   = current_time( 'mysql' );
		// phpcs:ignore WordPress.DB.PreparedSQL
		$wpdb->query( $wpdb->prepare(
			"INSERT INTO {$table} (term, hits, last_searched_at) VALUES (%s, 1, %s)
			 ON DUPLICATE KEY UPDATE hits = hits + 1, last_searched_at = %s",
			$term,
			$now,
			$now
		) );
	}

	/** @return string[] */
	public function trending( int $limit = 6 ): array {
		return $this->fetch( "ORDER BY hits DESC, last_searched_at DESC", $limit );
	}

	/** @return string[] */
	public function recent( int $limit = 5 ): array {
		return $this->fetch( "ORDER BY last_searched_at DESC", $limit );
	}

	/** @return string[] */
	private function fetch( string $order, int $limit ): array {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT term FROM {$table} {$order} LIMIT %d", $limit ) );
		if ( ! $rows ) {
			return [];
		}
		return array_map(
			static fn( string $t ): string => function_exists( 'mb_convert_case' ) ? mb_convert_case( $t, MB_CASE_TITLE, 'UTF-8' ) : ucwords( $t ),
			$rows
		);
	}
}
