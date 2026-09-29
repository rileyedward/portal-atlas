<?php

namespace App\Console\Commands;

use App\Actions\Community\RecalculateConfidence;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('confidence:recalculate')]
#[Description('Recalculate cached confidence scores for all markers, items and objectives')]
class RecalculateConfidenceCommand extends Command
{
    public function handle(RecalculateConfidence $recalculate): int
    {
        $this->info("Recalculated {$recalculate->handle()} records.");

        return self::SUCCESS;
    }
}
