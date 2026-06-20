<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use App\Models\StorageBucket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class UserDashboard extends Component
{
    public function deleteBucket($bucketId)
    {
        // Cari bucket yang sesuai dengan ID dan milik user yang sedang login
        $bucket = StorageBucket::where('id', $bucketId)
                               ->where('user_id', Auth::id())
                               ->firstOrFail();

        try {
            // 1. Hubungkan ke MiniStack menggunakan kredensial spesifik bucket tersebut
            $s3 = new S3Client([
                'version'                 => 'latest',
                'region'                  => 'id-1',
                'endpoint'                => env('MINISTACK_ENDPOINT', 'http://127.0.0.1:4566'),
                'use_path_style_endpoint' => true,
                'credentials'             => [
                    'key'    => $bucket->access_key,
                    'secret' => $bucket->secret_key,
                ],
            ]);

            // 2. Perintahkan MiniStack untuk menghancurkan bucket secara fisik
            $s3->deleteBucket([
                'Bucket' => $bucket->bucket_name,
            ]);

            // 3. Hapus rekam jejak dari database Laravel
            $bucket->delete();

            session()->flash('message', '🗑️ Bucket fisik berhasil dihentikan (terminated).');
        } catch (AwsException $e) {
            Log::error('AWS S3 Delete Error: ' . $e->getAwsErrorMessage());
            session()->flash('error', 'Gagal menghapus IaaS: Bucket mungkin tidak kosong atau tidak ditemukan di server.');
        } catch (\Exception $e) {
            Log::error('Kesalahan Sistem: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan internal sistem.');
        }
    }

    public function render()
    {
        /** @var User|null $user */
        $user = Auth::user();
        $buckets = StorageBucket::where('user_id', $user->id)->latest()->get();

        return view('user-dashboard', [
            'user' => $user,
            'buckets' => $buckets,
            'bucketCount' => $buckets->count(),
        ]);
    }
}