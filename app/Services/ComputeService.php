<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\ComputeInstance;
use Aws\Ecs\EcsClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Str;

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
        $data = [
            'user_id' => $user->id,
            'name' => 'vm-' . uniqid(),
            'plan' => $plan,
            'status' => 'PROVISIONING',
            'metadata' => [],
        ];

        $instance = ComputeInstance::create($data);

        // If Ministack configured, try to start a container via ECS on MiniStack
        if ($this->baseUrl) {
            try {
                $ecs = new EcsClient([
                    'version' => 'latest',
                    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                    'endpoint' => rtrim($this->baseUrl, '/'),
                    'use_path_style_endpoint' => true,
                    'credentials' => [
                        'key' => env('AWS_ACCESS_KEY_ID', 'test'),
                        'secret' => env('AWS_SECRET_ACCESS_KEY', 'test'),
                    ],
                    'http' => ['verify' => false],
                ]);

                // Choose resources and image based on plan
                $plans = [
                    'micro' => ['cpu' => 10,  'memory' => 128,  'image' => 'alpine:latest'],
                    'small' => ['cpu' => 50,  'memory' => 256,  'image' => 'alpine:latest'],
                    'medium'=> ['cpu' => 100, 'memory' => 512,  'image' => 'alpine:latest'],
                    'large' => ['cpu' => 200, 'memory' => 1024, 'image' => 'alpine:latest'],
                ];

                $spec = $plans[$plan] ?? $plans['micro'];

                // Register a minimal task definition that runs an Alpine sleep loop
                $family = 'cloudpet-instance-' . $instance->id;
                $register = $ecs->registerTaskDefinition([
                    'family' => $family,
                    'networkMode' => 'bridge',
                    'containerDefinitions' => [
                        [
                            'name' => 'cloudpet-worker',
                            'image' => $spec['image'],
                            'essential' => true,
                            'command' => ['sh', '-c', 'sleep 3600'],
                            'memory' => $spec['memory'],
                            'cpu' => $spec['cpu'],
                            'environment' => [
                                ['name' => 'CLOUDPET_PLAN', 'value' => $plan],
                                ['name' => 'CLOUDPET_USER_ID', 'value' => (string) $user->id],
                                ['name' => 'CLOUDPET_INSTANCE_ID', 'value' => (string) $instance->id],
                            ],
                        ],
                    ],
                ]);

                $taskDefArn = $register->get('taskDefinition')['taskDefinitionArn'] ?? null;

                if ($taskDefArn) {
                    $run = $ecs->runTask([
                        'cluster' => 'default',
                        'taskDefinition' => $taskDefArn,
                        'count' => 1,
                    ]);

                    $tasks = $run->get('tasks') ?? [];
                        if (count($tasks) > 0) {
                            $task = $tasks[0];
                            $instance->status = 'RUNNING';
                            $instance->metadata = [
                                'taskArn' => $task['taskArn'] ?? null,
                                'taskDefinition' => $taskDefArn,
                                'plan' => $plan,
                                'spec' => $spec,
                                'raw' => $task,
                            ];
                            $instance->save();
                    } else {
                            $instance->status = 'FAILED';
                        $instance->metadata = ['error' => $run->toArray()];
                        $instance->save();
                    }
                } else {
                        $instance->status = 'FAILED';
                    $instance->metadata = ['error' => 'no taskDefinitionArn'];
                    $instance->save();
                }
            } catch (AwsException $e) {
                $instance->status = 'FAILED';
                $instance->metadata = ['aws_exception' => $e->getMessage()];
                $instance->save();
            } catch (\Exception $e) {
                $instance->status = 'FAILED';
                $instance->metadata = ['exception' => $e->getMessage()];
                $instance->save();
            }
        } else {
            // simulate provisioning delay
            $instance->status = 'RUNNING';
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
                $ecs = new EcsClient([
                    'version' => 'latest',
                    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                    'endpoint' => rtrim($this->baseUrl, '/'),
                    'use_path_style_endpoint' => true,
                    'credentials' => [
                        'key' => env('AWS_ACCESS_KEY_ID', 'test'),
                        'secret' => env('AWS_SECRET_ACCESS_KEY', 'test'),
                    ],
                    'http' => ['verify' => false],
                ]);

                $meta = $instance->metadata ?? [];

                if ($action === 'terminate' || $action === 'stop') {
                    // stop the running task if we have a taskArn
                    $taskArn = $meta['taskArn'] ?? null;
                    if ($taskArn) {
                        $ecs->stopTask(['cluster' => 'default', 'task' => $taskArn, 'reason' => strtoupper($action)]);
                        $instance->status = ($action === 'stop') ? 'stopped' : 'terminated';
                        $meta['stopped_at'] = now()->toDateTimeString();

                        // If terminating, deregister the task definition to clean up
                        if ($action === 'terminate') {
                            $taskDefArn = $meta['taskDefinition'] ?? null;
                            if ($taskDefArn) {
                                try {
                                    $ecs->deregisterTaskDefinition(['taskDefinition' => $taskDefArn]);
                                    // remove task references
                                    unset($meta['taskArn']);
                                    unset($meta['taskDefinition']);
                                    $meta['deregistered_at'] = now()->toDateTimeString();
                                } catch (AwsException $e) {
                                    $meta['deregister_error'] = $e->getMessage();
                                }
                            }
                        }

                        $instance->metadata = $meta;
                        $instance->save();
                        return $instance;
                    }
                }

                if ($action === 'start') {
                    // run a new task using existing taskDefinition if present
                    $taskDef = $meta['taskDefinition'] ?? null;
                    if ($taskDef) {
                        $run = $ecs->runTask(['cluster' => 'default', 'taskDefinition' => $taskDef, 'count' => 1]);
                        $tasks = $run->get('tasks') ?? [];
                        if (count($tasks) > 0) {
                            $task = $tasks[0];
                            $meta['taskArn'] = $task['taskArn'] ?? null;
                            $instance->status = 'running';
                            $instance->metadata = $meta;
                            $instance->save();
                            return $instance;
                        }
                    }
                }
            } catch (AwsException $e) {
                $instance->metadata = array_merge($instance->metadata ?? [], ['aws_exception' => $e->getMessage()]);
                $instance->save();
                return $instance;
            } catch (\Exception $e) {
                // fallthrough to simulated
            }
        }

        // simulated behavior fallback
        if ($action === 'start') $instance->status = 'running';
        if ($action === 'stop') $instance->status = 'stopped';
        if ($action === 'terminate') $instance->status = 'terminated';
        $instance->save();

        return $instance;
    }
}
