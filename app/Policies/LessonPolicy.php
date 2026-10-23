<?php

namespace App\Policies;

/**
 * Só o dono vê, altera ou apaga a aula (ACE-04, MAT-06).
 */
class LessonPolicy extends OwnedModelPolicy {}
