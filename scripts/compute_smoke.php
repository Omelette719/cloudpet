<?php

// Lightweight smoke script to create a compute instance using the app's ComputeService.
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\ComputeService;

$user = User::first();
if (! $user) {
    echo "No users found in database.\n";
    exit(1);
}

$svc = app(ComputeService::class);
$instance = $svc->createInstance($user, 'micro');

// reload from DB to get database-generated id irrespective of model key settings
$fresh = \App\Models\ComputeInstance::where('name', $instance->name)->orderBy('created_at', 'desc')->first();

echo "Created instance id=" . ($fresh->id ?? '[unknown]') . " status=" . ($fresh->status ?? $instance->status) . "\n";
if (isset($fresh->metadata['taskArn'])) {
    echo "taskArn: " . $fresh->metadata['taskArn'] . "\n";
}

// exercise lifecycle: stop -> start -> terminate
echo "Stopping instance...\n";
$svc->changeStatus($fresh, 'stop');
$fresh = \App\Models\ComputeInstance::find($fresh->id);
echo "Status after stop: " . ($fresh->status ?? '[unknown]') . "\n";

echo "Starting instance...\n";
$svc->changeStatus($fresh, 'start');
$fresh = \App\Models\ComputeInstance::find($fresh->id);
echo "Status after start: " . ($fresh->status ?? '[unknown]') . "\n";

echo "Terminating instance...\n";
$svc->changeStatus($fresh, 'terminate');
$fresh = \App\Models\ComputeInstance::find($fresh->id);
echo "Status after terminate: " . ($fresh->status ?? '[unknown]') . "\n";

return 0;
