<?php

namespace App\Jobs;

use App\Models\BlockVolume;
use App\Models\ResourceStateLog;
use App\Services\MiniStackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class ProvisionBlockVolume implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public BlockVolume $volume;
    public int $tries = 3;

    public function __construct(BlockVolume $volume)
    {
        $this->volume = $volume;
    }

    public function handle(MiniStackService $miniStack): void
    {
        Log::info("Provisioning volume {$this->volume->id}: {$this->volume->volume_name}");

        try {
            $this->appendLog("[1/3] Memulai provisioning volume \"{$this->volume->volume_name}\" ({$this->volume->size_gb} GB)...");

            // Control plane: buat volume di Ministack via EC2 API
            $this->appendLog("[2/3] Membuat volume di cloud provider (EC2 CreateVolume)...");
            $providerId = $miniStack->createVolume(
                $this->volume->volume_name,
                $this->volume->size_gb
            );
            $this->appendLog("  Volume ID: {$providerId}");
            $this->appendLog("  Region: " . config('services.ministack.region', 'id-1') . "a");
            $this->appendLog("  Tipe: gp2, Ukuran: {$this->volume->size_gb} GB");

            // Data plane: buat Docker named volume
            $dockerVol = "cp_vol_{$providerId}";
            $this->appendLog("[3/3] Membuat data plane (Docker named volume)...");
            $this->appendLog("  docker volume create {$dockerVol}");
            exec('docker volume create ' . escapeshellarg($dockerVol) . ' 2>&1', $out, $rc);
            if ($rc !== 0) {
                $this->appendLog("  ERROR: " . implode(' ', $out));
                throw new Exception("Docker volume create gagal: " . implode(' ', $out));
            }
            $this->appendLog("  Docker volume \"{$dockerVol}\" berhasil dibuat.");

            $this->volume->update([
                'provider_volume_id' => $providerId,
                'status'             => 'AVAILABLE',
            ]);

            $this->appendLog("---");
            $this->appendLog("Volume siap digunakan!");
            $this->appendLog("Attach ke Compute Instance untuk mulai menggunakan.");

            ResourceStateLog::create([
                'id'            => (string) Str::uuid(),
                'resource_type' => 'block_volume',
                'resource_id'   => $this->volume->id,
                'old_state'     => 'PROVISIONING',
                'new_state'     => 'AVAILABLE',
                'message'       => "Volume berhasil dibuat (EC2: {$providerId}).",
            ]);

            Log::info("Volume {$this->volume->id} tersedia: {$providerId}");

        } catch (Exception $e) {
            Log::error("Gagal provisioning volume {$this->volume->id}: {$e->getMessage()}");
            $this->appendLog("GAGAL: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->volume->update(['status' => 'ERROR']);
        $this->appendLog("Provisioning dihentikan setelah beberapa percobaan gagal.");

        ResourceStateLog::create([
            'id'            => (string) Str::uuid(),
            'resource_type' => 'block_volume',
            'resource_id'   => $this->volume->id,
            'old_state'     => 'PROVISIONING',
            'new_state'     => 'ERROR',
            'message'       => 'Gagal provisioning: ' . $exception->getMessage(),
        ]);
    }

    protected function appendLog(string $line): void
    {
        $this->volume->provision_log = ($this->volume->provision_log ?? '') . $line . "\n";
        $this->volume->save();
    }
}
