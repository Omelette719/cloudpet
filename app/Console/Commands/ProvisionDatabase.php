<?php

namespace App\Console\Commands;

use App\Models\ManagedDatabase;
use App\Services\DatabaseService;
use Illuminate\Console\Command;

class ProvisionDatabase extends Command
{
    protected $signature   = 'database:provision {id}';
    protected $description = 'Provisioning managed database di background.';

    public function handle(DatabaseService $service): int
    {
        $db = ManagedDatabase::findOrFail($this->argument('id'));

        if ($db->status !== 'PROVISIONING') {
            $this->warn("Database {$db->id} bukan PROVISIONING (status: {$db->status}).");
            return self::FAILURE;
        }

        try {
            $service->provision($db);
            $this->info("Database {$db->id} berhasil diprovisioning.");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $db->update(['status' => 'ERROR']);
            $db->provision_log = ($db->provision_log ?? '') . "GAGAL: {$e->getMessage()}\n";
            $db->save();
            $this->error("Gagal: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
