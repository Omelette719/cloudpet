<?php

namespace App\Livewire\Volume;

use Livewire\Component;
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

    protected $rules = [
        'volumeName' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_-]+$/',
        'sizeGb'     => 'required|integer|min:1|max:1000',
    ];

    public function createVolume(VolumeService $volumeService)
    {
        $this->validate();

        try {
            $volumeService->createBlockVolume(Auth::user(), $this->volumeName, $this->sizeGb);
            session()->flash('success', 'Volume sedang diprovisioning!');
            $this->reset('volumeName');
            $this->sizeGb = 10;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
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

        return view('livewire.volume.volume-manager', [
            'volumes'   => $volumes,
            'instances' => $instances,
        ]);
    }
}