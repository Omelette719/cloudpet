<?php

namespace App\Jobs;

use App\Models\ManagedDatabase;
use App\Services\DatabaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProvisionManagedDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ManagedDatabase $database;
    public int $tries = 2;

    public function __construct(ManagedDatabase $database)
    {
        $this->database = $database;
    }

    public function handle(DatabaseService $service): void
    {
        Log::info("Provisioning database {$this->database->id}: {$this->database->engine}");

        try {
            $service->provision($this->database);
            Log::info("Database {$this->database->id} ready.");
        } catch (Exception $e) {
            Log::error("Database provisioning failed {$this->database->id}: {$e->getMessage()}");
            $this->database->provision_log = ($this->database->provision_log ?? '') . "GAGAL: {$e->getMessage()}\n";
            $this->database->save();
            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->database->update(['status' => 'ERROR']);
        $this->database->provision_log = ($this->database->provision_log ?? '') . "Provisioning dihentikan: {$exception->getMessage()}\n";
        $this->database->save();
    }
}
