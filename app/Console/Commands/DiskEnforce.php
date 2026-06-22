<?php

namespace App\Console\Commands;

use App\Models\ComputeInstance;
use App\Services\ComputeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DiskEnforce extends Command
{
    protected $signature   = 'disk:enforce';
    protected $description = 'Cek penggunaan disk setiap instance RUNNING. Stop jika melebihi limit volume.';

    public function handle(ComputeService $compute): int
    {
        $instances = ComputeInstance::where('status', 'RUNNING')
            ->whereNotNull('metadata')
            ->get();

        $this->info('[' . now()->toDateTimeString() . '] Memeriksa ' . $instances->count() . ' instance...');

        foreach ($instances as $instance) {
            $meta       = $instance->metadata ?? [];
            $volumeGb   = $meta['volume_size_gb'] ?? null;
            $target     = $meta['container_name'] ?? $meta['container_id'] ?? null;

            if (!$volumeGb || !$target) continue;

            $mountPoint = match ($meta['type'] ?? 'vm') {
                'ide'      => '/home/coder/project',
                'notebook' => '/home/jovyan/work',
                default    => '/data',
            };

            exec('docker exec ' . escapeshellarg($target) . ' du -sb ' . escapeshellarg($mountPoint) . ' 2>/dev/null', $out, $rc);
            $usedBytes = $rc === 0 ? (int) ($out[0] ?? 0) : 0;
            $usedGb    = $usedBytes / (1024 ** 3);

            $pct = $volumeGb > 0 ? round(($usedGb / $volumeGb) * 100, 0) : 0;

            if ($usedGb >= $volumeGb) {
                $this->warn("  {$instance->name}: {$usedGb}G / {$volumeGb}G (OVER LIMIT) — stopping...");
                Log::warning("Disk over limit: {$instance->name} ({$usedGb}G / {$volumeGb}G). Auto-stop.");

                // Read-only dulu agar data tidak corrupt
                exec('docker exec ' . escapeshellarg($target) . ' chmod -R a-w ' . escapeshellarg($mountPoint) . ' 2>/dev/null');
                $compute->changeStatus($instance, 'stop');
            } elseif ($pct >= 90) {
                $this->line("  {$instance->name}: {$pct}% — mendekati limit.");
            }
        }

        $this->info('Selesai.');
        return self::SUCCESS;
    }
}
