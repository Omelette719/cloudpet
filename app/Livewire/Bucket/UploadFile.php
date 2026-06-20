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

    // Type Hinting untuk mencegah error
    public $file = null; 
    public StorageBucket $bucket;
    public string $prefix = '';

    public function mount(StorageBucket $bucket, string $prefix = ''): void
    {
        $this->bucket = $bucket;
        $this->prefix = $prefix;
    }

    public function upload(): void
    {
        $this->validate(['file' => 'required|max:10240']);

        // Memanggil Service Class agar tidak duplikasi
        $s3 = MiniStackService::getClient($this->bucket->access_key, $this->bucket->secret_key);
        $finalKey = $this->prefix . $this->file->getClientOriginalName();

        // Membuka file stream
        $stream = fopen($this->file->getRealPath(), 'r');

        try {
            $s3->putObject([
                'Bucket' => $this->bucket->bucket_name,
                'Key'    => $finalKey,
                'Body'   => $stream,
            ]);

            $this->dispatch('file-uploaded');
            
            // Reset file yang aman untuk Livewire 3
            $this->file = null; 
            session()->flash('message', '✅ File berhasil diupload!');

        } catch (\Exception $e) {
            // Error Handling
            Log::error('Upload gagal: ' . $e->getMessage());
            session()->flash('error', '❌ Gagal mengunggah file: Server penyimpanan menolak.');
        } finally {
            // Mencegah Memory Leak: Pastikan file pointer selalu ditutup meskipun upload gagal
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