<?php

namespace App\Services;

use App\Jobs\ProvisionBlockVolume;
use App\Models\BlockVolume;
use App\Models\ComputeInstance;
use App\Models\ResourceStateLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            throw new Exception('Pembuatan gagal. Pastikan akun aktif dan saldo mencukupi.');
        }

        $usedGb = $user->volumeUsedGb();
        $limitGb = $user->volumeLimitGb();
        if ($usedGb + $sizeGb > $limitGb) {
            throw new Exception("Batas block storage tercapai. Terpakai: {$usedGb} GB / {$limitGb} GB. Upgrade membership untuk menambah kapasitas.");
        }

        $volume = BlockVolume::create([
            'user_id'     => $user->id,
            'volume_name' => $name,
            'size_gb'     => $sizeGb,
            'status'      => 'PROVISIONING',
        ]);

        ProvisionBlockVolume::dispatchSync($volume);

        return $volume;
    }

    public function attachVolume(BlockVolume $volume, ComputeInstance $instance): void
    {
        if ($volume->status !== 'AVAILABLE') {
            throw new Exception('Hanya volume berstatus AVAILABLE yang bisa dipasang.');
        }
        if (!$volume->provider_volume_id) {
            throw new Exception('Volume belum selesai diprovisioning.');
        }

        $instanceRef = $instance->metadata['container_id'] ?? (string) $instance->id;

        // Control plane: EC2 AttachVolume (best effort — instance bukan EC2 asli)
        try {
            $this->miniStack->attachVolume($volume->provider_volume_id, $instanceRef);
        } catch (Exception $e) {
            Log::warning("EC2 AttachVolume skip: {$e->getMessage()}");
        }

        // Data plane: recreate container dengan volume baru di-mount
        if (in_array($instance->status, ['RUNNING', 'STOPPED'])) {
            $mounts = $this->getVolumeMounts($instance, adding: $volume);
            $this->recreateContainerWithVolumes($instance, $mounts);
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

    public function detachVolume(BlockVolume $volume): void
    {
        if ($volume->status !== 'ATTACHED') {
            throw new Exception('Volume tidak sedang terpasang.');
        }

        $instance    = $volume->computeInstance;
        $instanceRef = $instance?->metadata['container_id'] ?? (string) ($instance?->id ?? '');

        // Control plane: EC2 DetachVolume (best effort)
        if ($instance && $volume->provider_volume_id) {
            try {
                $this->miniStack->detachVolume($volume->provider_volume_id, $instanceRef);
            } catch (Exception $e) {
                Log::warning("EC2 DetachVolume skip: {$e->getMessage()}");
            }
        }

        // Data plane: recreate container tanpa volume ini
        if ($instance && in_array($instance->status, ['RUNNING', 'STOPPED'])) {
            $mounts = $this->getVolumeMounts($instance, removing: $volume);
            $this->recreateContainerWithVolumes($instance, $mounts);
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

    public function deleteVolume(BlockVolume $volume): void
    {
        if ($volume->status === 'ATTACHED') {
            throw new Exception('Volume sedang terpasang (ATTACHED). Lepas (detach) terlebih dahulu.');
        }

        DB::transaction(function () use ($volume) {
            // Control plane: EC2 DeleteVolume
            if ($volume->provider_volume_id) {
                try {
                    $this->miniStack->deleteVolume($volume->provider_volume_id);
                } catch (Exception $e) {
                    Log::error("EC2 DeleteVolume gagal: {$e->getMessage()}");
                }

                // Data plane: hapus Docker named volume
                $dockerVol = "cp_vol_{$volume->provider_volume_id}";
                exec('docker volume rm ' . escapeshellarg($dockerVol) . ' 2>&1', $out, $rc);
                if ($rc !== 0) {
                    Log::warning("Docker volume rm gagal untuk {$dockerVol}: " . implode(' ', $out));
                }
            }

            ResourceStateLog::create([
                'id'            => (string) Str::uuid(),
                'resource_type' => 'block_volume',
                'resource_id'   => $volume->id,
                'old_state'     => $volume->status,
                'new_state'     => 'DELETED',
                'message'       => "Volume {$volume->volume_name} dihapus.",
            ]);

            $volume->delete();
        });
    }

    // ── Docker volume mount helpers ─────────────────────────────────────────

    protected function getVolumeMounts(ComputeInstance $instance, ?BlockVolume $adding = null, ?BlockVolume $removing = null): array
    {
        $volumes = BlockVolume::where('compute_instance_id', $instance->id)
            ->where('status', 'ATTACHED')
            ->get();

        $mounts = [];
        foreach ($volumes as $vol) {
            if ($removing && $vol->id === $removing->id) {
                continue;
            }
            $mounts[] = [
                'docker_name' => "cp_vol_{$vol->provider_volume_id}",
                'mount_path'  => "/mnt/volumes/{$vol->volume_name}",
            ];
        }

        if ($adding) {
            $mounts[] = [
                'docker_name' => "cp_vol_{$adding->provider_volume_id}",
                'mount_path'  => "/mnt/volumes/{$adding->volume_name}",
            ];
        }

        return $mounts;
    }

    protected function recreateContainerWithVolumes(ComputeInstance $instance, array $mounts): void
    {
        $meta          = $instance->metadata ?? [];
        $containerName = $meta['container_name'] ?? null;

        if (!$containerName) {
            throw new Exception('Instance tidak memiliki container.');
        }

        $wasRunning = $instance->status === 'RUNNING';
        $tempImage  = 'cp_snap_' . $instance->id;

        // 1. Commit state container ke image sementara
        exec('docker commit ' . escapeshellarg($containerName) . ' ' . escapeshellarg($tempImage) . ' 2>&1', $commitOut, $commitRc);
        if ($commitRc !== 0) {
            throw new Exception('Gagal commit state container: ' . implode(' ', $commitOut));
        }

        // 2. Stop + remove container lama
        exec('docker stop ' . escapeshellarg($containerName) . ' 2>&1');
        exec('docker rm ' . escapeshellarg($containerName) . ' 2>&1', $rmOut, $rmRc);
        if ($rmRc !== 0) {
            exec('docker rmi ' . escapeshellarg($tempImage) . ' 2>&1');
            throw new Exception('Gagal menghapus container lama.');
        }

        // 3. Build volume flags
        $volumeFlags = '';
        foreach ($mounts as $m) {
            $volumeFlags .= sprintf(' -v %s:%s', escapeshellarg($m['docker_name']), escapeshellarg($m['mount_path']));
        }

        // 4. Recreate container
        $createCmd = $this->buildDockerCreateCmd($instance, $tempImage, $volumeFlags);
        exec($createCmd . ' 2>&1', $createOut, $createRc);

        if ($createRc !== 0) {
            Log::error("Docker create gagal: " . implode(' ', $createOut));
            $fallback = $this->buildDockerCreateCmd($instance, $tempImage, '');
            exec($fallback . ' 2>&1');
            if ($wasRunning) {
                exec('docker start ' . escapeshellarg($containerName) . ' 2>&1');
            }
            exec('docker rmi ' . escapeshellarg($tempImage) . ' 2>&1');
            throw new Exception('Gagal memasang volume ke container.');
        }

        // 5. Start jika sebelumnya running
        if ($wasRunning) {
            exec('docker start ' . escapeshellarg($containerName) . ' 2>&1');
        }

        // 6. Update container ID di metadata
        exec('docker inspect --format "{{.Id}}" ' . escapeshellarg($containerName) . ' 2>&1', $idOut, $idRc);
        if ($idRc === 0 && !empty($idOut[0])) {
            $meta['container_id'] = trim($idOut[0]);
            $instance->metadata = $meta;
            $instance->save();
        }

        // 7. Cleanup image sementara
        exec('docker rmi ' . escapeshellarg($tempImage) . ' 2>&1');
    }

    protected function buildDockerCreateCmd(ComputeInstance $instance, string $image, string $volumeFlags): string
    {
        $meta = $instance->metadata ?? [];
        $type = $meta['type'] ?? 'vm';
        $res  = $meta['resources'] ?? ['cpu' => 1, 'memory' => 1024];

        $parts = [
            'docker create',
            '--name ' . escapeshellarg($meta['container_name']),
            sprintf('--memory=%dm', $res['memory']),
            sprintf('--cpus=%s', $res['cpu']),
            '--network=' . escapeshellarg($meta['network']),
        ];

        // Port mapping per tipe instance
        if ($type === 'vm' && isset($meta['ssh_port'])) {
            $parts[] = sprintf('-p %d:22', $meta['ssh_port']);
        } elseif ($type === 'ide' && isset($meta['ide_port'])) {
            $parts[] = sprintf('-p %d:8080', $meta['ide_port']);
        } elseif ($type === 'notebook' && isset($meta['nb_port'])) {
            $parts[] = sprintf('-p %d:8888', $meta['nb_port']);
        }

        // Storage bind mount
        $storagePath = $meta['storage_path'] ?? '';
        if ($storagePath) {
            $target = match ($type) {
                'ide'      => '/home/coder/project',
                'notebook' => '/home/jovyan/work',
                default    => '/data',
            };
            $parts[] = sprintf('-v %s:%s', escapeshellarg($storagePath), $target);
        }

        // Environment variables per tipe
        if ($type === 'ide' && isset($meta['ide_password'])) {
            $parts[] = '-e PASSWORD=' . escapeshellarg($meta['ide_password']);
        } elseif ($type === 'notebook' && isset($meta['nb_token'])) {
            $parts[] = '-e JUPYTER_TOKEN=' . escapeshellarg($meta['nb_token']);
        }

        // Block volume mounts
        if (trim($volumeFlags)) {
            $parts[] = trim($volumeFlags);
        }

        $parts[] = '--restart=unless-stopped';
        $parts[] = escapeshellarg($image);

        return implode(' ', $parts);
    }
}
