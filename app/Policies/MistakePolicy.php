<?php

namespace App\Policies;

/**
 * Só o dono vê o próprio caderno de erros (ACE-04).
 */
class MistakePolicy extends OwnedModelPolicy {}
