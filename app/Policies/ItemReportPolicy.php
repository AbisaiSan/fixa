<?php

namespace App\Policies;

/**
 * Só o dono vê ou desfaz o próprio report (ACE-04).
 */
class ItemReportPolicy extends OwnedModelPolicy {}
