<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('listas:sincronizar')->daily()->at('02:00')->withoutOverlapping();
Schedule::command('sarlaft:procesar-alertas-vencidas')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/laravel.log'));

Schedule::command('sarlaft:pull-intentos')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/laravel.log'));
