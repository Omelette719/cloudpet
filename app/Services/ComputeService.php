<?php

namespace App\Services;

use App\Models\ComputeInstance;
use Illuminate\Support\Str;

class ComputeService
{
    // ─── Katalog plan ────────────────────────────────────────────────────────
    const PLANS = [
        'nano'   => ['cpu' => 0.5, 'memory' => 512,  'disk' => 10,  'price' => 500,  'label' => 'Nano'],
        'micro'  => ['cpu' => 1,   'memory' => 1024,  'disk' => 20,  'price' => 1000, 'label' => 'Micro'],
        'small'  => ['cpu' => 1,   'memory' => 2048,  'disk' => 40,  'price' => 2000, 'label' => 'Small'],
        'medium' => ['cpu' => 2,   'memory' => 4096,  'disk' => 80,  'price' => 4000, 'label' => 'Medium'],
        'large'  => ['cpu' => 4,   'memory' => 8192,  'disk' => 160, 'price' => 8000, 'label' => 'Large'],
    ];

    // OS images yang tersedia — pakai image resmi (selalu ada di Docker Hub),
    // openssh-server di-install saat container start (lihat buildBootstrapScript()).
    // 'family' menentukan package manager: apt (ubuntu/debian) atau apk (alpine).
    const IMAGES = [
        'ubuntu-22.04' => ['image' => 'ubuntu:22.04', 'label' => 'Ubuntu 22.04', 'family' => 'apt'],
        'ubuntu-20.04' => ['image' => 'ubuntu:20.04', 'label' => 'Ubuntu 20.04', 'family' => 'apt'],
        'debian-12'    => ['image' => 'debian:12',    'label' => 'Debian 12',    'family' => 'apt'],
        'alpine'       => ['image' => 'alpine:latest', 'label' => 'Alpine Linux', 'family' => 'apk'],
    ];

    const DEFAULT_OS = 'ubuntu-22.04';

    // ─── Buat instance baru ──────────────────────────────────────────────────

    public function createInstance($user, string $planKey, array $options = []): ComputeInstance
    {
        $instance = $this->initInstance($user, $planKey, $options);

        // Jalankan tahap berat (docker pull/run) di background via artisan command
        // supaya request HTTP ini langsung balik tanpa nunggu provisioning kelar.
        // Frontend lalu polling GET /cloud/api/instances/{id}/log untuk live progress.
        $artisan = base_path('artisan');
        $logFile = storage_path('logs/provision_' . $instance->id . '.out');
        $cmd = sprintf(
            'nohup php %s compute:provision %d > %s 2>&1 &',
            escapeshellarg($artisan),
            $instance->id,
            escapeshellarg($logFile)
        );
        exec($cmd);

        return $instance;
    }

    // ─── Tahap 1: buat record instance secepatnya (dipanggil langsung dari controller) ──

    public function initInstance($user, string $planKey, array $options = []): ComputeInstance
    {
        $plan   = self::PLANS[$planKey] ?? self::PLANS['micro'];
        $osKey  = $options['os'] ?? self::DEFAULT_OS;
        $osConf = self::IMAGES[$osKey] ?? self::IMAGES[self::DEFAULT_OS];

        return ComputeInstance::create([
            'user_id'       => $user->id,
            'name'          => 'vm-' . Str::random(8),
            'plan'          => $planKey,
            'os'            => $osKey,
            'status'        => 'PROVISIONING',
            'metadata'      => [],
            'provision_log' => "[1/4] Menyiapkan instance \"" . $osConf['label'] . "\" (plan: {$plan['label']})...\n",
        ]);
    }

    // ─── Tahap 2: kerjaan berat docker, dipanggil dari command compute:provision ───────

