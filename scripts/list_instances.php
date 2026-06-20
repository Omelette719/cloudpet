<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = \App\Models\ComputeInstance::all()->map(function($r){
    return [
        'id' => $r->id,
        'name' => $r->name,
        'plan' => $r->plan,
        'status' => $r->status,
        'metadata' => $r->metadata,
    ];
});
echo json_encode($rows, JSON_PRETTY_PRINT) . PHP_EOL;
