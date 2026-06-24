<?php

namespace RTB\Chat\Responders;

use RTB\Chat\Message;
use RTB\Chat\Reply;

defined( 'ABSPATH' ) || exit;

/**
 * Un responder gère une intention. Le premier qui « handles() » répond.
 */
interface Responder {

	public function handles( Message $msg ): bool;

	public function respond( Message $msg ): Reply;
}
