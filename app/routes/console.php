<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();*/

Schedule::command('payments:send-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping();

Schedule::command('leads:send-follow-ups')
    ->dailyAt('09:15')
    ->withoutOverlapping();
