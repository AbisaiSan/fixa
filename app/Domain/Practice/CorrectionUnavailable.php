<?php

namespace App\Domain\Practice;

use RuntimeException;

/**
 * A IA não conseguiu corrigir agora e o item não tem gabarito para cobrir.
 */
class CorrectionUnavailable extends RuntimeException {}
