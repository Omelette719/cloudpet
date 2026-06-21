<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class BillingTick extends Command
{
    protected $signature   = 'billing:tick';
    protected $description = 'Potong saldo user untuk setiap instance yang sedang RUNNING (dijalankan tiap jam via scheduler).';

    public function handle(BillingService $billing): int
    {
        $this->info('[' . now()->toDateTimeString() . '] Billing tick dimulai...');
        $billing->runHourlyTick();
        $this->info('Billing tick selesai.');
        return self::SUCCESS;
    }
}