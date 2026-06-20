<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ComputeInstance;
use App\Services\ComputeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComputeTerminateTest extends TestCase
{
    use RefreshDatabase;

    public function test_terminate_removes_instance_record()
    {
        $user = User::factory()->create();

        $svc = app(ComputeService::class);

        // create instance
        $instance = $svc->createInstance($user, 'micro');

        $fresh = ComputeInstance::where('name', $instance->name)->first();
        $this->assertNotNull($fresh, 'Instance should exist after create');

        // terminate via service
        $result = $svc->changeStatus($fresh, 'terminate');

        $this->assertIsArray($result, 'Service should return array result for terminate');
        $this->assertArrayHasKey('deleted', $result);
        $this->assertTrue($result['deleted'], 'Instance should be deleted by service');

        // DB should no longer contain the record
        $this->assertDatabaseMissing('compute_instances', ['id' => $fresh->id]);
    }
}
