<?php

namespace App\Livewire\Bucket;

use App\Models\StorageBucket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Services\MiniStackService;

#[Layout('layouts::app')]
class BucketManager extends Component
{
    // *** PERBARUAN: Penambahan Type Hinting pada properti ***
    public string $bucketId;
    public StorageBucket $bucket;
    public string $currentPrefix = ''; 
    public array $objects = []; 
    public array $folders = []; 

    protected $listeners = ['create-folder' => 'createFolder'];

    // *** PERBARUAN: Penambahan Type Hinting pada parameter $id ***
    public function mount(string $id)
    {
        $this->bucketId = $id;
        
        // Pastikan hanya pemilik bucket yang bisa mengakses
        $this->bucket = StorageBucket::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $this->loadObjects();
    }

    protected function getS3Client(): S3Client
    {
        // Panggil Service Class
        return MiniStackService::getClient($this->bucket->access_key, $this->bucket->secret_key);
    }

    public function loadObjects(): void
    {
        try {
            $s3 = $this->getS3Client();

            $result = $s3->listObjectsV2([
                'Bucket'    => $this->bucket->bucket_name,
                'Prefix'    => $this->currentPrefix,
                'Delimiter' => '/'
            ]);

            $this->folders = $result->get('CommonPrefixes') ?: [];
            $contents = $result->get('Contents') ?: [];
            
            $this->objects = array_filter($contents, function($item) {
                return $item['Key'] !== $this->currentPrefix;
            });

        } catch (\Exception $e) {
            Log::error('Gagal memuat isi bucket: ' . $e->getMessage());
            session()->flash('error', 'Gagal memuat isi bucket.');
        }
    }

    public function openFolder(string $prefix): void
    {
        $this->currentPrefix = $prefix;
        $this->loadObjects();
    }

    public function goUp(): void
    {
        if (empty($this->currentPrefix)) return;

        $parts = explode('/', rtrim($this->currentPrefix, '/'));
        array_pop($parts);
        
        $this->currentPrefix = empty($parts) ? '' : implode('/', $parts) . '/';
        $this->loadObjects();
    }

    public function deleteBucket()
    {
        try {
            $s3 = $this->getS3Client();
            $s3->deleteBucket(['Bucket' => $this->bucket->bucket_name]);
            $this->bucket->delete();
            session()->flash('message', '🗑️ Bucket berhasil dihentikan.');
            return $this->redirectRoute('user.dashboard', navigate: true);
        } catch (AwsException $e) {
            session()->flash('error', $e->getAwsErrorCode() == 'BucketNotEmpty' ? 'Bucket tidak kosong!' : 'Gagal hapus.');
        }
    }

    public function deleteObject(string $key)
    {
        try {
            $this->getS3Client()->deleteObject([
                'Bucket' => $this->bucket->bucket_name,
                'Key'    => $key,
            ]);
            $this->loadObjects(); 
            session()->flash('message', '🗑️ File dihapus.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal hapus: ' . $e->getMessage());
        }
    }

    public function createFolder(string $folderName)
    {
        if(empty($folderName)) return;
        
        try {
            $folderPath = $this->currentPrefix . trim($folderName, '/') . '/';
            $this->getS3Client()->putObject([
                'Bucket' => $this->bucket->bucket_name,
                'Key'    => $folderPath,
                'Body'   => '', 
            ]);
            $this->loadObjects();
            session()->flash('message', '📁 Folder dibuat.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function generateDownloadUrl(string $key)
    {
        $cmd = $this->getS3Client()->getCommand('GetObject', [
            'Bucket' => $this->bucket->bucket_name,
            'Key'    => $key
        ]);

        $request = $this->getS3Client()->createPresignedRequest($cmd, '+20 minutes');
        return redirect((string)$request->getUri());
    }

    public function render()
    {
        return view('livewire.bucket.bucket-manager');
    }
}