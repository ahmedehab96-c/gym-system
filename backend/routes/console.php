<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('memberships:update-statuses')->daily();
Schedule::command('subscriptions:update-statuses')->daily();

// Phase 24: automated communication reminders.
Schedule::command('invoices:send-payment-reminders')->daily();
Schedule::command('classes:send-reminders')->hourly();
