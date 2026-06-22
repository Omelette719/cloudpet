<?php

namespace App\Livewire\Bucket;

use App\Models\StorageBucket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // Ditambahkan untuk Transaksi DB
use Illuminate\Support\Str;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Livewire\Component;
use App\Services\MiniStackService;

class CreateBucket extends Component
{
    public $loading = false; // *** PERBARUAN: Penanganan Double-Click ***

    public function createBucket()
    {
        $this->loading = true;
        $user = Auth::user();

        if (!$user->canCreateBucket()) {
            session()->flash('error', 'Batas jumlah bucket tercapai (' . $user->maxBuckets() . ' bucket untuk membership ' . $user->membershipLabel() . '). Upgrade membership untuk menambah bucket.');
            $this->loading = false;
            return;
        }

        $accessKey  = 'AKIA' . strtoupper(Str::random(16));
        $secretKey  = Str::random(32);
        $cleanName  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->name));
        $bucketName = "cp-{$cleanName}-" . strtolower(Str::random(5));

        try {
            $s3 = MiniStackService::getClient($accessKey, $secretKey);

            // 2. Gunakan DB Transaction agar konsisten
            DB::beginTransaction();

            // 3. Buat Bucket di MiniStack
            $s3->createBucket(['Bucket' => $bucketName]);

            // 4. Simpan ke Database
            StorageBucket::create([
                'id'          => (string) Str::uuid(),
                'user_id'     => $user->id,
                'bucket_name' => $bucketName,
                'access_key'  => $accessKey,
                'secret_key'  => $secretKey,
            ]);

            DB::commit(); // Simpan perubahan jika sukses semua

            session()->flash('message', '🎉 Bucket berhasil dibuat!');
            return $this->redirectRoute('user.dashboard', navigate: true);

        } catch (AwsException $e) {
            DB::rollBack(); // *** PERBARUAN: Rollback jika S3 gagal ***
            Log::error('AWS SDK Error: ' . $e->getMessage());
            session()->flash('error', 'Infrastruktur tidak merespon: ' . $e->getAwsErrorMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Kesalahan Sistem: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan internal.');
        } finally {
            $this->loading = false; // Matikan loading state
        }
    }

    public function render()
    {
        return view('livewire.bucket.create-bucket');
    }
}