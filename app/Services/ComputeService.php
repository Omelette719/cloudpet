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

    protected function computeUsageAndCost(\App\Models\ComputeInstance $instance)
    {
        // rates (server authoritative)
        $CPU_RATE = config('compute.cpu_rate', 4);
        $VRAM_RATE = config('compute.vram_rate', 250);

        // determine price per hour: prefer submitted price, fallback to calculation
        $meta = $instance->metadata ?? [];
        $requested = $meta['requested'] ?? [];

        if (! empty($requested['price'])) {
            $pricePerHour = (float) $requested['price'];
        } else {
            $cpu = $requested['cpu'] ?? ($meta['spec']['cpu'] ?? 0);
            $vram = $requested['vram_gb'] ?? ($meta['spec']['memory'] ? ($meta['spec']['memory']/1024) : 0);
            $pricePerHour = round(($cpu * $CPU_RATE) + ($vram * $VRAM_RATE));
        }

        $instance->price_per_hour = $pricePerHour;

        // compute usage hours
        $started = $instance->started_at;
        $stopped = $instance->stopped_at ?? now();
        if ($started) {
            $seconds = max(0, $stopped->getTimestamp() - $started->getTimestamp());
            $hours = round($seconds / 3600, 2);
        } else {
            $hours = 0;
        }

        $instance->usage_hours = $hours;
        $instance->cost = round($hours * $pricePerHour, 2);
        // store in metadata too for compatibility
        $meta['usage_hours'] = $instance->usage_hours;
        $meta['price_per_hour'] = $instance->price_per_hour;
        $meta['cost'] = $instance->cost;
        $instance->metadata = $meta;

        // Stripe integration removed: server computes and stores authoritative cost only.
    }

    public function createInstance($user, $plan, array $options = [])
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

        $ssh = $options['ssh'] ?? false;
        $persistent = $options['persistent'] ?? false;
        $requestedRuntime = $options['runtime'] ?? null;
        $requestedVram = $options['vram'] ?? null; // in GB
        $requestedCpu = $options['cpu'] ?? null; // cpu units (approx)
        $requestedSize = $options['size'] ?? null;
        $requestedPrice = $options['price'] ?? null;

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
                    // Jupyter runtime (interactive notebook)
                    'jupyter' => ['cpu' => 100, 'memory' => 512, 'image' => 'jupyter/minimal-notebook'],
                    // code-server runtime (VS Code in the browser)
                    'code-server' => ['cpu' => 100, 'memory' => 512, 'image' => 'codercom/code-server:latest'],
                ];

                $spec = $plans[$plan] ?? $plans['micro'];

                // Allow runtime override (e.g., user selects 'jupyter' runtime even if plan is 'small')
                if ($requestedRuntime) {
                    if (isset($plans[$requestedRuntime])) {
                        $spec = $plans[$requestedRuntime];
                    } else {
                        // map some known runtimes
                        if ($requestedRuntime === 'jupyter') {
                            $spec = ['cpu' => 100, 'memory' => 512, 'image' => 'jupyter/minimal-notebook'];
                        }
                        if ($requestedRuntime === 'code-server') {
                            $spec = ['cpu' => 100, 'memory' => 512, 'image' => 'codercom/code-server:latest'];
                        }
                    }
                }

                // If user requested vram (GB), translate to memory MB
                if ($requestedVram) {
                    $spec['memory'] = (int) max(128, $requestedVram * 1024);
                }

                // If user requested cpu, apply (best-effort)
                if ($requestedCpu) {
                    $spec['cpu'] = (int) max(10, $requestedCpu);
                }

                // If SSH requested, use an SSH-capable image and reserve a host port
                if ($ssh) {
                    $spec['image'] = 'rastasheep/ubuntu-sshd:latest';
                    // choose a random host port in a high ephemeral range
                    $spec['hostPort'] = rand(22000, 22999);
                }

                // If Jupyter runtime, expose notebook port and set token
                if (isset($spec['image']) && strpos($spec['image'], 'jupyter') !== false) {
                    // set a token for the notebook
                    $token = bin2hex(random_bytes(12));
                    $spec['jupyter_token'] = $token;
                    $spec['containerPort'] = 8888;
                    $spec['hostPort'] = rand(28000, 28999);
                }

                // If code-server runtime, expose web IDE port and set a password
                if (isset($spec['image']) && strpos($spec['image'], 'code-server') !== false) {
                    $password = bin2hex(random_bytes(8));
                    $spec['codeserver_password'] = $password;
                    $spec['containerPort'] = 8080;
                    $spec['hostPort'] = rand(29000, 29999);
                }

                // Register a minimal task definition that runs an Alpine sleep loop
                $family = 'cloudpet-instance-' . $instance->id;
                $containerDef = [
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
                ];

                $volumes = [];
                if ($persistent) {
                    // create a host path for persistent storage
                    $hostPath = storage_path('app/compute/' . $instance->id);
                    if (! is_dir($hostPath)) {
                        mkdir($hostPath, 0755, true);
                    }
                    $volumes[] = ['name' => 'instance-data', 'host' => ['sourcePath' => $hostPath]];
                    $containerDef['mountPoints'] = [
                        ['sourceVolume' => 'instance-data', 'containerPath' => '/data', 'readOnly' => false],
                    ];
                    $instance->metadata = array_merge($instance->metadata ?? [], ['volumePath' => $hostPath]);
                }

                // record requested configuration for UI visibility
                $instance->metadata = array_merge($instance->metadata ?? [], [
                    'requested' => [
                        'runtime' => $requestedRuntime ?? $plan,
                        'vram_gb' => $requestedVram,
                        'cpu' => $requestedCpu,
                        'size' => $requestedSize,
                        'price' => $requestedPrice,
                    ]
                ]);

                if ($ssh && isset($spec['hostPort'])) {
                    $containerDef['portMappings'] = [
                        ['containerPort' => 22, 'hostPort' => $spec['hostPort'], 'protocol' => 'tcp'],
                    ];
                    $instance->metadata = array_merge($instance->metadata ?? [], ['ssh_host_port' => $spec['hostPort']]);
                }

                // Port mapping and env for interactive runtimes
                if (isset($spec['containerPort']) && isset($spec['hostPort'])) {
                    $containerDef['portMappings'] = $containerDef['portMappings'] ?? [];
                    $containerDef['portMappings'][] = ['containerPort' => $spec['containerPort'], 'hostPort' => $spec['hostPort'], 'protocol' => 'tcp'];

                    // Jupyter token
                    if (isset($spec['jupyter_token'])) {
                        $containerDef['environment'][] = ['name' => 'JUPYTER_TOKEN', 'value' => $spec['jupyter_token']];
                        $instance->metadata = array_merge($instance->metadata ?? [], ['jupyter_token' => $spec['jupyter_token'], 'jupyter_host_port' => $spec['hostPort']]);
                    }

                    // code-server password
                    if (isset($spec['codeserver_password'])) {
                        // code-server respects PASSWORD env for simple auth
                        $containerDef['environment'][] = ['name' => 'PASSWORD', 'value' => $spec['codeserver_password']];
                        $instance->metadata = array_merge($instance->metadata ?? [], ['codeserver_password' => $spec['codeserver_password'], 'codeserver_host_port' => $spec['hostPort']]);
                    }
                }

                $register = $ecs->registerTaskDefinition([
                    'family' => $family,
                    'networkMode' => 'bridge',
                    'containerDefinitions' => [$containerDef],
                    'volumes' => $volumes,
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
                            $instance->metadata = array_merge($instance->metadata ?? [], [
                                'taskArn' => $task['taskArn'] ?? null,
                                'taskDefinition' => $taskDefArn,
                                'plan' => $plan,
                                'spec' => $spec,
                                'raw' => $task,
                            ]);
                            $instance->save();
                    } else {
                            $instance->status = 'FAILED';
                        $instance->metadata = array_merge($instance->metadata ?? [], ['error' => $run->toArray()]);
                        $instance->save();
                    }
                } else {
                        $instance->status = 'FAILED';
                    $instance->metadata = array_merge($instance->metadata ?? [], ['error' => 'no taskDefinitionArn']);
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
                        $instance->status = ($action === 'stop') ? 'STOPPED' : 'TERMINATED';
                        // record stopped time
                        $instance->stopped_at = now();
                        $meta['stopped_at'] = $instance->stopped_at->toDateTimeString();

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
                        // compute usage and cost when stopping/terminating
                        $this->computeUsageAndCost($instance);
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
                            $instance->status = 'RUNNING';
                            // record start time
                            $instance->started_at = now();
                            $meta['started_at'] = $instance->started_at->toDateTimeString();
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
        if ($action === 'start') {
            $instance->status = 'RUNNING';
            $instance->started_at = now();
            $instance->metadata = array_merge($instance->metadata ?? [], ['started_at' => $instance->started_at->toDateTimeString()]);
            $instance->save();
            return $instance;
        }

        if ($action === 'stop') {
            $instance->status = 'STOPPED';
            $instance->stopped_at = now();
            $instance->metadata = array_merge($instance->metadata ?? [], ['stopped_at' => $instance->stopped_at->toDateTimeString()]);
            $this->computeUsageAndCost($instance);
            $instance->save();
            return $instance;
        }

        if ($action === 'terminate') {
            $instance->status = 'TERMINATED';
            $instance->stopped_at = now();
            $instance->metadata = array_merge($instance->metadata ?? [], ['stopped_at' => $instance->stopped_at->toDateTimeString()]);
            $this->computeUsageAndCost($instance);
            $instance->save();
            return $instance;
        }

        return $instance;
    }
}
