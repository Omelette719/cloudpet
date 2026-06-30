<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('cloud:meter-storage')->hourly();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:tick')->hourly();
Schedule::command('storage:sync')->everyFifteenMinutes();
Schedule::command('disk:enforce')->everyFiveMinutes();

// Untuk Laravel 10 ke bawah — di app/Console/Kernel.php method schedule():
// $schedule->command('billing:tick')->hourly();
// $schedule->command('storage:sync')->everyFifteenMinutes();

// Jalankan scheduler di Windows (development):
// php artisan schedule:work