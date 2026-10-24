<?php

namespace App\Console\Commands;

use App\Domain\Progress\XpLedger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fixa:xp-rebuild')]
#[Description('Recalcula o registro de XP a partir de cartões, respostas e sessões, sem duplicar (J5-B7)')]
class RebuildXp extends Command
{
    public function handle(XpLedger $ledger): int
    {
        $this->info('Eventos de XP criados: '.$ledger->rebuild().'.');

        return self::SUCCESS;
    }
}
