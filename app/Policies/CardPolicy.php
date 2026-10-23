<?php

namespace App\Policies;

/**
 * Só o dono vê, altera ou apaga o cartão (ACE-04).
 */
class CardPolicy extends OwnedModelPolicy {}
