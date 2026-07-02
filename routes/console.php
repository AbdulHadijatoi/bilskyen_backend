<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$timezone = config('marketplace.timezone', 'Europe/Copenhagen');

Schedule::command('subscriptions:expire')
    ->dailyAt('00:01')
    ->timezone($timezone);

Schedule::command('subscriptions:activate-scheduled')
    ->dailyAt('00:02')
    ->timezone($timezone);

Schedule::command('subscriptions:charge-daily-listings')
    ->dailyAt('00:05')
    ->timezone($timezone);

Schedule::command('listings:expire-stale')
    ->dailyAt('00:15')
    ->timezone($timezone);

Schedule::command('listings:send-expiry-reminders')
    ->dailyAt('08:00')
    ->timezone($timezone);

Schedule::command('invoices:generate-usage')
    ->monthlyOn((int) config('marketplace.usage_invoice_day_of_month', 1), '01:00')
    ->timezone($timezone);

Schedule::command('invoices:mark-overdue')
    ->dailyAt('02:00')
    ->timezone($timezone);

Schedule::command('alerts:price-drops')->dailyAt('08:00')->timezone($timezone);
Schedule::command('alerts:saved-searches')->dailyAt('08:30')->timezone($timezone);
Schedule::command('dealers:market-pulse-digest')->weeklyOn(1, '09:00')->timezone($timezone);

Schedule::command('notifications:dispatch')
    ->everyFiveMinutes()
    ->timezone($timezone);

Schedule::command('analytics:aggregate-daily')
    ->dailyAt('01:30')
    ->timezone($timezone);

Schedule::command('cms:publish-scheduled')
    ->everyFiveMinutes()
    ->timezone($timezone);

Schedule::command('syndication:sync')
    ->hourly()
    ->timezone($timezone);

Schedule::command('feeds:upload-sftp')
    ->dailyAt('03:00')
    ->timezone($timezone);

Schedule::command('marketing:process-queue')
    ->everyFifteenMinutes()
    ->timezone($timezone);

Schedule::command('marketing:process-abandoned')
    ->everyThirtyMinutes()
    ->timezone($timezone);

Schedule::command('gdpr:process-requests')
    ->hourly()
    ->timezone($timezone);

Schedule::command('vehicle-import:expire-stale-batches')
    ->everyFiveMinutes()
    ->timezone($timezone);

Schedule::command('queue:work database --stop-when-empty --max-time=55')
    ->everyMinute()
    ->withoutOverlapping()
    ->timezone($timezone);
