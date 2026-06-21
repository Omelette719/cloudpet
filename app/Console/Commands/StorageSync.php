<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BillingService;
use Illuminate\Console\Command;

class StorageSync extends Command
{
    protected $signature   = 'storage:sync';
    protected $description = 'Hitung ulang storage usage semua user dan stop instance kalau storage penuh.';

    public function handle(BillingService $billing): int
    {
        $this->info('Syncing storage usage...');
        User::where('role', 'user')->each(function (User $user) use ($billing) {
            $gb = $billing->syncStorageUsage($user);
            $this->line("  User #{$user->id} {$user->email}: {$gb} GB / {$user->storage_quota_gb} GB");
        });
        $this->info('Done.');
        return self::SUCCESS;
    }
}