<?php

namespace App\Livewire\Bucket;

use App\Models\StorageBucket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class CreateBucket extends Component
{
    public function createBucket()
    {
        $user = Auth::user();

        // Pastikan konfigurasi env sudah diset
        $apiUrl = env('MINISTACK_API_URL');
        $apiToken = env('MINISTACK_API_TOKEN');

        if (!$apiUrl || !$apiToken) {
            session()->flash('error', 'Konfigurasi MiniStack belum diatur oleh Admin.');
            return;
        }

        try {
            // 1. Mengirim Request ke API MiniStack
            $response = Http::withToken($apiToken)
                ->timeout(15) // Timeout 15 detik untuk antisipasi proses IaaS
                ->post("{$apiUrl}/api/v1/buckets", [
                    'user_email' => $user->email,
                    'user_name'  => $user->name,
                    'region'     => 'id-1', // Sesuaikan jika ada multi-region
                ]);

            // 2. Cek apakah response dari MiniStack error (status 4xx atau 5xx)
            if ($response->failed()) {
                // Log response dari server untuk keperluan debugging admin
                Log::error('MiniStack API Error: ' . $response->body());
                throw new \Exception('API MiniStack menolak permintaan atau terjadi kesalahan server.');
            }

            $data = $response->json('data');

            // 3. Validasi kembalian data dari MiniStack
            if (!isset($data['bucket_name']) || !isset($data['access_key']) || !isset($data['secret_key'])) {
                Log::error('Format response MiniStack tidak sesuai: ' . json_encode($data));
                throw new \Exception('Data kredensial dari MiniStack tidak lengkap.');
            }

            // 4. Simpan kredensial ke database lokal CloudPet
            StorageBucket::create([
                'id'          => (string) Str::uuid(),
                'user_id'     => $user->id,
                'bucket_name' => $data['bucket_name'],
                'access_key'  => $data['access_key'],
                'secret_key'  => $data['secret_key'],
            ]);

            session()->flash('message', '🎉 Bucket berhasil diprovisioning di MiniStack!');
            
            // Refresh halaman dashboard pengguna agar tabel terupdate
            $this->redirectRoute('user.dashboard', navigate: true);

        } catch (\Exception $e) {
            Log::error('Gagal membuat bucket IaaS: ' . $e->getMessage());
            session()->flash('error', 'Gagal membuat bucket: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.bucket.create-bucket');
    }
}