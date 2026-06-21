<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ComputeService;
use App\Models\ComputeInstance;

class ComputeController extends Controller
{
    protected $service;

    public function __construct(ComputeService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        // support ?archived=1 to list soft-deleted instances
        if ($request->query('archived') == '1') {
            $items = ComputeInstance::onlyTrashed()->where('user_id', $user->id)->orderBy('created_at','desc')->get();
        } else {
            $items = ComputeInstance::where('user_id', $user->id)->orderBy('created_at','desc')->get();
        }

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $request->validate([
            'plan' => 'required|string',
            'runtime' => 'nullable|string',
            'vram' => 'nullable|numeric',
            'cpu' => 'nullable|numeric',
            'size' => 'nullable|string',
            'price' => 'nullable|numeric',
            'ssh' => 'nullable|boolean',
            'persistent' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $options = [
            'ssh' => (bool) ($request->input('ssh') ?? false),
            'persistent' => (bool) ($request->input('persistent') ?? false),
            'runtime' => $request->input('runtime'),
            'vram' => $request->input('vram') ? (float) $request->input('vram') : null,
            'cpu' => $request->input('cpu') ? (int) $request->input('cpu') : null,
            'size' => $request->input('size'),
            'price' => $request->input('price') ? (float) $request->input('price') : null,
        ];

        $instance = $this->service->createInstance($user, $request->plan, $options);
        return response()->json($instance, 201);
    }

    public function action(Request $request, $id)
    {
        $request->validate(['action' => 'required|string']);
        $user = $request->user();
        $instance = ComputeInstance::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $action = $request->action;

        // handle archive (soft-delete) separately
        if ($action === 'archive') {
            if ($instance->status !== 'TERMINATED') {
                return response()->json(['deleted' => false, 'error' => 'instance must be TERMINATED before archiving'], 422);
            }

            $instance->delete();
            return response()->json(['deleted' => true], 200);
        }

        // restore a trashed instance
        if ($action === 'restore') {
            // allow restore only for trashed instances
            $trashed = ComputeInstance::onlyTrashed()->where('id', $id)->where('user_id', $user->id)->first();
            if (! $trashed) {
                return response()->json(['restored' => false, 'error' => 'not found or not trashed'], 404);
            }
            $trashed->restore();
            return response()->json(['restored' => true], 200);
        }

        // permanent delete (purge)
        if ($action === 'purge') {
            $trashed = ComputeInstance::onlyTrashed()->where('id', $id)->where('user_id', $user->id)->first();
            if (! $trashed) {
                return response()->json(['deleted' => false, 'error' => 'not found or not trashed'], 404);
            }
            try {
                $trashed->forceDelete();
                return response()->json(['deleted' => true], 200);
            } catch (\Exception $e) {
                return response()->json(['deleted' => false, 'error' => $e->getMessage()], 500);
            }
        }

        $result = $this->service->changeStatus($instance, $action);

        // If service handled deletion, return its result
        if (is_array($result) && array_key_exists('deleted', $result)) {
            return response()->json($result, ($result['deleted'] ? 200 : 500));
        }

        // otherwise, return the (possibly updated) instance
        try {
            $instance->refresh();
        } catch (\Exception $e) {
            // if refresh fails, return a generic not-found
            return response()->json(['error' => 'instance not found after action'], 404);
        }

        return response()->json($instance);
    }

    // Export usage CSV (admin)
    public function exportUsage(Request $request)
    {
        $this->authorize('viewAny', ComputeInstance::class);

        $rows = ComputeInstance::with('user')->orderBy('created_at','desc')->get();

        $filename = 'compute-usage-' . now()->format('YmdHis') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function() use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id','name','user_email','plan','status','started_at','stopped_at','usage_hours','price_per_hour','cost']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id,
                    $r->name,
                    $r->user->email ?? null,
                    $r->plan,
                    $r->status,
                    $r->started_at ? $r->started_at->toDateTimeString() : null,
                    $r->stopped_at ? $r->stopped_at->toDateTimeString() : null,
                    $r->usage_hours,
                    $r->price_per_hour,
                    $r->cost,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
