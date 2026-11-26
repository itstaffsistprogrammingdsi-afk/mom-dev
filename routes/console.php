<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('mom:remind-action-items')
    ->dailyAt('09:00')
    ->timezone('Asia/Jakarta')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('mom:check-failed-jobs')
    ->everyFifteenMinutes()
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('mom:prune-data --force')
    ->dailyAt('03:00')
    ->timezone('Asia/Jakarta')
    ->onOneServer()
    ->withoutOverlapping();
