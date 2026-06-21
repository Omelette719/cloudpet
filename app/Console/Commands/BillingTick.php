<?php

namespace App\Console\Commands;

use App\Models\BlockVolume;
use App\Models\BillingTransaction;
use App\Services\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingTick extends Command
{
    protected $signature   = 'billing:tick';
    protected $description = 'Potong saldo user untuk setiap instance RUNNING dan Block Volume aktif.';

    public function handle(BillingService $billing): int
    {
        $this->info('[' . now()->toDateTimeString() . '] Billing tick dimulai...');
        
        // 1. Penagihan Compute Instance (Sistem Lama)
        $billing->runHourlyTick();
        
        // 2. Penagihan Block Storage
        $this->info('Memproses penagihan Block Storage...');
        
        // Gunakan lazyById untuk performa jika data banyak
        BlockVolume::with('user')
            ->whereIn('status', ['AVAILABLE', 'ATTACHED'])
            ->chunk(100, function ($volumes) {
                foreach ($volumes as $volume) {
                    $user = $volume->user;
                    if (!$user) continue;

                    $costPerHour = $volume->size_gb * 15;

                    DB::transaction(function () use ($user, $volume, $costPerHour) {
                        if ($user->balance >= $costPerHour) {
                            $user->decrement('balance', $costPerHour);

                            // Jika menggunakan HasUuids di Model, tidak perlu set 'id' secara manual
                            BillingTransaction::create([
                                'user_id'          => $user->id,
                                'amount'           => -$costPerHour,
                                'transaction_type' => 'HOURLY_USAGE',
                                'description'      => "Biaya sewa Block Volume ({$volume->volume_name} - {$volume->size_gb}GB) per jam.",
                            ]);
                        } else {
                            $user->update(['account_status' => 'SUSPENDED']);
                        }
                    });

                    // Cek status setelah transaksi selesai
                    if ($user->fresh()->account_status === 'SUSPENDED') {
                        $this->warn("User ID {$user->id} kehabisan saldo untuk volume {$volume->volume_name}.");
                    }
                }
            });

        $this->info('Billing tick selesai.');
        return Command::SUCCESS;
    }
}