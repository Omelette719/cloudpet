<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Controller;
use App\Models\ManagedDatabase;
use App\Models\Plan;
use App\Services\DatabaseService;
use Illuminate\Http\Request;

class DatabaseController extends Controller
{
    protected DatabaseService $service;

    public function __construct(DatabaseService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $items = ManagedDatabase::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->makeVisible('db_password');

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|string|exists:plans,id',
            'engine'  => 'required|string|in:' . implode(',', array_keys(DatabaseService::ENGINES)),
        ]);

        try {
            $db = $this->service->createDatabase($request->user(), $request->plan_id, $request->engine);
            return response()->json($db->makeVisible('db_password'), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function action(Request $request, $id)
    {
        $request->validate(['action' => 'required|string|in:start,stop,terminate']);

        $db = ManagedDatabase::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        try {
            $this->service->changeStatus($db, $request->action);
            return response()->json($db->refresh()->makeVisible('db_password'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function log(Request $request, $id)
    {
        $db = ManagedDatabase::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        return response()->json(['log' => $db->provision_log, 'status' => $db->status]);
    }

    public function destroy(Request $request, $id)
    {
        $db = ManagedDatabase::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        if ($db->status === 'RUNNING') {
            return response()->json(['error' => 'Stop database terlebih dahulu.'], 422);
        }

        if ($db->status !== 'TERMINATED') {
            try { $this->service->changeStatus($db, 'terminate'); } catch (\Exception $e) { /* ignore */ }
        }

        $db->delete();
        return response()->json(['deleted' => true]);
    }

    public function plans()
    {
        return response()->json([
            'plans'   => Plan::where('service_type', 'DATABASE')->get(),
            'engines' => collect(DatabaseService::ENGINES)->map(fn($e, $k) => ['key' => $k, 'label' => $e['label'], 'driver' => $e['driver']]),
        ]);
    }

    // ── Database management endpoints ────────────────────────────────────────

    public function connectionInfo(Request $request, $id)
    {
        $db = ManagedDatabase::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        return response()->json($this->service->getConnectionInfo($db));
    }

    public function tables(Request $request, $id)
    {
        $db = ManagedDatabase::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        if ($db->status !== 'RUNNING') return response()->json(['error' => 'Database tidak aktif.'], 422);

        try {
            $pdo    = $this->service->connectPdo($db);
            $driver = $db->metadata['driver'] ?? 'mysql';

            if ($driver === 'pgsql') {
                $stmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
                $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            } else {
                $stmt = $pdo->query('SHOW TABLES');
                $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            }

            return response()->json(['tables' => $tables]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function tableRows(Request $request, $id, $table)
    {
        $db = ManagedDatabase::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        if ($db->status !== 'RUNNING') return response()->json(['error' => 'Database tidak aktif.'], 422);

        $table  = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $limit  = min((int) $request->query('limit', 100), 500);
        $offset = max((int) $request->query('offset', 0), 0);

        try {
            $pdo    = $this->service->connectPdo($db);
            $driver = $db->metadata['driver'] ?? 'mysql';

            // Columns
            if ($driver === 'pgsql') {
                $colStmt = $pdo->prepare("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_schema='public' AND table_name=? ORDER BY ordinal_position");
            } else {
                $colStmt = $pdo->prepare("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_schema=? AND table_name=? ORDER BY ordinal_position");
            }

            if ($driver === 'pgsql') {
                $colStmt->execute([$table]);
            } else {
                $colStmt->execute([$db->db_name, $table]);
            }
            $columns = $colStmt->fetchAll();

            // Rows
            $stmt = $pdo->query("SELECT * FROM \"{$table}\" LIMIT {$limit} OFFSET {$offset}");
            $rows = $stmt->fetchAll();

            // Count
            $countStmt = $pdo->query("SELECT COUNT(*) FROM \"{$table}\"");
            $total = (int) $countStmt->fetchColumn();

            return response()->json(['columns' => $columns, 'rows' => $rows, 'total' => $total]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function executeQuery(Request $request, $id)
    {
        $request->validate(['sql' => 'required|string|max:5000']);

        $db = ManagedDatabase::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        if ($db->status !== 'RUNNING') return response()->json(['error' => 'Database tidak aktif.'], 422);

        $sql = trim($request->sql);

        try {
            $pdo  = $this->service->connectPdo($db);
            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $isSelect = stripos($sql, 'SELECT') === 0 || stripos($sql, 'SHOW') === 0 || stripos($sql, 'DESCRIBE') === 0;

            if ($isSelect) {
                $rows = $stmt->fetchAll();
                $columns = $rows ? array_keys($rows[0]) : [];
                return response()->json(['columns' => $columns, 'rows' => $rows, 'affected' => count($rows)]);
            }

            return response()->json(['affected' => $stmt->rowCount(), 'message' => 'Query berhasil.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
