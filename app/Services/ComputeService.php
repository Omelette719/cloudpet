<?php

namespace App\Services;

use App\Models\BlockVolume;
use App\Models\ComputeInstance;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ComputeService
{
    // ─── Tipe instance ────────────────────────────────────────────────────────
    // vm      = Linux VM dengan SSH + web terminal (wetty)
    // ide     = VS Code di browser (code-server)
    // notebook= Jupyter Notebook (Google Colab style)

    const TYPES = [
        'vm' => [
            'label' => 'Virtual Machine',
            'icon'  => '🖥',
            'desc'  => 'Linux penuh dengan akses SSH dan web terminal',
        ],
        'ide' => [
            'label' => 'Cloud IDE',
            'icon'  => '💻',
            'desc'  => 'VS Code di browser, siap coding langsung',
        ],
        'notebook' => [
            'label' => 'Notebook',
            'icon'  => '📓',
            'desc'  => 'Jupyter Notebook, mirip Google Colab',
        ],
    ];

    // ─── Pricing (custom resources) ──────────────────────────────────────────
    const CPU_RATE  = 500;  // Rp per vCPU per jam
    const RAM_RATE  = 500;  // Rp per GB RAM per jam

    const VCPU_OPTIONS = [1, 2, 3, 4, 6, 8];
    const RAM_OPTIONS  = [512, 1024, 2048, 4096, 8192, 16384]; // MB

    public static function calculatePrice(int $cpu, int $ramMb): int
    {
        return (int) ($cpu * self::CPU_RATE + ($ramMb / 1024) * self::RAM_RATE);
    }

    // ─── OS untuk VM ──────────────────────────────────────────────────────────
    const IMAGES = [
        'ubuntu-22.04' => ['image' => 'ubuntu:22.04',  'label' => 'Ubuntu 22.04', 'family' => 'apt'],
        'ubuntu-20.04' => ['image' => 'ubuntu:20.04',  'label' => 'Ubuntu 20.04', 'family' => 'apt'],
        'debian-12'    => ['image' => 'debian:12',     'label' => 'Debian 12',    'family' => 'apt'],
        'alpine'       => ['image' => 'alpine:latest', 'label' => 'Alpine Linux', 'family' => 'apk'],
    ];

    const DEFAULT_OS   = 'ubuntu-22.04';
    const DEFAULT_TYPE = 'vm';

    // ─── Buat instance ────────────────────────────────────────────────────────

    public function createInstance(User $user, array $options = []): ComputeInstance
    {
        $instance = $this->initInstance($user, $options);

        $artisan = base_path('artisan');
        $logFile = storage_path('logs/provision_' . $instance->id . '.out');

        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen(sprintf('start /B php %s compute:provision %s > %s 2>&1',
                escapeshellarg($artisan), escapeshellarg($instance->id), escapeshellarg($logFile)), 'r'));
        } else {
            exec(sprintf('nohup php %s compute:provision %s > %s 2>&1 &',
                escapeshellarg($artisan), escapeshellarg($instance->id), escapeshellarg($logFile)));
        }

        return $instance;
    }

    // ─── Tahap 1: buat record di DB ──────────────────────────────────────────

    public function initInstance(User $user, array $options = []): ComputeInstance
    {
        $type     = $options['type'] ?? self::DEFAULT_TYPE;
        $cpu      = $options['cpu']  ?? 1;
        $ramMb    = $options['ram']  ?? 1024;
        $osKey    = $options['os']   ?? self::DEFAULT_OS;
        $osConf   = self::IMAGES[$osKey]  ?? self::IMAGES[self::DEFAULT_OS];
        $typeConf = self::TYPES[$type]    ?? self::TYPES['vm'];
        $volumeId = $options['volume_id'] ?? null;
        $price    = self::calculatePrice($cpu, $ramMb);

        $prefix = match($type) {
            'ide'      => 'ide',
            'notebook' => 'nb',
            default    => 'vm',
        };

        $ramLabel = $ramMb >= 1024 ? ($ramMb / 1024) . 'GB' : $ramMb . 'MB';

        return ComputeInstance::create([
            'user_id'       => $user->id,
            'name'          => $prefix . '-' . Str::random(8),
            'plan'          => "{$cpu}c-{$ramLabel}",
            'os'            => $osKey,
            'status'        => 'PROVISIONING',
            'metadata'      => ['type' => $type, 'volume_id' => $volumeId, 'cpu' => $cpu, 'ram' => $ramMb],
            'provision_log' => "[1/3] Menyiapkan {$typeConf['label']} ({$cpu} vCPU, {$ramLabel})...\n",
        ]);
    }

    // ─── Tahap 2: provisioning ───────────────────────────────────────────────

    public function provision(ComputeInstance $instance): ComputeInstance
    {
        $type = $instance->metadata['type'] ?? self::DEFAULT_TYPE;

        return match($type) {
            'ide'      => $this->provisionIde($instance),
            'notebook' => $this->provisionNotebook($instance),
            default    => $this->provisionVm($instance),
        };
    }

    // ─── Provision VM ─────────────────────────────────────────────────────────

    protected function provisionVm(ComputeInstance $instance): ComputeInstance
    {
        $user   = $instance->user;
        $meta   = $instance->metadata ?? [];
        $cpu    = $meta['cpu'] ?? 1;
        $ramMb  = $meta['ram'] ?? 1024;
        $price  = self::calculatePrice($cpu, $ramMb);
        $osKey  = $instance->os               ?? self::DEFAULT_OS;
        $osConf = self::IMAGES[$osKey]         ?? self::IMAGES[self::DEFAULT_OS];

        [$appendLog, $execStream] = $this->makeHelpers($instance);

        $sshPort   = $this->pickAvailablePort(10000, 19999);
        $wettyPort = $this->pickAvailablePort(20000, 29999);
        $ramLabel  = $ramMb >= 1024 ? ($ramMb / 1024) . 'GB' : $ramMb . 'MB';

        $dockerVolume   = $this->resolveStorageMount($instance);
        $storagePath    = $dockerVolume ?? storage_path('app/compute/' . $instance->id);
        $containerName  = 'cp_' . $instance->id . '_' . $user->id;
        $wettyName      = 'cp_wetty_' . $instance->id . '_' . $user->id;
        $sshPassword    = Str::random(16);
        $networkName    = 'cp_user_' . $user->id;

        if (!$dockerVolume && !is_dir($storagePath)) mkdir($storagePath, 0755, true);

        // Network
        $appendLog('[2/3] Menyiapkan network isolasi...');
        try { $this->ensureNetwork($networkName); }
        catch (\RuntimeException $e) {
            $appendLog('GAGAL: ' . $e->getMessage());
            $instance->status = 'FAILED'; $instance->metadata = array_merge($instance->metadata ?? [], ['error' => $e->getMessage()]); $instance->save();
            return $instance;
        }

        // Pull + run VM
        $family    = $osConf['family'] ?? 'apt';
        $bootstrap = $this->buildBootstrapScript($family, $sshPassword);
        $shell     = $family === 'apk' ? 'sh' : 'bash';

        $appendLog('[3/3] Menyiapkan VM "' . $osConf['label'] . '"...');
        $appendLog('  Pulling image ' . $osConf['image'] . '...');
        $pull = $execStream('docker pull ' . escapeshellarg($osConf['image']));
        if ($pull['rc'] !== 0) {
            $appendLog('GAGAL pull image.');
            $instance->status = 'FAILED'; $instance->metadata = array_merge($instance->metadata ?? [], ['error' => 'pull failed']); $instance->save();
            return $instance;
        }
        $appendLog('  Image siap. Menjalankan container...');

        $storageFlag = $dockerVolume
            ? "-v {$dockerVolume}:/data"
            : '-v ' . escapeshellarg($storagePath) . ':/data';

        $vmCmd = sprintf(
            'docker run -d --name %s --memory=%dm --memory-swap=%dm --cpus=%s --network=%s -p %d:22 %s --restart=unless-stopped %s %s -c %s',
            escapeshellarg($containerName), $ramMb, $ramMb, $cpu,
            escapeshellarg($networkName), $sshPort, $storageFlag,
            escapeshellarg($osConf['image']), $shell, escapeshellarg($bootstrap)
        );
        $run = $execStream($vmCmd . ' 2>&1');
        if ($run['rc'] !== 0) {
            $appendLog('GAGAL docker run (exit ' . $run['rc'] . ').');
            $instance->status = 'FAILED'; $instance->metadata = array_merge($instance->metadata ?? [], ['error' => 'run failed']); $instance->save();
            return $instance;
        }
        $containerId = trim($run['out'][0] ?? $run['last']);
        $appendLog('Container VM siap (' . substr($containerId, 0, 12) . '). Menunggu SSH...');
        $this->waitForPort($sshPort);

        // Wetty
        $appendLog('Menyiapkan web terminal (wetty)...');
        $sshHost  = PHP_OS_FAMILY === 'Windows' ? 'host.docker.internal' : '172.17.0.1';
        $wettyCmd = sprintf(
            'docker run -d --name %s -p %d:3000 --restart=unless-stopped wettyoss/wetty --ssh-host=%s --ssh-port=%d --ssh-user=root --base=/terminal/ --port=3000',
            escapeshellarg($wettyName), $wettyPort, $sshHost, $sshPort
        );
        $appendLog('  Pulling image wettyoss/wetty...');
        $wettyPull = $execStream('docker pull wettyoss/wetty');
        $wettyOk   = false;
        if ($wettyPull['rc'] === 0) {
            $wettyRun = $execStream($wettyCmd . ' 2>&1');
            $wettyOk  = $wettyRun['rc'] === 0;
        }
        if ($wettyOk) $appendLog('Web terminal siap di port ' . $wettyPort . '.');
        else          { $appendLog('Peringatan: web terminal gagal. SSH manual tetap tersedia.'); $wettyPort = null; }

        $containerIp = $this->getContainerIp($containerName, $networkName);

        // EC2 control plane: register instance di MiniStack
        $ec2InstanceId = null;
        try {
            $miniStack = app(MiniStackService::class);
            $ec2InstanceId = $miniStack->runInstance($containerName);
            $appendLog('EC2 Instance registered: ' . $ec2InstanceId);
        } catch (\Exception $e) {
            Log::warning("EC2 RunInstances skip: {$e->getMessage()}");
        }

        $appendLog('✅ VM siap! SSH port: ' . $sshPort . ($wettyPort ? ' | Web terminal: ' . $wettyPort : ''));

        $volumeId = $instance->metadata['volume_id'] ?? null;
        $volume   = $volumeId ? BlockVolume::find($volumeId) : null;

        $instance->status         = 'RUNNING';
        $instance->started_at     = now();
        $instance->ip_address     = $containerIp;
        $instance->price_per_hour = $price;
        $instance->metadata       = [
            'type'            => 'vm',
            'ec2_instance_id' => $ec2InstanceId,
            'container_id'    => $containerId,
            'container_name'  => $containerName,
            'wetty_container' => $wettyOk ? $wettyName : null,
            'network'         => $networkName,
            'storage_path'    => $storagePath,
            'volume_id'       => $volumeId,
            'volume_name'     => $volume?->volume_name,
            'volume_size_gb'  => $volume?->size_gb,
            'ssh_port'        => $sshPort,
            'ssh_user'        => 'root',
            'ssh_password'    => $sshPassword,
            'wetty_port'      => $wettyPort,
            'os'              => $osKey,
            'os_label'        => $osConf['label'],
            'plan_label'      => "{$cpu} vCPU · {$ramLabel}",
            'resources'       => ['cpu' => $cpu, 'memory' => $ramMb],
            'price_per_hour'  => $price,
        ];
        $instance->save();
        $this->attachVolumeToInstance($instance);
        return $instance;
    }

    // ─── Provision IDE (code-server / VS Code) ────────────────────────────────

    protected function provisionIde(ComputeInstance $instance): ComputeInstance
    {
        $user    = $instance->user;
        $meta    = $instance->metadata ?? [];
        $cpu     = $meta['cpu'] ?? 1;
        $ramMb   = $meta['ram'] ?? 1024;
        $price   = self::calculatePrice($cpu, $ramMb);
        $ramLabel = $ramMb >= 1024 ? ($ramMb / 1024) . 'GB' : $ramMb . 'MB';
        [$appendLog, $execStream] = $this->makeHelpers($instance);

        $idePort       = $this->pickAvailablePort(30000, 39999);
        $dockerVolume  = $this->resolveStorageMount($instance);
        $storagePath   = $dockerVolume ?? storage_path('app/compute/' . $instance->id);
        $containerName = 'cp_ide_' . $instance->id . '_' . $user->id;
        $idePassword   = Str::random(16);
        $networkName   = 'cp_user_' . $user->id;

        if (!$dockerVolume && !is_dir($storagePath)) mkdir($storagePath, 0755, true);

        $appendLog('[2/3] Menyiapkan network...');
        try { $this->ensureNetwork($networkName); }
        catch (\RuntimeException $e) {
            $appendLog('GAGAL: ' . $e->getMessage());
            $instance->status = 'FAILED'; $instance->save(); return $instance;
        }

        $appendLog('[3/3] Menyiapkan Cloud IDE (VS Code)...');
        $appendLog('  Pulling image codercom/code-server...');
        $pull = $execStream('docker pull codercom/code-server:latest');
        if ($pull['rc'] !== 0) {
            $appendLog('GAGAL pull image.');
            $instance->status = 'FAILED'; $instance->save(); return $instance;
        }
        $appendLog('  Image siap. Menjalankan IDE...');

        $storageFlag = $dockerVolume
            ? "-v {$dockerVolume}:/home/coder/project"
            : '-v ' . escapeshellarg($storagePath) . ':/home/coder/project';

        $cmd = sprintf(
            'docker run -d --name %s --memory=%dm --memory-swap=%dm --cpus=%s --network=%s -p %d:8080 %s -e PASSWORD=%s --restart=unless-stopped codercom/code-server:latest',
            escapeshellarg($containerName), $ramMb, $ramMb, $cpu,
            escapeshellarg($networkName), $idePort,
            $storageFlag, escapeshellarg($idePassword)
        );
        $run = $execStream($cmd . ' 2>&1');
        if ($run['rc'] !== 0) {
            $appendLog('GAGAL docker run (exit ' . $run['rc'] . ').');
            $instance->status = 'FAILED'; $instance->save(); return $instance;
        }

        $containerId = trim($run['out'][0] ?? $run['last']);
        $appendLog('Container IDE siap (' . substr($containerId, 0, 12) . '). Menunggu server siap...');
        $this->waitForPort($idePort, 30);

        // EC2 control plane
        $ec2InstanceId = null;
        try {
            $ec2InstanceId = app(MiniStackService::class)->runInstance($containerName);
            $appendLog('EC2 Instance registered: ' . $ec2InstanceId);
        } catch (\Exception $e) { Log::warning("EC2 RunInstances skip: {$e->getMessage()}"); }

        $appendLog('✅ Cloud IDE siap! Buka di: http://localhost:' . $idePort);

        $volumeId = $instance->metadata['volume_id'] ?? null;
        $volume   = $volumeId ? BlockVolume::find($volumeId) : null;

        $instance->status         = 'RUNNING';
        $instance->started_at     = now();
        $instance->price_per_hour = $price;
        $instance->metadata       = [
            'type'           => 'ide',
            'ec2_instance_id'=> $ec2InstanceId,
            'container_id'   => $containerId,
            'container_name' => $containerName,
            'network'        => $networkName,
            'storage_path'   => $storagePath,
            'volume_id'      => $volumeId,
            'volume_name'    => $volume?->volume_name,
            'volume_size_gb' => $volume?->size_gb,
            'ide_port'       => $idePort,
            'ide_password'   => $idePassword,
            'plan_label'     => "{$cpu} vCPU · {$ramLabel}",
            'resources'      => ['cpu' => $cpu, 'memory' => $ramMb],
            'price_per_hour' => $price,
        ];
        $instance->save();
        $this->attachVolumeToInstance($instance);
        return $instance;
    }

    // ─── Provision Notebook (Jupyter) ─────────────────────────────────────────

    protected function provisionNotebook(ComputeInstance $instance): ComputeInstance
    {
        $user    = $instance->user;
        $meta    = $instance->metadata ?? [];
        $cpu     = $meta['cpu'] ?? 1;
        $ramMb   = $meta['ram'] ?? 1024;
        $price   = self::calculatePrice($cpu, $ramMb);
        $ramLabel = $ramMb >= 1024 ? ($ramMb / 1024) . 'GB' : $ramMb . 'MB';
        [$appendLog, $execStream] = $this->makeHelpers($instance);

        $nbPort        = $this->pickAvailablePort(40000, 49999);
        $dockerVolume  = $this->resolveStorageMount($instance);
        $storagePath   = $dockerVolume ?? storage_path('app/compute/' . $instance->id);
        $containerName = 'cp_nb_' . $instance->id . '_' . $user->id;
        $nbToken       = Str::random(32);
        $networkName   = 'cp_user_' . $user->id;

        if (!$dockerVolume && !is_dir($storagePath)) mkdir($storagePath, 0755, true);

        $appendLog('[2/3] Menyiapkan network...');
        try { $this->ensureNetwork($networkName); }
        catch (\RuntimeException $e) {
            $appendLog('GAGAL: ' . $e->getMessage());
            $instance->status = 'FAILED'; $instance->save(); return $instance;
        }

        $appendLog('[3/3] Menyiapkan Jupyter Notebook...');
        $appendLog('  Pulling image jupyter/minimal-notebook...');
        $pull = $execStream('docker pull jupyter/minimal-notebook:latest');
        if ($pull['rc'] !== 0) {
            $appendLog('GAGAL pull image.');
            $instance->status = 'FAILED'; $instance->save(); return $instance;
        }
        $appendLog('  Image siap. Menjalankan Jupyter...');

        $storageFlag = $dockerVolume
            ? "-v {$dockerVolume}:/home/jovyan/work"
            : '-v ' . escapeshellarg($storagePath) . ':/home/jovyan/work';

        $cmd = sprintf(
            'docker run -d --name %s --memory=%dm --memory-swap=%dm --cpus=%s --network=%s -p %d:8888 %s -e JUPYTER_TOKEN=%s --restart=unless-stopped jupyter/minimal-notebook:latest',
            escapeshellarg($containerName), $ramMb, $ramMb, $cpu,
            escapeshellarg($networkName), $nbPort,
            $storageFlag, escapeshellarg($nbToken)
        );
        $run = $execStream($cmd . ' 2>&1');
        if ($run['rc'] !== 0) {
            $appendLog('GAGAL docker run (exit ' . $run['rc'] . ').');
            $instance->status = 'FAILED'; $instance->save(); return $instance;
        }

        $containerId = trim($run['out'][0] ?? $run['last']);
        $appendLog('Container Jupyter siap (' . substr($containerId, 0, 12) . '). Menunggu server siap...');
        $this->waitForPort($nbPort, 30);

        // EC2 control plane
        $ec2InstanceId = null;
        try {
            $ec2InstanceId = app(MiniStackService::class)->runInstance($containerName);
            $appendLog('EC2 Instance registered: ' . $ec2InstanceId);
        } catch (\Exception $e) { Log::warning("EC2 RunInstances skip: {$e->getMessage()}"); }

        $appendLog('✅ Jupyter Notebook siap! Buka di: http://localhost:' . $nbPort . '/?token=' . $nbToken);

        $volumeId = $instance->metadata['volume_id'] ?? null;
        $volume   = $volumeId ? BlockVolume::find($volumeId) : null;

        $instance->status         = 'RUNNING';
        $instance->started_at     = now();
        $instance->price_per_hour = $price;
        $instance->metadata       = [
            'type'           => 'notebook',
            'ec2_instance_id'=> $ec2InstanceId,
            'container_id'   => $containerId,
            'container_name' => $containerName,
            'network'        => $networkName,
            'storage_path'   => $storagePath,
            'volume_id'      => $volumeId,
            'volume_name'    => $volume?->volume_name,
            'volume_size_gb' => $volume?->size_gb,
            'nb_port'        => $nbPort,
            'nb_token'       => $nbToken,
            'plan_label'     => "{$cpu} vCPU · {$ramLabel}",
            'resources'      => ['cpu' => $cpu, 'memory' => $ramMb],
            'price_per_hour' => $price,
        ];
        $instance->save();
        $this->attachVolumeToInstance($instance);
        return $instance;
    }

    // ─── Ubah status ──────────────────────────────────────────────────────────

    public function changeStatus(ComputeInstance $instance, string $action): ComputeInstance
    {
        $meta          = $instance->metadata ?? [];
        $containerName = $meta['container_name'] ?? null;
        $containerId   = $meta['container_id']   ?? null;
        $wettyContainer = $meta['wetty_container'] ?? null;
        $target        = $containerName ?? $containerId;

        if (!$target) return $this->simulateAction($instance, $action);

        $companions    = array_filter([$wettyContainer]);
        $ec2InstanceId = $meta['ec2_instance_id'] ?? null;
        $miniStack     = app(MiniStackService::class);

        switch ($action) {
            case 'start':
                exec('docker start ' . escapeshellarg($target) . ' 2>&1', $out, $rc);
                if ($rc === 0) {
                    $instance->status = 'RUNNING'; $instance->started_at = now();
                    foreach ($companions as $c) exec('docker start ' . escapeshellarg($c) . ' 2>&1');
                    if ($ec2InstanceId) { try { $miniStack->startInstance($ec2InstanceId); } catch (\Exception $e) {} }
                } else logger()->error('docker start failed: ' . implode(' ', $out));
                break;

            case 'stop':
                exec('docker stop ' . escapeshellarg($target) . ' 2>&1', $out, $rc);
                if ($rc === 0) {
                    $instance->status = 'STOPPED'; $instance->stopped_at = now();
                    $this->computeUsageAndCost($instance);
                    foreach ($companions as $c) exec('docker stop ' . escapeshellarg($c) . ' 2>&1');
                    if ($ec2InstanceId) { try { $miniStack->stopInstance($ec2InstanceId); } catch (\Exception $e) {} }
                } else logger()->error('docker stop failed: ' . implode(' ', $out));
                break;

            case 'restart':
                exec('docker restart ' . escapeshellarg($target) . ' 2>&1', $out, $rc);
                if ($rc === 0) {
                    $instance->status = 'RUNNING';
                    foreach ($companions as $c) exec('docker restart ' . escapeshellarg($c) . ' 2>&1');
                } else logger()->error('docker restart failed: ' . implode(' ', $out));
                break;

            case 'terminate':
                exec('docker stop ' . escapeshellarg($target) . ' 2>&1');
                exec('docker rm -f ' . escapeshellarg($target) . ' 2>&1');
                foreach ($companions as $c) {
                    exec('docker stop ' . escapeshellarg($c) . ' 2>&1');
                    exec('docker rm -f ' . escapeshellarg($c) . ' 2>&1');
                }
                if ($ec2InstanceId) { try { $miniStack->terminateInstance($ec2InstanceId); } catch (\Exception $e) {} }
                $instance->status = 'TERMINATED'; $instance->stopped_at = now();
                $this->computeUsageAndCost($instance);
                $this->detachVolumeFromInstance($instance);
                break;
        }

        $instance->save();
        return $instance;
    }

    // ─── Live stats ───────────────────────────────────────────────────────────

    public function getStats(ComputeInstance $instance): array
    {
        $meta   = $instance->metadata ?? [];
        $target = $meta['container_name'] ?? $meta['container_id'] ?? null;
        if (!$target || $instance->status !== 'RUNNING') return ['error' => 'container not running'];

        exec('docker stats --no-stream --format "{{.CPUPerc}},{{.MemUsage}},{{.MemPerc}},{{.NetIO}},{{.BlockIO}}" ' . escapeshellarg($target) . ' 2>&1', $out, $rc);
        if ($rc !== 0 || empty($out)) return ['error' => 'stats unavailable'];

        $parts = explode(',', $out[0]);

        $stats = [
            'cpu_perc'  => trim($parts[0] ?? '0%'),
            'mem_usage' => trim($parts[1] ?? '0B / 0B'),
            'mem_perc'  => trim($parts[2] ?? '0%'),
            'net_io'    => trim($parts[3] ?? '0B / 0B'),
            'block_io'  => trim($parts[4] ?? '0B / 0B'),
        ];

        // Disk usage — jika pakai block volume, tampilkan volume size sebagai total
        $mountPoint = match($meta['type'] ?? 'vm') {
            'ide'      => '/home/coder/project',
            'notebook' => '/home/jovyan/work',
            default    => '/data',
        };

        $volumeSizeGb = $meta['volume_size_gb'] ?? null;
        $nullDevice   = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';

        if ($volumeSizeGb) {
            exec('docker exec ' . escapeshellarg($target) . ' du -sb ' . escapeshellarg($mountPoint) . ' 2>' . $nullDevice, $duOut, $duRc);
            $usedBytes = $duRc === 0 ? (int) ($duOut[0] ?? 0) : 0;
            $usedGb    = $usedBytes / (1024 ** 3);
            $usedLabel = $usedGb >= 1 ? round($usedGb, 1) . 'G' : round($usedBytes / (1024 ** 2), 1) . 'M';
            $diskPct   = $volumeSizeGb > 0 ? round(($usedGb / $volumeSizeGb) * 100, 0) : 0;

            $stats['disk_used'] = $usedLabel . ' / ' . $volumeSizeGb . 'G';
            $stats['disk_pct']  = $diskPct;

            // Soft enforcement: kalau melebihi 100%, set read-only lalu stop
            if ($usedGb >= $volumeSizeGb) {
                $stats['disk_over_limit'] = true;
                // Set mount read-only agar tidak bisa tulis lagi
                exec('docker exec ' . escapeshellarg($target) . ' chmod -R a-w ' . escapeshellarg($mountPoint) . ' 2>' . $nullDevice);
            }
        } else {
            exec('docker exec ' . escapeshellarg($target) . ' df -h ' . escapeshellarg($mountPoint) . ' 2>&1', $dfOut, $dfRc);
            if ($dfRc === 0 && isset($dfOut[1])) {
                $cols = preg_split('/\s+/', trim($dfOut[1]));
                $stats['disk_used'] = ($cols[2] ?? '—') . ' / ' . ($cols[1] ?? '—');
                $stats['disk_pct']  = rtrim($cols[4] ?? '0', '%');
            }
        }

        return $stats;
    }

    // ─── Storage mount helper ────────────────────────────────────────────────

    protected function resolveStorageMount(ComputeInstance $instance): ?string
    {
        $volumeId = $instance->metadata['volume_id'] ?? null;
        if (!$volumeId) return null;

        $volume = BlockVolume::find($volumeId);
        if (!$volume || !$volume->provider_volume_id) return null;

        return "cp_vol_{$volume->provider_volume_id}";
    }

    protected function attachVolumeToInstance(ComputeInstance $instance): void
    {
        $volumeId = $instance->metadata['volume_id'] ?? null;
        if (!$volumeId) return;

        BlockVolume::where('id', $volumeId)->update([
            'status' => 'ATTACHED',
            'compute_instance_id' => $instance->id,
        ]);
    }

    protected function detachVolumeFromInstance(ComputeInstance $instance): void
    {
        BlockVolume::where('compute_instance_id', $instance->id)
            ->update(['status' => 'AVAILABLE', 'compute_instance_id' => null]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    protected function makeHelpers(ComputeInstance $instance): array
    {
        $appendLog = function (string $line) use ($instance): void {
            $instance->provision_log = ($instance->provision_log ?? '') . $line . "\n";
            $instance->save();
        };

        $execStream = function (string $cmd) use ($appendLog): array {
            $proc = proc_open($cmd, [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
            if (!is_resource($proc)) return ['rc' => -1, 'out' => [], 'last' => ''];
            fclose($pipes[0]);
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);
            $out = []; $last = '';
            while (true) {
                $status = proc_get_status($proc);
                foreach ([$pipes[1], $pipes[2]] as $pipe) {
                    $line = fgets($pipe);
                    if ($line !== false && trim($line) !== '') {
                        $line = rtrim($line); $out[] = $line; $last = $line;
                        $appendLog('  ' . $line);
                    }
                }
                if (!$status['running'] && fgets($pipes[1]) === false && fgets($pipes[2]) === false) break;
                usleep(50000);
            }
            fclose($pipes[1]); fclose($pipes[2]);
            return ['rc' => proc_close($proc), 'out' => $out, 'last' => $last];
        };

        return [$appendLog, $execStream];
    }

    protected function ensureNetwork(string $networkName): void
    {
        exec('docker network inspect ' . escapeshellarg($networkName) . ' 2>&1', $o, $rc);
        if ($rc === 0) return;

        exec('docker network create ' . escapeshellarg($networkName) . ' 2>&1', $out, $createRc);
        if ($createRc !== 0) throw new \RuntimeException('docker network create gagal: ' . implode(' ', $out));

        $attempts = 0;
        do {
            usleep(300000);
            exec('docker network inspect ' . escapeshellarg($networkName) . ' 2>&1', $o, $checkRc);
            $attempts++;
        } while ($checkRc !== 0 && $attempts < 10);

        if ($checkRc !== 0) throw new \RuntimeException('Network tidak kunjung tersedia setelah ' . $attempts . ' percobaan.');
    }

    protected function waitForPort(int $port, int $maxAttempts = 36): void
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $conn = @fsockopen('127.0.0.1', $port, $errno, $errstr, 2);
            if ($conn) { fclose($conn); return; }
            sleep(5);
        }
    }

    protected function buildBootstrapScript(string $family, string $sshPassword): string
    {
        $escapedPw = str_replace("'", "'\\''", $sshPassword);
        $pwLine    = "echo 'root:{$escapedPw}' | chpasswd";

        if ($family === 'apk') {
            return "apk add --no-cache openssh-server >/dev/null 2>&1 && ssh-keygen -A >/dev/null 2>&1 && {$pwLine} && sed -ri 's/^#?PermitRootLogin.*/PermitRootLogin yes/' /etc/ssh/sshd_config && sed -ri 's/^#?PasswordAuthentication.*/PasswordAuthentication yes/' /etc/ssh/sshd_config && exec /usr/sbin/sshd -D";
        }

        return "export DEBIAN_FRONTEND=noninteractive && apt-get update -qq && apt-get install -y -qq openssh-server >/dev/null 2>&1 && mkdir -p /run/sshd && {$pwLine} && sed -ri 's/^#?PermitRootLogin.*/PermitRootLogin yes/' /etc/ssh/sshd_config && sed -ri 's/^#?PasswordAuthentication.*/PasswordAuthentication yes/' /etc/ssh/sshd_config && exec /usr/sbin/sshd -D";
    }

    protected function getContainerIp(string $containerName, string $network): ?string
    {
        exec('docker inspect --format "{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}" ' . escapeshellarg($containerName) . ' 2>&1', $out, $rc);
        return $rc === 0 ? (trim($out[0] ?? '') ?: null) : null;
    }

    protected function pickAvailablePort(int $min, int $max): int
    {
        $used = ComputeInstance::whereNotIn('status', ['TERMINATED'])
            ->whereNotNull('metadata')->get()->pluck('metadata')
            ->flatMap(fn($m) => array_filter([
                $m['ssh_port'] ?? null, $m['wetty_port'] ?? null,
                $m['ide_port'] ?? null, $m['nb_port']   ?? null,
            ]))->filter()->toArray();

        do { $port = rand($min, $max); } while (in_array($port, $used));
        return $port;
    }

    protected function computeUsageAndCost(ComputeInstance $instance): void
    {
        $started = $instance->started_at;
        $stopped = $instance->stopped_at ?? now();
        $hours   = $started ? round(max(0, $stopped->getTimestamp() - $started->getTimestamp()) / 3600, 4) : 0;
        $pricePerHour = $instance->price_per_hour ?? ($instance->metadata['price_per_hour'] ?? 0);
        $instance->usage_hours = $hours; $instance->price_per_hour = $pricePerHour;
        $instance->cost = round($hours * $pricePerHour, 2);
    }

    protected function simulateAction(ComputeInstance $instance, string $action): ComputeInstance
    {
        match ($action) {
            'start'     => ($instance->status = 'RUNNING')    && ($instance->started_at = now()),
            'stop'      => ($instance->status = 'STOPPED')    && ($instance->stopped_at = now()),
            'terminate' => ($instance->status = 'TERMINATED') && ($instance->stopped_at = now()),
            default     => null,
        };
        if (in_array($action, ['stop', 'terminate'])) $this->computeUsageAndCost($instance);
        $instance->save();
        return $instance;
    }
}