    public function provision(ComputeInstance $instance): ComputeInstance
    {
        $user   = $instance->user;
        $plan   = self::PLANS[$instance->plan] ?? self::PLANS['micro'];
        $osKey  = $instance->os ?? self::DEFAULT_OS;
        $osConf = self::IMAGES[$osKey] ?? self::IMAGES[self::DEFAULT_OS];

        $appendLog = function (string $line) use ($instance) {
            $instance->provision_log = ($instance->provision_log ?? '') . $line . "\n";
            $instance->save();
        };

        $sshPort = $this->pickAvailablePort(10000, 19999);

        $storagePath = storage_path('app/compute/' . $instance->id);
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $containerName = 'cp_' . $instance->id . '_' . $user->id;
        $sshPassword   = Str::random(16);
        $networkName   = 'cp_user_' . $user->id;

        $appendLog('[2/4] Menyiapkan network isolasi (' . $networkName . ')...');
        $this->ensureNetwork($networkName);

        $family    = $osConf['family'] ?? 'apt';
        $bootstrap = $this->buildBootstrapScript($family, $sshPassword);
        $shell     = $family === 'apk' ? 'sh' : 'bash';

        $cmd = sprintf(
            'docker run -d --name %s' .
                ' --memory=%dm --cpus=%s' .
                ' --network=%s' .
                ' -p %d:22' .
                ' -v %s:/data' .
                ' --restart=unless-stopped' .
                ' %s %s -c %s',
            escapeshellarg($containerName),
            $plan['memory'],
            $plan['cpu'],
            escapeshellarg($networkName),
            $sshPort,
            escapeshellarg($storagePath),
            escapeshellarg($osConf['image']),
            $shell,
            escapeshellarg($bootstrap)
        );

        $appendLog('[3/4] Menyiapkan image "' . $osConf['image'] . '" & install OpenSSH server (bisa makan waktu beberapa puluh detik kalau image/paket belum di-cache)...');
        exec($cmd . ' 2>&1', $out, $rc);

        if ($rc !== 0) {
            $appendLog('GAGAL: docker run keluar dengan kode ' . $rc);
            foreach ($out as $line) {
                $appendLog('  ' . $line);
            }
            $instance->status   = 'FAILED';
            $instance->metadata = ['error' => 'docker run failed', 'cmd_output' => $out];
            $instance->save();
            return $instance;
        }

        $containerId = trim($out[0] ?? '');
        $appendLog('Container berhasil dibuat (' . substr($containerId, 0, 12) . ').');

        sleep(1);
        $containerIp = $this->getContainerIp($containerName, $networkName);

        $appendLog('[4/4] Instance siap. SSH port: ' . $sshPort);

        $instance->status     = 'RUNNING';
        $instance->started_at = now();
        $instance->ip_address = $containerIp;
        $instance->metadata   = [
            'container_id'   => $containerId,
            'container_name' => $containerName,
            'network'        => $networkName,
            'storage_path'   => $storagePath,
            'ssh_port'       => $sshPort,
            'ssh_user'       => 'root',
            'ssh_password'   => $sshPassword,
            'os'             => $osKey,
            'os_label'       => $osConf['label'],
            'plan'           => $instance->plan,
            'plan_label'     => $plan['label'],
            'resources'      => [
                'cpu'    => $plan['cpu'],
                'memory' => $plan['memory'],
                'disk'   => $plan['disk'],
            ],
            'price_per_hour' => $plan['price'],
        ];
        $instance->price_per_hour = $plan['price'];
        $instance->save();

        return $instance;
    }

    // ─── Ubah status ─────────────────────────────────────────────────────────
    // (sisanya sama persis seperti file aslimu — changeStatus, getStats, ensureNetwork,
    // getContainerIp, pickAvailablePort, computeUsageAndCost, simulateAction —
    // tidak ada yang diubah, jadi tidak saya ulang di sini.)

    // ─── Ubah status ─────────────────────────────────────────────────────────

    public function changeStatus(ComputeInstance $instance, string $action): ComputeInstance
    {
        $meta          = $instance->metadata ?? [];
        $containerName = $meta['container_name'] ?? null;
        $containerId   = $meta['container_id']   ?? null;
        $target        = $containerName ?? $containerId;

        if (!$target) {
            // Tidak ada container → update status saja (mock/legacy)
            return $this->simulateAction($instance, $action);
        }

        switch ($action) {
            case 'start':
                exec('docker start ' . escapeshellarg($target), $out, $rc);
                if ($rc === 0) {
                    $instance->status     = 'RUNNING';
                    $instance->started_at = now();
                }
                break;

            case 'stop':
                exec('docker stop ' . escapeshellarg($target), $out, $rc);
                if ($rc === 0) {
                    $instance->status     = 'STOPPED';
                    $instance->stopped_at = now();
                    $this->computeUsageAndCost($instance);
                }
                break;

            case 'terminate':
                exec('docker stop ' . escapeshellarg($target) . ' 2>/dev/null');
                exec('docker rm -f ' . escapeshellarg($target) . ' 2>/dev/null');
                $instance->status     = 'TERMINATED';
                $instance->stopped_at = now();
                $this->computeUsageAndCost($instance);
                break;

            case 'restart':
                exec('docker restart ' . escapeshellarg($target), $out, $rc);
                if ($rc === 0) {
                    $instance->status = 'RUNNING';
                }
                break;
        }

        $instance->save();
        return $instance;
    }

    // ─── Ambil live stats container ──────────────────────────────────────────

