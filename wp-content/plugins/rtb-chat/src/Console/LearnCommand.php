<?php

namespace RTB\Chat\Console;

use RTB\Chat\Learning\Learner;

defined( 'ABSPATH' ) || exit;

/**
 * « wp rtb-chat learn [--fresh] » : apprend le vocabulaire du contenu RTB
 * (titres, extraits, corps, catégories, tags) → correction orthographique de l'assistant.
 */
final class LearnCommand {

	/**
	 * @param array $args
	 * @param array $assoc
	 */
	public function __invoke( array $args, array $assoc ): void {
		$stats = ( new Learner() )->run( isset( $assoc['fresh'] ) );
		\WP_CLI::success( sprintf(
			'Lexique appris : %s mots traités, %d mots uniques retenus.',
			number_format( $stats['processed'] ),
			$stats['unique']
		) );
	}
}
