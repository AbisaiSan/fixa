<?php

namespace App\Domain\Lessons\Reports;

/**
 * O que está errado no item reportado (MAT-05), como no protótipo.
 */
enum ReportReason: string
{
    case WrongTranslation = 'wrong_translation';
    case ConfusingExplanation = 'confusing_explanation';
    case UnnaturalExample = 'unnatural_example';
    case Other = 'other';
}
