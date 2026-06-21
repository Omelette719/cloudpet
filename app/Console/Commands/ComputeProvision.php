<?php

namespace App\Console\Commands;

use App\Models\ComputeInstance;
use App\Services\ComputeService;
use Illuminate\Console\Command;

class ComputeProvision extends Command
{
    protected $signature = 'compute:provision {instance}';

    protected $description = 'Jalankan tahap berat (docker run/pull) untuk sebuah compute instance secara terpisah dari request HTTP.';

    public function handle(ComputeService $service): int
    {
        $instance = ComputeInstance::find($this->argument('instance'));

        if (!$instance) {
            $this->error('Instance tidak ditemukan.');
            return self::FAILURE;
        }

        if ($instance->status !== 'PROVISIONING') {
            return self::SUCCESS;
        }

        $service->provision($instance);

        return self::SUCCESS;
    }
}