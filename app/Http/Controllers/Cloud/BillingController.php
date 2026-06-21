<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    protected BillingService $billing;

    public function __construct(BillingService $billing)
    {
        $this->billing = $billing;
    }

    // GET /cloud/api/billing/summary
    public function summary(Request $request)
    {
        $user = $request->user();
        $user->refresh();

        return response()->json([
            'balance'            => (float) $user->balance,
            'account_status'     => $user->account_status,
            'storage_used_gb'    => (float) $user->storage_used_gb,
            'storage_quota_gb'   => (int) $user->storage_quota_gb,
            'storage_used_pct'   => $user->storageUsedPercent(),
            'storage_plan'       => $user->storage_plan ?? 'free',
            'storage_plan_label' => $user->storagePlanLabel(),
            'storage_plan_expires_at' => $user->storage_plan_expires_at?->toDateString(),
            'can_run_instances'  => $user->canRunInstances(),
        ]);
    }

    // POST /cloud/api/billing/topup
    public function topUp(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000|max:10000000',
            'note'   => 'nullable|string|max:100',
        ]);

        try {
            $tx = $this->billing->topUp($request->user(), (float) $request->amount, $request->note ?? '');
            return response()->json(['success' => true, 'transaction' => $tx, 'balance' => (float) $request->user()->fresh()->balance]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // GET /cloud/api/billing/history
    public function history(Request $request)
    {
        $history = $this->billing->getHistory($request->user(), 30);
        return response()->json($history);
    }

    // POST /cloud/api/billing/storage-subscribe
    public function storageSubscribe(Request $request)
    {
        $request->validate(['plan' => 'required|string|in:free,starter,pro,business']);

        try {
            $this->billing->subscribeStorage($request->user(), $request->plan);
            $user = $request->user()->fresh();
            return response()->json([
                'success'          => true,
                'storage_plan'     => $user->storage_plan,
                'storage_quota_gb' => $user->storage_quota_gb,
                'balance'          => (float) $user->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // POST /cloud/api/billing/storage-sync
    public function storageSync(Request $request)
    {
        $used = $this->billing->syncStorageUsage($request->user());
        return response()->json(['storage_used_gb' => $used]);
    }
}