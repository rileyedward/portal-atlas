<?php

use App\Models\PageView;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Page views are kept for PageView::RETENTION_DAYS. Needs the scheduler enabled on Laravel Cloud.
Schedule::command('model:prune', ['--model' => [PageView::class]])->daily();
