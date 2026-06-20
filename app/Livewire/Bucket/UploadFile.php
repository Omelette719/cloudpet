<?php

namespace App\Livewire\Bucket;

use App\Models\StorageBucket;
use App\Services\MiniStackService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class UploadFile extends Component
{
    use WithFileUploads;

    public $file = null; 
    public StorageBucket $bucket;
    public string $prefix = '';

    public function mount(StorageBucket $bucket, string $prefix = ''): void
    {
        $this->bucket = $bucket;
        $this->prefix = $prefix;
    }

    // ✅ UBAH NAMA FUNGSI JADI saveFile (menghindari bentrok dengan core Livewire)
    public function saveFile(): void
    {
        // ✅ Batasan ukuran (max) sudah dihapus dari validasi
        $this->validate([
            'file' => 'required'
        ], [
            'file.required' => 'Pilih file terlebih dahulu sebelum klik upload!'
        ]);

        $s3 = MiniStackService::getClient($this->bucket->access_key, $this->bucket->secret_key);
        $finalKey = $this->prefix . $this->file->getClientOriginalName();
        $stream = $this->file->readStream(); 

        try {
            $s3->putObject([
                'Bucket' => $this->bucket->bucket_name,
                'Key'    => $finalKey,
                'Body'   => $stream,
            ]);

            $this->dispatch('file-uploaded');
            $this->file = null; 
            session()->flash('message', '✅ File berhasil diunggah!');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Upload gagal: ' . $e->getMessage());
            session()->flash('error', '❌ Gagal mengunggah file.');
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
    
    public function render()
    {
        return view('livewire.bucket.upload-file');
    }
}