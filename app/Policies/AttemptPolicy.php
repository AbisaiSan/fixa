<?php

namespace App\Policies;

/**
 * Só o dono vê a própria resposta (ACE-04).
 */
class AttemptPolicy extends OwnedModelPolicy {}
