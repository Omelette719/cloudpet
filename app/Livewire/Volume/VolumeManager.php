<?php

namespace App\Livewire\Volume;

use Livewire\Component;
use App\Models\BlockVolume;
use App\Services\VolumeService;
use App\Services\MiniStackService; // Tambahkan ini
use Illuminate\Support\Facades\Auth;

class VolumeManager extends Component
{
    public string $volumeName = '';
    public int $sizeGb = 10; // Default ukuran 10 GB

    // Aturan validasi (menggantikan Form Request konvensional)
    protected $rules = [
        'volumeName' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_-]+$/',
        'sizeGb' => 'required|integer|min:1|max:1000' // Maks 1 TB per volume
    ];

    public function createVolume()
    {
        $this->validate();

        // Instansiasi manual jika injection lewat parameter fungsi gagal
        $volumeService = new VolumeService(new MiniStackService());

        try {
            $volumeService->createBlockVolume(Auth::user(), $this->volumeName, $this->sizeGb);
            session()->flash('success', 'Berhasil!');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
    public function render()
    {
        // Ingat Aturan Arsitektur: Livewire yang melakukan kueri, BUKAN file Blade!
        $volumes = BlockVolume::where('user_id', Auth::id())->latest()->get();
        
        return view('livewire.volume.volume-manager', [
            'volumes' => $volumes
        ]);
    }
}