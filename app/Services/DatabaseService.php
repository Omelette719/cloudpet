<?php

namespace App\Services;

use App\Models\ManagedDatabase;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Str;
use Exception;

class DatabaseService
{
    const ENGINES = [
        'postgres-15' => ['image' => 'postgres:15-alpine', 'label' => 'PostgreSQL 15', 'port' => 5432, 'driver' => 'pgsql'],
        'postgres-14' => ['image' => 'postgres:14-alpine', 'label' => 'PostgreSQL 14', 'port' => 5432, 'driver' => 'pgsql'],
        'mysql-8'     => ['image' => 'mysql:8.0',          'label' => 'MySQL 8.0',     'port' => 3306, 'driver' => 'mysql'],
        'mysql-5.7'   => ['image' => 'mysql:5.7',          'label' => 'MySQL 5.7',     'port' => 3306, 'driver' => 'mysql'],
        'mariadb-10'  => ['image' => 'mariadb:10',         'label' => 'MariaDB 10',    'port' => 3306, 'driver' => 'mysql'],
    ];

    public function createDatabase(User $user, string $planId, string $engine): ManagedDatabase
    {
        if (!$user->canRunInstances()) {
            throw new Exception('Pastikan akun aktif dan saldo mencukupi.');
        }

        if (!$user->canCreateDatabase()) {
            throw new Exception('Batas jumlah database tercapai (' . $user->maxDatabases() . ' untuk membership ' . $user->membershipLabel() . '). Upgrade membership untuk menambah.');
        }

        $plan = Plan::where('id', $planId)->where('service_type', 'DATABASE')->firstOrFail();

        if (!$user->canUseDbPlan($plan->name)) {
            throw new Exception('Plan "' . $plan->name . '" tidak tersedia di membership ' . $user->membershipLabel() . '. Upgrade untuk menggunakan plan ini.');
        }

        $engineConf = self::ENGINES[$engine] ?? null;
        if (!$engineConf) throw new Exception('Engine tidak valid.');

        $dbName   = 'db_' . Str::random(8);
        $dbUser   = 'user_' . Str::random(6);
        $dbPass   = Str::random(20);

        $database = ManagedDatabase::create([
            'id'             => (string) Str::uuid(),
            'user_id'        => $user->id,
            'plan_id'        => $plan->id,
            'engine'         => $engine,
            'db_name'        => $dbName,
            'db_user'        => $dbUser,
            'db_password'    => $dbPass,
            'status'         => 'PROVISIONING',
            'price_per_hour' => $plan->price,
        ]);

        // Background provisioning (seperti ComputeService)
        $artisan = base_path('artisan');
        $logFile = storage_path('logs/provision_db_' . $database->id . '.out');

        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen(sprintf('start /B php %s database:provision %s > %s 2>&1',
                escapeshellarg($artisan), escapeshellarg($database->id), escapeshellarg($logFile)), 'r'));
        } else {
            exec(sprintf('nohup php %s database:provision %s > %s 2>&1 &',
                escapeshellarg($artisan), escapeshellarg($database->id), escapeshellarg($logFile)));
        }

        return $database;
    }

    public function provision(ManagedDatabase $database): ManagedDatabase
    {
        $engineConf    = self::ENGINES[$database->engine] ?? null;
        if (!$engineConf) throw new Exception('Engine tidak dikenali.');

        $user          = $database->user;
        $plan          = $database->plan;
        $containerName = 'cp_db_' . substr($database->id, 0, 8) . '_' . $user->id;
        $networkName   = 'cp_user_' . $user->id;
        $mappedPort    = $this->pickAvailablePort(50000, 59999);

        $this->appendLog($database, "[1/4] Menyiapkan database {$engineConf['label']}...");

        // Network
        $this->ensureNetwork($networkName);

        // Pull image (stream output real-time)
        $this->appendLog($database, "[2/4] Pulling image {$engineConf['image']}...");
        $pullRc = $this->execStream($database, 'docker pull ' . escapeshellarg($engineConf['image']));
        if ($pullRc !== 0) {
            throw new Exception('Gagal pull image.');
        }

        // Run container
        $this->appendLog($database, "[3/4] Menjalankan container database...");
        $envFlags = $this->buildEnvFlags($database, $engineConf);
        $cmd = sprintf(
            'docker run -d --name %s --memory=%dm --memory-swap=%dm --cpus=%s --network=%s -p %d:%d %s --restart=unless-stopped %s',
            escapeshellarg($containerName),
            $plan->ram ?? 1024,
            $plan->ram ?? 1024,
            $plan->vcpu ?? 1,
            escapeshellarg($networkName),
            $mappedPort,
            $engineConf['port'],
            $envFlags,
            escapeshellarg($engineConf['image'])
        );

        $runResult = $this->execStream($database, $cmd, true);
        if ($runResult['rc'] !== 0) {
            throw new Exception('Docker run gagal: ' . $runResult['last']);
        }

        $containerId = trim($runResult['last']);
        $this->appendLog($database, "  Container: " . substr($containerId, 0, 12));

        // Wait for database to be ready
        $this->appendLog($database, "[4/4] Menunggu database siap...");
        $this->waitForPort($mappedPort, 40);

        // RDS control plane: register di MiniStack
        $rdsId = 'cpdb-' . substr($database->id, 0, 8);
        try {
            $miniStack = app(MiniStackService::class);
            $miniStack->createDBInstance($rdsId, $database->engine, $database->db_name, $database->db_user, $database->db_password);
            $this->appendLog($database, "  RDS Instance registered: {$rdsId}");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("RDS CreateDBInstance skip: {$e->getMessage()}");
        }
        $database->rds_identifier = $rdsId;

        $database->update([
            'host'           => '127.0.0.1',
            'port'           => $mappedPort,
            'status'         => 'RUNNING',
            'started_at'     => now(),
            'rds_identifier' => $database->rds_identifier,
            'metadata'       => [
                'container_id'   => $containerId,
                'container_name' => $containerName,
                'network'        => $networkName,
                'engine_label'   => $engineConf['label'],
                'driver'         => $engineConf['driver'],
                'internal_port'  => $engineConf['port'],
                'mapped_port'    => $mappedPort,
                'plan_name'      => $plan->name,
                'plan_label'     => ucfirst(str_replace('db-', '', $plan->name)),
                'resources'      => ['cpu' => $plan->vcpu, 'memory' => $plan->ram, 'storage' => $plan->storage],
                'price_per_hour' => (float) $plan->price,
            ],
        ]);

        $this->appendLog($database, "---");
        $this->appendLog($database, "Database siap!");
        $this->appendLog($database, "Host: 127.0.0.1:{$mappedPort}");
        $this->appendLog($database, "Database: {$database->db_name}");
        $this->appendLog($database, "User: {$database->db_user}");

        return $database;
    }

    public function changeStatus(ManagedDatabase $database, string $action): ManagedDatabase
    {
        $meta          = $database->metadata ?? [];
        $containerName = $meta['container_name'] ?? null;

        if (!$containerName) throw new Exception('Container tidak ditemukan.');

        $rdsId     = $database->rds_identifier;
        $miniStack = app(MiniStackService::class);

        switch ($action) {
            case 'start':
                exec('docker start ' . escapeshellarg($containerName) . ' 2>&1', $out, $rc);
                if ($rc === 0) {
                    $database->status     = 'RUNNING';
                    $database->started_at = now();
                    if ($rdsId) { try { $miniStack->startDBInstance($rdsId); } catch (\Exception $e) {} }
                }
                break;

            case 'stop':
                exec('docker stop ' . escapeshellarg($containerName) . ' 2>&1', $out, $rc);
                if ($rc === 0) {
                    $database->status     = 'STOPPED';
                    $database->stopped_at = now();
                    $this->computeUsage($database);
                    if ($rdsId) { try { $miniStack->stopDBInstance($rdsId); } catch (\Exception $e) {} }
                }
                break;

            case 'terminate':
                exec('docker stop ' . escapeshellarg($containerName) . ' 2>&1');
                exec('docker rm -f ' . escapeshellarg($containerName) . ' 2>&1');
                if ($rdsId) { try { $miniStack->deleteDBInstance($rdsId); } catch (\Exception $e) {} }
                $database->status     = 'TERMINATED';
                $database->stopped_at = now();
                $this->computeUsage($database);
                break;
        }

        $database->save();
        return $database;
    }

    public function getConnectionInfo(ManagedDatabase $database): array
    {
        $meta   = $database->metadata ?? [];
        $driver = $meta['driver'] ?? 'mysql';
        $host   = $database->host ?? '127.0.0.1';
        $port   = $database->port;

        return [
            'host'     => $host,
            'port'     => $port,
            'database' => $database->db_name,
            'username' => $database->db_user,
            'password' => $database->db_password,
            'driver'   => $driver,
            'dsn'      => "{$driver}:host={$host};port={$port};dbname={$database->db_name}",
            'cli'      => $driver === 'pgsql'
                ? "psql -h {$host} -p {$port} -U {$database->db_user} -d {$database->db_name}"
                : "mysql -h {$host} -P {$port} -u {$database->db_user} -p {$database->db_name}",
            'laravel_env' => implode("\n", [
                "DB_CONNECTION={$driver}",
                "DB_HOST={$host}",
                "DB_PORT={$port}",
                "DB_DATABASE={$database->db_name}",
                "DB_USERNAME={$database->db_user}",
                "DB_PASSWORD={$database->db_password}",
            ]),
        ];
    }

    public function connectPdo(ManagedDatabase $database): \PDO
    {
        $meta   = $database->metadata ?? [];
        $driver = $meta['driver'] ?? 'mysql';
        $dsn    = "{$driver}:host={$database->host};port={$database->port};dbname={$database->db_name}";
        return new \PDO($dsn, $database->db_user, $database->db_password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT => 5,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function buildEnvFlags(ManagedDatabase $db, array $engineConf): string
    {
        if ($engineConf['driver'] === 'pgsql') {
            return sprintf('-e POSTGRES_DB=%s -e POSTGRES_USER=%s -e POSTGRES_PASSWORD=%s',
                escapeshellarg($db->db_name), escapeshellarg($db->db_user), escapeshellarg($db->db_password));
        }
        $rootPw = Str::random(24);
        return sprintf('-e MYSQL_DATABASE=%s -e MYSQL_USER=%s -e MYSQL_PASSWORD=%s -e MYSQL_ROOT_PASSWORD=%s',
            escapeshellarg($db->db_name), escapeshellarg($db->db_user), escapeshellarg($db->db_password), escapeshellarg($rootPw));
    }

    protected function ensureNetwork(string $name): void
    {
        exec('docker network inspect ' . escapeshellarg($name) . ' 2>&1', $o, $rc);
        if ($rc === 0) return;
        exec('docker network create ' . escapeshellarg($name) . ' 2>&1');
    }

    protected function waitForPort(int $port, int $maxAttempts = 30): void
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $conn = @fsockopen('127.0.0.1', $port, $errno, $errstr, 2);
            if ($conn) { fclose($conn); return; }
            sleep(3);
        }
    }

    protected function pickAvailablePort(int $min, int $max): int
    {
        $used = ManagedDatabase::whereNotIn('status', ['TERMINATED'])
            ->whereNotNull('port')->pluck('port')->toArray();

        for ($attempts = 0; $attempts < 100; $attempts++) {
            $port = rand($min, $max);
            if (in_array($port, $used)) continue;

            // Test apakah port benar-benar bisa di-bind (Windows Hyper-V bisa blokir)
            $sock = @stream_socket_server("tcp://0.0.0.0:{$port}", $errno, $errstr);
            if ($sock) {
                fclose($sock);
                return $port;
            }
        }

        throw new Exception('Tidak ada port tersedia di range ' . $min . '-' . $max);
    }

    protected function computeUsage(ManagedDatabase $db): void
    {
        if (!$db->started_at) return;
        $stopped = $db->stopped_at ?? now();
        $hours   = round(max(0, $stopped->getTimestamp() - $db->started_at->getTimestamp()) / 3600, 4);
        $db->usage_hours = ($db->usage_hours ?? 0) + $hours;
        $db->cost        = ($db->cost ?? 0) + round($hours * $db->price_per_hour, 2);
    }

    protected function execStream(ManagedDatabase $db, string $cmd, bool $returnDetail = false): mixed
    {
        $proc = proc_open($cmd . ' 2>&1', [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($proc)) {
            return $returnDetail ? ['rc' => -1, 'last' => 'proc_open failed'] : -1;
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $last = '';

        while (true) {
            $status = proc_get_status($proc);
            foreach ([$pipes[1], $pipes[2]] as $pipe) {
                $line = fgets($pipe);
                if ($line !== false && trim($line) !== '') {
                    $line = rtrim($line);
                    $last = $line;
                    $this->appendLog($db, '  ' . $line);
                }
            }
            if (!$status['running'] && fgets($pipes[1]) === false && fgets($pipes[2]) === false) break;
            usleep(100000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $rc = proc_close($proc);

        return $returnDetail ? ['rc' => $rc, 'last' => $last] : $rc;
    }

    protected function appendLog(ManagedDatabase $db, string $line): void
    {
        $db->provision_log = ($db->provision_log ?? '') . $line . "\n";
        $db->save();
    }
}
