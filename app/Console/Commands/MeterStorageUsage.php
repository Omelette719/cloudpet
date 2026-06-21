<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StorageBucket;
use App\Models\BillingTransaction;
use App\Services\MiniStackService;
use Illuminate\Support\Str;

class MeterStorageUsage extends Command
{
    // Nama perintah yang akan dijalankan di terminal/cron
    protected $signature = 'cloud:meter-storage';
    protected $description = 'Menghitung total GB bucket user dan mencatat tagihan S3 Pay-As-You-Go';

    public function handle()
    {
        $this->info('Memulai kalkulasi tagihan storage S3...');
        
        // Tarif Storage IaaS: Misalnya Rp 150 per Gigabyte per jam
        $RATE_PER_GB_HOUR = 150; 

        $buckets = StorageBucket::all();

        foreach ($buckets as $bucket) {
            try {
                // Koneksi ke bucket spesifik ini
                $s3 = MiniStackService::getClient($bucket->access_key, $bucket->secret_key);
                
                // Ambil daftar semua file di dalam bucket
                $objects = $s3->listObjectsV2(['Bucket' => $bucket->bucket_name]);

                $totalBytes = 0;

                // Jumlahkan ukuran setiap file
                if (!empty($objects['Contents'])) {
                    foreach ($objects['Contents'] as $object) {
                        $totalBytes += $object['Size'];
                    }
                }

                // Konversi dari Bytes ke Gigabytes
                $totalGB = $totalBytes / (1024 * 1024 * 1024);

                // Hanya buat tagihan jika ada file di dalam bucket (> 0 GB)
                if ($totalGB > 0) {
                    $cost = $totalGB * $RATE_PER_GB_HOUR;

                    // Insert ke tabel billing_transactions
                    BillingTransaction::create([
                        'id' => (string) Str::uuid(),
                        'user_id' => $bucket->user_id,
                        'amount' => round($cost, 4), // 4 desimal untuk keakuratan micro-billing
                        'transaction_type' => 'HOURLY_USAGE',
                        'description' => "Tagihan IaaS Storage: {$bucket->bucket_name} (" . round($totalGB, 4) . " GB)",
                    ]);

                    $this->info("✅ Bucket [{$bucket->bucket_name}] | Terpakai: " . round($totalGB, 4) . " GB | Ditagih: Rp " . round($cost, 4));
                } else {
                    $this->line("ℹ️ Bucket [{$bucket->bucket_name}] kosong, dilewati.");
                }

            } catch (\Exception $e) {
                $this->error("❌ Gagal memproses bucket [{$bucket->bucket_name}]: " . $e->getMessage());
            }
        }

        $this->info('Kalkulasi tagihan storage selesai!');
    }
}