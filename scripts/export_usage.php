<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ComputeInstance;

$rows = ComputeInstance::with('user')->orderBy('created_at','desc')->get();

$dir = __DIR__ . '/../storage/exports';
if (! is_dir($dir)) mkdir($dir, 0755, true);
$filename = $dir . '/compute-usage-' . date('YmdHis') . '.csv';

$out = fopen($filename, 'w');
fputcsv($out, ['id','name','user_email','plan','status','started_at','stopped_at','usage_hours','price_per_hour','cost']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r->id,
        $r->name,
        $r->user->email ?? null,
        $r->plan,
        $r->status,
        $r->started_at ? $r->started_at->toDateTimeString() : null,
        $r->stopped_at ? $r->stopped_at->toDateTimeString() : null,
        $r->usage_hours,
        $r->price_per_hour,
        $r->cost,
    ]);
}
fclose($out);

echo "Wrote usage CSV to: {$filename}\n";

return 0;
