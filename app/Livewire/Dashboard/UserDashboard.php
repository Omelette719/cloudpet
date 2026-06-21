<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use App\Models\StorageBucket;
use App\Models\BillingTransaction;
use App\Services\MiniStackService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Carbon\Carbon;

#[Layout('layouts::app')]
class UserDashboard extends Component
{
    public function render()
    {
        /** @var User|null $user */
        $user = Auth::user();
        $buckets = StorageBucket::where('user_id', $user->id)->get();
        $bucketCount = $buckets->count();

        // 1. Kalkulasi Storage Terpakai secara langsung dari S3
        $totalBytes = 0;
        foreach ($buckets as $bucket) {
            try {
                $s3 = MiniStackService::getClient($bucket->access_key, $bucket->secret_key);
                $objects = $s3->listObjectsV2(['Bucket' => $bucket->bucket_name]);
                if (!empty($objects['Contents'])) {
                    foreach ($objects['Contents'] as $object) {
                        $totalBytes += $object['Size'];
                    }
                }
            } catch (\Exception $e) {
                // Abaikan jika bucket tidak dapat dijangkau sementara
            }
        }
        $storageUsedGB = $totalBytes / (1024 * 1024 * 1024);
        $storageLabel = $storageUsedGB < 1 
            ? round($totalBytes / (1024 * 1024), 2) . ' MB' 
            : round($storageUsedGB, 2) . ' GB';

        // 2. Ambil total tagihan bulan berjalan
        $totalBilling = BillingTransaction::where('user_id', $user->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount');

        return view('user-dashboard', [
            'user' => $user,
            'bucketCount' => $bucketCount,
            'storageLabel' => $storageLabel,
            'totalBilling' => $totalBilling,
        ]);
    }
}