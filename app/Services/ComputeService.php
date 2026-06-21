<?php

namespace App\Services;

use App\Models\ComputeInstance;
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

    // ─── Katalog plan ─────────────────────────────────────────────────────────
    const PLANS = [
        'nano'   => ['cpu' => 0.5, 'memory' => 512,  'disk' => 10,  'price' => 500,  'label' => 'Nano'],
        'micro'  => ['cpu' => 1,   'memory' => 1024, 'disk' => 20,  'price' => 1000, 'label' => 'Micro'],
        'small'  => ['cpu' => 1,   'memory' => 2048, 'disk' => 40,  'price' => 2000, 'label' => 'Small'],
        'medium' => ['cpu' => 2,   'memory' => 4096, 'disk' => 80,  'price' => 4000, 'label' => 'Medium'],
        'large'  => ['cpu' => 4,   'memory' => 8192, 'disk' => 160, 'price' => 8000, 'label' => 'Large'],
    ];

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

    public function createInstance($user, string $planKey, array $options = []): ComputeInstance
    {
        $instance = $this->initInstance($user, $planKey, $options);

        $artisan = base_path('artisan');
        $logFile = storage_path('logs/provision_' . $instance->id . '.out');

        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen(sprintf('start /B php %s compute:provision %d > %s 2>&1',
                escapeshellarg($artisan), $instance->id, escapeshellarg($logFile)), 'r'));
        } else {
            exec(sprintf('nohup php %s compute:provision %d > %s 2>&1 &',
                escapeshellarg($artisan), $instance->id, escapeshellarg($logFile)));
        }

        return $instance;
    }

    // ─── Tahap 1: buat record di DB ──────────────────────────────────────────

    public function initInstance($user, string $planKey, array $options = []): ComputeInstance
    {
        $type   = $options['type'] ?? self::DEFAULT_TYPE;
        $plan   = self::PLANS[$planKey] ?? self::PLANS['micro'];
        $osKey  = $options['os']        ?? self::DEFAULT_OS;
        $osConf = self::IMAGES[$osKey]  ?? self::IMAGES[self::DEFAULT_OS];
        $typeConf = self::TYPES[$type]  ?? self::TYPES['vm'];

        $prefix = match($type) {
            'ide'      => 'ide',
            'notebook' => 'nb',
            default    => 'vm',
        };

        return ComputeInstance::create([
            'user_id'       => $user->id,
            'name'          => $prefix . '-' . Str::random(8),
            'plan'          => $planKey,
            'os'            => $osKey,
            'status'        => 'PROVISIONING',
            'metadata'      => ['type' => $type],
            'provision_log' => "[1/3] Menyiapkan {$typeConf['label']} (plan: {$plan['label']})...\n",
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
        $plan   = self::PLANS[$instance->plan] ?? self::PLANS['micro'];
        $osKey  = $instance->os               ?? self::DEFAULT_OS;
        $osConf = self::IMAGES[$osKey]         ?? self::IMAGES[self::DEFAULT_OS];

        [$appendLog, $execStream] = $this->makeHelpers($instance);

        $sshPort   = $this->pickAvailablePort(10000, 19999);
        $wettyPort = $this->pickAvailablePort(20000, 29999);

        $storagePath    = storage_path('app/compute/' . $instance->id);
        $containerName  = 'cp_' . $instance->id . '_' . $user->id;
        $wettyName      = 'cp_wetty_' . $instance->id . '_' . $user->id;
        $sshPassword    = Str::random(16);
        $networkName    = 'cp_user_' . $user->id;

        if (!is_dir($storagePath)) mkdir($storagePath, 0755, true);

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

        $vmCmd = sprintf(
            'docker run -d --name %s --memory=%dm --cpus=%s --network=%s -p %d:22 -v %s:/data --restart=unless-stopped %s %s -c %s',
            escapeshellarg($containerName), $plan['memory'], $plan['cpu'],
            escapeshellarg($networkName), $sshPort, escapeshellarg($storagePath),
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
        $appendLog('✅ VM siap! SSH port: ' . $sshPort . ($wettyPort ? ' | Web terminal: ' . $wettyPort : ''));

        $instance->status         = 'RUNNING';
        $instance->started_at     = now();
        $instance->ip_address     = $containerIp;
        $instance->price_per_hour = $plan['price'];
        $instance->metadata       = [
            'type'            => 'vm',
            'container_id'    => $containerId,
            'container_name'  => $containerName,
            'wetty_container' => $wettyOk ? $wettyName : null,
            'network'         => $networkName,
            'storage_path'    => $storagePath,
            'ssh_port'        => $sshPort,
            'ssh_user'        => 'root',
            'ssh_password'    => $sshPassword,
            'wetty_port'      => $wettyPort,
            'os'              => $osKey,
            'os_label'        => $osConf['label'],
            'plan'            => $instance->plan,
            'plan_label'      => $plan['label'],
            'resources'       => ['cpu' => $plan['cpu'], 'memory' => $plan['memory'], 'disk' => $plan['disk']],
            'price_per_hour'  => $plan['price'],
        ];
        $instance->save();
        return $instance;
    }

    // ─── Provision IDE (code-server / VS Code) ────────────────────────────────

    protected function provisionIde(ComputeInstance $instance): ComputeInstance
    {
        $user    = $instance->user;
        $plan    = self::PLANS[$instance->plan] ?? self::PLANS['micro'];
        [$appendLog, $execStream] = $this->makeHelpers($instance);

        $idePort      = $this->pickAvailablePort(30000, 39999);
        $storagePath  = storage_path('app/compute/' . $instance->id);
        $containerName = 'cp_ide_' . $instance->id . '_' . $user->id;
        $idePassword   = Str::random(16);
        $networkName   = 'cp_user_' . $user->id;

        if (!is_dir($storagePath)) mkdir($storagePath, 0755, true);

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

        $cmd = sprintf(
            'docker run -d --name %s --memory=%dm --cpus=%s --network=%s -p %d:8080 -v %s:/home/coder/project -e PASSWORD=%s --restart=unless-stopped codercom/code-server:latest',
            escapeshellarg($containerName), $plan['memory'], $plan['cpu'],
            escapeshellarg($networkName), $idePort,
            escapeshellarg($storagePath), escapeshellarg($idePassword)
        );
        $run = $execStream($cmd . ' 2>&1');
        if ($run['rc'] !== 0) {
            $appendLog('GAGAL docker run (exit ' . $run['rc'] . ').');
            $instance->status = 'FAILED'; $instance->save(); return $instance;
        }

        $containerId = trim($run['out'][0] ?? $run['last']);
        $appendLog('Container IDE siap (' . substr($containerId, 0, 12) . '). Menunggu server siap...');
        $this->waitForPort($idePort, 30);

        $appendLog('✅ Cloud IDE siap! Buka di: http://localhost:' . $idePort);

        $instance->status         = 'RUNNING';
        $instance->started_at     = now();
        $instance->price_per_hour = $plan['price'];
        $instance->metadata       = [
            'type'           => 'ide',
            'container_id'   => $containerId,
            'container_name' => $containerName,
            'network'        => $networkName,
            'storage_path'   => $storagePath,
            'ide_port'       => $idePort,
            'ide_password'   => $idePassword,
            'plan'           => $instance->plan,
            'plan_label'     => $plan['label'],
            'resources'      => ['cpu' => $plan['cpu'], 'memory' => $plan['memory'], 'disk' => $plan['disk']],
            'price_per_hour' => $plan['price'],
        ];
        $instance->save();
        return $instance;
    }

    // ─── Provision Notebook (Jupyter) ─────────────────────────────────────────

    protected function provisionNotebook(ComputeInstance $instance): ComputeInstance
    {
        $user    = $instance->user;
        $plan    = self::PLANS[$instance->plan] ?? self::PLANS['micro'];
        [$appendLog, $execStream] = $this->makeHelpers($instance);

        $nbPort       = $this->pickAvailablePort(40000, 49999);
        $storagePath  = storage_path('app/compute/' . $instance->id);
        $containerName = 'cp_nb_' . $instance->id . '_' . $user->id;
        $nbToken       = Str::random(32);
        $networkName   = 'cp_user_' . $user->id;

        if (!is_dir($storagePath)) mkdir($storagePath, 0755, true);

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

        $cmd = sprintf(
            'docker run -d --name %s --memory=%dm --cpus=%s --network=%s -p %d:8888 -v %s:/home/jovyan/work -e JUPYTER_TOKEN=%s --restart=unless-stopped jupyter/minimal-notebook:latest',
            escapeshellarg($containerName), $plan['memory'], $plan['cpu'],
            escapeshellarg($networkName), $nbPort,
            escapeshellarg($storagePath), escapeshellarg($nbToken)
        );
        $run = $execStream($cmd . ' 2>&1');
        if ($run['rc'] !== 0) {
            $appendLog('GAGAL docker run (exit ' . $run['rc'] . ').');
            $instance->status = 'FAILED'; $instance->save(); return $instance;
        }

        $containerId = trim($run['out'][0] ?? $run['last']);
        $appendLog('Container Jupyter siap (' . substr($containerId, 0, 12) . '). Menunggu server siap...');
        $this->waitForPort($nbPort, 30);

        $appendLog('✅ Jupyter Notebook siap! Buka di: http://localhost:' . $nbPort . '/?token=' . $nbToken);

        $instance->status         = 'RUNNING';
        $instance->started_at     = now();
        $instance->price_per_hour = $plan['price'];
        $instance->metadata       = [
            'type'           => 'notebook',
            'container_id'   => $containerId,
            'container_name' => $containerName,
            'network'        => $networkName,
            'storage_path'   => $storagePath,
            'nb_port'        => $nbPort,
            'nb_token'       => $nbToken,
            'plan'           => $instance->plan,
            'plan_label'     => $plan['label'],
            'resources'      => ['cpu' => $plan['cpu'], 'memory' => $plan['memory'], 'disk' => $plan['disk']],
            'price_per_hour' => $plan['price'],
        ];
        $instance->save();
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

        $companions = array_filter([$wettyContainer]);

        switch ($action) {
            case 'start':
                exec('docker start ' . escapeshellarg($target) . ' 2>&1', $out, $rc);
                if ($rc === 0) {
                    $instance->status = 'RUNNING'; $instance->started_at = now();
                    foreach ($companions as $c) exec('docker start ' . escapeshellarg($c) . ' 2>&1');
                } else logger()->error('docker start failed: ' . implode(' ', $out));
                break;

            case 'stop':
                exec('docker stop ' . escapeshellarg($target) . ' 2>&1', $out, $rc);
                if ($rc === 0) {
                    $instance->status = 'STOPPED'; $instance->stopped_at = now();
                    $this->computeUsageAndCost($instance);
                    foreach ($companions as $c) exec('docker stop ' . escapeshellarg($c) . ' 2>&1');
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
                $instance->status = 'TERMINATED'; $instance->stopped_at = now();
                $this->computeUsageAndCost($instance);
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

        // Disk usage dari volume /data (atau mount point sesuai tipe)
        $mountPoint = match($meta['type'] ?? 'vm') {
            'ide'      => '/home/coder/project',
            'notebook' => '/home/jovyan/work',
            default    => '/data',
        };

        exec('docker exec ' . escapeshellarg($target) . ' df -h ' . escapeshellarg($mountPoint) . ' 2>&1', $dfOut, $dfRc);
        if ($dfRc === 0 && isset($dfOut[1])) {
            // Format: Filesystem Size Used Avail Use% Mounted
            $cols = preg_split('/\s+/', trim($dfOut[1]));
            $stats['disk_used'] = ($cols[2] ?? '—') . ' / ' . ($cols[1] ?? '—');
            $stats['disk_pct']  = rtrim($cols[4] ?? '0', '%');
        }

        return $stats;
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