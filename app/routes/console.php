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

Schedule::command('payments:send-confirmations')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('leads:send-follow-ups')
    ->dailyAt('09:15')
    ->withoutOverlapping();

Schedule::command('rsvp:send-scheduled-invitations')
    ->everyMinute()
    ->withoutOverlapping();

if (config('backup.schedule_enabled')) {
    Schedule::command('backup:run')->dailyAt('02:15')->timezone('Europe/Rome')->withoutOverlapping(180);
    Schedule::command('backup:clean')->dailyAt('03:15')->timezone('Europe/Rome')->withoutOverlapping(180);
    Schedule::command('backup:monitor')->dailyAt('08:30')->timezone('Europe/Rome')->withoutOverlapping();
}
