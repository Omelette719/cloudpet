<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ComputeInstance;

class ComputePurge extends Command
{
    protected $signature = 'compute:purge {--days=30 : Purge trashed instances older than N days}';
    protected $description = 'Permanently delete soft-deleted compute instances older than given days';

    public function handle()
    {
        $days = (int) $this->option('days');
        $threshold = now()->subDays($days);
        $this->info("Purging compute instances soft-deleted before {$threshold}");

        $items = ComputeInstance::onlyTrashed()->where('updated_at', '<', $threshold)->get();
        $this->info('Found: ' . $items->count());

        foreach ($items as $it) {
            // TODO: run provider cleanup if necessary
            $this->line("Purging: {$it->id} ({$it->name})");
            $it->forceDelete();
        }

        $this->info('Purge complete.');
        return 0;
    }
}
