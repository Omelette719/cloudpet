<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BillingTransaction;
use App\Models\BlockVolume;
use App\Models\ComputeInstance;
use App\Models\ManagedDatabase;
use App\Models\Plan;
use App\Models\ResourceStateLog;
use App\Models\StorageBucket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    // ── Dashboard Stats ──────────────────────────────────────────────────

    public function stats()
    {
        return response()->json([
            'users'              => User::where('role', 'user')->count(),
            'admins'             => User::where('role', 'admin')->count(),
            'instances_running'  => ComputeInstance::where('status', 'RUNNING')->count(),
            'instances_total'    => ComputeInstance::whereNotIn('status', ['TERMINATED'])->count(),
            'databases_running'  => ManagedDatabase::where('status', 'RUNNING')->count(),
            'volumes_total'      => BlockVolume::whereNotIn('status', ['ERROR'])->count(),
            'buckets_total'      => StorageBucket::count(),
            'revenue_today'      => abs(BillingTransaction::whereDate('created_at', today())->where('amount', '<', 0)->sum('amount')),
            'revenue_month'      => abs(BillingTransaction::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('amount', '<', 0)->sum('amount')),
            'topup_month'        => BillingTransaction::whereMonth('created_at', now()->month)->where('transaction_type', 'TOPUP')->sum('amount'),
        ]);
    }

    // ── Server Monitoring ─────────────────────────────────────────────────

    public function serverStats()
    {
        $data = ['cpu' => null, 'ram' => null, 'disk' => null, 'network' => null, 'uptime' => null, 'docker' => null, 'os' => null];

        // OS info
        $data['os'] = php_uname('s') . ' ' . php_uname('r');
        $data['hostname'] = gethostname();
        $data['php'] = PHP_VERSION;

        if (PHP_OS_FAMILY === 'Windows') {
            // CPU usage via wmic
            exec('wmic cpu get LoadPercentage /value 2>NUL', $cpuOut);
            foreach ($cpuOut as $line) {
                if (str_starts_with(trim($line), 'LoadPercentage=')) {
                    $data['cpu'] = ['percent' => (int) explode('=', $line)[1]];
                }
            }

            // CPU name
            exec('wmic cpu get Name /value 2>NUL', $cpuNameOut);
            foreach ($cpuNameOut as $line) {
                if (str_starts_with(trim($line), 'Name=')) {
                    $data['cpu']['name'] = trim(explode('=', $line, 2)[1]);
                }
            }

            // CPU cores
            $data['cpu']['cores'] = (int) ($_SERVER['NUMBER_OF_PROCESSORS'] ?? 1);

            // RAM via wmic
            exec('wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /value 2>NUL', $ramOut);
            $ramData = [];
            foreach ($ramOut as $line) {
                $line = trim($line);
                if (str_starts_with($line, 'TotalVisibleMemorySize=')) $ramData['total'] = (int) explode('=', $line)[1];
                if (str_starts_with($line, 'FreePhysicalMemory='))    $ramData['free']  = (int) explode('=', $line)[1];
            }
            if (!empty($ramData['total'])) {
                $totalMb = round($ramData['total'] / 1024);
                $freeMb  = round(($ramData['free'] ?? 0) / 1024);
                $usedMb  = $totalMb - $freeMb;
                $data['ram'] = [
                    'total_mb' => $totalMb,
                    'used_mb'  => $usedMb,
                    'free_mb'  => $freeMb,
                    'percent'  => $totalMb > 0 ? round(($usedMb / $totalMb) * 100, 1) : 0,
                ];
            }

            // Disk
            exec('wmic logicaldisk where "DriveType=3" get DeviceID,Size,FreeSpace /value 2>NUL', $diskOut);
            $disks = [];
            $current = [];
            foreach ($diskOut as $line) {
                $line = trim($line);
                if ($line === '' && !empty($current)) { $disks[] = $current; $current = []; continue; }
                if (str_starts_with($line, 'DeviceID='))  $current['drive']    = explode('=', $line)[1];
                if (str_starts_with($line, 'Size='))      $current['total_gb'] = round((int) explode('=', $line)[1] / (1024 ** 3), 1);
                if (str_starts_with($line, 'FreeSpace=')) $current['free_gb']  = round((int) explode('=', $line)[1] / (1024 ** 3), 1);
            }
            if (!empty($current)) $disks[] = $current;
            foreach ($disks as &$d) {
                $d['used_gb'] = round(($d['total_gb'] ?? 0) - ($d['free_gb'] ?? 0), 1);
                $d['percent'] = ($d['total_gb'] ?? 0) > 0 ? round(($d['used_gb'] / $d['total_gb']) * 100, 1) : 0;
            }
            $data['disk'] = $disks;

            // Uptime
            exec('wmic os get LastBootUpTime /value 2>NUL', $upOut);
            foreach ($upOut as $line) {
                if (str_starts_with(trim($line), 'LastBootUpTime=')) {
                    $raw = explode('=', trim($line))[1];
                    $bootTime = \Carbon\Carbon::createFromFormat('YmdHis', substr($raw, 0, 14));
                    $data['uptime'] = $bootTime->diffForHumans(null, true) . ' uptime';
                }
            }

            // Network (bytes since boot — delta calculated client-side)
            exec('netstat -e 2>NUL', $netOut);
            if (count($netOut) >= 4) {
                $bytes = preg_split('/\s+/', trim($netOut[3] ?? ''));
                $data['network'] = [
                    'rx_bytes' => (int) ($bytes[1] ?? 0),
                    'tx_bytes' => (int) ($bytes[2] ?? 0),
                ];
            }
        } else {
            // Linux fallback
            $data['cpu'] = ['percent' => (int) trim(shell_exec("top -bn1 | grep 'Cpu(s)' | awk '{print $2}'")), 'cores' => (int) trim(shell_exec('nproc'))];
            $memInfo = shell_exec('free -m | grep Mem');
            if ($memInfo) {
                $parts = preg_split('/\s+/', trim($memInfo));
                $data['ram'] = ['total_mb' => (int) $parts[1], 'used_mb' => (int) $parts[2], 'free_mb' => (int) $parts[3], 'percent' => round((int) $parts[2] / (int) $parts[1] * 100, 1)];
            }
        }

        // Docker containers
        exec('docker ps --format "{{.Names}}\t{{.Status}}\t{{.Image}}" 2>&1', $dockerOut, $dockerRc);
        $data['docker'] = [
            'running'    => $dockerRc === 0 ? count($dockerOut) : 0,
            'containers' => $dockerRc === 0 ? array_map(function ($line) {
                $parts = explode("\t", $line);
                return ['name' => $parts[0] ?? '', 'status' => $parts[1] ?? '', 'image' => $parts[2] ?? ''];
            }, array_slice($dockerOut, 0, 20)) : [],
        ];

        return response()->json($data);
    }

    // ── Plans CRUD ───────────────────────────────────────────────────────

    public function plansList()
    {
        return response()->json(Plan::orderBy('service_type')->orderBy('price')->get());
    }

    public function planStore(Request $request)
    {
        $request->validate([
            'service_type' => 'required|in:DATABASE,STORAGE',
            'name'         => 'required|string|max:100',
            'vcpu'         => 'nullable|integer|min:0',
            'ram'          => 'nullable|integer|min:0',
            'storage'      => 'nullable|integer|min:0',
            'price'        => 'required|numeric|min:0',
        ]);

        $plan = Plan::create([
            'id'           => (string) Str::uuid(),
            'service_type' => $request->service_type,
            'name'         => $request->name,
            'vcpu'         => $request->vcpu,
            'ram'          => $request->ram,
            'storage'      => $request->storage,
            'price'        => $request->price,
        ]);

        return response()->json($plan, 201);
    }

    public function planUpdate(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $request->validate([
            'name'    => 'sometimes|string|max:100',
            'vcpu'    => 'nullable|integer|min:0',
            'ram'     => 'nullable|integer|min:0',
            'storage' => 'nullable|integer|min:0',
            'price'   => 'sometimes|numeric|min:0',
        ]);

        $plan->update($request->only(['name', 'vcpu', 'ram', 'storage', 'price']));
        return response()->json($plan);
    }

    public function planDestroy($id)
    {
        $plan = Plan::findOrFail($id);

        $inUse = ManagedDatabase::where('plan_id', $id)->whereNotIn('status', ['TERMINATED'])->exists();
        if ($inUse) {
            return response()->json(['error' => 'Plan sedang digunakan oleh database aktif.'], 422);
        }

        $plan->delete();
        return response()->json(['deleted' => true]);
    }

    // ── Users Management ─────────────────────────────────────────────────

    public function usersList(Request $request)
    {
        $query = User::query();

        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }
        if ($request->role && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        $users = $query->orderByDesc('created_at')->paginate(15);

        return response()->json($users);
    }

    public function userShow($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'user'         => $user,
            'instances'    => ComputeInstance::where('user_id', $id)->count(),
            'databases'    => ManagedDatabase::where('user_id', $id)->whereNotIn('status', ['TERMINATED'])->count(),
            'volumes'      => BlockVolume::where('user_id', $id)->whereNotIn('status', ['ERROR'])->count(),
            'buckets'      => StorageBucket::where('user_id', $id)->count(),
            'transactions' => BillingTransaction::where('user_id', $id)->orderByDesc('created_at')->limit(20)->get(),
        ]);
    }

    public function userUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'account_status' => 'sometimes|in:ACTIVE,SUSPENDED',
            'balance_adjust' => 'sometimes|numeric',
            'storage_plan'   => 'sometimes|in:free,starter,pro,business',
        ]);

        if ($request->has('account_status')) {
            $user->account_status = $request->account_status;
        }

        if ($request->has('balance_adjust') && $request->balance_adjust != 0) {
            $amount = (float) $request->balance_adjust;
            $user->balance = max(0, (float) $user->balance + $amount);

            BillingTransaction::create([
                'id'               => (string) Str::uuid(),
                'user_id'          => $user->id,
                'amount'           => $amount,
                'transaction_type' => $amount > 0 ? 'ADMIN_TOPUP' : 'ADMIN_DEDUCT',
                'description'      => 'Penyesuaian saldo oleh admin',
            ]);
        }

        if ($request->has('storage_plan')) {
            $tier = User::MEMBERSHIP_PLANS[$request->storage_plan] ?? null;
            if ($tier) {
                $user->storage_plan     = $request->storage_plan;
                $user->storage_quota_gb = $tier['volume_limit_gb'];
            }
        }

        $user->save();
        return response()->json($user);
    }

    // ── Activity Logs ────────────────────────────────────────────────────

    public function activityLogs(Request $request)
    {
        $query = ActivityLog::with('user')->orderByDesc('created_at');

        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('action', 'like', "%{$s}%")
                ->orWhere('resource_type', 'like', "%{$s}%")
                ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$s}%")));
        }

        return response()->json($query->paginate(20));
    }

    // ── Resource State Logs ──────────────────────────────────────────────

    public function resourceStateLogs(Request $request)
    {
        $query = ResourceStateLog::orderByDesc('created_at');

        if ($request->resource_type) {
            $query->where('resource_type', $request->resource_type);
        }
        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('message', 'like', "%{$s}%")
                ->orWhere('resource_type', 'like', "%{$s}%"));
        }

        return response()->json($query->paginate(20));
    }

    // ── System Error Logs ────────────────────────────────────────────────

    public function errorLogs(Request $request)
    {
        $logFile = storage_path('logs/laravel.log');
        if (!file_exists($logFile)) {
            return response()->json(['lines' => [], 'total' => 0]);
        }

        $content = file_get_contents($logFile);
        // Split by log entry pattern [YYYY-MM-DD HH:MM:SS]
        $entries = preg_split('/(?=\[\d{4}-\d{2}-\d{2})/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $entries = array_reverse($entries);

        // Filter errors/warnings only
        $level = $request->level ?? 'all';
        if ($level !== 'all') {
            $entries = array_filter($entries, fn($e) => str_contains(strtolower($e), strtolower($level)));
        }

        if ($request->search) {
            $s = $request->search;
            $entries = array_filter($entries, fn($e) => stripos($e, $s) !== false);
        }

        $entries = array_values($entries);
        $total   = count($entries);
        $page    = max(1, (int) $request->page);
        $perPage = 15;
        $sliced  = array_slice($entries, ($page - 1) * $perPage, $perPage);

        $parsed = array_map(function ($entry) {
            $levelMatch = '';
            if (preg_match('/local\.(ERROR|WARNING|INFO|DEBUG|CRITICAL)/i', $entry, $m)) {
                $levelMatch = strtoupper($m[1]);
            }
            $dateMatch = '';
            if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $entry, $d)) {
                $dateMatch = $d[1];
            }
            return [
                'level'   => $levelMatch,
                'date'    => $dateMatch,
                'message' => mb_substr(trim($entry), 0, 500),
            ];
        }, $sliced);

        return response()->json([
            'lines'        => $parsed,
            'total'        => $total,
            'page'         => $page,
            'per_page'     => $perPage,
            'last_page'    => ceil($total / $perPage),
        ]);
    }
}
