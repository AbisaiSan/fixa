<?php

namespace App\Policies;

/**
 * Só o dono vê, altera ou apaga a palavra (ACE-04).
 */
class WordPolicy extends OwnedModelPolicy {}
