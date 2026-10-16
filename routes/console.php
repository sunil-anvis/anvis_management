<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('domains:sync-statuses')->dailyAt('00:01');
Schedule::command('app:send-domain-expiry-notifications')->dailyAt('09:00');

