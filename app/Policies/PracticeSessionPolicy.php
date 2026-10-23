<?php

namespace App\Policies;

/**
 * Só o dono vê e responde a sessão de prática (ACE-04).
 */
class PracticeSessionPolicy extends OwnedModelPolicy {}
