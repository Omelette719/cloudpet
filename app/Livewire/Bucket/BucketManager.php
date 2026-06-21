<?php

namespace App\Livewire\Bucket;

use App\Models\StorageBucket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Livewire\Attributes\On;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Services\MiniStackService;

#[Layout('layouts::app')]
class BucketManager extends Component
{
    public string $bucketId;
    public StorageBucket $bucket;
    public string $currentPrefix = ''; 
    public array $objects = []; 
    public array $folders = []; 
    
    // ✅ Tambahkan properti khusus untuk nama folder baru
    public string $newFolderName = ''; 

    public function mount(string $id)
    {
        $this->bucketId = $id;
        $this->bucket = StorageBucket::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $this->loadObjects();
    }

    protected function getS3Client(): S3Client
    {
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
            
            // 1. Filter out the current directory marker
            $filteredContents = array_filter($contents, function($item) {
                return $item['Key'] !== $this->currentPrefix;
            });

            // 2. Mapping & Reset Keys: Ubah object S3 jadi primitive array agar aman di Livewire
            $this->objects = array_map(function($item) {
                return [
                    'Key'  => $item['Key'],
                    'Size' => $item['Size'],
                    // Ubah Object S3 DateTime menjadi format string biasa
                    'LastModified' => $item['LastModified'] instanceof \DateTimeInterface 
                                        ? $item['LastModified']->format('Y-m-d H:i:s') 
                                        : (string) $item['LastModified'],
                ];
            }, array_values($filteredContents)); // <--- array_values sangat penting di Livewire 3!

        } catch (\Aws\Exception\AwsException $e) {
            if ($e->getAwsErrorCode() == 'NoSuchBucket') {
                $this->bucket->delete(); 
                session()->flash('error', '🧹 Bucket ini sudah tidak ada secara fisik di server S3 (Ghost Bucket). Data dibersihkan otomatis.');
                $this->redirectRoute('user.dashboard', navigate: true);
            } else {
                \Illuminate\Support\Facades\Log::error('AWS S3 Error: ' . $e->getAwsErrorMessage());
                session()->flash('error', 'Gagal memuat isi bucket dari server penyimpanan.');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('System Error - Gagal memuat isi bucket: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan sistem saat membaca bucket.');
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
            
            // Hapus dari database jika sukses di S3
            $this->bucket->delete();
            
            session()->flash('message', '🗑️ Bucket berhasil dihentikan.');
            return $this->redirectRoute('user.dashboard', navigate: true);
            
        } catch (\Aws\Exception\AwsException $e) {
            $errorCode = $e->getAwsErrorCode();
            
            if ($errorCode == 'BucketNotEmpty') {
                session()->flash('error', '⚠️ Bucket tidak kosong! Hapus semua file & folder terlebih dahulu.');
            
            // Jika bucket sudah terhapus/hilang di server S3, langsung bersihkan database
            } elseif ($errorCode == 'NoSuchBucket') {
                $this->bucket->delete();
                session()->flash('message', '🧹 Bucket sudah tidak ada di server S3. Data dibersihkan dari database.');
                return $this->redirectRoute('user.dashboard', navigate: true);
                
            } else {
                session()->flash('error', 'Gagal hapus infrastruktur: ' . $e->getAwsErrorMessage());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal hapus bucket: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan sistem saat menghapus.');
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

    public function createFolder()
    {
        if(empty($this->newFolderName)) return;
        
        try {
            $folderPath = $this->currentPrefix . trim($this->newFolderName, '/') . '/';
            $this->getS3Client()->putObject([
                'Bucket' => $this->bucket->bucket_name,
                'Key'    => $folderPath,
                'Body'   => '', 
            ]);
            
            $this->loadObjects();
            $this->newFolderName = ''; // Kosongkan kolom input
            session()->flash('message', '📁 Folder berhasil dibuat.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal membuat folder: ' . $e->getMessage());
        }
    }

    // ✅ Tangkap sinyal "file-uploaded" dari UploadFile.php agar tabel langsung refresh
    #[On('file-uploaded')]
    public function refreshTableAfterUpload()
    {
        $this->loadObjects();
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

    public function deleteFolder(string $prefix)
    {
        try {
            $s3 = $this->getS3Client();
            
            // 1. Ambil SEMUA file dan sub-folder di dalam folder ini
            $objects = $s3->listObjectsV2([
                'Bucket' => $this->bucket->bucket_name,
                'Prefix' => $prefix
            ]);

            // 2. Kumpulkan key (nama file) yang mau dihapus
            if (!empty($objects->get('Contents'))) {
                $objectsToDelete = [];
                foreach ($objects->get('Contents') as $object) {
                    $objectsToDelete[] = ['Key' => $object['Key']];
                }

                // 3. Hapus semuanya secara massal (termasuk foldernering-nya)
                $s3->deleteObjects([
                    'Bucket' => $this->bucket->bucket_name,
                    'Delete' => ['Objects' => $objectsToDelete]
                ]);
            }
            
            $this->loadObjects();
            session()->flash('message', '🗑️ Folder dan seluruh isinya berhasil dihapus.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menghapus folder: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.bucket.bucket-manager');
    }
}