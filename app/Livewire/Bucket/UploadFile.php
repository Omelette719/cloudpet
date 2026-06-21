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
    
    // Properti untuk Custom Metadata
    public string $metaKey = '';
    public string $metaValue = '';

    public function mount(StorageBucket $bucket, string $prefix = ''): void
    {
        $this->bucket = $bucket;
        $this->prefix = $prefix;
    }

    public function saveFile(): void
    {
        $this->validate([
            'file' => 'required'
        ], [
            'file.required' => 'Pilih file terlebih dahulu sebelum klik upload!'
        ]);

        $s3 = MiniStackService::getClient($this->bucket->access_key, $this->bucket->secret_key);
        $finalKey = $this->prefix . $this->file->getClientOriginalName();
        $stream = $this->file->readStream(); 

        try {
            $params = [
                'Bucket' => $this->bucket->bucket_name,
                'Key'    => $finalKey,
                'Body'   => $stream,
            ];

            // Sisipkan metadata jika user mengisinya
            if (!empty(trim($this->metaKey)) && !empty(trim($this->metaValue))) {
                $params['Metadata'] = [
                    trim($this->metaKey) => trim($this->metaValue)
                ];
            }

            $s3->putObject($params);

            $this->dispatch('file-uploaded');
            
            // Reset input
            $this->file = null; 
            $this->metaKey = '';
            $this->metaValue = '';
            
            session()->flash('message', '✅ File beserta metadata berhasil diunggah!');

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