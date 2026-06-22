<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class StorageSync extends Command
{
    protected $signature   = 'storage:sync';
    protected $description = 'Tampilkan ringkasan penggunaan block storage per user.';

    public function handle(): int
    {
        $this->info('Block storage usage per user:');
        User::where('role', 'user')->each(function (User $user) {
            $used  = $user->volumeUsedGb();
            $limit = $user->volumeLimitGb();
            $this->line("  User #{$user->id} {$user->email}: {$used} GB / {$limit} GB ({$user->storage_plan})");
        });
        $this->info('Done.');
        return self::SUCCESS;
    }
}
