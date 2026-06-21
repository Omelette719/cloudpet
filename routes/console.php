<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:tick')->hourly();
Schedule::command('storage:sync')->everyFifteenMinutes();

// Untuk Laravel 10 ke bawah — di app/Console/Kernel.php method schedule():
// $schedule->command('billing:tick')->hourly();
// $schedule->command('storage:sync')->everyFifteenMinutes();

// Jalankan scheduler di Windows (development):
// php artisan schedule:work