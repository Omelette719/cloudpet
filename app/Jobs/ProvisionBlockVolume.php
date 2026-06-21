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
use Illuminate\Support\Facades\Log; // Tambahkan import Log
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
        Log::info("Memulai proses provisioning volume ID: {$this->volume->id} - {$this->volume->volume_name}");

        try {
            // Panggil service untuk membuat volume
            $providerId = $miniStack->createVolume($this->volume->volume_name, $this->volume->size_gb);

            // Update database jika sukses
            $this->volume->update([
                'provider_volume_id' => $providerId,
                'status' => 'AVAILABLE',
            ]);

            ResourceStateLog::create([
                'id'            => (string) Str::uuid(),
                'resource_type' => 'block_volume',
                'resource_id'   => $this->volume->id,
                'old_state'     => 'PROVISIONING',
                'new_state'     => 'AVAILABLE',
                'message'       => 'Volume berhasil dialokasikan oleh MiniStack.',
            ]);
            
            Log::info("Berhasil: Volume ID {$this->volume->id} telah tersedia.");

        } catch (Exception $e) {
            // LOG INI SANGAT PENTING:
            // Ini akan mencatat pesan error asli dari API atau koneksi
            Log::error("GAGAL Provisioning Volume ID {$this->volume->id}. Error: " . $e->getMessage());
            
            // Lempar kembali exception agar job tercatat sebagai FAIL dan diproses retry
            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->volume->update(['status' => 'ERROR']);

        ResourceStateLog::create([
            'id'            => (string) Str::uuid(),
            'resource_type' => 'block_volume',
            'resource_id'   => $this->volume->id,
            'old_state'     => 'PROVISIONING',
            'new_state'     => 'ERROR',
            'message'       => 'Gagal membuat volume. Pesan Error: ' . $exception->getMessage(),
        ]);
        
        Log::critical("Provisioning Volume ID {$this->volume->id} dihentikan karena gagal setelah beberapa kali percobaan.");
    }
}