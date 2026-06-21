<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Controller;
use App\Models\ComputeInstance;
use App\Services\ComputeService;
use Illuminate\Http\Request;

class ComputeController extends Controller
{
    protected ComputeService $service;

    public function __construct(ComputeService $service)
    {
        $this->service = $service;
    }

    // GET /cloud/api/instances
    public function index(Request $request)
    {
        $user = $request->user();

        $items = $request->query('archived') == '1'
            ? ComputeInstance::onlyTrashed()->where('user_id', $user->id)->orderByDesc('created_at')->get()
            : ComputeInstance::where('user_id', $user->id)->orderByDesc('created_at')->get();

        return response()->json($items);
    }

    // POST /cloud/api/instances
    public function store(Request $request)
    {
        $request->validate([
            'plan' => 'required|string|in:nano,micro,small,medium,large',
            'type' => 'nullable|string|in:vm,ide,notebook',
            'os'   => 'nullable|string|in:ubuntu-22.04,ubuntu-20.04,debian-12,alpine',
        ]);

        $instance = $this->service->createInstance($request->user(), $request->plan, [
            'type' => $request->input('type', ComputeService::DEFAULT_TYPE),
            'os'   => $request->input('os',   ComputeService::DEFAULT_OS),
        ]);

        return response()->json($instance, 201);
    }

    // POST /cloud/api/instances/{id}/action
    public function action(Request $request, $id)
    {
        $request->validate(['action' => 'required|string|in:start,stop,restart,terminate,archive,restore,purge']);

        $user   = $request->user();
        $action = $request->action;

        if (in_array($action, ['restore', 'purge'])) {
            $instance = ComputeInstance::onlyTrashed()->where('id', $id)->where('user_id', $user->id)->firstOrFail();
            if ($action === 'restore') { $instance->restore(); return response()->json(['restored' => true]); }
            $instance->forceDelete();
            return response()->json(['deleted' => true]);
        }

        $instance = ComputeInstance::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        if ($action === 'archive') {
            if ($instance->status !== 'TERMINATED') return response()->json(['error' => 'Harus TERMINATED dulu.'], 422);
            $instance->delete();
            return response()->json(['deleted' => true]);
        }

        $this->service->changeStatus($instance, $action);
        return response()->json($instance->refresh());
    }

    // GET /cloud/api/instances/{id}/stats
    public function stats(Request $request, $id)
    {
        $instance = ComputeInstance::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        return response()->json($this->service->getStats($instance));
    }

    // GET /cloud/api/instances/{id}/log
    public function log(Request $request, $id)
    {
        $instance = ComputeInstance::withTrashed()->where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        return response()->json(['log' => $instance->provision_log, 'status' => $instance->status]);
    }

    // GET /cloud/api/plans
    public function plans()
    {
        return response()->json([
            'plans'  => ComputeService::PLANS,
            'types'  => ComputeService::TYPES,
            'images' => ComputeService::IMAGES,
        ]);
    }

    // GET /cloud/api/usage/export (admin)
    public function exportUsage(Request $request)
    {
        $rows     = ComputeInstance::with('user')->orderByDesc('created_at')->get();
        $filename = 'compute-usage-' . now()->format('YmdHis') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id','name','user_email','type','plan','os','status','started_at','stopped_at','usage_hours','price_per_hour','cost']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->id, $r->name, $r->user->email ?? null, $r->metadata['type'] ?? 'vm', $r->plan, $r->os, $r->status,
                    $r->started_at?->toDateTimeString(), $r->stopped_at?->toDateTimeString(),
                    $r->usage_hours, $r->price_per_hour, $r->cost]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}