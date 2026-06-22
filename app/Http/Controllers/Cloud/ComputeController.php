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

    public function index(Request $request)
    {
        $user = $request->user();

        $items = $request->query('archived') == '1'
            ? ComputeInstance::onlyTrashed()->where('user_id', $user->id)->orderByDesc('created_at')->get()
            : ComputeInstance::where('user_id', $user->id)->orderByDesc('created_at')->get();

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validCpus = implode(',', ComputeService::VCPU_OPTIONS);
        $validRams = implode(',', ComputeService::RAM_OPTIONS);

        $request->validate([
            'cpu'       => 'required|integer|in:' . $validCpus,
            'ram'       => 'required|integer|in:' . $validRams,
            'type'      => 'nullable|string|in:vm,ide,notebook',
            'os'        => 'nullable|string|in:ubuntu-22.04,ubuntu-20.04,debian-12,alpine',
            'volume_id' => 'required|integer|exists:block_volumes,id',
        ]);

        $user = $request->user();

        if (!$user->canUseResources($request->cpu, $request->ram)) {
            return response()->json([
                'error' => "Membership {$user->membershipLabel()} maksimal {$user->maxVcpu()} vCPU dan " . ($user->maxRamMb() / 1024) . " GB RAM. Upgrade untuk resource lebih besar.",
            ], 403);
        }

        $volume = \App\Models\BlockVolume::where('id', $request->volume_id)
            ->where('user_id', $user->id)
            ->where('status', 'AVAILABLE')
            ->first();

        if (!$volume) {
            return response()->json(['error' => 'Volume tidak tersedia. Pastikan volume berstatus AVAILABLE.'], 422);
        }

        $instance = $this->service->createInstance($user, [
            'type'      => $request->input('type', ComputeService::DEFAULT_TYPE),
            'os'        => $request->input('os',   ComputeService::DEFAULT_OS),
            'cpu'       => $request->cpu,
            'ram'       => $request->ram,
            'volume_id' => $volume->id,
        ]);

        return response()->json($instance, 201);
    }

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

    public function stats(Request $request, $id)
    {
        $instance = ComputeInstance::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        return response()->json($this->service->getStats($instance));
    }

    public function log(Request $request, $id)
    {
        $instance = ComputeInstance::withTrashed()->where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        return response()->json(['log' => $instance->provision_log, 'status' => $instance->status]);
    }

    public function plans(Request $request)
    {
        $user = $request->user();

        $volumes = $user
            ? \App\Models\BlockVolume::where('user_id', $user->id)
                ->where('status', 'AVAILABLE')
                ->select('id', 'volume_name', 'size_gb')
                ->get()
            : [];

        return response()->json([
            'types'             => ComputeService::TYPES,
            'images'            => ComputeService::IMAGES,
            'vcpu_options'      => ComputeService::VCPU_OPTIONS,
            'ram_options'       => ComputeService::RAM_OPTIONS,
            'cpu_rate'          => ComputeService::CPU_RATE,
            'ram_rate'          => ComputeService::RAM_RATE,
            'max_vcpu'          => $user ? $user->maxVcpu() : 1,
            'max_ram_mb'        => $user ? $user->maxRamMb() : 2048,
            'membership'        => $user ? $user->storage_plan ?? 'free' : 'free',
            'available_volumes' => $volumes,
        ]);
    }

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
