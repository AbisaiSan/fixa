<?php

namespace App\Domain\Lessons\Reports;

/**
 * Estado do report (MAT-05). Só "open" tira o item da rotina.
 */
enum ReportStatus: string
{
    // Em análise: o item sai da rotina.
    case Open = 'open';
    // O aluno desfez o report.
    case Withdrawn = 'withdrawn';
    // O item deixou de existir: foi removido ou a revisão foi regenerada.
    case Superseded = 'superseded';
}
