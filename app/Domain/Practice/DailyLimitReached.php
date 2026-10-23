<?php

namespace App\Domain\Practice;

use RuntimeException;

/**
 * O aluno usou as correções por IA do dia (ATV-05). Só a IA para.
 */
class DailyLimitReached extends RuntimeException {}