    public function getStats(ComputeInstance $instance): array
    {
        $meta   = $instance->metadata ?? [];
        $target = $meta['container_name'] ?? $meta['container_id'] ?? null;

        if (!$target || $instance->status !== 'RUNNING') {
            return ['error' => 'container not running'];
        }

        // docker stats --no-stream output format:
        // CONTAINER ID, NAME, CPU %, MEM USAGE / LIMIT, MEM %, NET I/O, BLOCK I/O, PIDS
        $cmd = 'docker stats --no-stream --format ' .
            '"{{.CPUPerc}},{{.MemUsage}},{{.MemPerc}},{{.NetIO}},{{.BlockIO}}" ' .
            escapeshellarg($target) . ' 2>/dev/null';

        exec($cmd, $out, $rc);

        if ($rc !== 0 || empty($out)) {
            return ['error' => 'stats unavailable'];
        }

        $parts = explode(',', $out[0]);

        return [
            'cpu_perc'  => trim($parts[0] ?? '0%'),
            'mem_usage' => trim($parts[1] ?? '0B / 0B'),
            'mem_perc'  => trim($parts[2] ?? '0%'),
            'net_io'    => trim($parts[3] ?? '0B / 0B'),
            'block_io'  => trim($parts[4] ?? '0B / 0B'),
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    protected function ensureNetwork(string $networkName): void
    {
        exec('docker network inspect ' . escapeshellarg($networkName) . ' > /dev/null 2>&1', $o, $rc);
        if ($rc !== 0) {
            exec('docker network create ' . escapeshellarg($networkName) . ' 2>/dev/null');
        }
    }
    /**
     * Bikin script yang dijalankan sebagai CMD container: install openssh-server,
     * set password root, izinkan login root via password, lalu jalankan sshd di foreground
     * (foreground penting supaya container tidak langsung exit).
     */
    protected function buildBootstrapScript(string $family, string $sshPassword): string
    {
        $pwLine = "echo 'root:{$sshPassword}' | chpasswd";

        if ($family === 'apk') {
            // Alpine
            return "apk add --no-cache openssh-server >/dev/null 2>&1 && " .
                "ssh-keygen -A >/dev/null 2>&1 && " .
                "{$pwLine} && " .
                "sed -ri 's/^#?PermitRootLogin.*/PermitRootLogin yes/' /etc/ssh/sshd_config && " .
                "sed -ri 's/^#?PasswordAuthentication.*/PasswordAuthentication yes/' /etc/ssh/sshd_config && " .
                "exec /usr/sbin/sshd -D";
        }

        // Ubuntu / Debian (apt)
        return "export DEBIAN_FRONTEND=noninteractive && " .
            "apt-get update -qq && " .
            "apt-get install -y -qq openssh-server >/dev/null 2>&1 && " .
            "mkdir -p /run/sshd && " .
            "{$pwLine} && " .
            "sed -ri 's/^#?PermitRootLogin.*/PermitRootLogin yes/' /etc/ssh/sshd_config && " .
            "sed -ri 's/^#?PasswordAuthentication.*/PasswordAuthentication yes/' /etc/ssh/sshd_config && " .
            "exec /usr/sbin/sshd -D";
    }

    protected function getContainerIp(string $containerName, string $network): ?string
    {
        $cmd = 'docker inspect --format ' .
            '"{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}" ' .
            escapeshellarg($containerName) . ' 2>/dev/null';
        exec($cmd, $out, $rc);
        return $rc === 0 ? (trim($out[0] ?? '') ?: null) : null;
    }

    protected function pickAvailablePort(int $min, int $max): int
    {
        // Ambil semua port yang sudah dipakai di metadata instances aktif
        $used = ComputeInstance::whereNotIn('status', ['TERMINATED'])
            ->whereNotNull('metadata')
            ->get()
            ->pluck('metadata')
            ->map(fn($m) => $m['ssh_port'] ?? null)
            ->filter()
            ->toArray();

        do {
            $port = rand($min, $max);
        } while (in_array($port, $used));

        return $port;
    }

    protected function computeUsageAndCost(ComputeInstance $instance): void
    {
        $started = $instance->started_at;
        $stopped = $instance->stopped_at ?? now();

        if ($started) {
            $seconds = max(0, $stopped->getTimestamp() - $started->getTimestamp());
            $hours   = round($seconds / 3600, 4);
        } else {
            $hours = 0;
        }

        $pricePerHour = $instance->price_per_hour
            ?? ($instance->metadata['price_per_hour'] ?? 0);

        $instance->usage_hours    = $hours;
        $instance->price_per_hour = $pricePerHour;
        $instance->cost           = round($hours * $pricePerHour, 2);
    }

    protected function simulateAction(ComputeInstance $instance, string $action): ComputeInstance
    {
        match ($action) {
            'start'     => ($instance->status = 'RUNNING') && ($instance->started_at = now()),
            'stop'      => ($instance->status = 'STOPPED') && ($instance->stopped_at = now()),
            'terminate' => ($instance->status = 'TERMINATED') && ($instance->stopped_at = now()),
            default     => null,
        };
        if (in_array($action, ['stop', 'terminate'])) {
            $this->computeUsageAndCost($instance);
        }
        $instance->save();
        return $instance;
    }
}
