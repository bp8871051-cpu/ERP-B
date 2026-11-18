<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic permanent purge of soft-deleted records older than 30 days every day at midnight
Schedule::command('erp:purge-soft-deleted --days=30')->daily();
