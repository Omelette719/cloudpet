<?php

namespace App\Livewire\Volume;

use Livewire\Component;
use App\Models\ActivityLog;
use App\Models\BlockVolume;
use App\Models\ComputeInstance;
use App\Services\VolumeService;
use Illuminate\Support\Facades\Auth;

class VolumeManager extends Component
{
    public string $volumeName = '';
    public int $sizeGb = 10;

    public ?int $attachingVolumeId = null;
    public string $attachInstanceId = '';

    public ?int $provisioningVolumeId = null;

    protected $rules = [
        'volumeName' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_-]+$/',
        'sizeGb'     => 'required|integer|min:1|max:1000',
    ];

    public function createVolume(VolumeService $volumeService)
    {
        $this->validate();

        try {
            $volume = $volumeService->createBlockVolume(Auth::user(), $this->volumeName, $this->sizeGb);
            ActivityLog::log('create_volume', 'block_volume', (string) $volume->id);
            $this->provisioningVolumeId = $volume->id;
            $this->reset('volumeName');
            $this->sizeGb = 10;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function dismissProvisioning(): void
    {
        $this->provisioningVolumeId = null;
    }

    public function openAttachModal(int $volumeId): void
    {
        $this->attachingVolumeId = $volumeId;
        $this->attachInstanceId = '';
    }

    public function closeAttachModal(): void
    {
        $this->attachingVolumeId = null;
        $this->attachInstanceId = '';
    }

    public function confirmAttach(VolumeService $volumeService): void
    {
        if (!$this->attachingVolumeId || !$this->attachInstanceId) {
            session()->flash('error', 'Pilih Compute Instance terlebih dahulu.');
            return;
        }

        $volume   = BlockVolume::where('id', $this->attachingVolumeId)->where('user_id', Auth::id())->firstOrFail();
        $instance = ComputeInstance::where('id', $this->attachInstanceId)->where('user_id', Auth::id())->firstOrFail();

        try {
            $volumeService->attachVolume($volume, $instance);
            ActivityLog::log('attach_volume', 'block_volume', (string) $volume->id);
            session()->flash('success', 'Volume berhasil dipasang.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->closeAttachModal();
    }

    public function detachVolume(int $volumeId, VolumeService $volumeService): void
    {
        $volume = BlockVolume::where('id', $volumeId)->where('user_id', Auth::id())->firstOrFail();

        try {
            $volumeService->detachVolume($volume);
            ActivityLog::log('detach_volume', 'block_volume', (string) $volume->id);
            session()->flash('success', 'Volume berhasil dilepas.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function deleteVolume(int $volumeId, VolumeService $volumeService): void
    {
        $volume = BlockVolume::where('id', $volumeId)->where('user_id', Auth::id())->firstOrFail();

        try {
            $volumeService->deleteVolume($volume);
            ActivityLog::log('delete_volume', 'block_volume', (string) $volume->id);
            session()->flash('success', 'Volume berhasil dihapus.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $volumes = BlockVolume::where('user_id', Auth::id())->latest()->get();

        $instances = ComputeInstance::where('user_id', Auth::id())
            ->whereIn('status', ['RUNNING', 'STOPPED'])
            ->get();

        $provisioningLog = null;
        if ($this->provisioningVolumeId) {
            $vol = BlockVolume::find($this->provisioningVolumeId);
            if ($vol && $vol->user_id === Auth::id()) {
                $provisioningLog = [
                    'log'    => $vol->provision_log ?? '',
                    'status' => $vol->status,
                ];
            } else {
                $this->provisioningVolumeId = null;
            }
        }

        return view('livewire.volume.volume-manager', [
            'volumes'         => $volumes,
            'instances'       => $instances,
            'provisioningLog' => $provisioningLog,
        ]);
    }
}
