<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Filesystem\FilesystemAdapter;

class UploadPetPhoto extends Component
{
    use WithFileUploads;

    public $photo;
    public $uploadedUrl;

    public function save()
    {
        // Check if photo is uploaded first
        if (!$this->photo) {
            session()->flash('error', 'Silakan pilih foto terlebih dahulu');
            return;
        }

        $this->validate([
            'photo' => 'required|image|max:1024',
        ]);

        try {
            // Try S3/MinIO first
            $disk = 's3';
            $path = $this->photo->store('pet-photos', $disk);
            
            /** @var FilesystemAdapter $storage */
            $storage = Storage::disk($disk);
            $this->uploadedUrl = $storage->url($path);
            
            $this->reset('photo');
            session()->flash('message', 'Foto berhasil disimpan ke Bucket MinIO! 🎉');
            
        } catch (\Exception $e) {
            Log::error('MinIO Upload Error: ' . $e->getMessage());
            
            // Fallback to local storage if MinIO fails
            try {
                Log::warning('Falling back to local storage');
                $path = $this->photo->store('pet-photos', 'public');
                /** @var FilesystemAdapter $storage */
                $storage = Storage::disk('public');
                $this->uploadedUrl = $storage->url($path);
                
                $this->reset('photo');
                session()->flash('message', 'Foto berhasil disimpan ke Local Storage (MinIO tidak tersedia)');
                
            } catch (\Exception $fallbackError) {
                Log::error('Local Storage Fallback Error: ' . $fallbackError->getMessage());
                session()->flash('error', 'Gagal upload foto: ' . $e->getMessage());
            }
        }
    }

    public function render()
    {
        return view('livewire.upload-pet-photo');
    }
}
