<?php

namespace App\Services;

use App\Jobs\ProvisionBlockVolume;
use App\Models\BlockVolume;
use App\Models\User;
use Exception;
// use App\Jobs\ProvisionBlockVolume; 

class VolumeService
{
    protected MiniStackService $miniStack;

    public function __construct(MiniStackService $miniStack)
    {
        $this->miniStack = $miniStack;
    }

    /**
     * Memulai proses pembuatan Block Volume untuk user.
     */
    public function createBlockVolume(User $user, string $name, int $sizeGb): BlockVolume
    {
        // Gunakan method bawaan dari User model Anda untuk validasi
        if (!$user->canRunInstances()) {
            throw new Exception("Pembuatan gagal. Pastikan akun aktif, saldo mencukupi, dan storage tidak penuh.");
        }

        // Simpan ke DB dengan status awal
        $volume = BlockVolume::create([
            'user_id' => $user->id,
            'volume_name' => $name,
            'size_gb' => $sizeGb,
            'status' => 'PROVISIONING',
        ]);

        // Lempar ke Job Queue agar API hit ke MiniStack berjalan di background
        ProvisionBlockVolume::dispatch($volume);

        return $volume;
    }
}