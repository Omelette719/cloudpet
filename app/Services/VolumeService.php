<?php

namespace App\Services;

use App\Jobs\ProvisionBlockVolume;
use App\Models\BlockVolume;
use App\Models\ComputeInstance;
use App\Models\ResourceStateLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class VolumeService
{
    protected MiniStackService $miniStack;

    public function __construct(MiniStackService $miniStack)
    {
        $this->miniStack = $miniStack;
    }

    public function createBlockVolume(User $user, string $name, int $sizeGb): BlockVolume
    {
        if (!$user->canRunInstances()) {
            throw new Exception('Pembuatan gagal. Pastikan akun aktif, saldo mencukupi, dan storage tidak penuh.');
        }

        $volume = BlockVolume::create([
            'user_id'     => $user->id,
            'volume_name' => $name,
            'size_gb'     => $sizeGb,
            'status'      => 'PROVISIONING',
        ]);

        ProvisionBlockVolume::dispatch($volume);

        return $volume;
    }

    /** Pasang volume ke sebuah Compute Instance. */
    public function attachVolume(BlockVolume $volume, ComputeInstance $instance): void
    {
        if ($volume->status !== 'AVAILABLE') {
            throw new Exception('Hanya volume berstatus AVAILABLE yang bisa dipasang.');
        }
        if (!$volume->provider_volume_id) {
            throw new Exception('Volume belum selesai diprovisioning.');
        }

        // Catatan: instance dijalankan sbg container Docker lokal, bukan via
        // provider eksternal — kita pakai container_id sbg referensi "instance id".
        $instanceRef = $instance->metadata['container_id'] ?? (string) $instance->id;

        if (!$this->miniStack->attachVolume($volume->provider_volume_id, $instanceRef)) {
            throw new Exception('Gagal memasang volume ke instance pada provider.');
        }

        DB::transaction(function () use ($volume, $instance) {
            $old = $volume->status;
            $volume->update(['status' => 'ATTACHED', 'compute_instance_id' => $instance->id]);

            ResourceStateLog::create([
                'id'            => (string) Str::uuid(),
                'resource_type' => 'block_volume',
                'resource_id'   => $volume->id,
                'old_state'     => $old,
                'new_state'     => 'ATTACHED',
                'message'       => "Volume dipasang ke instance {$instance->name}.",
            ]);
        });
    }

    /** Lepas volume dari Compute Instance. */
    public function detachVolume(BlockVolume $volume): void
    {
        if ($volume->status !== 'ATTACHED') {
            throw new Exception('Volume tidak sedang terpasang.');
        }

        $instance    = $volume->computeInstance; // bisa null kalau instance sudah dihapus permanen
        $instanceRef = $instance?->metadata['container_id'] ?? (string) ($instance?->id ?? '');

        // Kalau instance-nya sudah tidak ada lagi, tidak perlu panggil provider — langsung bersihkan state.
        if ($instance !== null && !$this->miniStack->detachVolume($volume->provider_volume_id, $instanceRef)) {
            throw new Exception('Gagal melepas volume dari instance pada provider.');
        }

        DB::transaction(function () use ($volume) {
            $volume->update(['status' => 'AVAILABLE', 'compute_instance_id' => null]);

            ResourceStateLog::create([
                'id'            => (string) Str::uuid(),
                'resource_type' => 'block_volume',
                'resource_id'   => $volume->id,
                'old_state'     => 'ATTACHED',
                'new_state'     => 'AVAILABLE',
                'message'       => 'Volume dilepas dari instance.',
            ]);
        });
    }

    /** Hapus volume permanen. */
    public function deleteVolume(BlockVolume $volume): void
    {
        if ($volume->status === 'ATTACHED') {
            throw new Exception('Lepas (detach) volume terlebih dahulu sebelum menghapus.');
        }

        if ($volume->provider_volume_id && !$this->miniStack->deleteVolume($volume->provider_volume_id)) {
            throw new Exception('Gagal menghapus volume pada provider.');
        }

        ResourceStateLog::create([
            'id'            => (string) Str::uuid(),
            'resource_type' => 'block_volume',
            'resource_id'   => $volume->id,
            'old_state'     => $volume->status,
            'new_state'     => 'DELETED',
            'message'       => "Volume {$volume->volume_name} dihapus oleh user.",
        ]);

        $volume->delete();
    }
}