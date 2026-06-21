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
use Exception;

class ProvisionBlockVolume implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public BlockVolume $volume;

    /**
     * Jumlah maksimal percobaan ulang (retry) jika jaringan ke MiniStack bermasalah.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(BlockVolume $volume)
    {
        $this->volume = $volume;
    }

    /**
     * Execute the job.
     * MiniStackService secara otomatis diinjeksi (Dependency Injection) oleh Laravel.
     */
    public function handle(MiniStackService $miniStack): void
    {
        try {
            // 1. Eksekusi pemanggilan API ke MiniStack
            $providerId = $miniStack->createVolume($this->volume->volume_name, $this->volume->size_gb);

            // 2. Jika sukses, perbarui status di database
            $this->volume->update([
                'provider_volume_id' => $providerId,
                'status' => 'AVAILABLE', // Volume siap digunakan/ditempel
            ]);

            // 3. Catat di sistem audit trail agar muncul di dashboard riwayat
            ResourceStateLog::create([
                'resource_type' => 'block_volume',
                'resource_id' => $this->volume->id,
                'old_state' => 'PROVISIONING',
                'new_state' => 'AVAILABLE',
                'message' => 'Volume berhasil dialokasikan oleh MiniStack.'
            ]);

        } catch (Exception $e) {
            // Jika gagal, Laravel akan otomatis mencoba lagi (hingga batas $tries habis)
            throw $e;
        }
    }

    /**
     * Handle a job failure (dijalankan otomatis jika sudah gagal 3x).
     */
    public function failed(Exception $exception): void
    {
        // Ubah status menjadi ERROR agar tidak nyangkut di PROVISIONING selamanya
        $this->volume->update(['status' => 'ERROR']);

        ResourceStateLog::create([
            'resource_type' => 'block_volume',
            'resource_id' => $this->volume->id,
            'old_state' => 'PROVISIONING',
            'new_state' => 'ERROR',
            'message' => 'Gagal membuat volume setelah 3x percobaan. Error: ' . $exception->getMessage()
        ]);
    }
}