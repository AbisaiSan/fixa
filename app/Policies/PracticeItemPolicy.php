<?php

namespace App\Policies;

/**
 * Só o dono responde ou reporta o item (ACE-04).
 */
class PracticeItemPolicy extends OwnedModelPolicy {}
