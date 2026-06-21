<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class BillingTick extends Command
{
    protected $signature   = 'billing:tick';
    protected $description = 'Potong saldo user untuk setiap instance yang sedang RUNNING dan Block Volume aktif (dijalankan tiap jam via scheduler).';

    public function handle(BillingService $billing): int
    {
        $this->info('[' . now()->toDateTimeString() . '] Billing tick dimulai...');
        
        // ---------------------------------------------------------
        // 1. PENAGIHAN COMPUTE INSTANCE (Sistem Lama)
        // ---------------------------------------------------------
        $billing->runHourlyTick();
        
        // ---------------------------------------------------------
        // 2. PENAGIHAN BLOCK STORAGE
        // ---------------------------------------------------------
        $this->info('Memproses penagihan Block Storage...');
        
        // Ambil semua volume yang aktif (tidak dalam proses pembuatan/penghapusan)
        $activeVolumes = \App\Models\BlockVolume::with('user')
            ->whereIn('status', ['AVAILABLE', 'ATTACHED'])
            ->get();

        foreach ($activeVolumes as $volume) {
            $user = $volume->user;
            
            // Asumsi biaya (contoh: Rp 15 per GB setiap jamnya)
            // Jadi volume 100GB akan memakan biaya Rp 1.500 per jam.
            $costPerHour = $volume->size_gb * 15;

            if ($user->balance >= $costPerHour) {
                // Potong saldo
                $user->decrement('balance', $costPerHour);

                // Catat riwayat transaksi ke DB
                \App\Models\BillingTransaction::create([
                    'user_id' => $user->id,
                    'amount' => $costPerHour,
                    'type' => 'DEDUCTION',
                    'description' => "Biaya sewa Block Volume ({$volume->volume_name} - {$volume->size_gb}GB) per jam."
                ]);
            } else {
                // Jika saldo habis, Anda bisa mengatur status akun menjadi SUSPENDED
                $user->update(['account_status' => 'SUSPENDED']);
                $this->warn("User ID {$user->id} kehabisan saldo untuk volume {$volume->volume_name}.");
            }
        }

        $this->info('[' . now()->toDateTimeString() . '] Billing tick selesai.');
        
        // Return diletakkan HANYA di bagian paling akhir fungsi
        return self::SUCCESS;
    }
}