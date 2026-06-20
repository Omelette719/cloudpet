<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\ComputeInstance;

class ComputeService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.ministack.url');
        $this->apiKey = config('services.ministack.key');
    }

    public function createInstance($user, $plan)
    {
        // create local record first
        $instance = ComputeInstance::create([
            'user_id' => $user->id,
            'name' => 'vm-' . uniqid(),
            'plan' => $plan,
            'status' => 'provisioning',
            'metadata' => [],
        ]);

        // If Ministack configured, call its API; otherwise simulate
        if ($this->baseUrl) {
            try {
                $res = Http::withHeaders([
                    'X-Api-Key' => $this->apiKey,
                ])->post(rtrim($this->baseUrl, '/') . '/api/instances', [
                    'name' => $instance->name,
                    'plan' => $plan,
                    'owner' => $user->id,
                ]);

                if ($res->successful()) {
                    $data = $res->json();
                    $instance->status = $data['status'] ?? 'running';
                    $instance->metadata = $data['metadata'] ?? $data;
                    $instance->save();
                } else {
                    $instance->status = 'failed';
                    $instance->metadata = ['error' => $res->body()];
                    $instance->save();
                }
            } catch (\Exception $e) {
                $instance->status = 'failed';
                $instance->metadata = ['exception' => $e->getMessage()];
                $instance->save();
            }
        } else {
            // simulate provisioning delay
            $instance->status = 'running';
            $instance->metadata = ['mock' => true];
            $instance->save();
        }

        return $instance;
    }

    public function changeStatus(ComputeInstance $instance, string $action)
    {
        // action: start|stop|terminate
        if ($this->baseUrl) {
            try {
                $res = Http::withHeaders(['X-Api-Key' => $this->apiKey])->post(rtrim($this->baseUrl, '/') . "/api/instances/{$instance->id}/action", ['action' => $action]);
                if ($res->successful()) {
                    $data = $res->json();
                    $instance->status = $data['status'] ?? $instance->status;
                    $instance->metadata = array_merge($instance->metadata ?? [], $data['metadata'] ?? []);
                    $instance->save();
                    return $instance;
                }
            } catch (\Exception $e) {
                // fallthrough to simulated
            }
        }

        // simulated behavior
        if ($action === 'start') $instance->status = 'running';
        if ($action === 'stop') $instance->status = 'stopped';
        if ($action === 'terminate') $instance->status = 'terminated';
        $instance->save();

        return $instance;
    }
}
